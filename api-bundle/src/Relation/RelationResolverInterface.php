<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

interface RelationResolverInterface
{
    public function supports(object $relation): bool;

    public function resolveToReference(mixed $value, object $relation): mixed;

    public function resolveToIdentifier(mixed $value, object $relation): mixed;
}
