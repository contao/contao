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

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\InsertTags;
use Contao\System;
use Doctrine\DBAL\Connection;
use Highlight\Highlighter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class ContentElementTestCase extends TestCase
{
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

        $controller->setContainer($container);
        System::setContainer($container);

        // Render template with model data
        $model = $this->mockClassWithProperties(ContentModel::class);

        foreach ($modelData as $key => $value) {
            $model->$key = $value;
        }

        $controller->setFragmentOptions([
            'template' => $template ?? "content_element/{$modelData['type']}",
        ]);

        $response = $controller(new Request(), $model, 'main');

        // Record response context data
        $responseContextData = [
            DocumentLocation::head->value => $GLOBALS['TL_HEAD'] ?? [],
            DocumentLocation::endOfBody->value => $GLOBALS['TL_BODY'] ?? [],
        ];

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
        $resourceBasePath = Path::canonicalize(__DIR__.'/../../../src/Resources');

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
        $environment = new Environment($contaoFilesystemLoader);

        // Contao extension
        $environment->addExtension(new ContaoExtension($environment, $contaoFilesystemLoader, $this->createMock(ContaoCsrfTokenManager::class)));

        // Runtime loaders
        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
                HighlighterRuntime::class => static fn () => new HighlighterRuntime(),
            ])
        );

        $environment->enableStrictVariables();

        return $environment;
    }
}
