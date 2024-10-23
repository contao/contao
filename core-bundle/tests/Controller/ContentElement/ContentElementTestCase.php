<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\ArticleModel;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\Image\Studio\FigureBuilderStub;
use Contao\CoreBundle\Tests\Image\Studio\ImageResultStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\CspRuntime;
use Contao\CoreBundle\Twig\Runtime\FormatterRuntime;
use Contao\CoreBundle\Twig\Runtime\FragmentRuntime;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;
use Contao\CoreBundle\Twig\Runtime\StringRuntime;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Input;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Highlight\Highlighter;
use Nyholm\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

abstract class ContentElementTestCase extends TestCase
{
    final public const FILE_IMAGE1 = '0a2073bc-c966-4e7b-83b9-163a06aa87e7';

    final public const FILE_IMAGE2 = '7ebca224-553f-4f36-b853-e6f3af3eff42';

    final public const FILE_IMAGE3 = '3045209c-b73d-4a69-b30b-cda8c8008099';

    final public const FILE_IMAGE_MISSING = '33389f37-b15c-4990-910d-49fd93adcf93';

    final public const FILE_VIDEO_MP4 = 'e802b519-8e08-4075-913c-7603ec6f2376';

    final public const FILE_VIDEO_OGV = 'd950e33a-dacc-42ad-ba97-6387d05348c4';

    final public const FILE_SUBTITLES_INVALID_VTT = '5234dfa3-d98f-42c7-ae36-ed8440478de2';

    final public const FILE_SUBTITLES_EN_VTT = 'a3c1e6d9-7d5b-4e3f-9e7d-6b9f2d3c841b';

    final public const FILE_SUBTITLES_DE_VTT = '1e48cbce-6354-4ca0-b133-6272aba46828';

    final public const ARTICLE1 = 123;

    final public const ARTICLE2 = 456;

    final public const PAGE1 = 5;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([
            DcaExtractor::class,
            DcaLoader::class,
            System::class,
            Config::class,
            InsertTags::class,
        ]);

        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $modelData
     *
     * @param-out array $responseContextData
     */
    protected function renderWithModelData(AbstractContentElementController $controller, array $modelData, string|null $template = null, bool $asEditorView = false, array|null &$responseContextData = null, ContainerBuilder|null $adjustedContainer = null, array $nestedFragments = []): Response
    {
        $framework = $this->getDefaultFramework($nestedFragments);

        // Setup Twig environment
        $loader = $this->getContaoFilesystemLoader();
        $environment = $this->getEnvironment($loader, $framework);

        // Setup container with helper services
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn($asEditorView)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));
        $container->set('contao.routing.content_url_generator', $this->createMock(ContentUrlGenerator::class));
        $container->set('contao.routing.scope_matcher', $scopeMatcher);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('contao.twig.filesystem_loader', $loader);
        $container->set('contao.twig.interop.context_factory', new ContextFactory());
        $container->set('twig', $environment);
        $container->set('contao.framework', $framework);
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));
        $container->set('fragment.handler', $this->createMock(FragmentHandler::class));

        if ($adjustedContainer) {
            $container->merge($adjustedContainer);

            foreach ($adjustedContainer->getServiceIds() as $serviceId) {
                if ('service_container' === $serviceId) {
                    continue;
                }

                $container->set($serviceId, $adjustedContainer->get($serviceId));
            }
        }

        $controller->setContainer($container);
        System::setContainer($container);

        // Render template with model data
        $model = $this->mockClassWithProperties(ContentModel::class);
        $model
            ->method('getOverwriteMetadata')
            ->willReturnCallback(
                static function () use ($modelData): Metadata|null {
                    if (!($modelData['overwriteMeta'] ?? null)) {
                        return null;
                    }

                    $data = $modelData;

                    if (isset($data['imageTitle'])) {
                        $data[Metadata::VALUE_TITLE] = $data['imageTitle'];
                    }

                    if (isset($data['imageUrl'])) {
                        $data[Metadata::VALUE_URL] = $data['imageUrl'];
                    }

                    return new Metadata(array_intersect_key($data, array_flip(['title', 'alt', 'link', 'caption', 'license'])));
                },
            )
        ;

        foreach ($modelData as $key => $value) {
            $model->$key = $value;
        }

        $controller->setFragmentOptions([
            'template' => $template ?? "content_element/{$modelData['type']}",
            'type' => $modelData['type'],
        ]);

        $request = new Request();
        $request->attributes->set('nestedFragments', $nestedFragments);

        $response = $controller($request, $model, 'main');

        // Record response context data
        $responseContextData = array_filter([
            DocumentLocation::head->value => $GLOBALS['TL_HEAD'] ?? [],
            DocumentLocation::stylesheets->value => $GLOBALS['TL_STYLE_SHEETS'] ?? [],
            DocumentLocation::endOfBody->value => $GLOBALS['TL_BODY'] ?? [],
        ]);

        // Reset state
        unset($GLOBALS['TL_HEAD'], $GLOBALS['TL_STYLE_SHEETS'], $GLOBALS['TL_BODY']);

        $this->resetStaticProperties([Highlighter::class]);

        return $response;
    }

    protected function normalizeWhiteSpaces(string $string): string
    {
        // https://stackoverflow.com/questions/5312349/minifying-final-html-output-using-regular-expressions-with-codeigniter
        $minifyRegex = '(                         # Collapse ws everywhere but in blacklisted elements
            (?>                                   # Match all whitespans other than single space
                [^\S ]\s*                         # Either one [\t\r\n\f\v] and zero or more ws,
                | \s{2,}                          # or two or more consecutive-any-whitespace
            )                                     # Note: The remaining regex consumes no text at all
            (?=                                   # Ensure we are not in a blacklist tag
                (?:                               # Begin (unnecessary) group.
                    (?:                           # Zero or more of...
                        [^<]++                    # Either one or more non-"<"
                        | <                       # or a < starting a non-blacklist tag
                        (?!/?(?:textarea|pre)\b)
                    )*+                           # (This could be "unroll-the-loop"ified)
                )                                 # End (unnecessary) group
                (?:                               # Begin alternation group
                    <                             # Either a blacklist start tag
                    (?>textarea|pre)\b
                    | \z                          # or end of file
                )                                 # End alternation group
            )                                     # If we made it here, we are not in a blacklist tag
        )ix';

        return trim(preg_replace($minifyRegex, ' ', $string));
    }

    protected function assertSameHtml(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            $this->normalizeWhiteSpaces($expected),
            $this->normalizeWhiteSpaces($actual),
            $message,
        );
    }

    protected function getContaoFilesystemLoader(): ContaoFilesystemLoader
    {
        $resourceBasePath = Path::canonicalize(__DIR__.'/../../../');

        $resourceFinder = $this->createMock(ResourceFinder::class);
        $resourceFinder
            ->method('getExistingSubpaths')
            ->with('templates')
            ->willReturn(['ContaoCore' => $resourceBasePath.'/contao/templates'])
        ;

        $templateLocator = new TemplateLocator(
            '',
            $resourceFinder,
            $themeNamespace = new ThemeNamespace(),
            $this->createMock(Connection::class),
        );

        return new ContaoFilesystemLoader(
            new NullAdapter(),
            $templateLocator,
            $themeNamespace,
            $this->createMock(ContaoFramework::class),
            $this->createMock(PageFinder::class),
            $resourceBasePath,
        );
    }

    protected function getEnvironment(ContaoFilesystemLoader $contaoFilesystemLoader, ContaoFramework $framework): Environment
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string => \sprintf(
                    'translated(%s%s%s)',
                    null !== $domain ? "$domain:" : '',
                    $id,
                    $parameters ? '['.implode(', ', $parameters).']' : '',
                ),
            )
        ;

        $packages = $this->createMock(Packages::class);
        $packages
            ->method('getUrl')
            ->willReturnCallback(static fn (string $url): string => '/'.$url)
        ;

        $environment = new Environment($contaoFilesystemLoader);
        $environment->addExtension(new TranslationExtension($translator));
        $environment->addExtension(new AssetExtension($packages));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $contaoFilesystemLoader,
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor(new NullAdapter(), $environment),
            ),
        );

        // Runtime loaders
        $insertTagParser = $this->getDefaultInsertTagParser();
        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                FragmentRuntime::class => static fn () => new FragmentRuntime($framework),
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
                HighlighterRuntime::class => static fn () => new HighlighterRuntime(),
                SchemaOrgRuntime::class => static fn () => new SchemaOrgRuntime($responseContextAccessor),
                FormatterRuntime::class => static fn () => new FormatterRuntime($framework),
                CspRuntime::class => static fn () => new CspRuntime($responseContextAccessor, new WysiwygStyleProcessor([])),
                StringRuntime::class => static fn () => new StringRuntime($framework),
            ]),
        );

        $environment->enableStrictVariables();

        return $environment;
    }

    protected function getDefaultStorage(): VirtualFilesystem
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $storage
            ->method('get')
            ->willReturnCallback(
                static function (Uuid $uuid): FilesystemItem|null {
                    $storageMap = [
                        self::FILE_IMAGE1 => new FilesystemItem(
                            true,
                            'image1.jpg',
                            123456,
                            1024,
                            'image/jpg',
                            new ExtraMetadata([
                                'localized' => new MetadataBag(
                                    ['en' => new Metadata([Metadata::VALUE_TITLE => 'image1 title'])],
                                    ['en'],
                                ),
                            ]),
                        ),
                        self::FILE_IMAGE2 => new FilesystemItem(true, 'image2.jpg', null, null, 'image/jpeg'),
                        self::FILE_IMAGE3 => new FilesystemItem(true, 'image3.jpg', null, null, 'image/jpeg'),
                        self::FILE_IMAGE_MISSING => new FilesystemItem(true, 'image_missing.jpg', null, null, 'image/jpeg'),
                        self::FILE_VIDEO_MP4 => new FilesystemItem(true, 'video.mp4', null, null, 'video/mp4'),
                        self::FILE_VIDEO_OGV => new FilesystemItem(true, 'video.ogv', null, null, 'video/ogg'),
                        self::FILE_SUBTITLES_INVALID_VTT => new FilesystemItem(true, 'subtitles-incomplete.vtt', null, null, 'text/vtt'),
                        self::FILE_SUBTITLES_EN_VTT => new FilesystemItem(
                            true,
                            'subtitles-en.vtt',
                            null,
                            null,
                            'text/vtt',
                            new ExtraMetadata([
                                'localized' => new MetadataBag(
                                    ['en' => new Metadata([Metadata::VALUE_TITLE => 'English'])],
                                    ['en'],
                                ),
                                'textTrack' => new TextTrack(
                                    'en',
                                    null,
                                ),
                            ]),
                        ),
                        self::FILE_SUBTITLES_DE_VTT => new FilesystemItem(
                            true,
                            'subtitles-de.vtt',
                            null,
                            null,
                            'text/vtt',
                            new ExtraMetadata([
                                'localized' => new MetadataBag(
                                    ['en' => new Metadata([Metadata::VALUE_TITLE => 'Deutsch'])],
                                    ['en'],
                                ),
                                'textTrack' => new TextTrack(
                                    'de',
                                    TextTrackType::captions,
                                ),
                            ]),
                        ),
                    ];

                    return $storageMap[$uuid->toRfc4122()] ?? null;
                },
            )
        ;

        $storage
            ->method('generatePublicUri')
            ->willReturnCallback(
                static function (string $path): Uri|null {
                    $publicUriMap = [
                        'image1.jpg' => new Uri('https://example.com/files/image1.jpg'),
                        'image2.jpg' => new Uri('https://example.com/files/image2.jpg'),
                        'image3.jpg' => new Uri('https://example.com/files/image3.jpg'),
                        'video.mp4' => new Uri('https://example.com/files/video.mp4'),
                        'video.ogv' => new Uri('https://example.com/files/video.ogv'),
                        'subtitles-incomplete.vtt' => new Uri('https://example.com/files/subtitles-incomplete.vtt'),
                        'subtitles-en.vtt' => new Uri('https://example.com/files/subtitles-en.vtt'),
                        'subtitles-de.vtt' => new Uri('https://example.com/files/subtitles-de.vtt'),
                    ];

                    return $publicUriMap[$path] ?? null;
                },
            )
        ;

        return $storage;
    }

    protected function getDefaultStudio(): Studio
    {
        $studio = $this->createMock(Studio::class);
        $studio
            ->method('createFigureBuilder')
            ->willReturn(new FigureBuilderStub(
                [
                    'files/image1.jpg' => new ImageResultStub([
                        'src' => 'files/image1.jpg',
                    ]),
                    'files/image2.jpg' => new ImageResultStub([
                        'src' => 'files/image2.jpg',
                    ]),
                    'files/image3.jpg' => new ImageResultStub([
                        'src' => 'files/image3.jpg',
                    ]),
                ],
                [
                    self::FILE_IMAGE1 => 'files/image1.jpg',
                    self::FILE_IMAGE2 => 'files/image2.jpg',
                    self::FILE_IMAGE3 => 'files/image3.jpg',
                ],
            ))
        ;

        return $studio;
    }

    protected function getDefaultInsertTagParser(): InsertTagParser
    {
        $replaceDemo = static fn (string $input): string => str_replace(
            ['{{demo}}', '{{br}}'],
            ['demo', '<br>'],
            $input,
        );

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replace')
            ->willReturnCallback($replaceDemo)
        ;

        $insertTagParser
            ->method('replaceInline')
            ->willReturnCallback($replaceDemo)
        ;

        $insertTagParser
            ->method('replaceChunked')
            ->willReturnCallback(
                static function (string $input) use ($replaceDemo): ChunkedText {
                    if (preg_match('/^(.*)\{\{br}}(.*)$/', $input, $matches)) {
                        return new ChunkedText([$matches[1], '<br>', $matches[2]]);
                    }

                    return new ChunkedText([$replaceDemo($input)]);
                },
            )
        ;

        return $insertTagParser;
    }

    protected function getDefaultFramework(array $nestedFragments = []): ContaoFramework
    {
        $GLOBALS['TL_LANG'] = [
            'MSC' => [
                'decimalSeparator' => '.',
                'thousandsSeparator' => ',',
            ],
            'UNITS' => ['Byte'],
        ];

        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->willReturnCallback(
                static fn (string $key) => [
                    'allowedTags' => '<a><b><i>',
                    'allowedAttributes' => serialize([
                        ['key' => '*', 'value' => 'data-*,id,class'],
                        ['key' => 'a', 'value' => 'href,rel,target'],
                    ]),
                    'allowedDownload' => 'jpg,txt',
                ][$key] ?? null,
            )
        ;

        $inputAdapter = $this->mockAdapter(['stripTags']);
        $inputAdapter
            ->method('stripTags')
            ->willReturnArgument(0)
        ;

        $page1 = $this->mockClassWithProperties(PageModel::class);
        $page1->id = self::PAGE1;

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->method('findPublishedById')
            ->willReturnCallback(static fn (int $id) => [self::PAGE1 => $page1][$id] ?? null)
        ;

        $article1 = $this->mockClassWithProperties(ArticleModel::class);
        $article1->id = self::ARTICLE1;
        $article1->pid = self::PAGE1;
        $article1->title = 'A title';
        $article1->teaser = '<p>This will tease you to read article 1.</p>';

        $article2 = $this->mockClassWithProperties(ArticleModel::class);
        $article2->id = self::ARTICLE2;
        $article2->pid = self::PAGE1;
        $article2->title = 'Just a title, no teaser';
        $article2->teaser = null;

        $articleAdapter = $this->mockAdapter(['findPublishedById']);
        $articleAdapter
            ->method('findPublishedById')
            ->willReturnCallback(static fn (int $id) => match ($id) {
                self::ARTICLE1 => $article1,
                self::ARTICLE2 => $article2,
                default => null,
            })
        ;

        $controllerAdapter = $this->mockAdapter(['getContentElement']);
        $controllerAdapter
            ->method('getContentElement')
            ->with($this->isInstanceOf(ContentElementReference::class))
            ->willReturnOnConsecutiveCalls(...array_map(static fn ($el) => $el->getContentModel()->type, $nestedFragments))
        ;

        $stringUtil = $this->mockAdapter(['encodeEmail']);
        $stringUtil
            ->method('encodeEmail')
            ->willReturnArgument(0)
        ;

        return $this->mockContaoFramework([
            Config::class => $configAdapter,
            Input::class => $inputAdapter,
            PageModel::class => $pageAdapter,
            ArticleModel::class => $articleAdapter,
            Controller::class => $controllerAdapter,
            StringUtil::class => $stringUtil,
        ]);
    }
}
