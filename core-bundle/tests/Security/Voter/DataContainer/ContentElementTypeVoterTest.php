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
use Contao\CoreBundle\Security\Voter\DataContainer\ContentElementTypeVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentElementTypeVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $voter = new ContentElementTypeVoter($this->createMock(AccessDecisionManagerInterface::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'navigation']),
                ['whatever'],
            ),
        );

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_content', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_content'],
            ),
        );
    }

    /**
     * @dataProvider checksElementAccessPermissionProvider
     */
    public function testChecksElementAccessPermission(CreateAction|DeleteAction|ReadAction|UpdateAction $action, array $types): void
    {
        $token = $this->createMock(TokenInterface::class);
        $matcher = $this->exactly(\count($types));

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($matcher)
            ->method('decide')
            ->with(
                $token,
                [ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE],
                $this->callback(static fn (string $type) => $types[$matcher->getInvocationCount() - 1] === $type),
            )
            ->willReturn(true)
        ;

        $voter = new ContentElementTypeVoter($accessDecisionManager);
        $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);
    }

    public static function checksElementAccessPermissionProvider(): iterable
    {
        yield [
            new ReadAction('tl_content', []),
            [],
        ];

        yield [
            new CreateAction('tl_content', ['type' => 'foo']),
            ['foo'],
        ];

        yield [
            new UpdateAction('tl_content', ['type' => 'foo']),
            ['foo'],
        ];

        yield [
            new UpdateAction('tl_content', ['type' => 'foo'], ['type' => 'bar']),
            ['foo', 'bar'],
        ];

        yield [
            new DeleteAction('tl_content', ['type' => 'bar']),
            ['bar'],
        ];
    }
}
