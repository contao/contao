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
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;

class FavoritesVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->with('SELECT user FROM tl_favorites WHERE id = :id')
            ->willReturnCallback(
                static fn (string $query, array $args): int => match ((int) $args['id']) {
                    42 => 2, // current user
                    17 => 3, // different user
                    default => 0,
                }
            )
        ;

        $voter = new FavoritesVoter($security, $connection);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_favorites'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        $token = $this->createMock(TokenInterface::class);

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                ['whatever']
            )
        );

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_favorites']
            )
        );

        // Permission denied
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 17]),
                [ContaoCorePermissions::DC_PREFIX.'tl_favorites']
            )
        );
    }
}
