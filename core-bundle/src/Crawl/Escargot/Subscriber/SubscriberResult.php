<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Escargot\Subscriber;

class SubscriberResult
{
    private string|null $warning = null;

    /**
     * Mixed custom info. Must be serializable, so
     * it can be transported between requests.
     */
    private array $info = [];

    public function __construct(
        private bool $wasSuccessful,
        private string $summary,
    ) {
    }

    public function wasSuccessful(): bool
    {
        return $this->wasSuccessful;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getWarning(): string|null
    {
        return $this->warning;
    }

    public function setWarning(string|null $warning): self
    {
        $this->warning = $warning;

        return $this;
    }

    public function setInfo(array $info): void
    {
        $this->info = $info;
    }

    public function addInfo(string $key, mixed $value): self
    {
        $this->info[$key] = $value;

        return $this;
    }

    public function getInfo(string $key): mixed
    {
        return $this->info[$key] ?? null;
    }

    public function getAllInfo(): array
    {
        return $this->info;
    }

    public function toArray(): array
    {
        return [
            'wasSuccessful' => $this->wasSuccessful(),
            'summary' => $this->getSummary(),
            'warning' => $this->getWarning(),
            'info' => $this->getAllInfo(),
        ];
    }

    public static function fromArray(array $data): self
    {
        $result = new self($data['wasSuccessful'], $data['summary']);

        if (isset($data['warning'])) {
            $result->setWarning($data['warning']);
        }

        if (isset($data['info'])) {
            $result->setInfo($data['info']);
        }

        return $result;
    }
}
