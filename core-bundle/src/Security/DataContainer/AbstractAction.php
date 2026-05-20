<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\DataContainer;

abstract class AbstractAction implements \Stringable
{
    /**
     * This allows for performance optimizations when voting on many data sets so
     * voters are given the chance to preload voting information all at once.
     *
     * Intended usage:.
     *
     * $allRecords = [
     *    ['id' => 1, 'pid' => 3, 'type' => 'foo'],
     *    ['id' => 42, 'pid' => 4, 'type' => 'bar'],
     *    ['id' => 18, 'pid' => 4, 'type' => 'baz'],
     * ];
     *
     * foreach ($allRecords as $record) {
     *     $action = new ReadAction('<table>', $record);
     *     $action->setPreloadHints($allRecords);
     *
     *     $this->denyAccessUnlessGranted('contao_dc.<table>', $action);
     * }
     *
     * This ensures that every record is checked individually for maximum security, but it
     * also allows voters to optimize for performance (entirely optional though).
     */
    private array|null $preloadHints = null;
    private string|null $accessDecisionCacheKey = null;

    public function __construct(private readonly string $dataSource)
    {
    }

    public function __toString(): string
    {
        return \sprintf('[Subject: %s]', implode('; ', $this->getSubjectInfo()));
    }

    public function getPreloadHints(): array|null
    {
        return $this->preloadHints;
    }

    public function setPreloadHints(array $preloadHints): self
    {
        if (null !== $this->preloadHints) {
            throw new \InvalidArgumentException('Cannot override configured preload hints.');
        }

        $this->preloadHints = $preloadHints;
        $this->accessDecisionCacheKey = null;

        return $this;
    }

    public function getDataSource(): string
    {
        return $this->dataSource;
    }

    public function getAccessDecisionCacheKey(): string
    {
        return $this->accessDecisionCacheKey ??= hash('xxh3', serialize($this->normalizeForCacheKey($this->getCacheKeyData())));
    }

    protected function getSubjectInfo(): array
    {
        $subject = [];
        $subject[] = 'Source: '.$this->getDataSource();

        return $subject;
    }

    protected function getCacheKeyData(): array
    {
        return [
            'dataSource' => $this->dataSource,
            'preloadHints' => $this->preloadHints,
        ];
    }

    private function normalizeForCacheKey(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map($this->normalizeForCacheKey(...), $value);
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeForCacheKey($item);
        }

        ksort($normalized);

        return $normalized;
    }
}
