<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\NewsletterBundle\Security\Voter\LegacyBackendAccessVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LegacyBackendAccessVoterTest extends TestCase
{
    public function testSupports(): void
    {
        $voter = new LegacyBackendAccessVoter();

        $this->assertTrue($voter->supportsAttribute('contao_user.newsletterp'));
        $this->assertTrue($voter->supportsAttribute('contao_user.newsletterp.create'));
        $this->assertTrue($voter->supportsAttribute('contao_user.newsletterp.delete'));
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

        $voter = new LegacyBackendAccessVoter();

        $this->assertSame($expected, $voter->vote($token, $subject, [$attribute]));
    }

    public static function userDataProvider(): iterable
    {
        yield [
            ['cud' => []],
            'contao_user.newsletterp',
            null,
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_newsletter_channel::create']],
            'contao_user.newsletterp',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.newsletterp',
            'create',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_newsletter_channel::create']],
            'contao_user.newsletterp',
            'create',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_newsletter_channel::create']],
            'contao_user.newsletterp.create',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.newsletterp',
            'delete',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_newsletter_channel::delete']],
            'contao_user.newsletterp',
            'delete',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_newsletter_channel::delete']],
            'contao_user.newsletterp.delete',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
