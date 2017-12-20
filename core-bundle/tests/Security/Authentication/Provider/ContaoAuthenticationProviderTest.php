<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication\Provider;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\Provider\ContaoAuthenticationProvider;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ContaoAuthenticationProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $authenticationProvider = new ContaoAuthenticationProvider(
            $this->createMock(UserProviderInterface::class),
            $this->createMock(UserCheckerInterface::class),
            'contao_frontend',
            $this->createMock(EncoderFactoryInterface::class),
            $this->createMock(ContaoFrameworkInterface::class),
            $this->createMock(TranslatorInterface::class),
            new RequestStack(),
            $this->createMock(\Swift_Mailer::class)
        );

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\Provider\ContaoAuthenticationProvider',
            $authenticationProvider
        );
    }
}
