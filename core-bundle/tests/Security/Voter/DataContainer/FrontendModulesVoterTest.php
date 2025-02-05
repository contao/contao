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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FrontendModuleVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FrontendModulesVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $voter = new FrontendModuleVoter($this->createMock(AccessDecisionManagerInterface::class));

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
                new ReadAction('foo', ['id' => 42, 'type' => 'navigation']),
                ['whatever'],
            ),
        );
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testUserCanOnlyAccessPermittedModuleTypes(array $userData, array $expected): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes', true],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES], null, true],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE], 'listing', $userData['isAdmin'] || \in_array('html', $userData['frontendModules'], true)],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE], 'html', $userData['isAdmin'] || \in_array('html', $userData['frontendModules'], true)],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE], 'navigation', $userData['isAdmin'] || \in_array('navigation', $userData['frontendModules'], true)],
            ])
        ;

        $voter = new FrontendModuleVoter($accessDecisionManager);

        // Reading is always permitted, although type "listing" is not explicitly allowed
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42, 'type' => 'listing']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        // The HTML module is not permitted for any user in this dataset (create,
        // update, delete)
        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new CreateAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        $this->assertSame(
            $expected['html'],
            $voter->vote(
                $token,
                new DeleteAction('foo', ['id' => 42, 'type' => 'html']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        // The navigation module is only permitted for one user in this dataset (create,
        // update, delete)
        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new CreateAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );

        $this->assertSame(
            $expected['navigation'],
            $voter->vote(
                $token,
                new DeleteAction('foo', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_module'],
            ),
        );
    }

    public static function userDataProvider(): iterable
    {
        yield 'Admin user has unlimited access' => [
            ['isAdmin' => true],
            ['html' => VoterInterface::ACCESS_ABSTAIN, 'navigation' => VoterInterface::ACCESS_ABSTAIN],
        ];

        yield 'User has unlimited access to front end modules' => [
            ['isAdmin' => false, 'frontendModules' => []],
            ['html' => VoterInterface::ACCESS_DENIED, 'navigation' => VoterInterface::ACCESS_DENIED],
        ];

        yield 'User access limited to specific module type' => [
            ['isAdmin' => false, 'frontendModules' => ['navigation']],
            ['html' => VoterInterface::ACCESS_DENIED, 'navigation' => VoterInterface::ACCESS_ABSTAIN],
        ];
    }
}
