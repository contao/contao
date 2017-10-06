<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\FragmentRegistry\FrontendModule;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass;
use Contao\CoreBundle\FragmentRegistry\AbstractFragmentRenderer;
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

        $fragmentIdentifier = FragmentRegistryPass::TAG_FRAGMENT_FRONTEND_MODULE.'.'.$moduleModel->type;

        return $this->renderFragment($fragmentIdentifier, $attributes, $query);
    }
}
