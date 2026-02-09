<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ContentComposition;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\ContentComposition\ContentComposition;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ContentCompositionTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TL_ADMIN_NAME'],
            $GLOBALS['TL_ADMIN_EMAIL'],
            $GLOBALS['TL_LANGUAGE'],
        );

        parent::tearDown();
    }

    public function testUsesCustomTemplateFromRouteDefaults(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->adminEmail = 'foo@bar.com';

        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('_template')
            ->willReturn('page/foo')
        ;

        $pageRegistry = $this->createStub(PageRegistry::class);
        $pageRegistry
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $contentComposition = new ContentComposition(
            $this->createStub(ContaoFramework::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(PictureFactory::class),
            $this->createStub(PreviewFactory::class),
            $this->createStub(ContaoContext::class),
            $this->createStub(RendererInterface::class),
            $this->createStub(RequestStack::class),
            $this->createStub(LocaleAwareInterface::class),
            $pageRegistry,
        );

        $builder = $contentComposition->createContentCompositionBuilder($page);

        $this->assertSame('page/foo', $builder->buildLayoutTemplate()->getName());
    }
}
