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
use Contao\CoreBundle\Security\Voter\BackendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackendAccessVoterTest extends TestCase
{
    /**
     * @var BackendAccessVoter
     */
    private $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new BackendAccessVoter();
    }

    public function testAbstainsIfTheAttributeIsNotAString(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $attributes = [new Expression('!is_granted("ROLE_MEMBER")')];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', $attributes));
    }

    public function testAbstainsIfThereIsNoContaoUserAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $attributes = ['foo', 'bar', 'contao.', 'contao_user_name'];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', $attributes));
    }

    public function testDeniesAccessIfTheTokenDoesNotHaveABackendUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, 'foo', ['contao_user.alexf']));
    }

    public function testDeniesAccessIfTheSubjectIsNotAScalarOrArray(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new \stdClass(), ['contao_user.alexf']));
    }

    public function testDeniesAccessIfTheUserObjectDeniesAccess(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('hasAccess')
            ->willReturn(false)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, 'foo', ['contao_user.alexf']));
    }

    public function testGrantsAccessIfTheUserObjectGrantsAccess(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('hasAccess')
            ->with('foo', 'alexf')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, 'foo', ['contao_user.alexf']));
    }

    public function testGrantsAccessIfUserCanEditFieldsOfTable(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('canEditFieldsOf')
            ->with('tl_foobar')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, 'tl_foobar', ['contao_user.can_edit_fields']));
    }

    public function testGrantsAccessIfUserCanEditPageAsArray(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(1, [])
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, [], ['contao_user.can_edit_page']));
    }

    public function testGrantsAccessIfUserCanEditPageModel(): void
    {
        $page = $this->createMock(PageModel::class);
        $page
            ->expects($this->once())
            ->method('row')
            ->willReturn([])
        ;

        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(1, [])
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $page, ['contao_user.can_edit_page']));
    }

    /**
     * @dataProvider isAllowedProvider
     */
    public function testGrantsAccessIfUserIsAllowed(string $field, int $permission): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with($permission, [])
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, [], ['contao_user.'.$field]));
    }

    public function isAllowedProvider(): \Generator
    {
        yield ['can_edit_page', 1];
        yield ['can_edit_page_hierarchy', 2];
        yield ['can_delete_page', 3];
        yield ['can_edit_articles', 4];
        yield ['can_edit_article_hierarchy', 5];
        yield ['can_delete_articles', 6];
    }
}
