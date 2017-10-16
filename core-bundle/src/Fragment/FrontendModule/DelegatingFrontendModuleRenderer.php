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

class DelegatingFrontendModuleRenderer implements FrontendModuleRendererInterface
{
    /**
     * @var FrontendModuleRendererInterface[]
     */
    private $renderers = [];

    /**
     * Adds a renderer.
     *
     * @param FrontendModuleRendererInterface $renderer
     */
    public function addRenderer(FrontendModuleRendererInterface $renderer): void
    {
        $this->renderers[] = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): bool
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($moduleModel, $inColumn, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function render(ModuleModel $moduleModel, string $inColumn = 'main', string $scope = ContaoCoreBundle::SCOPE_FRONTEND): ?string
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($moduleModel, $inColumn, $scope)) {
                return $renderer->render($moduleModel, $inColumn, $scope);
            }
        }

        return null;
    }
}
