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
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\InsertTags;
use Contao\System;
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

    /**
     * @dataProvider provideVariableStatements
     */
    public function testReplacesInsertTags(string $content, string $expected): void
    {
        $context = ['text' => '<br> {{br}}'];

        $this->assertSame($expected, $this->render($content, $context));
    }

    public function provideVariableStatements(): \Generator
    {
        yield 'no insert tag replacement' => [
            '{{ text }}',
            '&lt;br&gt; {{br}}',
        ];

        yield 'insert tag replacement with escaping' => [
            '{{ text|insert_tag }}',
            '&lt;br&gt; &lt;br&gt;',
        ];

        yield 'raw insert tag, escaped outer text' => [
            '{{ text|insert_tag_raw }}',
            '&lt;br&gt; <br>',
        ];

        yield 'all raw with insert_tag' => [
            '{{ text|insert_tag|raw }}',
            '<br> <br>',
        ];

        yield 'all raw with insert_tag_raw' => [
            '{{ text|insert_tag_raw|raw }}',
            '<br> <br>',
        ];
    }

    private function render(string $content, array $context): string
    {
        $templates = [
            '@Contao/test.html.twig' => $content,
        ];

        $environment = new Environment(new ArrayLoader($templates));
        $environment->setExtensions([new ContaoExtension($environment, $this->createMock(TemplateHierarchyInterface::class))]);

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('hasFrontendUser')
            ->willReturn(false)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.security.token_checker', $tokenChecker);

        System::setContainer($container);

        $insertTagParser = new InsertTagParser($this->createMock(ContaoFramework::class));

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                InsertTagRuntime::class => static fn () => new InsertTagRuntime($insertTagParser),
            ])
        );

        return $environment->render('@Contao/test.html.twig', $context);
    }
}
