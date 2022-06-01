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

abstract class AbstractAction
{
    /**
     * This allows for performance optimizations when voting on many data sets so voters are given
     * the chance to preload voting information all at once.
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
     *     $action->preloadHints = $allRecords;
     *
     *     $this->denyAccessUnlessGranted('contao_dc.<table>', $action);
     * }
     *
     * This ensures, every record is checked individually for maximum security, but it also allows voters to optimize
     * for performance (entirely optional though).
     */
    public array $preloadHints = [];

    public function __construct(
        private string $dataSource,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('[Subject: %s]', implode('; ', $this->getSubjectInfo()));
    }

    public function getDataSource(): string
    {
        return $this->dataSource;
    }

    protected function getSubjectInfo(): array
    {
        $subject = [];
        $subject[] = 'Source: '.$this->getDataSource();

        return $subject;
    }
}
