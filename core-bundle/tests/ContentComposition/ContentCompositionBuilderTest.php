<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\ContentComposition;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\ContentComposition\ContentCompositionBuilder;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Contao\ThemeModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ContentCompositionBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TL_ADMIN_EMAIL'],
            $GLOBALS['TL_LANGUAGE'],
        );

        parent::tearDown();
    }

    public function testBuildTemplateFailsIfThereIsNoLayout(): void
    {
        $framework = $this->createContaoFrameworkStub([
            LayoutModel::class => $this->createAdapterStub(['findById']),
        ]);

        $page = $this->createClassWithPropertiesStub(PageModel::class, ['layout' => 123]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Could not find layout ID "123"')
        ;

        $builder = $this->getContentCompositionBuilder($framework, $page, $logger);

        $this->expectException(NoLayoutSpecifiedException::class);
        $this->expectExceptionMessage('No layout specified');

        $builder->buildLayoutTemplate();
    }

    public function testSetsUpFrameworkAndAddsDefaultDataToTemplate(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, [
            'layout' => 1,
            'language' => 'de-DE',
            'adminEmail' => 'admin@contao.org',
        ]);

        $pictureFactory = $this->createMock(PictureFactory::class);
        $pictureFactory
            ->expects($this->once())
            ->method('setDefaultDensities')
            ->with('<densities>')
        ;

        $previewFactory = $this->createMock(PreviewFactory::class);
        $previewFactory
            ->expects($this->once())
            ->method('setDefaultDensities')
            ->with('<densities>')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with('de_DE')
        ;

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $translator = $this->createMock(LocaleAwareInterface::class);
        $translator
            ->expects($this->once())
            ->method('setLocale')
            ->with('de_DE')
        ;

        $builder = $this->getContentCompositionBuilder(
            $this->mockFramework(),
            $page,
            pictureFactory: $pictureFactory,
            previewFactory: $previewFactory,
            requestStack: $requestStack,
            translator: $translator,
        );

        $template = $builder->buildLayoutTemplate();

        $this->assertSame('admin@contao.org', $GLOBALS['TL_ADMIN_EMAIL']);
        $this->assertSame('de-DE', $GLOBALS['TL_LANGUAGE']);
        $this->assertSame(1, $page->layoutId);
        $this->assertSame('<template>', $page->template);
        $this->assertSame('<templates-dir>', $page->templateGroup);

        $expectedTemplateData = [
            'page' => [
                'layout' => 1,
                'language' => 'de-DE',
                'adminEmail' => 'admin@contao.org',
                'layoutId' => 1,
                'template' => '<template>',
                'templateGroup' => '<templates-dir>',
            ],
            'layout' => [
                'id' => 1,
                'pid' => 42,
                'defaultImageDensities' => '<densities>',
                'template' => '<template>',
            ],
            'locale' => 'de_DE',
            'rtl' => false,
            'element_references' => [],
        ];

        $this->assertSame($expectedTemplateData, $template->getData());
    }

    private function getContentCompositionBuilder(ContaoFramework $framework, PageModel $page, LoggerInterface|null $logger = null, PictureFactory|null $pictureFactory = null, PreviewFactory|null $previewFactory = null, RequestStack|null $requestStack = null, LocaleAwareInterface|null $translator = null,): ContentCompositionBuilder
    {
        return new ContentCompositionBuilder(
            $framework,
            $logger ?? $this->createStub(LoggerInterface::class),
            $pictureFactory ?? $this->createStub(PictureFactory::class),
            $previewFactory ?? $this->createStub(PreviewFactory::class),
            $this->createStub(ContaoContext::class),
            $this->createStub(RendererInterface::class),
            $requestStack ?? $this->createStub(RequestStack::class),
            $translator ?? $this->createStub(LocaleAwareInterface::class),
            $page,
        );
    }

    private function mockFramework(): ContaoFramework
    {
        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'id' => 1,
            'pid' => 42,
            'defaultImageDensities' => '<densities>',
            'template' => '<template>',
        ]);

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->willReturnCallback(static fn (int $id) => 1 === $id ? $layout : null)
        ;

        $theme = $this->createClassWithPropertiesStub(ThemeModel::class, [
            'id' => 42,
            'templates' => '<templates-dir>',
        ]);

        $themeAdapter = $this->createAdapterStub(['findById']);
        $themeAdapter
            ->method('findById')
            ->willReturnCallback(static fn (int $id) => 42 === $id ? $theme : null)
        ;

        $systemAdapter = $this->createAdapterMock(['loadLanguageFile']);
        $systemAdapter
            ->expects($this->once())
            ->method('loadLanguageFile')
            ->with('default')
        ;

        $framework = $this->createContaoFrameworkMock([
            LayoutModel::class => $layoutAdapter,
            ThemeModel::class => $themeAdapter,
            System::class => $systemAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        return $framework;
    }
}
