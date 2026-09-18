<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Twig\Extension\RuntimeExtensionInterface;

final class FragmentRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function renderModule(array $context, int|string $typeOrId, array $data = []): string
    {
        if ('article' === $typeOrId) {
            $typeOrId = 0;
        }

        return $this->framework
            ->getAdapter(Controller::class)
            ->getFrontendModule(
                0 !== $typeOrId ? $this->getModel(ModuleModel::class, $typeOrId, $data) : 0,
                $context['_slot_name'] ?? 'main',
            )
        ;
    }

    public function renderContent(ContentElementReference|int|string $typeOrId, array $data = []): string
    {
        if ($typeOrId instanceof ContentElementReference) {
            $modelOrReference = $typeOrId;
        } elseif (\is_string($typeOrId) && \is_array($data['nested_fragments'] ?? null)) {
            $modelOrReference = $this->getContentReference($typeOrId, $data);
        } else {
            $modelOrReference = $this->getModel(ContentModel::class, $typeOrId, $data);
        }

        return $this->framework->getAdapter(Controller::class)->getContentElement($modelOrReference);
    }

    private function getContentReference(string $type, array $data = []): ContentElementReference
    {
        $nestedFragments = array_map(
            fn (array $element) => $this->getContentReference($element['type'], $element),
            $data['nested_fragments'] ?? [],
        );

        unset($data['nested_fragments']);

        $model = $this->getModel(ContentModel::class, $type, $data);

        $contentElementReference = new ContentElementReference($model, 'main', [], true);
        $contentElementReference->setNestedFragments($nestedFragments);

        return $contentElementReference;
    }

    /**
     * @param class-string<ContentModel|ModuleModel> $class
     */
    private function getModel(string $class, int|string $typeOrId, array $data = []): ContentModel|ModuleModel|null
    {
        if (is_numeric($typeOrId)) {
            /** @var Adapter<ContentModel|ModuleModel> $adapter */
            $adapter = $this->framework->getAdapter($class);
            $model = $adapter->findById($typeOrId);
        } else {
            $model = $this->framework->createInstance($class);
            $model->type = $typeOrId;

            if ($model instanceof ContentModel) {
                $model->addImage = false;
                $model->showPreview = false;
                $model->inline = false;
                $model->overwriteMeta = false;
                $model->fullsize = false;
                $model->thead = false;
                $model->tfoot = false;
                $model->tleft = false;
                $model->sortable = false;
                $model->closeSections = false;
                $model->target = false;
                $model->overwriteLink = false;
                $model->useImage = false;
                $model->useHomeDir = false;
                $model->metaIgnore = false;
                $model->splashImage = false;
                $model->sliderContinuous = false;
                $model->protected = false;
                $model->invisible = false;
                $model->embed = '';
                $model->vimeo = '';
                $model->sortBy = '';
            } elseif ($model instanceof ModuleModel) {
                $model->hardLimit = false;
                $model->showProtected = false;
                $model->defineRoot = false;
                $model->showHidden = false;
                $model->autologin = false;
                $model->redirectBack = false;
                $model->reqFullAuth = false;
                $model->fuzzy = false;
                $model->loadFirst = false;
                $model->useCaption = false;
                $model->fullsize = false;
                $model->disableCaptcha = false;
                $model->reg_allowLogin = false;
                $model->reg_skipName = false;
                $model->reg_deleteDir = false;
                $model->reg_assignDir = false;
                $model->reg_activate = false;
                $model->protected = false;
                $model->rss_feed = '';
            }
        }

        if (null === $model) {
            return null;
        }

        foreach ($data as $k => $v) {
            if (null !== $v && !\is_scalar($v)) {
                $v = serialize($v);
            }

            $model->$k = $v;
        }

        $model->preventSaving(false);

        return $model;
    }
}
