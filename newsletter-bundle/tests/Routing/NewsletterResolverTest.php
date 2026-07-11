<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Routing;

use Contao\Model;
use Contao\NewsletterBundle\Routing\NewsletterResolver;
use Contao\NewsletterChannelModel;
use Contao\NewsletterModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NewsletterResolverTest extends ContaoTestCase
{
    public function testResolveNewsletter(): void
    {
        $target = $this->createClassWithPropertiesStub(PageModel::class);
        $channel = $this->createClassWithPropertiesStub(NewsletterChannelModel::class, ['jumpTo' => 42]);
        $content = $this->createStub(NewsletterModel::class);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
            NewsletterChannelModel::class => $this->createConfiguredAdapterStub(['findById' => $channel]),
        ]);

        $resolver = new NewsletterResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($target, $result->content);
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('getParametersForContentProvider')]
    public function testGetParametersForContent(string $class, array $properties, array $expected): void
    {
        $content = $this->createClassWithPropertiesStub($class, $properties);
        $pageModel = $this->createStub(PageModel::class);

        $resolver = new NewsletterResolver($this->createContaoFrameworkStub());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public static function getParametersForContentProvider(): iterable
    {
        yield 'Uses the newsletter alias' => [
            NewsletterModel::class,
            ['id' => 42, 'alias' => 'foobar'],
            ['parameters' => '/foobar'],
        ];

        yield 'Uses newsletter ID if alias is empty' => [
            NewsletterModel::class,
            ['id' => 42, 'alias' => ''],
            ['parameters' => '/42'],
        ];

        yield 'Only supports NewsletterModel' => [
            PageModel::class,
            [],
            [],
        ];
    }
}
