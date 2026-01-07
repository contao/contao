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
use Contao\CoreBundle\Security\Voter\DataContainer\UserProfileVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserProfileVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 2]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $voter = new UserProfileVoter();

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_user'));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertFalse($voter->supportsType(CreateAction::class));
        $this->assertFalse($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsType(DeleteAction::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['user' => 42]),
                ['whatever'],
            ),
        );

        // Not the current user
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new UpdateAction('tl_user', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_user'],
            ),
        );

        // Allow update on current user if new is empty
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                new UpdateAction('tl_user', ['id' => 2]),
                [ContaoCorePermissions::DC_PREFIX.'tl_user'],
            ),
        );

        $GLOBALS['TL_DCA']['tl_user']['palettes']['login'] = 'foo,baz';

        // Allow update on current user if fields are in login palette
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                new UpdateAction('tl_user', ['id' => 2], ['foo' => 'bar']),
                [ContaoCorePermissions::DC_PREFIX.'tl_user'],
            ),
        );

        // Abstain if fields are not in login palette
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new UpdateAction('tl_user', ['id' => 2], ['bar' => 'baz']),
                [ContaoCorePermissions::DC_PREFIX.'tl_user'],
            ),
        );

        unset($GLOBALS['TL_DCA']);
    }
}
