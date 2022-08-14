<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\PublicUri;

use Symfony\Component\HttpFoundation\HeaderUtils;

interface ContentDispositionAware
{
    /**
     * @phpstan-return HeaderUtils::DISPOSITION_INLINE|HeaderUtils::DISPOSITION_ATTACHMENT
     */
    public function getContentDispositionType(): string;
}
