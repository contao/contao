<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\LegacyInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\SimpleTokenExpressionLanguage;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\CoreBundle\Twig\Runtime\SimpleTokenRuntime;
use Contao\InsertTags;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class InsertTagTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    #[DataProvider('provideVariableStatements')]
    public function testReplacesInsertTags(string $content, string $expected): void
    {
        $context = ['text' => '<br> {{br}}'];

        $this->assertSame($expected, $this->render($content, $context));
    }

    public static function provideVariableStatements(): iterable
    {
        yield 'no insert tag replacement' => [
            '{{ text }}',
            '&lt;br&gt; {{br}}',
        ];

        yield 'insert tag replacement with escaping' => [
            '{{ text|insert_tag }}',
            "&lt;br&gt; \n",
        ];

        yield 'raw insert tag, escaped outer text' => [
            '{{ text|insert_tag_html }}',
            '&lt;br&gt; <br>',
        ];

        yield 'all raw with insert_tag' => [
            '{{ text|insert_tag|raw }}',
            "<br> \n",
        ];

        yield 'all raw with insert_tag_html' => [
            '{{ text|raw|insert_tag_html }}',
            '<br> <br>',
        ];
    }

    #[DataProvider('provideSimpleTokenVariableStatements')]
    public function testReplacesInsertTagsAndSimpleTokens(string $content, string $expected): void
    {
        $context = ['text' => '<br> {{br}} ##token## {{abbr::##token##}}##token##{{abbr}}'];

        $this->assertSame($expected, $this->render($content, $context));
    }

    public static function provideSimpleTokenVariableStatements(): iterable
    {
        yield 'no replacement' => [
            '{{ text }}',
            '&lt;br&gt; {{br}} ##token## {{abbr::##token##}}##token##{{abbr}}',
        ];

        yield 'insert tag replacement with escaping' => [
            '{{ text|insert_tag|simple_token({ token: "<token>" }) }}',
            "&lt;br&gt; \n &lt;token&gt; &lt;token&gt;",
        ];

        yield 'raw insert tag, escaped outer text' => [
            '{{ text|simple_token({ token: "<token>" })|insert_tag_html }}',
            '&lt;br&gt; <br> &lt;token&gt; <abbr title="&lt;token&gt;">&lt;token&gt;</abbr>',
        ];

        yield 'all raw with insert_tag' => [
            '{{ text|insert_tag|simple_token({ token: "<token>" })|raw }}',
            "<br> \n <token> <token>",
        ];

        yield 'all raw with insert_tag_html' => [
            '{{ text|simple_token({ token: "<token>" })|raw|insert_tag_html }}',
            '<br> <br> <token> <abbr title="&lt;token&gt;"><token></abbr>',
        ];

        yield 'insert tag replacement with escaping and simple token as HTML' => [
            '{{ text|insert_tag|simple_token_html({ token: "<token>" }) }}',
            "&lt;br&gt; \n &lt;token&gt; &lt;token&gt;",
        ];

        yield 'raw insert tag, escaped outer text and simple token as HTML' => [
            '{{ text|insert_tag_html|simple_token_html({ token: "<token>" }) }}',
            '&lt;br&gt; <br> &lt;token&gt; <abbr title="&lt;token&gt;">&lt;token&gt;</abbr>',
        ];

        yield 'all raw with insert_tag and simple token as HTML' => [
            '{{ text|insert_tag|raw|simple_token_html({ token: "<token>" }) }}',
            "<br> \n &lt;token&gt; &lt;token&gt;",
        ];

        yield 'all raw with insert_tag_html and simple token as HTML' => [
            '{{ text|raw|insert_tag_html|simple_token_html({ token: "<token>" }) }}',
            '<br> <br> &lt;token&gt; <abbr title="&lt;token&gt;">&lt;token&gt;</abbr>',
        ];

        yield 'safe HTML with insert_tag_html' => [
            '{{ "{{safe_html}}"|insert_tag_html }}',
            '<div title="">',
        ];

        yield 'unsafe text with insert_tag_html' => [
            '{{ "{{unsafe_text}}"|insert_tag_html }}',
            '&lt;script src=&quot;&quot;&gt;',
        ];

        yield 'unsafe url with insert_tag_html' => [
            '{{ "{{unsafe_url}}"|insert_tag_html }}',
            '&lt;script src=&quot;&quot;&gt;',
        ];

        yield 'safe HTML with insert_tag' => [
            '{{ "{{safe_html}}"|insert_tag }}',
            "\n",
        ];

        yield 'safe HTML not pre escaped with insert_tag' => [
            '{{ "" ~ "{{safe_html}}"|insert_tag }}',
            "\n",
        ];

        yield 'unsafe text with insert_tag' => [
            '{{ "{{unsafe_text}}"|insert_tag }}',
            '&lt;script src=&quot;&quot;&gt;',
        ];

        yield 'unsafe url with insert_tag' => [
            '{{ "{{unsafe_url}}"|insert_tag }}',
            '&lt;script src=&quot;&quot;&gt;',
        ];
    }

    private function render(string $content, array $context): string
    {
        $templates = [
            '@Contao/test.html.twig' => $content,
        ];

        $environment = new Environment(new ArrayLoader($templates));

        $environment->setExtensions([
            new ContaoExtension(
                $environment,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
            ),
        ]);

        $tokenChecker = $this->createStub(TokenChecker::class);
        $tokenChecker
            ->method('hasFrontendUser')
            ->willReturn(false)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.security.token_checker', $tokenChecker);
        $container->set('monolog.logger.contao.error', $this->createStub(LoggerInterface::class));

        System::setContainer($container);

        $insertTagParser = new InsertTagParser($this->createStub(ContaoFramework::class), $this->createStub(LoggerInterface::class), $this->createStub(FragmentHandler::class));
        $insertTagParser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));
        $insertTagParser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'abbr', null, true, false));
        $insertTagParser->addSubscription(new InsertTagSubscription(static fn (ResolvedInsertTag $insertTag): InsertTagResult => new InsertTagResult('<div title="">', OutputType::html), '__invoke', 'safe_html', null, true, false));
        $insertTagParser->addSubscription(new InsertTagSubscription(static fn (ResolvedInsertTag $insertTag): InsertTagResult => new InsertTagResult('<script src="">', OutputType::text), '__invoke', 'unsafe_text', null, true, false));
        $insertTagParser->addSubscription(new InsertTagSubscription(static fn (ResolvedInsertTag $insertTag): InsertTagResult => new InsertTagResult('<script src="">', OutputType::url), '__invoke', 'unsafe_url', null, true, false));

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
                SimpleTokenRuntime::class => static fn () => new SimpleTokenRuntime(new SimpleTokenParser(new SimpleTokenExpressionLanguage())),
            ]),
        );

        return $environment->render('@Contao/test.html.twig', $context);
    }
}
