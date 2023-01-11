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

use Contao\CoreBundle\Controller\ContentElement\ToplinkController;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;

class ToplinkControllerTest extends ContentElementTestCase
{
    /**
     * @dataProvider provideLinkText
     */
    public function testOutputsToplinkAndScript(string $linkText, string $expectedLinkElement): void
    {
        $response = $this->renderWithModelData(
            new ToplinkController(),
            [
                'type' => 'toplink',
                'linkTitle' => $linkText,
            ],
            null,
            false,
            $responseContextData
        );

        $expectedOutput = <<<HTML
            <!-- indexer::stop -->
            <div class="content-toplink">
                $expectedLinkElement
            </div>
            <!-- indexer::continue -->
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());

        $additionalBodyCode = $responseContextData[DocumentLocation::endOfBody->value];

        $this->assertCount(1, $additionalBodyCode);

        $this->assertMatchesRegularExpression(
            '/<script>[^<]+link\.href = location\.href[^<]+<\/script>/',
            $additionalBodyCode['toplink_script']
        );
    }

    public function provideLinkText(): \Generator
    {
        yield 'no value' => [
            '',
            '<a href="#top" data-toplink>translated(contao_default:MSC.backToTop)</a>',
        ];

        yield 'user defined value' => [
            'All the way up!',
            '<a href="#top" data-toplink title="All the way up!">All the way up!</a>',
        ];
    }

    public function testDoesNotAddScriptInEditorView(): void
    {
        $this->renderWithModelData(
            new ToplinkController(),
            [
                'type' => 'toplink',
                'linkTitle' => '',
            ],
            null,
            true,
            $responseContextData
        );

        $this->assertEmpty($responseContextData);
    }
}
