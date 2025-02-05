<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Slug;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidCharacters
{
    private const DEFAULT_OPTIONS = [
        '\pN\p{Ll}' => 'unicodeLowercase',
        '\pN\pL' => 'unicode',
        '0-9a-z' => 'asciiLowercase',
        '0-9a-zA-Z' => 'ascii',
    ];

    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Returns the options for the valid characters setting suitable for widgets.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        $options = [];

        foreach (self::DEFAULT_OPTIONS as $option => $label) {
            $options[$option] = $this->translator->trans('MSC.validCharacters.'.$label, [], 'contao_default');
        }

        $event = new SlugValidCharactersEvent($options);

        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::SLUG_VALID_CHARACTERS);

        return $event->getOptions();
    }
}
