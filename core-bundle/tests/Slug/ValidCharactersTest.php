<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Slug;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use Contao\CoreBundle\Slug\ValidCharacters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidCharactersTest extends TestCase
{
    public function testReadsTheOptionsFromTheDispatchedEvent(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    function (SlugValidCharactersEvent $event): bool {
                        $this->assertArrayHasKey('\pN\p{Ll}', $event->getOptions());
                        $this->assertArrayHasKey('\pN\pL', $event->getOptions());
                        $this->assertArrayHasKey('0-9a-z', $event->getOptions());
                        $this->assertArrayHasKey('0-9a-zA-Z', $event->getOptions());

                        return true;
                    },
                ),
                ContaoCoreEvents::SLUG_VALID_CHARACTERS,
            )
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->atLeastOnce())
            ->method('trans')
        ;

        $validCharacters = new ValidCharacters($eventDispatcher, $translator);
        $options = $validCharacters->getOptions();

        $this->assertArrayHasKey('\pN\p{Ll}', $options);
        $this->assertArrayHasKey('\pN\pL', $options);
        $this->assertArrayHasKey('0-9a-z', $options);
        $this->assertArrayHasKey('0-9a-zA-Z', $options);
    }
}
