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

use Contao\ArrayUtil;

final class TemporaryAccessOption
{
    public function __construct(
        private int $ttl,
        private readonly string $contentHash,
    ) {
        \assert('' !== $this->contentHash);
        // Ensure TTL is max 1 year
        $this->ttl = min($this->ttl, 31_536_000);
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    public static function createFromContent(int $ttl, array $content): self
    {
        return new self($ttl, ArrayUtil::consistentHash($content));
    }
}
