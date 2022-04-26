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
            ],
            responseContextData: $responseContextData
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/code">
                <pre><code class="hljs php"><span class="hljs-meta">&lt;?php</span><span class="hljs-class"><span class="hljs-keyword">class</span><span class="hljs-title">Foo</span></span>{}</code></pre>
            </div>

            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());

        $expectedHeadCode = <<<'HTML'
            <link rel="preload" href="vendor/scrivo/highlight_php/styles/foundation.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript>
                <link rel="stylesheet" href="vendor/scrivo/highlight_php/styles/foundation.css">
            </noscript>
            HTML;

        $additionalHeadCode = $responseContextData[DocumentLocation::head->value];

        $this->assertCount(1, $additionalHeadCode);
        $this->assertSameHtml($expectedHeadCode, $additionalHeadCode['highlight_php_css']);
    }
}
