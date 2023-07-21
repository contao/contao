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
use Contao\CoreBundle\Security\Voter\DataContainer\FrontendModulesVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FrontendModulesVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $voter = new FrontendModulesVoter($security);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_module'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                ['whatever']
            )
        );
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testUserCanOnlyAccessPermittedModuleTypes(array $userData, array $expected): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'isAdmin' => false, ...$userData]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $token = $this->createMock(TokenInterface::class);

        $voter = new FrontendModulesVoter($security);

        // Reading is always permitted
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        // The HTML module is not permitted for any user in this dataset (create, update, delete)
        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new CreateAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new DeleteAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        // The navigation module is only permitted for one user in this dataset (create, update, delete)
        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new CreateAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );

        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new DeleteAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module']
            )
        );
    }

    public function userDataProvider(): \Generator
    {
        yield 'Admin user has unlimited access' => [
            ['isAdmin' => true],
            ['html' => VoterInterface::ACCESS_GRANTED, 'navigation' => VoterInterface::ACCESS_GRANTED],
        ];
        yield 'User has unlimited access to front end modules' => [
            ['isAdmin' => false, 'frontendModules' => []],
            ['html' => VoterInterface::ACCESS_GRANTED, 'navigation' => VoterInterface::ACCESS_GRANTED],
        ];
        yield 'User access limited to specific module type' => [
            ['isAdmin' => false, 'frontendModules' => ['navigation']],
            ['html' => VoterInterface::ACCESS_DENIED, 'navigation' => VoterInterface::ACCESS_GRANTED],
        ];
    }
}
