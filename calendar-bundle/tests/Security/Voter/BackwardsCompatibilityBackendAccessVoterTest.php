<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Security\Voter;

use Contao\BackendUser;
use Contao\CalendarBundle\Security\Voter\BackwardsCompatibilityBackendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackwardsCompatibilityBackendAccessVoterTest extends TestCase
{
    public function testSupports(): void
    {
        $voter = new BackwardsCompatibilityBackendAccessVoter();

        $this->assertTrue($voter->supportsAttribute('contao_user.calendarp'));
        $this->assertTrue($voter->supportsAttribute('contao_user.calendarp.create'));
        $this->assertTrue($voter->supportsAttribute('contao_user.calendarp.delete'));
        $this->assertTrue($voter->supportsAttribute('contao_user.calendarfeedp'));
        $this->assertTrue($voter->supportsAttribute('contao_user.calendarfeedp.create'));
        $this->assertTrue($voter->supportsAttribute('contao_user.calendarfeedp.delete'));
        $this->assertFalse($voter->supportsAttribute('contao_user.foo'));
    }

    #[DataProvider('userDataProvider')]
    public function testHasAccess(array $userData, string $attribute, string|null $subject, int $expected): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, $userData);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $voter = new BackwardsCompatibilityBackendAccessVoter();

        $this->assertSame($expected, $voter->vote($token, $subject, [$attribute]));
    }

    public static function userDataProvider(): iterable
    {
        yield [
            ['cud' => []],
            'contao_user.calendarp',
            null,
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar::create']],
            'contao_user.calendarp',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.calendarp',
            'create',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar::create']],
            'contao_user.calendarp',
            'create',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_calendar::create']],
            'contao_user.calendarp.create',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.calendarp',
            'delete',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar::delete']],
            'contao_user.calendarp',
            'delete',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_calendar::delete']],
            'contao_user.calendarp.delete',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.calendarfeedp',
            null,
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar_feed::create']],
            'contao_user.calendarfeedp',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.calendarfeedp',
            'create',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar_feed::create']],
            'contao_user.calendarfeedp',
            'create',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_calendar_feed::create']],
            'contao_user.calendarfeedp.create',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.calendarfeedp',
            'delete',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_calendar_feed::delete']],
            'contao_user.calendarfeedp',
            'delete',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_calendar_feed::delete']],
            'contao_user.calendarfeedp.delete',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
