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
use Contao\CoreBundle\Security\Voter\DataContainer\UserAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserAccessVoterTest extends TestCase
{
    public function testDeniesAccessIfUserHasNoAccessToUserModule(): void
    {
        $token = $this->mockToken();
        $accessDecisionManager = $this->mockAccessDecisionManager($token, false);

        $subject = new ReadAction('tl_user', ['id' => 2]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testAllowsReadOfItselfWithoutUserModule(): void
    {
        $token = $this->mockToken(['id' => 2]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token, false);

        $subject = new ReadAction('tl_user', ['id' => 2]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAllowsUpdateOfItselfWithoutUserModule(): void
    {
        $token = $this->mockToken(['id' => 2]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token, false);

        $subject = new UpdateAction('tl_user', ['id' => 2], ['foo' => 'bar']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAllowsReadAction(): void
    {
        $token = $this->mockToken();
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new ReadAction('tl_user', ['id' => 2]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testUserCannotDeleteItself(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new DeleteAction('tl_user', ['id' => 42]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testUserCannotDisableItself(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 42], ['disable' => '1']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testUserCannotChangeItsAdminState(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 42], ['admin' => '1']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testAdminCanDeleteAllUsers(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token, true, true);

        $subject = new DeleteAction('tl_user', ['id' => 2]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAdminCanUpdateAllUsers(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token, true, true);

        $subject = new UpdateAction('tl_user', ['id' => 2], ['foo' => 'bar']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testRegularUserCanUpdateRegularUsers(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 2], ['foo' => 'bar']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection([42]));
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testRegularUserCanCreateRegularUsers(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 2, 'foo' => 'bar']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection([42]));
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testRegularUserCannotSetAdminFlag(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 2], ['admin' => 1]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testRegularUserCannotCreateAdminUser(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new CreateAction('tl_user', ['id' => 2, 'admin' => 1]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public function testRegularUserCanCreateRegularUser(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new CreateAction('tl_user', ['id' => 2, 'admin' => 0]);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection());
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testRegularUserCannotUpdateAdminUsers(): void
    {
        $token = $this->mockToken(['id' => 42]);
        $accessDecisionManager = $this->mockAccessDecisionManager($token);

        $subject = new UpdateAction('tl_user', ['id' => 2], ['foo' => 'bar']);

        $voter = new UserAccessVoter($accessDecisionManager, $this->mockConnection([2]));
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    private function mockToken(UserInterface|array|null $user = null): TokenInterface&MockObject
    {
        $token = $this->createMock(TokenInterface::class);

        if (\is_array($user)) {
            $user = $this->mockClassWithProperties(BackendUser::class, $user);
        }

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        return $token;
    }

    private function mockAccessDecisionManager(TokenInterface $token, bool $allowModule = true, bool $isAdmin = false): AccessDecisionManagerInterface&MockObject
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'user', $allowModule],
                [$token, ['ROLE_ADMIN'], null, $isAdmin],
            ])
        ;

        return $accessDecisionManager;
    }

    private function mockConnection(array|null $adminIds = null): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);

        if ($adminIds) {
            $connection
                ->expects($this->once())
                ->method('fetchFirstColumn')
                ->with('SELECT id FROM tl_user WHERE `admin` = 1')
                ->willReturn($adminIds)
            ;
        } else {
            $connection
                ->expects($this->never())
                ->method('fetchFirstColumn')
            ;
        }

        return $connection;
    }
}
