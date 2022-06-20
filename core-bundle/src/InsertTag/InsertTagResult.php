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

class InsertTagResult
{
    /**
     * @param list<string> $cacheTags
     */
    public function __construct(private \Stringable|string $value, private OutputType $outputType = OutputType::text, private \DateTimeImmutable|null $expiresAt = null, private array $cacheTags = [])
    {
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    public function getOutputType(): OutputType
    {
        return $this->outputType;
    }

    public function getExpiresAt(): \DateTimeImmutable|null
    {
        return $this->expiresAt;
    }

    /**
     * @return list<string>
     */
    public function getCacheTags(): array
    {
        return $this->cacheTags;
    }
}
