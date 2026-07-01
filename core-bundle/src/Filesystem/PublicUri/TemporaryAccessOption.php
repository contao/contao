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
    public const MAX_TTL = 31_536_000;

    public function __construct(
        private readonly int $ttl,
        private readonly string $contentHash,
    ) {
        \assert('' !== $this->contentHash, 'Content hash must be a non-empty string');
        \assert($this->ttl > 0 && $this->ttl <= self::MAX_TTL, \sprintf('TTL must not be 0 and not exceed one year. "%d" given.', $this->ttl)); // TTL should never exceed a year
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
