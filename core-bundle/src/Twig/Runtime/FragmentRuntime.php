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

    public function renderModule(int|string $typeOrId, array $data = []): string
    {
        $model = $this->getModel(ModuleModel::class, $typeOrId, $data);

        return $this->framework->getAdapter(Controller::class)->getFrontendModule($model);
    }

    public function renderContent(int|string $typeOrId, array $data = []): string
    {
        $model = $this->getModel(ContentModel::class, $typeOrId, $data);

        return $this->framework->getAdapter(Controller::class)->getContentElement($model);
    }

    /**
     * @param class-string<ContentModel|ModuleModel> $class
     */
    private function getModel(string $class, int|string $typeOrId, array $data = []): ContentModel|ModuleModel
    {
        if (is_numeric($typeOrId)) {
            /** @var Adapter<ContentModel|ModuleModel> $adapter */
            $adapter = $this->framework->getAdapter($class);
            $model = $adapter->findByPk($typeOrId);
        } else {
            $model = $this->framework->createInstance($class);
            $model->type = $typeOrId;
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
