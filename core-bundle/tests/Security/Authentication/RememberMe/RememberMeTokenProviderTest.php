<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\RememberMe;

use Contao\CoreBundle\Entity\RememberMe;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Contao\CoreBundle\Security\Authentication\RememberMe\RememberMeTokenProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;

class RememberMeTokenProviderTest extends TestCase
{
    public function testCreatesTokenWhenSearchedBySeries(): void
    {
        $series = 'series';
        $value = 'value';
        $lastUsed = new \DateTime();
        $userIdentifier = 'foobar';

        $rememberMe = new RememberMe(FrontendUser::class, $userIdentifier, $series, $value, $lastUsed);

        $rememberMeRepository = $this->createMock(RememberMeRepository::class);
        $rememberMeRepository
            ->expects($this->once())
            ->method('findBySeries')
            ->with($series)
            ->willReturn($rememberMe)
        ;

        $rememberMeTokenProvider = new RememberMeTokenProvider($rememberMeRepository);
        $token = $rememberMeTokenProvider->loadTokenBySeries($series);

        $this->assertSame(FrontendUser::class, $token->getClass());
        $this->assertSame($userIdentifier, $token->getUserIdentifier());
        $this->assertSame($series, $token->getSeries());
        $this->assertSame($value, $token->getTokenValue());
        $this->assertSame($lastUsed->getTimestamp(), $token->getLastUsed()->getTimestamp());
    }

    public function testTheDeletionOfTokens(): void
    {
        $series = 'series';

        $rememberMeRepository = $this->createMock(RememberMeRepository::class);
        $rememberMeRepository
            ->expects($this->once())
            ->method('deleteBySeries')
            ->with($series)
        ;

        $rememberMeTokenProvider = new RememberMeTokenProvider($rememberMeRepository);
        $rememberMeTokenProvider->deleteTokenBySeries($series);
    }

    public function testIfTheTokenGetsUpdated(): void
    {
        $series = 'series';

        $rememberMe = new RememberMe(FrontendUser::class, 'foobar', $series, 'value', new \DateTime());

        $rememberMeRepository = $this->createMock(RememberMeRepository::class);
        $rememberMeRepository
            ->expects($this->once())
            ->method('findBySeries')
            ->with($series)
            ->willReturn($rememberMe)
        ;

        $rememberMeRepository
            ->expects($this->once())
            ->method('persist')
        ;

        $rememberMeTokenProvider = new RememberMeTokenProvider($rememberMeRepository);
        $rememberMeTokenProvider->updateToken($series, 'new-value', new \DateTime());
    }

    public function testTheTokenCreation(): void
    {
        $token = new PersistentToken(FrontendUser::class, 'foobar', 'series', 'value', new \DateTime());

        $rememberMeRepository = $this->createMock(RememberMeRepository::class);
        $rememberMeRepository
            ->expects($this->once())
            ->method('persist')
        ;

        $rememberMeTokenProvider = new RememberMeTokenProvider($rememberMeRepository);
        $rememberMeTokenProvider->createNewToken($token);
    }
}
