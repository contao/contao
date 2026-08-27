<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Validator\Constraints;

use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Validator\Constraints\DataContainerRecordSchema;
use Contao\ApiBundle\Validator\Constraints\DataContainerRecordSchemaValidator;
use Contao\Controller;
use Contao\TestCase\ContaoTestCase;
use Contao\Validator;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class DataContainerRecordSchemaValidatorTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testValidatesTitleLengthAccordingToTheGeneratedSchema(): void
    {
        $this->assertViolation(
            'Field "title": Maximum string length is 5, found 6',
            'title',
            new DataContainerRecord('tl_content', ['title' => 'abcdef']),
        );
    }

    public function testValidatesBooleanTypeAccordingToTheGeneratedSchema(): void
    {
        $this->assertViolation(
            'Field "published": The data (string) must match the type: boolean',
            'published',
            new DataContainerRecord('tl_content', [
                'title' => 'abc',
                'published' => 'yes',
            ]),
        );
    }

    public function testValidatesSharedRegexAccordingToTheGeneratedSchema(): void
    {
        $this->assertViolation(
            \sprintf('Field "digits": The string should match pattern: %s', Validator::REGEXP_DIGIT),
            'digits',
            new DataContainerRecord('tl_content', [
                'title' => 'abc',
                'published' => true,
                'digits' => '12a',
            ]),
        );
    }

    private function assertViolation(string $expectedMessage, string $expectedPath, DataContainerRecord $record): void
    {
        $validator = $this->createValidator();

        $context = $this->createMock(ExecutionContextInterface::class);
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($expectedMessage)
            ->willReturn($builder)
        ;

        $builder
            ->expects($this->once())
            ->method('atPath')
            ->with($expectedPath)
            ->willReturnSelf()
        ;

        $builder
            ->expects($this->once())
            ->method('addViolation')
        ;

        $validator->initialize($context);
        $validator->validate($record, new DataContainerRecordSchema());
    }

    private function createValidator(): DataContainerRecordSchemaValidator
    {
        $controllerAdapter = $this->createAdapterMock(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->once())
            ->method('loadDataContainer')
            ->willReturnCallback(
                static function (string $table): void {
                    $GLOBALS['TL_DCA'][$table]['fields'] = [
                        'title' => [
                            'inputType' => 'text',
                            'eval' => [
                                'mandatory' => true,
                                'maxlength' => 5,
                            ],
                            'sql' => [
                                'type' => 'string',
                                'length' => 5,
                            ],
                        ],
                        'published' => [
                            'inputType' => 'checkbox',
                            'sql' => [
                                'type' => 'boolean',
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
                },
            )
        ;

        $framework = $this->createContaoFrameworkMock([Controller::class => $controllerAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $schemaFactory = new DataContainerSchemaFactory($framework);

        return new DataContainerRecordSchemaValidator($schemaFactory, new JsonSchemaValidator());
    }
}
