<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Defer;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Defer\DeferredStringable;
use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\Defer\Renderer;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;

class DeferTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new DeferTokenParser();

        $this->assertSame('defer', $tokenParser->getTag());
    }

    public function testOutputsDeferredStringables(): void
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor($this->createMock(Storage::class), $environment),
            ),
        );

        $environment->addTokenParser(new DeferTokenParser());
        $environment->setLoader(new ArrayLoader([
            'template.html.twig' => '{% defer %}{{ counter }}{% enddefer %}{% do counter.increase() %}',
        ]));

        $template = $environment->load('template.html.twig');

        $chunks = iterator_to_array($template->stream($this->getDefaultContext()), false);

        $this->assertCount(1, $chunks);
        $this->assertInstanceOf(DeferredStringable::class, $chunks[0]);
        $this->assertSame('1', (string) $chunks[0]);

        $this->assertSame(
            '0',
            $environment->render('template.html.twig', $this->getDefaultContext()),
            'linear order using the default renderer',
        );

        $this->assertSame(
            '1',
            (new Renderer($environment))->render('template.html.twig', $this->getDefaultContext()),
            'deferred order using the deferred renderer',
        );
    }

    private function getDefaultContext(): array
    {
        return [
            'counter' => new class() {
                private int $value = 0;

                public function increase(): void
                {
                    ++$this->value;
                }

                public function __toString(): string
                {
                    return (string) $this->value;
                }
            },
        ];
    }
}
