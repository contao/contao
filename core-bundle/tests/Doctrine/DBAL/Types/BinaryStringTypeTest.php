<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Doctrine\DBAL\Types;

use Contao\CoreBundle\Doctrine\DBAL\Types\BinaryStringType;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class BinaryStringTypeTest extends TestCase
{
    /**
     * @var BinaryStringType
     */
    private $type;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Type::addType(BinaryStringType::NAME, BinaryStringType::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = Type::getType(BinaryStringType::NAME);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Doctrine\DBAL\Types\BinaryStringType', $this->type);
    }

    public function testReturnsABinaryDefinitionForAFixedLengthField(): void
    {
        $fieldDefinition = ['fixed' => true];

        /** @var AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject $platform */
        $platform = $this
            ->getMockBuilder(AbstractPlatform::class)
            ->setMethods(['getBinaryTypeDeclarationSQL', 'getBlobTypeDeclarationSQL'])
            ->getMockForAbstractClass()
        ;

        $platform
            ->expects($this->once())
            ->method('getBinaryTypeDeclarationSQL')
        ;

        $platform
            ->expects($this->never())
            ->method('getBlobTypeDeclarationSQL')
        ;

        $this->type->getSQLDeclaration($fieldDefinition, $platform);
    }

    public function testReturnsABlobDefinitionForAVariableLengthField(): void
    {
        $fieldDefinition = ['fixed' => false];

        /** @var AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject $platform */
        $platform = $this
            ->getMockBuilder(AbstractPlatform::class)
            ->setMethods(['getBinaryTypeDeclarationSQL', 'getBlobTypeDeclarationSQL'])
            ->getMockForAbstractClass()
        ;

        $platform
            ->expects($this->never())
            ->method('getBinaryTypeDeclarationSQL')
        ;

        $platform
            ->expects($this->once())
            ->method('getBlobTypeDeclarationSQL')
        ;

        $this->type->getSQLDeclaration($fieldDefinition, $platform);
    }

    public function testReturnsTheCorrectName(): void
    {
        $this->assertSame(BinaryStringType::NAME, $this->type->getName());
    }

    public function testRequiresAnSqlCommentHintForTheCustomType(): void
    {
        $this->assertTrue(
            $this->type->requiresSQLCommentHint($this->getMockForAbstractClass(AbstractPlatform::class))
        );
    }
}
