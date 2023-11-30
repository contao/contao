<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Translation\TranslatableLabelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 *
 * Generates option_callbacks for DCA fields with enum configurations,
 * optionally also generating translated references for enums implementing
 * \Contao\CoreBundle\Translation\TranslatableLabelInterface
 *
 * Example configuration:
 *   $GLOBALS['TL_DCA']['tl_example']['fields']['enum_field'] = [
 *     'enum' => StringBackedEnum::class,
 *   ]
 */
#[AsHook('loadDataContainer', priority: 200)]
class EnumOptionsListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(string $table): void
    {
        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $field => $config) {
            if (isset($GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback']) || \is_array($GLOBALS['TL_DCA'][$table]['fields'][$field]['options'] ?? null)) {
                continue;
            }

            if ($enum = ($config['enum'] ?? null)) {
                if (!is_subclass_of($enum, \BackedEnum::class)) {
                    throw new \LogicException(sprintf('Invalid enum configuration. Class "%s" must extend BackedEnum.', $enum));
                }

                // Build the options from the enum cases.
                $GLOBALS['TL_DCA'][$table]['fields'][$field]['options_callback'] = static fn () => array_map(
                    static fn ($case) => $case->value,
                    $enum::cases(),
                );

                // Build references with translations for a translatable enum.
                if (!isset($config['reference']) && is_subclass_of($enum, TranslatableLabelInterface::class)) {
                    $reference = [];

                    /** @var TranslatableLabelInterface&\BackedEnum $case */
                    foreach ($enum::cases() as $case) {
                        $reference[$case->value] = $case->label()->trans($this->translator);
                    }

                    $GLOBALS['TL_DCA'][$table]['fields'][$field]['reference'] = $reference;
                }
            }
        }
    }
}
