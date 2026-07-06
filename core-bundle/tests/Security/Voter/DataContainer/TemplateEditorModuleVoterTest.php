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
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\TemplateEditorModuleVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TemplateEditorModuleVoterTest extends TestCase
{
    public function testAbstainsCreateIfTemplateEditorIsNotInModules(): void
    {
        $subject = new CreateAction('tl_user', [
            'id' => 42,
        ]);

        $voter = new TemplateEditorModuleVoter($this->createStub(AccessDecisionManagerInterface::class));
        $decision = $voter->vote($this->createStub(TokenInterface::class), $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAbstainsUpdateIfTemplateEditorIsNotInNewModules(): void
    {
        $subject = new UpdateAction(
            'tl_user',
            [
                'id' => 42,
                'foo' => 'bar',
            ],
            [
                'id' => 42,
                'foo' => 'baz',
            ],
        );

        $voter = new TemplateEditorModuleVoter($this->createStub(AccessDecisionManagerInterface::class));
        $decision = $voter->vote($this->createStub(TokenInterface::class), $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAbstainsUpdateIfTemplateEditorIsInCurrentModules(): void
    {
        $subject = new UpdateAction(
            'tl_user',
            [
                'id' => 42,
                'modules' => serialize(['foo', 'tpl_editor']),
            ],
            [
                'id' => 42,
                'modules' => serialize(['bar', 'tpl_editor']),
            ],
        );

        $voter = new TemplateEditorModuleVoter($this->createStub(AccessDecisionManagerInterface::class));
        $decision = $voter->vote($this->createStub(TokenInterface::class), $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testAbstainsIfUserIsAdmin(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(true)
        ;

        $subject = new UpdateAction(
            'tl_user',
            [
                'id' => 42,
                'modules' => serialize(['foo']),
            ],
            [
                'id' => 42,
                'modules' => serialize(['foo', 'tpl_editor']),
            ],
        );

        $voter = new TemplateEditorModuleVoter($accessDecisionManager);
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public function testDeniesIfTemplateEditorIs(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(false)
        ;

        $subject = new UpdateAction(
            'tl_user',
            [
                'id' => 42,
                'modules' => serialize(['foo']),
            ],
            [
                'id' => 42,
                'modules' => serialize(['foo', 'tpl_editor']),
            ],
        );

        $voter = new TemplateEditorModuleVoter($accessDecisionManager);
        $decision = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_user']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $decision);
    }

    public static function actionClassProvider(): iterable
    {
        yield [CreateAction::class];

        yield [UpdateAction::class];
    }
}
