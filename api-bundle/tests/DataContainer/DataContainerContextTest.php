<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use ApiPlatform\Metadata\Get;
use Contao\ApiBundle\DataContainer\DataContainerContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class DataContainerContextTest extends TestCase
{
    public function testBuildsFiniteAndRecursiveParentChains(): void
    {
        $operation = new Get(extraProperties: ['contao' => [
            'parents' => [
                ['table' => 'tl_news_archive', 'parameter' => 'news_archive_id'],
                ['table' => 'tl_news', 'parameter' => 'news_id'],
            ],
            'recursive_parent' => ['table' => 'tl_content', 'parameter' => 'nested', 'segment' => 'content'],
        ]]);

        $context = DataContainerContext::fromOperation($operation, [
            'news_archive_id' => '23',
            'news_id' => '42',
            'nested' => '5/content/9/content/12',
        ]);

        $this->assertSame(
            [
                ['table' => 'tl_news_archive', 'id' => 23],
                ['table' => 'tl_news', 'id' => 42],
                ['table' => 'tl_content', 'id' => 5],
                ['table' => 'tl_content', 'id' => 9],
                ['table' => 'tl_content', 'id' => 12],
            ],
            $context->getParents(),
        );
    }

    public function testRejectsAnInvalidRecursiveParentChain(): void
    {
        $operation = new Get(extraProperties: ['contao' => [
            'recursive_parent' => ['table' => 'tl_content', 'parameter' => 'nested', 'segment' => 'content'],
        ]]);

        $this->expectException(UnprocessableEntityHttpException::class);

        DataContainerContext::fromOperation($operation, ['nested' => '5/content/1 OR 1=1']);
    }

    public function testBuildsARecursiveParentChainFromAnIntegerIdentifier(): void
    {
        $operation = new Get(extraProperties: ['contao' => [
            'recursive_parent' => ['table' => 'tl_content', 'parameter' => 'nested', 'segment' => 'content'],
        ]]);

        $context = DataContainerContext::fromOperation($operation, ['nested' => 487]);

        $this->assertSame([['table' => 'tl_content', 'id' => 487]], $context->getParents());
    }
}
