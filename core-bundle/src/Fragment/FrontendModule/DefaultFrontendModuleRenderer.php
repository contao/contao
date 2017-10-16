<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment\FrontendModule;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Fragment\AbstractFragmentRenderer;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\ModuleModel;

class DefaultFrontendModuleRenderer extends AbstractFragmentRenderer implements FrontendModuleRendererInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string
    {
        $query = [];

        $attributes = [
            'moduleModel' => $moduleModel->id,
            'inColumn' => $inColumn,
            'scope' => $scope,
        ];

        $fragmentIdentifier = FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT.'.'.$moduleModel->type;

        return $this->renderFragment($fragmentIdentifier, $attributes, $query);
    }
}
