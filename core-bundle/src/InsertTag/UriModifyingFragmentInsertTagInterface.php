<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

interface UriModifyingFragmentInsertTagInterface
{
    public function getQueryForTag(InsertTag $tag, array $query): array;
}
