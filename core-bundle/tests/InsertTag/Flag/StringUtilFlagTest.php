<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Flag;

use Contao\CoreBundle\InsertTag\Flag\StringUtilFlag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StringUtilFlagTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    #[DataProvider('getFlags')]
    public function testFlags(string $flagName, string $source, string $expected): void
    {
        $flag = new StringUtilFlag();

        $this->assertSame($expected, $flag->$flagName(new InsertTagFlag($flagName), new InsertTagResult($source))->getValue());
    }

    public static function getFlags(): iterable
    {
        yield ['strtolower', 'FOO', 'foo'];
        yield ['strtoupper', 'foo', 'FOO'];
        yield ['ucfirst', 'foo', 'Foo'];
        yield ['lcfirst', 'FOO', 'fOO'];
        yield ['ucwords', 'foo bar', 'Foo Bar'];
        yield ['strtolower', 'FÖO', 'föo'];
        yield ['strtoupper', 'föo', 'FÖO'];
        yield ['ucfirst', 'öof', 'Öof'];
        yield ['lcfirst', 'ÖOF', 'öOF'];
        yield ['ucwords', 'öof bar', 'Öof Bar'];
    }

    #[DataProvider('getEncodingFlags')]
    public function testEncodesAttributesOnce(string $flagName, string $source, OutputType $outputType, string $expected): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('contao.sanitizer.allowed_url_protocols', ['http', 'https', 'mailto']);

        System::setContainer($container);

        $flag = new StringUtilFlag();
        $result = $flag->$flagName(new InsertTagFlag($flagName), new InsertTagResult($source, $outputType));

        $this->assertSame($expected, $result->getValue());
        $this->assertSame(OutputType::html, $result->getOutputType());
    }

    public static function getEncodingFlags(): iterable
    {
        yield ['attr', 'A &amp; B', OutputType::text, 'A &amp;amp; B'];
        yield ['attr', 'A &amp; B', OutputType::html, 'A &amp; B'];
        yield ['urlattr', 'a=1&amp;b=2', OutputType::text, 'a=1&amp;amp;b=2'];
        yield ['urlattr', '&#105;&#x6E;&#102;&#x6F;&#64;example.org', OutputType::html, '&#105;&#x6E;&#102;&#x6F;&#64;example.org'];
    }
}
