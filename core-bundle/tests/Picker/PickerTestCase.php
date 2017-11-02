<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\BackendUser;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class PickerTestCase extends TestCase
{
    /**
     * Mocks a token storage.
     *
     * @return TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockTokenStorage(): TokenStorageInterface
    {
        $user = $this->createPartialMock(BackendUser::class, ['hasAccess']);

        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }
}
