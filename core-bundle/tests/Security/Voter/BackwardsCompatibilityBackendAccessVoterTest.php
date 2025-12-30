<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Voter\BackwardsCompatibilityBackendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackwardsCompatibilityBackendAccessVoterTest extends TestCase
{
    public function testSupports(): void
    {
        $voter = new BackwardsCompatibilityBackendAccessVoter();

        $this->assertTrue($voter->supportsAttribute('contao_user.formp'));
        $this->assertTrue($voter->supportsAttribute('contao_user.formp.create'));
        $this->assertTrue($voter->supportsAttribute('contao_user.formp.delete'));
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
            'contao_user.formp',
            null,
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_form::create']],
            'contao_user.formp',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.formp',
            'create',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_form::create']],
            'contao_user.formp',
            'create',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_form::create']],
            'contao_user.formp.create',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.formp',
            'delete',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_form::delete']],
            'contao_user.formp',
            'delete',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_form::delete']],
            'contao_user.formp.delete',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
