<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Schema;

use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Contao\Validator;

final class DataContainerSchemaFactoryTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testCreatesSchemaFromDcaFields(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = [
            'id' => [
                'sql' => [
                    'type' => 'integer',
                ],
            ],
            'title' => [
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'maxlength' => 64,
                ],
                'sql' => [
                    'type' => 'string',
                    'length' => 64,
                ],
            ],
            'published' => [
                'inputType' => 'checkbox',
                'sql' => [
                    'type' => 'boolean',
                ],
            ],
            'type' => [
                'inputType' => 'select',
                'options' => ['a', 'b'],
                'sql' => [
                    'type' => 'string',
                    'length' => 1,
                ],
            ],
            'email' => [
                'eval' => [
                    'rgxp' => 'email',
                ],
                'sql' => [
                    'type' => 'string',
                ],
            ],
            'digits' => [
                'eval' => [
                    'rgxp' => 'digit',
                ],
                'sql' => [
                    'type' => 'string',
                ],
            ],
        ];

        $factory = new DataContainerSchemaFactory($this->createStub(ContaoFramework::class));

        $schema = $factory->create('tl_content');

        $this->assertSame('object', $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame(['title'], $schema['required']);
        $this->assertSame(['type' => 'integer', 'readOnly' => true], $schema['properties']['id']);
        $this->assertSame(['type' => 'string', 'maxLength' => 64], $schema['properties']['title']);
        $this->assertSame(['type' => 'boolean'], $schema['properties']['published']);
        $this->assertSame(['type' => 'string', 'maxLength' => 1, 'enum' => ['a', 'b']], $schema['properties']['type']);
        $this->assertSame(['type' => 'string', 'format' => 'email'], $schema['properties']['email']);
        $this->assertSame(['type' => 'string', 'pattern' => Validator::REGEXP_DIGIT], $schema['properties']['digits']);
    }
}
