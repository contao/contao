<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\DBAL\Types;

use Contao\CoreBundle\Doctrine\DBAL\Types\BinaryStringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

class BinaryStringTypeTest extends TestCase
{
    private Type $type;

    protected function setUp(): void
    {
        parent::setUp();

        if (Type::hasType(BinaryStringType::NAME)) {
            Type::overrideType(BinaryStringType::NAME, BinaryStringType::class);
        } else {
            Type::addType(BinaryStringType::NAME, BinaryStringType::class);
        }

        $this->type = Type::getType(BinaryStringType::NAME);
    }

    public function testReturnsABinaryDefinitionForAFixedLengthField(): void
    {
        $fieldDefinition = ['fixed' => true];

        $platform = $this
            ->getMockBuilder(AbstractPlatform::class)
            ->onlyMethods(['getBinaryTypeDeclarationSQL', 'getBlobTypeDeclarationSQL'])
            ->getMockForAbstractClass()
        ;

        $platform
            ->expects($this->once())
            ->method('getBinaryTypeDeclarationSQL')
            ->willReturn('BINARY(255)')
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

        $platform = $this
            ->getMockBuilder(AbstractPlatform::class)
            ->onlyMethods(['getBinaryTypeDeclarationSQL', 'getBlobTypeDeclarationSQL'])
            ->getMockForAbstractClass()
        ;

        $platform
            ->expects($this->never())
            ->method('getBinaryTypeDeclarationSQL')
        ;

        $platform
            ->expects($this->once())
            ->method('getBlobTypeDeclarationSQL')
            ->willReturn('BLOB')
        ;

        $this->type->getSQLDeclaration($fieldDefinition, $platform);
    }

    public function testReturnsTheCorrectName(): void
    {
        $this->assertSame(BinaryStringType::NAME, $this->type->getName());
    }

    public function testRequiresAnSqlCommentHintForTheCustomType(): void
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->getMockForAbstractClass(AbstractPlatform::class)));
    }
}
