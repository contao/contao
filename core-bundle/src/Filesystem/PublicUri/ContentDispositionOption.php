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

final class ContentDispositionOption implements OptionsInterface, ContentDispositionAware
{
    /**
     * @phpstan-var HeaderUtils::DISPOSITION_INLINE|HeaderUtils::DISPOSITION_ATTACHMENT
     */
    private string $contentDispositionType;

    public function __construct(bool $inline)
    {
        $this->contentDispositionType = $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT;
    }

    public function getContentDispositionType(): string
    {
        return $this->contentDispositionType;
    }
}
