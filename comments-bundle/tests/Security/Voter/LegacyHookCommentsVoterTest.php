<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Security\Voter;

use Contao\CommentsBundle\Security\ContaoCommentsPermissions;
use Contao\CommentsBundle\Security\Voter\LegacyHookCommentsVoter;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Tests\Fixtures\Helper\HookHelper;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LegacyHookCommentsVoterTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new LegacyHookCommentsVoter($this->createContaoFrameworkStub());

        $this->assertTrue($voter->supportsAttribute(ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_comments'));
        $this->assertTrue($voter->supportsType('array'));
        $this->assertFalse($voter->supportsType(CreateAction::class));
    }

    public function testAbstainsIfNoHooksAreDefined(): void
    {
        unset($GLOBALS['TL_HOOKS']['isAllowedToEditComment']);

        $token = $this->createMock(TokenInterface::class);

        $voter = new LegacyHookCommentsVoter($this->createContaoFrameworkStub());

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, ['source' => 'tl_foo', 'parent' => 42], [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT]));
    }

    public function testGrantsOnFirstHookThatReturnsTrue(): void
    {
        HookHelper::registerHook('isAllowedToEditComment', static fn () => true);

        HookHelper::registerHook(
            'isAllowedToEditComment',
            function (): void {
                $this->fail('This hook should never be called.');
            },
        );

        $token = $this->createMock(TokenInterface::class);

        $voter = $this->getVoter();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, ['source' => 'tl_foo', 'parent' => 42], [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT]));
    }

    public function testDeniesIfNoHookReturnsTrue(): void
    {
        HookHelper::registerHook('isAllowedToEditComment', static fn () => false);

        HookHelper::registerHook('isAllowedToEditComment', static fn () => '');

        $token = $this->createMock(TokenInterface::class);

        $voter = $this->getVoter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, ['source' => 'tl_foo', 'parent' => 42], [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT]));
    }

    private function getVoter(): LegacyHookCommentsVoter
    {
        $systemAdapter = $this->createAdapterStub(['importStatic']);
        $systemAdapter
            ->method('importStatic')
            ->willReturnArgument(0)
        ;

        return new LegacyHookCommentsVoter($this->createContaoFrameworkStub([System::class => $systemAdapter]));
    }
}
