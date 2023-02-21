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

    public function renderModule(string|int $type, array $data = []): string
    {
        $model = $this->getModel(ModuleModel::class, $type, $data);

        return $this->framework->getAdapter(Controller::class)->getFrontendModule($model);
    }

    public function renderContent(string $type, array $data = []): string
    {
        $model = $this->getModel(ContentModel::class, $type, $data);

        return $this->framework->getAdapter(Controller::class)->getContentElement($model);
    }

    private function getModel(string $class, string|int $type, array $data = []): ModuleModel|ContentModel
    {
        if (is_numeric($type)) {
            $model = $this->framework->getAdapter($class)->findByPk($type);
        } else {
            $model = new $class;
            $model->type = $type;
        }

        foreach ($data as $k => $v) {
            $model->$k = $v;
        }

        $model->preventSaving(false);

        return $model;
    }
}
