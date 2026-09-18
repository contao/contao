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

use Contao\ApiBundle\ApiPlatform\Serializer\DataContainerRecordNormalizer;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Validator\Constraints\DataContainerRecordSchema;
use Contao\ApiBundle\Validator\Constraints\DataContainerRecordSchemaValidator;
use Contao\CheckBox;
use Contao\Controller;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Contao\Validator;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class DataContainerRecordSchemaValidatorTest extends ContaoTestCase
{
    private array|null $widgets = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL'] = [
            'text' => TextField::class,
            'custom' => TextField::class,
            'checkbox' => CheckBox::class,
            'fileTree' => FileTree::class,
            'pageTree' => PageTree::class,
            'password' => Password::class,
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }

        parent::tearDown();
    }

    public function testValidatesOnlySubmittedFieldsDuringAPartialUpdate(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Too long for the current schema', 'published' => false, 'image' => null], 17);

        $record = new DataContainerRecordNormalizer()->denormalize(
            ['published' => true],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => $record],
        );

        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects($this->never())
            ->method('buildViolation')
        ;

        $validator = $this->createValidator();
        $validator->initialize($context);
        $validator->validate($record, new DataContainerRecordSchema());
    }

    public function testStillValidatesExplicitlyClearedFieldsDuringAPartialUpdate(): void
    {
        $record = new DataContainerRecordNormalizer()->denormalize(
            ['title' => null],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => new DataContainerRecord('tl_content', ['title' => 'abc'], 17)],
        );

        $this->assertViolation('Field "title": The data (null) must match the type: string', 'title', $record);
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

    public function testDoesNotRequireAnExistingPasswordToBeExposedForUpdates(): void
    {
        $controller = $this->createAdapterMock(['loadDataContainer']);
        $controller
            ->expects($this->once())
            ->method('loadDataContainer')
            ->willReturnCallback(
                static function (): void {
                    $GLOBALS['TL_DCA']['tl_content']['fields'] = [
                        'password' => ['inputType' => 'password', 'eval' => ['mandatory' => true], 'sql' => ['type' => 'string']],
                        'title' => ['inputType' => 'text', 'sql' => ['type' => 'string']],
                    ];
                },
            )
        ;
        $framework = $this->createContaoFrameworkStub([Controller::class => $controller]);
        $validator = new DataContainerRecordSchemaValidator(new DataContainerSchemaFactory($framework), new JsonSchemaValidator());
        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects($this->never())
            ->method('buildViolation')
        ;
        $validator->initialize($context);
        $validator->validate(new DataContainerRecord('tl_content', ['title' => 'Updated'], 17), new DataContainerRecordSchema());
    }

    private function assertViolation(string $expectedMessage, string $expectedPath, DataContainerRecord $record): void
    {
        $validator = $this->createValidator();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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

        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects($this->once())
            ->method('buildViolation')
            ->with($expectedMessage)
            ->willReturn($builder)
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
                            'inputType' => 'text',
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
