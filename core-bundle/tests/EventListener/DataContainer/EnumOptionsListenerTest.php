<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\EnumOptionsListener;
use Contao\CoreBundle\Tests\Fixtures\Enum\IntBackedEnum;
use Contao\CoreBundle\Tests\Fixtures\Enum\StringBackedEnum;
use Contao\CoreBundle\Tests\Fixtures\Enum\TranslatableEnum;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumOptionsListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testDoesNothingWithoutEnumConfigurations(): void
    {
        $dca = $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [],
            ],
        ];

        $listener = new EnumOptionsListener($this->createMock(TranslatorInterface::class));
        $listener('tl_foo');

        $this->assertSame($dca, $GLOBALS['TL_DCA']['tl_foo']);
    }

    /**
     * @dataProvider backedEnumProvider
     *
     * @param class-string<\BackedEnum> $enum
     */
    public function testGeneratesOptionCallbackForBackedEnums(string $enum): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [
                    'enum' => $enum,
                ],
            ],
        ];

        $listener = new EnumOptionsListener($this->createMock(TranslatorInterface::class));
        $listener('tl_foo');

        $this->assertSame(
            array_map(static fn ($case) => $case->value, $enum::cases()),
            $GLOBALS['TL_DCA']['tl_foo']['fields']['foo']['options_callback'](),
        );
    }

    public function testDoesNotOverwriteExistingOptionCallback(): void
    {
        $dca = $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [
                    'enum' => StringBackedEnum::class,
                    'options_callback' => static fn () => ['foo', 'bar'],
                ],
            ],
        ];

        $listener = new EnumOptionsListener($this->createMock(TranslatorInterface::class));
        $listener('tl_foo');

        $this->assertSame(
            $dca,
            $GLOBALS['TL_DCA']['tl_foo'],
        );
    }

    public function testDoesNotOverwriteExistingOptions(): void
    {
        $dca = $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [
                    'enum' => StringBackedEnum::class,
                    'options' => ['foo', 'bar'],
                ],
            ],
        ];

        $listener = new EnumOptionsListener($this->createMock(TranslatorInterface::class));
        $listener('tl_foo');

        $this->assertSame(
            $dca,
            $GLOBALS['TL_DCA']['tl_foo'],
        );
    }

    public function testGeneratesTranslatedReferenceForLabeledEnum(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [
                    'enum' => TranslatableEnum::class,
                ],
            ],
        ];

        $reference = [
            TranslatableEnum::OptionA->value => 'Option One',
            TranslatableEnum::OptionB->value => 'Option Two',
        ];
        $translations = array_values($reference);

        $translator = $this->createMock(TranslatorInterface::class);

        /** @var array<TranslatableMessage> $map */
        $map = array_map(static fn ($case) => $case->label(), TranslatableEnum::cases());
        $translator
            ->expects($this->exactly(\count(TranslatableEnum::cases())))
            ->method('trans')
            ->willReturnCallback(
                function (string $message, array $params, string $domain) use (&$map, &$translations) {
                    $target = array_shift($map);
                    $translated = array_shift($translations);

                    $this->assertSame($message, $target->getMessage());
                    $this->assertSame($params, $target->getParameters());
                    $this->assertSame($domain, $target->getDomain());

                    return $translated;
                },
            )
        ;

        $listener = new EnumOptionsListener($translator);
        $listener('tl_foo');

        $this->assertSame(
            $reference,
            $GLOBALS['TL_DCA']['tl_foo']['fields']['foo']['reference'],
        );
    }

    public function testDoesNotOverwriteExistingReference(): void
    {
        $dca = $GLOBALS['TL_DCA']['tl_foo'] = [
            'fields' => [
                'foo' => [
                    'enum' => StringBackedEnum::class,
                    'reference' => [
                        'foo' => 'Foo',
                        'bar' => 'Bar',
                    ],
                ],
            ],
        ];

        $listener = new EnumOptionsListener($this->createMock(TranslatorInterface::class));
        $listener('tl_foo');

        $this->assertSame(
            $dca['fields']['foo']['reference'],
            $GLOBALS['TL_DCA']['tl_foo']['fields']['foo']['reference'],
        );
    }

    /**
     * @return array<int, array<int, class-string<\BackedEnum>>>
     */
    public function backedEnumProvider(): array
    {
        return [
            [StringBackedEnum::class],
            [IntBackedEnum::class],
        ];
    }
}
