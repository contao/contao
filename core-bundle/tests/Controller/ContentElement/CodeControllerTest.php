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

use Contao\CoreBundle\Controller\ContentElement\CodeController;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;

class CodeControllerTest extends ContentElementTestCase
{
    public function testOutputsHighlightedCode(): void
    {
        $response = $this->renderWithModelData(
            new CodeController(),
            [
                'type' => 'code',
                'code' => '<?php class Foo{}',
                'highlight' => 'php',
                'headline' => ['unit' => 'h1', 'value' => 'Some Code'],
                'cssID' => serialize(['my-id', 'my-class']),
            ],
            null,
            false,
            $responseContextData,
        );

        $expectedOutput = <<<'HTML'
            <div id="my-id" class="my-class content-code">
                <h1>Some Code</h1>
                <pre><code class="hljs php"><span class="hljs-meta">&lt;?php</span> <span class="hljs-class"><span class="hljs-keyword">class</span> <span class="hljs-title">Foo</span></span>{}</code></pre>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());

        $expectedHeadCode = <<<'HTML'
            <link rel="preload" href="/foundation.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript>
                <link rel="stylesheet" href="/foundation.css">
            </noscript>
            HTML;

        $additionalHeadCode = $responseContextData[DocumentLocation::head->value];

        $this->assertCount(1, $additionalHeadCode);
        $this->assertSameHtml($expectedHeadCode, $additionalHeadCode['highlighter_css']);
    }

    public function testOutputsPlainEditorView(): void
    {
        $response = $this->renderWithModelData(
            new CodeController(),
            [
                'type' => 'code',
                'code' => '<?php class Foo{}',
                'highlight' => 'php',
                'headline' => ['unit' => 'h1', 'value' => 'Some Code'],
                'cssID' => serialize(['my-id', 'my-class']),
            ],
            null,
            true,
            $responseContextData,
        );

        $expectedOutput = <<<'HTML'
            <div id="my-id" class="my-class content-code">
                <h1>Some Code</h1>
                <pre>&lt;?php class Foo{}</pre>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
