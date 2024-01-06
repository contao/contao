<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\File;

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Tests\TestCase;

class MetadataBagTest extends TestCase
{
    public function testCreateAndGetData(): void
    {
        $metadata = [
            'en' => new Metadata([Metadata::VALUE_TITLE => 'the cat']),
            'de' => new Metadata([Metadata::VALUE_TITLE => 'die Katze']),
            'es' => new Metadata([Metadata::VALUE_TITLE => 'el gato']),
        ];

        $bag = new MetadataBag($metadata, ['fr', 'de', 'en']);

        $this->assertFalse($bag->empty());

        $this->assertTrue($bag->has('en'));
        $this->assertTrue($bag->has('fr', 'de'));
        $this->assertFalse($bag->has('fr'));
        $this->assertFalse($bag->has('it', 'ru'));

        $this->assertSame('the cat', $bag->get('en')->getTitle());
        $this->assertSame('die Katze', $bag->get('de')->getTitle());
        $this->assertSame('el gato', $bag->get('es')->getTitle());
        $this->assertNull($bag->get('it'));

        $this->assertSame('die Katze', $bag->getDefault()->getTitle());
        $this->assertSame('the cat', $bag->getFirst()->getTitle());

        $this->assertSame($metadata, $bag->all());

        $this->assertSame('the cat', $bag['en']->getTitle());
        $this->assertFalse(isset($bag['it']));
    }

    public function testEmpty(): void
    {
        $this->assertTrue((new MetadataBag([]))->empty());
    }

    public function testGetNoDefault(): void
    {
        $bag = new MetadataBag(['en' => new Metadata([])]);

        $this->assertNull($bag->getDefault());
    }

    public function testGetNoFirst(): void
    {
        $bag = new MetadataBag([]);

        $this->assertNull($bag->getFirst());
    }

    public function testThrowsOnInvalidArrayAccess(): void
    {
        $bag = new MetadataBag([]);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The locale "it" does not exist in this metadata bag.');

        /** @phpstan-ignore-next-line */
        $bag['it'];
    }

    public function testThrowsWhenSetting(): void
    {
        $bag = new MetadataBag([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Setting metadata is not supported in this metadata bag.');

        $bag['de'] = new Metadata([]);
    }

    public function testThrowsWhenUnsetting(): void
    {
        $bag = new MetadataBag(['en' => new Metadata([])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsetting metadata is not supported in this metadata bag.');

        unset($bag['en']);
    }

    /**
     * @dataProvider provideInvalidElements
     */
    public function testPreventsConstructingWithInvalidObjects(array $elements, string $type): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("The metadata bag can only contain elements of type Contao\\CoreBundle\\File\\Metadata, got $type.");

        new MetadataBag($elements);
    }

    public function provideInvalidElements(): \Generator
    {
        yield 'not an object' => [
            ['en' => new Metadata([]), 'de' => 'foo'],
            'string',
        ];

        yield 'invalid object' => [
            ['en' => new Metadata([]), 'de' => new \stdClass()],
            \stdClass::class,
        ];
    }

    /**
     * @dataProvider provideInvalidLocales
     */
    public function testPreventsConstructingWithInvalidLocales(array $locales, string $type): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("The metadata bag can only be constructed with default locales of type string, got $type.");

        new MetadataBag([], $locales);
    }

    public function provideInvalidLocales(): \Generator
    {
        yield 'contains non-string literal' => [
            ['en', 42],
            'int',
        ];

        yield 'contains objects' => [
            ['en', new \stdClass()],
            \stdClass::class,
        ];
    }
}
