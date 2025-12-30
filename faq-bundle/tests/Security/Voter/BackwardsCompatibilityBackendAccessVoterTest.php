<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FaqBundle\Security\Voter\BackwardsCompatibilityBackendAccessVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackwardsCompatibilityBackendAccessVoterTest extends TestCase
{
    public function testSupports(): void
    {
        $voter = new BackwardsCompatibilityBackendAccessVoter();

        $this->assertTrue($voter->supportsAttribute('contao_user.faqp'));
        $this->assertTrue($voter->supportsAttribute('contao_user.faqp.create'));
        $this->assertTrue($voter->supportsAttribute('contao_user.faqp.delete'));
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
            'contao_user.faqp',
            null,
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_faq_category::create']],
            'contao_user.faqp',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.faqp',
            'create',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_faq_category::create']],
            'contao_user.faqp',
            'create',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_faq_category::create']],
            'contao_user.faqp.create',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => []],
            'contao_user.faqp',
            'delete',
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['cud' => ['tl_faq_category::delete']],
            'contao_user.faqp',
            'delete',
            VoterInterface::ACCESS_GRANTED,
        ];

        yield [
            ['cud' => ['tl_faq_category::delete']],
            'contao_user.faqp.delete',
            null,
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
