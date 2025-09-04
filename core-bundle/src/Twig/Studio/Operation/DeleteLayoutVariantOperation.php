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
#[AsOperationForTemplateStudioElement(name: 'delete', priority: -1)]
class DeleteLayoutVariantOperation extends AbstractDeleteVariantOperation
{
    protected function getPrefix(): string
    {
        return 'layout';
    }

    protected function getDatabaseReferencesThatShouldBeMigrated(): array
    {
        return ['tl_layout.template'];
    }

    protected function setDatabaseValueToDefaultWhenMigrating(): bool
    {
        return true;
    }
}
