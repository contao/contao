<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ContentRouting;

use Contao\ArticleModel;
use Contao\CoreBundle\ContentRouting\ContentRoute;
use Contao\CoreBundle\ContentRouting\PageUrlResolver;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;

class PageUrlResolverTest extends TestCase
{
    /**
     * @var PageUrlResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PageUrlResolver();
    }

    public function testSupportsPages(): void
    {
        $this->assertTrue($this->resolver->supportsContent($this->mockPage()));
        $this->assertFalse($this->resolver->supportsContent($this->mockClassWithProperties(ArticleModel::class)));
    }

    public function testCreatesParameterdContentRoute(): void
    {
        $page = $this->mockPage();

        /** @var ContentRoute $route */
        $route = $this->resolver->resolveContent($page);

        $this->assertInstanceOf(ContentRoute::class, $route);
        $this->assertSame($page, $route->getPage());
        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('', $route->getDefault('parameters'));
    }

    /**
     * @return PageModel&MockObject $page
     */
    private function mockPage(array $properties = []): PageModel
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            array_merge(
                [
                    'id' => 17,
                    'alias' => 'bar',
                    'domain' => 'www.example.com',
                    'rootLanguage' => 'xy',
                    'rootUseSSL' => true,
                    'urlPrefix' => 'foo',
                    'urlSuffix' => '.baz',
                ],
                $properties
            )
        );
    }
}
