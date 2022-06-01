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
     * This allows for performance optimizations when voting on many IDs so voters are given
     * the chance to preload voting information all at once.
     * Intended usage:.
     *
     * $allIds = [1, 42, 135, 18];
     *
     * foreach ($allIds as $id) {
     *     $action = new ReadAction('<table>', $id);
     *     $action->preloadIds = $allIds;
     *
     *     $this->denyAccessUnlessGranted('contao_dc.<table>', $action);
     * }
     *
     * This ensures, every ID is checked individually for maximum security but it also allows voters to optimize
     * for performance (entirely optional though).
     */
    public array $preloadIds = [];

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
