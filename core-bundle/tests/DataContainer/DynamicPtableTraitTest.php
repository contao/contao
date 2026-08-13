<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\DynamicPtableTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DynamicPtableTraitTest extends TestCase
{
    public function testFindsTheDynamicParent(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([['id' => 5, 'pid' => 3, 'ptable' => 'tl_article']])
        ;

        $subject = new class() {
            use DynamicPtableTrait;

            /**
             * @return array{0: string, 1: int}
             */
            public function findParent(Connection $connection, string $table, int $id): array
            {
                return $this->getParentTableAndId($connection, $table, $id);
            }
        };
        $this->expectUserDeprecationMessage(
            'Since contao/core-bundle 6.1: Using "Contao\CoreBundle\DataContainer\DynamicPtableTrait" is deprecated and will no longer work in Contao 7. Use "Contao\CoreBundle\DataContainer\DcaHierarchy::getParentTableAndId()" instead.',
        );

        $this->assertSame(['tl_article', 3], $subject->findParent($connection, 'tl_content', 5));
    }
}
