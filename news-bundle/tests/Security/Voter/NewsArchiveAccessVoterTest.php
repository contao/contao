<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\NewsBundle\Security\Voter\NewsArchiveAccessVoter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Security;

class NewsArchiveAccessVoterTest extends WebTestCase
{
    public function testVoter(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->with(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, 42)
            ->willReturnOnConsecutiveCalls(
                true,
                false
            )
        ;

        $voter = new NewsArchiveAccessVoter($security);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_news_archive'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_news'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(NewsArchiveAccessVoter::class));

        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                ['whatever']
            )
        );

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_news_archive']
            )
        );

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_news_archive']
            )
        );
    }
}
