<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\RememberMe;

use Contao\CoreBundle\Security\Authentication\RememberMe\PersistentToken;
use PHPUnit\Framework\TestCase;

class PersistentTokenTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $lastUsed = new \DateTime();
        $token = new PersistentToken('class', 'username', 'series', 'value', $lastUsed);

        $this->assertSame('class', $token->getClass());
        $this->assertSame('username', $token->getUsername());
        $this->assertSame('series', $token->getSeries());
        $this->assertSame('value', $token->getTokenValue());
        $this->assertSame($lastUsed, $token->getLastUsed());
    }

    public function testFailsIfTheClassIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The class must not be empty.');

        new PersistentToken('', 'username', 'series', 'value', new \DateTime());
    }

    public function testFailsIfTheUsernameIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The username must not be empty.');

        new PersistentToken('class', '', 'series', 'value', new \DateTime());
    }

    public function testFailsIfTheSeriesIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The series must not be empty.');

        new PersistentToken('class', 'username', '', 'value', new \DateTime());
    }

    public function testFailsIfTheTokenValueIsEmpty(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The token value must not be empty.');

        new PersistentToken('class', 'username', 'series', '', new \DateTime());
    }
}
