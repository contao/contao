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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchy;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ContaoEscaperNodeVisitorTest extends TestCase
{
    public function testEscapesEntities(): void
    {
        $output = $this->getEnvironment()->render(
            'modern.html.twig',
            [
                'headline' => '&amp; is the HTML entity for &',
                'content' => 'This is <i>raw HTML</i>.',
            ]
        );

        $this->assertSame(
            '<h1>&amp;amp; is the HTML entity for &amp;</h1><p>This is <i>raw HTML</i>.</p>',
            $output
        );
    }

    public function testDoesNotDoubleEncode(): void
    {
        $output = $this->getEnvironment()->render(
            'legacy.html.twig',
            [
                'headline' => '&amp; will look like &',
                'content' => 'This is <i>raw HTML</i>.',
            ]
        );

        $this->assertSame(
            '<h1>&amp; will look like &amp;</h1><p>This is <i>raw HTML</i>.</p>',
            $output
        );
    }

    protected function getEnvironment(): Environment
    {
        $templateContent = '<h1>{{ headline }}</h1><p>{{ content|raw }}</p>';

        $loader = new ArrayLoader([
            'modern.html.twig' => $templateContent,
            'legacy.html.twig' => $templateContent,
        ]);

        $environment = new Environment($loader);

        $contaoExtension = new ContaoExtension($environment, $this->createMock(TemplateHierarchy::class));
        $contaoExtension->registerTemplateForInputEncoding('legacy.html.twig');

        $environment->addExtension($contaoExtension);

        return $environment;
    }
}
