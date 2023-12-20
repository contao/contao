<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FavoritesVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FavoritesVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user)
        ;

        $voter = new FavoritesVoter();

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_favorites'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['user' => 42]),
                ['whatever'],
            ),
        );

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_favorites', ['user' => 2]),
                [ContaoCorePermissions::DC_PREFIX.'tl_favorites'],
            ),
        );

        // Permission denied
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_favorites', ['user' => 3]),
                [ContaoCorePermissions::DC_PREFIX.'tl_favorites'],
            ),
        );
    }

    public function testDeniesAccessIfUserIsNotABackendUser(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class, ['id' => 2]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $voter = new FavoritesVoter();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_favorites', ['user' => 3]),
                [ContaoCorePermissions::DC_PREFIX.'tl_favorites'],
            ),
        );
    }
}
