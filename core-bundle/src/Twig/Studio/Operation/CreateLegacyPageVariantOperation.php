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
 * @internal
 */
#[AsOperationForTemplateStudioElement]
final class CreateLegacyPageVariantOperation extends AbstractCreateLegacyVariantOperation
{
    protected function getPrefix(): string
    {
        return 'fe_page';
    }

    protected function disallowCustomName(): bool
    {
        return true;
    }
}
