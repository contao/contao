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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\ModuleModel;
use Twig\Extension\RuntimeExtensionInterface;

final class FragmentRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private ContaoFramework $framework)
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
            $model = $this->framework->getAdapter($class)->findByPk($typeOrId);
        } else {
            $model = $this->framework->createInstance($class);
            $model->type = $typeOrId;
        }

        foreach ($data as $k => $v) {
            $model->$k = $v;
        }

        $model->preventSaving(false);

        return $model;
    }
}
