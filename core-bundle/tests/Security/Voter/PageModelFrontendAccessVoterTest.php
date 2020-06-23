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
use Contao\CoreBundle\Security\Voter\AbstractFrontendAccessVoter;
use Contao\CoreBundle\Security\Voter\PageModelFrontendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PageModelFrontendAccessVoterTest extends TestCase
{
    /**
     * @var PageModelFrontendAccessVoter
     */
    private $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new PageModelFrontendAccessVoter();
    }

    public function testAbstainsIfTheAttributeIsNotAString(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject();
        $attributes = [new Expression('!is_granted("ROLE_MEMBER")')];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    public function testAbstainsIfTheAttributeDoesNotMatch(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject();
        $attributes = ['foo', 'bar', 'contao.', 'contao_user_name'];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    public function testAbstainsIfTheSubjectDoesNotMatch(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockClassWithProperties(LayoutModel::class);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $subject, $attributes));
    }

    public function testGrantsAccessIfPageIsNotProtected(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject(false);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testDeniesAccessIfPageIsProtectedWithoutUser(): void
    {
        $token = $this->mockToken();
        $subject = $this->mockSubject(true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testDeniesAccessIfTokenDoesNotHaveMemberRole(): void
    {
        $token = $this->mockToken($this->createMock(FrontendUser::class), ['ROLE_FOO']);
        $subject = $this->mockSubject(true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testDeniesAccessIfPageIsProtectedWithoutFrontendUser(): void
    {
        $token = $this->mockToken($this->createMock(BackendUser::class));
        $subject = $this->mockSubject(true);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testGrantsAccessOnGroupMatch(): void
    {
        $user = $this->mockFrontendUser([1]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject(true, [1]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testDeniesAccessOnGroupMismatch(): void
    {
        $user = $this->mockFrontendUser([2]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject(true, [1]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $subject, $attributes));
    }

    public function testGrantsAccessOnGroupIntersect(): void
    {
        $user = $this->mockFrontendUser([1, 2, 3, 4, 5, 6, 7, 8]);
        $token = $this->mockToken($user);
        $subject = $this->mockSubject(true, [5, 6, 7]);
        $attributes = [AbstractFrontendAccessVoter::ATTRIBUTE];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, $attributes));
    }

    private function mockSubject(bool $protected = false, array $groups = [])
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            [
                'protected' => $protected,
                'groups' => $groups,
            ]
        );
    }

    private function mockFrontendUser(array $groups = [])
    {
        return $this->mockClassWithProperties(FrontendUser::class, ['groups' => $groups]);
    }

    private function mockToken($user = null, array $roles = ['ROLE_MEMBER'])
    {
        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->addMethods(['getRoleNames'])
            ->getMockForAbstractClass()
        ;

        $token
            ->method('getRoleNames')
            ->willReturn($roles)
        ;

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        return $token;
    }
}
