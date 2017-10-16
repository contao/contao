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
use Contao\ModuleModel;

interface FrontendModuleRendererInterface
{
    /**
     * Checks if the renderer supports the given model.
     *
     * @param ModuleModel $moduleModel
     * @param string      $inColumn
     * @param string      $scope
     *
     * @return bool
     */
    public function supports(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool;

    /**
     * Renders the fragment.
     *
     * @param ModuleModel $moduleModel
     * @param string      $inColumn
     * @param string      $scope
     *
     * @return null|string
     */
    public function render(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string;
}
