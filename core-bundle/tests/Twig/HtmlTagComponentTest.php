<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\String\HtmlAttributes;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFunction;

class HtmlTagComponentTest extends TestCase
{
    public function testRendersStructuredAndRawTags(): void
    {
        $environment = $this->createEnvironment();

        $this->assertSame(
            '<title>&lt;Title&gt;</title><meta data-legacy>',
            $environment->render('test.html.twig', [
                'tag' => HtmlTag::title('<Title>'),
                'raw_tag' => '<meta data-legacy>',
            ]),
        );
    }

    public function testAddsCspNoncesWithoutMutatingTheTag(): void
    {
        $environment = $this->createEnvironment('nonce-value');
        $script = HtmlTag::inlineScript('alert(1)');

        $this->assertSame(
            '<script nonce="nonce-value">alert(1)</script><script nonce="manual">alert(1)</script>',
            $environment->render('test.html.twig', [
                'tag' => $script,
                'raw_tag' => $script->withAttribute('nonce', 'manual'),
            ]),
        );
        $this->assertFalse(isset($script->getAttributes()['nonce']));
    }

    private function createEnvironment(string|null $nonce = null): Environment
    {
        $filesystemLoader = new FilesystemLoader();
        $filesystemLoader->addPath(__DIR__.'/../../contao/templates', 'Contao');

        $environment = new Environment(new ChainLoader([
            new ArrayLoader([
                'test.html.twig' => <<<'TWIG'
                    {{ include('@Contao/component/_html_tag.html.twig', {tag}, false) }}
                    {{- include('@Contao/component/_html_tag.html.twig', {tag: raw_tag}, false) }}
                    TWIG,
            ]),
            $filesystemLoader,
        ]), ['autoescape' => 'html']);
        $environment->getRuntime(EscaperRuntime::class)->addSafeClass(HtmlAttributes::class, ['html']);
        $environment->addFunction(new TwigFunction('csp_nonce', static fn (): string|null => $nonce));

        return $environment;
    }
}
