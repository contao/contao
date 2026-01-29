<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\ContentComposition;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\ContentComposition\ContentCompositionBuilder;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Contao\ThemeModel;
use Psr\Log\LoggerInterface;
use Spatie\SchemaOrg\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ContentCompositionBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TL_ADMIN_EMAIL'],
            $GLOBALS['TL_ADMIN_NAME'],
            $GLOBALS['TL_LANGUAGE'],
            $GLOBALS['TL_HEAD'],
            $GLOBALS['TL_BODY'],
            $GLOBALS['TL_STYLE_SHEETS'],
            $GLOBALS['TL_CSS'],
        );

        parent::tearDown();
    }

    public function testInstantiatingFailsIfThereIsNoLayout(): void
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

        $this->expectException(NoLayoutSpecifiedException::class);
        $this->expectExceptionMessage('No layout specified');

        $this->getContentCompositionBuilder($framework, $page, $logger);
    }

    public function testInstantiatingFailsIfLayoutIsNotOfModernType(): void
    {
        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->with(2)
            ->willReturn(
                $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'default']),
            )
        ;

        $framework = $this->createContaoFrameworkStub([LayoutModel::class => $layoutAdapter]);

        $page = $this->createClassWithPropertiesStub(PageModel::class, ['layout' => 2]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Layout type "default" is not supported in the Contao\CoreBundle\ContentComposition\ContentCompositionBuilder.');

        $this->getContentCompositionBuilder($framework, $page);
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
            page: $page,
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
                'type' => 'modern',
            ],
            'locale' => 'de_DE',
            'rtl' => false,
            'element_references' => [],
        ];

        $this->assertSame($expectedTemplateData, $template->getData());
    }

    public function testAddsResponseContextDataToTemplate(): void
    {
        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag = new HtmlHeadBag());
        $responseContext->add($jsonLdManager = new JsonLdManager($responseContext));

        $jsonLdManager
            ->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
            ->set($jsonLdManager->createSchemaOrgTypeFromArray(['@type' => 'ImageObject', 'name' => 'Name']), Graph::IDENTIFIER_DEFAULT)
        ;

        $GLOBALS['TL_HEAD'][] = '<meta content="additional-tag">';
        $GLOBALS['TL_BODY'][] = '<script>/* additional script */</script>';
        $GLOBALS['TL_STYLE_SHEETS'][] = '<link rel="stylesheet" href="additional_stylesheet.css">';
        $GLOBALS['TL_CSS'][] = 'additional_stylesheet_filename.css|123';

        $parameters = $this
            ->getContentCompositionBuilder()
            ->setResponseContext($responseContext)
            ->buildLayoutTemplate()
            ->getData()
        ;

        $expectedResponseContextData = [
            'head' => $htmlHeadBag,
            'end_of_head' => [
                '<link rel="stylesheet" href="https://static-url/additional_stylesheet_filename.css?v=202cb962">',
                '<link rel="stylesheet" href="additional_stylesheet.css">',
                '<meta content="additional-tag">',
            ],
            'end_of_body' => [
                '<script>/* additional script */</script>',
            ],
            'json_ld_scripts' => <<<'EOF'
                <script type="application/ld+json">
                {
                    "@context": "https:\/\/schema.org",
                    "@graph": [
                        {
                            "@type": "ImageObject",
                            "name": "Name"
                        }
                    ]
                }
                </script>
                EOF,
        ];

        $this->assertArrayHasKey('response_context', $parameters);
        $this->assertSame($expectedResponseContextData, iterator_to_array($parameters['response_context']->all()));
    }

    public function testAddsCompositedContentToTemplate(): void
    {
        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'id' => 1,
            'pid' => 42,
            'defaultImageDensities' => '<densities>',
            'template' => '<template>',
            'type' => 'modern',
            'modules' => serialize([
                ['enable' => true, 'mod' => '1', 'col' => 'A'],
                ['enable' => false, 'mod' => '2', 'col' => 'B'],
                ['enable' => true, 'mod' => 'content-3', 'col' => 'C'],
                ['enable' => false, 'mod' => 'content-4', 'col' => 'D'],
                ['enable' => true, 'mod' => 'content-5', 'col' => 'A'],
            ]),
        ]);

        $renderer = $this->createStub(RendererInterface::class);
        $renderer
            ->method('render')
            ->willReturnCallback(
                function (string $template, array $parameters): string {
                    $this->assertSame('@Contao/<slot-template>.html.twig', $template);

                    return implode(',', array_map(
                        static fn (array $reference): string => "<rendered {$reference['type']} {$reference['id']}>",
                        $parameters['references'],
                    ));
                },
            )
        ;

        $template = $this
            ->getContentCompositionBuilder($this->mockFramework($layout))
            ->setSlotTemplate('<slot-template>')
            ->setFragmentRenderer($renderer)
            ->buildLayoutTemplate()
        ;

        $parameters = $template->getData();

        $this->assertSame(
            [
                'A' => [
                    ['type' => 'frontend_module', 'id' => 1],
                    ['type' => 'content_element', 'id' => 5],
                ],
                'C' => [
                    ['type' => 'content_element', 'id' => 3],
                ],
            ],
            $parameters['element_references'],
        );

        $this->assertTrue($template->hasSlot('A'));
        $this->assertFalse($template->hasSlot('B'));
        $this->assertTrue($template->hasSlot('C'));
        $this->assertFalse($template->hasSlot('D'));

        $this->assertSame(
            '<rendered frontend_module 1>,<rendered content_element 5>',
            (string) $template->getSlot('A'),
        );

        $this->assertSame(
            '<rendered content_element 3>',
            (string) $template->getSlot('C'),
        );
    }

    private function getContentCompositionBuilder(ContaoFramework|null $framework = null, PageModel|null $page = null, LoggerInterface|null $logger = null, PictureFactory|null $pictureFactory = null, PreviewFactory|null $previewFactory = null, RequestStack|null $requestStack = null, LocaleAwareInterface|null $translator = null): ContentCompositionBuilder
    {
        $page ??= $this->createClassWithPropertiesStub(PageModel::class, [
            'layout' => 1,
            'language' => 'en',
        ]);

        $contaoContext = $this->createStub(ContaoContext::class);
        $contaoContext
            ->method('getStaticUrl')
            ->willReturn('https://static-url/')
        ;

        return new ContentCompositionBuilder(
            $framework ?? $this->mockFramework(),
            $logger ?? $this->createStub(LoggerInterface::class),
            $pictureFactory ?? $this->createStub(PictureFactory::class),
            $previewFactory ?? $this->createStub(PreviewFactory::class),
            $contaoContext,
            $this->createStub(RendererInterface::class),
            $requestStack ?? $this->createStub(RequestStack::class),
            $translator ?? $this->createStub(LocaleAwareInterface::class),
            $page,
        );
    }

    private function mockFramework(LayoutModel|null $layout = null): ContaoFramework
    {
        $layout ??= $this->createClassWithPropertiesStub(LayoutModel::class, [
            'id' => 1,
            'pid' => 42,
            'defaultImageDensities' => '<densities>',
            'template' => '<template>',
            'type' => 'modern',
        ]);

        $layoutAdapter = $this->createAdapterStub(['findById']);
        $layoutAdapter
            ->method('findById')
            ->willReturnCallback(static fn (int $id) => $layout->id === $id ? $layout : null)
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
