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
final class RenameContentElementVariantOperation extends AbstractRenameVariantOperation
{
    protected function getPrefix(): string
    {
        return 'content_element';
    }

    protected function getDatabaseReferencesThatShouldBeMigrated(): array
    {
        return ['tl_content.customTpl'];
    }
}
