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
use Contao\CoreBundle\Security\Voter\DataContainer\ImageSizeAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ImageSizeAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->expects($this->exactly(4))
            ->method('decide')
            ->withConsecutive(
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes'],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES]],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes'],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES]],
            )
            ->willReturnOnConsecutiveCalls(true, true, true, false)
        ;

        $voter = new ImageSizeAccessVoter($decisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_image_size'));
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

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_image_size', ['user' => 2]),
                [ContaoCorePermissions::DC_PREFIX.'tl_image_size'],
            ),
        );

        // Permission denied
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_image_size', ['user' => 3]),
                [ContaoCorePermissions::DC_PREFIX.'tl_image_size'],
            ),
        );
    }
}
