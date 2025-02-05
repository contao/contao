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
use Contao\CoreBundle\Security\Voter\DataContainer\LayoutAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LayoutAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $matcher = $this->exactly(4);
        $decisionManager
            ->expects($matcher)
            ->method('decide')
            ->willReturnCallback(
                function (...$parameters) use ($matcher, $token) {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame($token, $parameters[0]);
                        $this->assertSame([ContaoCorePermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);
                        $this->assertSame('themes', $parameters[2]);

                        return true;
                    }
                    if (2 === $matcher->numberOfInvocations()) {
                        $this->assertSame($token, $parameters[0]);
                        $this->assertSame([ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS], $parameters[1]);

                        return true;
                    }
                    if (3 === $matcher->numberOfInvocations()) {
                        $this->assertSame($token, $parameters[0]);
                        $this->assertSame([ContaoCorePermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);
                        $this->assertSame('themes', $parameters[2]);

                        return true;
                    }
                    if (4 === $matcher->numberOfInvocations()) {
                        $this->assertSame($token, $parameters[0]);
                        $this->assertSame([ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS], $parameters[1]);

                        return false;
                    }
                },
            )
        ;

        $voter = new LayoutAccessVoter($decisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_layout'));
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

        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_layout', ['user' => 2]),
                [ContaoCorePermissions::DC_PREFIX.'tl_layout'],
            ),
        );

        // Permission denied
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_layout', ['user' => 3]),
                [ContaoCorePermissions::DC_PREFIX.'tl_layout'],
            ),
        );
    }
}
