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
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\Image\Studio\FigureBuilderStub;
use Contao\CoreBundle\Tests\Image\Studio\ImageResultStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\FormatterRuntime;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Input;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Highlight\Highlighter;
use Nyholm\Psr7\Uri;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class ContentElementTestCase extends TestCase
{
    final public const FILE_IMAGE1 = '0a2073bc-c966-4e7b-83b9-163a06aa87e7';
    final public const FILE_IMAGE2 = '7ebca224-553f-4f36-b853-e6f3af3eff42';
    final public const FILE_IMAGE3 = '3045209c-b73d-4a69-b30b-cda8c8008099';
    final public const FILE_VIDEO_MP4 = 'e802b519-8e08-4075-913c-7603ec6f2376';
    final public const FILE_VIDEO_OGV = 'd950e33a-dacc-42ad-ba97-6387d05348c4';
    final public const ARTICLE1 = 123;
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
     * @param-out array<string, array<int|string, string>> $responseContextData
     */
    protected function renderWithModelData(AbstractContentElementController $controller, array $modelData, string|null $template = null, bool $asEditorView = false, array|null &$responseContextData = null): Response
    {
        // Setup Twig environment
        $loader = $this->getContaoFilesystemLoader();
        $environment = $this->getEnvironment($loader);

        // Setup container with helper services
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn($asEditorView)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));
        $container->set('contao.routing.scope_matcher', $scopeMatcher);
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('contao.twig.filesystem_loader', $loader);
        $container->set('contao.twig.interop.context_factory', new ContextFactory());
        $container->set('twig', $environment);
        $container->set('contao.framework', $this->getDefaultFramework());

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
                }
            )
        ;

        foreach ($modelData as $key => $value) {
            $model->$key = $value;
        }

        $controller->setFragmentOptions([
            'template' => $template ?? "content_element/{$modelData['type']}",
            'type' => $modelData['type'],
        ]);

        $response = $controller(new Request(), $model, 'main');

        // Record response context data
        $responseContextData = array_filter([
            DocumentLocation::head->value => $GLOBALS['TL_HEAD'] ?? [],
            DocumentLocation::endOfBody->value => $GLOBALS['TL_BODY'] ?? [],
        ]);

        // Reset state
        unset($GLOBALS['TL_HEAD'], $GLOBALS['TL_BODY']);

        $this->resetStaticProperties([Highlighter::class]);

        return $response;
    }

    protected function normalizeWhiteSpaces(string $string): string
    {
        // see https://stackoverflow.com/questions/5312349/minifying-final-html-output-using-regular-expressions-with-codeigniter
        $minifyRegex = <<<'EOD'
            (                                         # Collapse ws everywhere but in blacklisted elements
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
            )ix
            EOD;

        return trim(preg_replace($minifyRegex, ' ', $string));
    }

    protected function assertSameHtml(string $expected, string $actual, string $message = ''): void
    {
        $this->assertSame(
            $this->normalizeWhiteSpaces($expected),
            $this->normalizeWhiteSpaces($actual),
            $message
        );
    }

    protected function getContaoFilesystemLoader(): ContaoFilesystemLoader
    {
        $resourceBasePath = Path::canonicalize(__DIR__.'/../../../');

        $templateLocator = new TemplateLocator(
            '',
            ['ContaoCore' => ContaoCoreBundle::class],
            ['ContaoCore' => ['path' => $resourceBasePath]],
            $themeNamespace = new ThemeNamespace(),
            $this->createMock(Connection::class)
        );

        $loader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace);

        foreach ($templateLocator->findResourcesPaths() as $name => $resourcesPaths) {
            foreach ($resourcesPaths as $path) {
                $loader->addPath($path);
                $loader->addPath($path, "Contao_$name", true);
            }
        }

        $loader->buildInheritanceChains();

        return $loader;
    }

    protected function getEnvironment(ContaoFilesystemLoader $contaoFilesystemLoader): Environment
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = [], string $domain = null, string $locale = null): string => sprintf(
                    'translated(%s%s%s)',
                    null !== $domain ? "$domain:" : '',
                    $id,
                    !empty($parameters) ? '['.implode(', ', $parameters).']' : ''
                )
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
                $this->createMock(ContaoCsrfTokenManager::class)
            )
        );

        // Runtime loaders
        $insertTagParser = $this->getDefaultInsertTagParser();
        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $framework = $this->getDefaultFramework();

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
                HighlighterRuntime::class => static fn () => new HighlighterRuntime(),
                SchemaOrgRuntime::class => static fn () => new SchemaOrgRuntime($responseContextAccessor),
                FormatterRuntime::class => static fn () => new FormatterRuntime($framework),
            ])
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
                            [
                                'metadata' => new MetadataBag(
                                    ['en' => new Metadata([Metadata::VALUE_TITLE => 'image1 title'])],
                                    ['en']
                                ),
                            ],
                        ),
                        self::FILE_IMAGE2 => new FilesystemItem(true, 'image2.jpg'),
                        self::FILE_IMAGE3 => new FilesystemItem(true, 'image3.jpg'),
                        self::FILE_VIDEO_MP4 => new FilesystemItem(true, 'video.mp4'),
                        self::FILE_VIDEO_OGV => new FilesystemItem(true, 'video.ogv'),
                    ];

                    return $storageMap[$uuid->toRfc4122()] ?? null;
                }
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
                    ];

                    return $publicUriMap[$path] ?? null;
                }
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
                ]
            ))
        ;

        return $studio;
    }

    protected function getDefaultInsertTagParser(): InsertTagParser
    {
        $replaceDemo = static fn (string $input): string => str_replace(
            ['{{demo}}', '{{br}}'],
            ['demo', '<br>'],
            $input
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
                }
            )
        ;

        return $insertTagParser;
    }

    protected function getDefaultFramework(): ContaoFramework
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
                ][$key] ?? null
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

        $articleAdapter = $this->mockAdapter(['findPublishedById']);
        $articleAdapter
            ->method('findPublishedById')
            ->willReturnCallback(static fn (int $id) => [self::ARTICLE1 => $article1][$id] ?? null)
        ;

        return $this->mockContaoFramework([
            Config::class => $configAdapter,
            Input::class => $inputAdapter,
            PageModel::class => $pageAdapter,
            ArticleModel::class => $articleAdapter,
        ]);
    }
}
