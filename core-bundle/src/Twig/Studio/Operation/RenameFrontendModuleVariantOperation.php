<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;

/**
 * @experimental
 */
#[AsOperationForTemplateStudioElement]
class RenameFrontendModuleVariantOperation extends AbstractRenameVariantOperation
{
    protected function getPrefix(): string
    {
        return 'frontend_module';
    }
}
