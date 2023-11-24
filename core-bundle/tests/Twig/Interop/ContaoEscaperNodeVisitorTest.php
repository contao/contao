<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\TwigFunction;

class ContaoEscaperNodeVisitorTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testPriority(): void
    {
        $visitor = new ContaoEscaperNodeVisitor(static fn () => []);

        $this->assertSame(1, $visitor->getPriority());
    }

    public function testEscapesEntities(): void
    {
        $templateContent = '<h1>{{ headline }}</h1><p>{{ content|raw }}</p>';

        $output = $this->getEnvironment($templateContent)->render('modern.html.twig', [
            'headline' => '&amp; is the HTML entity for &',
            'content' => 'This is <i>raw HTML</i>.',
        ]);

        $this->assertSame('<h1>&amp;amp; is the HTML entity for &amp;</h1><p>This is <i>raw HTML</i>.</p>', $output);
    }

    public function testDoesNotDoubleEncode(): void
    {
        $templateContent = '<h1>{{ headline }}</h1><p>{{ content|raw }}</p>';

        $output = $this->getEnvironment($templateContent)->render('legacy.html.twig', [
            'headline' => '&amp; will look like &',
            'content' => 'This is <i>raw HTML</i>.',
        ]);

        $this->assertSame('<h1>&amp; will look like &amp;</h1><p>This is <i>raw HTML</i>.</p>', $output);
    }

    public function testHandlesFiltersAndFunctions(): void
    {
        $templateContent = '{{ heart() }} {{ target|trim }}';

        $environment = $this->getEnvironment($templateContent);
        $environment->addFunction(new TwigFunction('heart', static fn () => '&#9829;'));

        $output = $environment->render('legacy.html.twig', ['target' => ' Twig &amp; Contao ']);

        $this->assertSame('&#9829; Twig &amp; Contao', $output);
    }

    public function testUppercaseEntities(): void
    {
        $templateContent = '{{ content|upper }}';

        $output = $this->getEnvironment($templateContent)->render('legacy.html.twig', [
            'content' => '&quot;a&quot; &amp; &lt;b&gt;',
        ]);

        $this->assertSame('&quot;A&quot; &amp; &lt;B&gt;', $output);
    }

    /**
     * @group legacy
     */
    public function testHtmlAttrFilter(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.2: Using the "replaceInsertTags" hook has been deprecated %s.');

        $GLOBALS['TL_HOOKS'] = ['replaceInsertTags' => [[static::class, 'executeReplaceInsertTagsCallback']]];

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));
        $container->set('fragment.handler', $this->createMock(FragmentHandler::class));

        System::setContainer($container);

        $templateContent = "<span title={{ title|insert_tag|e('html_attr') }}></span>";

        $output = $this->getEnvironment($templateContent)->render('legacy.html.twig', [
            'title' => '{{flavor}} _is_ a flavor',
        ]);

        $this->assertSame('<span title=vanilla&#x20;_is_&#x20;a&#x20;flavor></span>', $output);

        unset($GLOBALS['TL_HOOKS']);
    }

    public function executeReplaceInsertTagsCallback(string $tag): string|false
    {
        return 'flavor' === $tag ? 'vanilla' : false;
    }

    private function getEnvironment(string $templateContent): Environment
    {
        $loader = new ArrayLoader([
            'modern.html.twig' => $templateContent,
            'legacy.html.twig' => $templateContent,
        ]);

        $environment = new Environment($loader);

        $contaoExtension = new ContaoExtension(
            $environment,
            $this->createMock(TemplateHierarchyInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class),
        );

        $contaoExtension->addContaoEscaperRule('/legacy\.html\.twig/');

        $environment->addExtension($contaoExtension);

        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
            ]),
        );

        return $environment;
    }
}
