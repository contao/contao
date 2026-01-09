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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\DataContainerPermissionVoter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DataContainerPermissionVoterTest extends TestCase
{
    public function testSupportsAttribute(): void
    {
        $voter = new DataContainerPermissionVoter(
            $this->createContaoFrameworkStub(),
            $this->createStub(AccessDecisionManagerInterface::class),
        );

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_foobar'));
        $this->assertFalse($voter->supportsAttribute('something_else'));
    }

    public function testSupportsType(): void
    {
        $voter = new DataContainerPermissionVoter(
            $this->createContaoFrameworkStub(),
            $this->createStub(AccessDecisionManagerInterface::class),
        );

        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsType('Foobar'));
    }

    public function testAbstainsIfAttributeIsNotSupported(): void
    {
        $voter = new DataContainerPermissionVoter(
            $this->stubContaoFramework(),
            $this->mockAccessDecisionManager(),
        );

        $token = $this->createStub(TokenInterface::class);
        $result = $voter->vote($token, new CreateAction('tl_foobar'), ['foobar']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[DataProvider('deniesCreateOnConfigProvider')]
    public function testDeniesOnConfig(string $config, CreateAction|DeleteAction|UpdateAction $subject): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config'][$config] = true;

        $voter = new DataContainerPermissionVoter(
            $this->stubContaoFramework(),
            $this->mockAccessDecisionManager(),
        );

        $token = $this->createStub(TokenInterface::class);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_foobar']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);

        unset($GLOBALS['TL_DCA']);
    }

    public static function deniesCreateOnConfigProvider(): iterable
    {
        yield [
            'closed',
            new CreateAction('tl_foobar'),
        ];

        yield [
            'notEditable',
            new CreateAction('tl_foobar'),
        ];

        yield [
            'notCreatable',
            new CreateAction('tl_foobar'),
        ];

        yield [
            'closed',
            new UpdateAction('tl_foobar', []),
        ];

        yield [
            'notEditable',
            new UpdateAction('tl_foobar', []),
        ];

        yield [
            'notDeletable',
            new DeleteAction('tl_foobar', []),
        ];
    }

    #[DataProvider('voteProvider')]
    public function testVote(CreateAction|DeleteAction|UpdateAction $subject, array|null $permissions, bool|null $accessDecision): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config']['permissions'] = $permissions;

        $voter = new DataContainerPermissionVoter(
            $this->stubContaoFramework(),
            $this->mockAccessDecisionManager($accessDecision),
        );

        $token = $this->createStub(TokenInterface::class);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'foobar']);

        $this->assertSame(false === $accessDecision ? VoterInterface::ACCESS_DENIED : VoterInterface::ACCESS_ABSTAIN, $result);

        unset($GLOBALS['TL_DCA']);
    }

    public static function voteProvider(): iterable
    {
        yield [
            new CreateAction('tl_foobar'),
            [],
            null,
        ];

        yield [
            new CreateAction('tl_foobar'),
            ['create'],
            false,
        ];

        yield [
            new CreateAction('tl_foobar'),
            ['create'],
            true,
        ];

        yield [
            new UpdateAction('tl_foobar', []),
            ['update'],
            false,
        ];

        yield [
            new UpdateAction('tl_foobar', []),
            ['update'],
            true,
        ];

        yield [
            new DeleteAction('tl_foobar', []),
            [],
            null,
        ];

        yield [
            new DeleteAction('tl_foobar', []),
            ['delete'],
            false,
        ];

        yield [
            new DeleteAction('tl_foobar', []),
            ['delete'],
            true,
        ];
    }

    private function stubContaoFramework(): ContaoFramework
    {
        return $this->createContaoFrameworkStub([
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
        ]);
    }

    private function mockAccessDecisionManager(bool|null $return = null): AccessDecisionManagerInterface&MockObject
    {
        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects(null === $return ? $this->never() : $this->once())
            ->method('decide')
            ->willReturn((bool) $return)
        ;

        return $accessDecisionManager;
    }
}
