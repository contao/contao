<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Slug;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\SlugValidCharactersEvent;
use Contao\CoreBundle\Slug\ValidCharacters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ValidCharactersTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $validCharacters = new ValidCharacters(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(TranslatorInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Slug\ValidCharacters', $validCharacters);
    }

    public function testReadsTheOptionsFromTheDispatchedEvent(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                ContaoCoreEvents::SLUG_VALID_CHARACTERS,
                $this->callback(
                    function (SlugValidCharactersEvent $event) use (&$path): bool {
                        $this->assertInternalType('array', $event->getOptions());
                        $this->assertArrayHasKey('\pN\p{Ll}', $event->getOptions());
                        $this->assertArrayHasKey('\pN\pL', $event->getOptions());
                        $this->assertArrayHasKey('0-9a-z', $event->getOptions());
                        $this->assertArrayHasKey('0-9a-zA-Z', $event->getOptions());

                        return true;
                    }
                )
            )
        ;

        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->expects($this->atLeastOnce())
            ->method('trans')
        ;

        $validCharacters = new ValidCharacters($eventDispatcher, $translator);
        $options = $validCharacters->getOptions();

        $this->assertInternalType('array', $options);
        $this->assertArrayHasKey('\pN\p{Ll}', $options);
        $this->assertArrayHasKey('\pN\pL', $options);
        $this->assertArrayHasKey('0-9a-z', $options);
        $this->assertArrayHasKey('0-9a-zA-Z', $options);
    }
}
