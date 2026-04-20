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
                0 !== $typeOrId ? $this->getModel('tl_module', $typeOrId, $data) : 0,
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
            $modelOrReference = $this->getModel('tl_content', $typeOrId, $data);
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

        $model = $this->getModel('tl_content', $type, $data);

        $contentElementReference = new ContentElementReference($model, 'main', [], true);
        $contentElementReference->setNestedFragments($nestedFragments);

        return $contentElementReference;
    }

    /**
     * @param class-string<ContentModel|ModuleModel> $class
     */
    private function getModel(string $table, int|string $typeOrId, array $data = []): ContentModel|ModuleModel|null
    {
        $class = $GLOBALS['TL_MODELS'][$table] ?? null;

        if (!$class || !\class_exists($class)) {
            return null;
        }

        if (is_numeric($typeOrId)) {
            /** @var Adapter<ContentModel|ModuleModel> $adapter */
            $adapter = $this->framework->getAdapter($class);
            $model = $adapter->findById($typeOrId);
        } else {
            $model = $this->framework->createInstance($class);
            $model->type = $typeOrId;
        }

        if (null === $model) {
            return null;
        }

        $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

        foreach ($data as $k => $v) {
            if (null !== $v && !\is_scalar($v)) {
                $csv = $GLOBALS['TL_DCA'][$table]['fields'][$k]['eval']['csv'] ?? null;
                $v = $csv ? implode($csv, $v) : serialize($v);
            }

            $model->$k = $v;
        }

        $model->preventSaving(false);

        return $model;
    }
}
