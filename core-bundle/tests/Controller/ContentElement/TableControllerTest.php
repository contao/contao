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

use Contao\CoreBundle\Controller\ContentElement\TableController;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;

class TableControllerTest extends ContentElementTestCase
{
    public function testOutputsTable(): void
    {
        $response = $this->renderWithModelData(
            new TableController(),
            [
                'type' => 'table',
                'tableitems' => serialize([
                    ['foo', 'bar'], ['foobar', 'baz'],
                ]),
                'summary' => 'My caption',
                'sortable' => true,
                'sortIndex' => '2',
                'sortOrder' => 'ascending',
            ],
            null,
            false,
            $responseContextData
        );

        $expectedOutput = <<<'HTML'
            <div class="content-table">
                <table>
                    <caption>My caption</caption>
                    <tbody>
                        <tr>
                            <td>foo</td>
                            <td>bar</td>
                        </tr>
                        <tr>
                            <td>foobar</td>
                            <td>baz</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertEmpty($responseContextData);
    }

    public function testOutputsTableWithHeaderAndFooter(): void
    {
        $response = $this->renderWithModelData(
            new TableController(),
            [
                'type' => 'table',
                'tableitems' => serialize([
                    ['header1', 'header2'], ['foo', 'bar'], ['footer1', 'footer2'],
                ]),
                'thead' => true,
                'tfoot' => true,
                'tleft' => true,
                'sortable' => true,
                'sortIndex' => '2',
                'sortOrder' => 'ascending',
            ],
            null,
            false,
            $responseContextData
        );

        $expectedOutput = <<<'HTML'
            <div class="content-table">
                <table data-sortable-table="{&quot;descending&quot;:false}">
                    <thead>
                        <tr>
                            <th data-sort-method="none">header1</th>
                            <th data-sort-default>header2</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td>footer1</td>
                            <td>footer2</td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <th scope="row">foo</th>
                            <td>bar</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());

        $additionalHeadCode = $responseContextData[DocumentLocation::head->value];

        $this->assertCount(1, $additionalHeadCode);

        $this->assertMatchesRegularExpression(
            '/<script>[^<]+tablesort.min.js[^<]+<\/script>/',
            $additionalHeadCode['tablesort_script']
        );
    }
}
