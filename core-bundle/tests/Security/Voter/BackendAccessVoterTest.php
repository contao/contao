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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\Voter\BackendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackendAccessVoterTest extends TestCase
{
    private BackendAccessVoter $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new BackendAccessVoter($this->mockContaoFramework());
    }

    public function testAbstainsIfTheAttributeIsContaoUser(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', ['contao_foo']));
    }

    public function testAbstainsIfTheContaoUserAttributeHasNoProperty(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', ['contao_user']));
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

    public function testDeniesAccessIfUserCannotEditFieldsOfTable(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['alexf' => ['tl_bar::foo']]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, 'tl_foobar', [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE]));
    }

    public function testDeniesAccessToEditFieldsOfTableIfSubjectIsNotAString(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, ['tl_foobar'], [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE]));
    }

    public function testGrantsAccessToEditFieldsOfTableIfUserIsAdmin(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['alexf' => [], 'isAdmin' => true]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, 'tl_foobar', [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE]));
    }

    public function testGrantsAccessToEditFieldsOfTable(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, 'tl_foobar', [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE]));
    }

    public function testGrantsAccessToEditFieldsOfTableInAttribute(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE.'.tl_foobar']));
    }

    public function testGrantsAccessToPageIfUserIsAdmin(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['isAdmin' => true]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = $this->createMock(PageModel::class);
        $page
            ->expects($this->never())
            ->method('row')
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $page, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    public function testGrantsAccessToPageFromArray(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = [
            'includeChmod' => true,
            'chmod' => ['w1'],
            'cuser' => 0,
            'cgroup' => 0,
        ];

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $page, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    public function testGrantsAccessToPageFromModel(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = $this->createMock(PageModel::class);
        $page
            ->expects($this->once())
            ->method('row')
            ->willReturn([
                'includeChmod' => true,
                'chmod' => ['w1'],
                'cuser' => 0,
                'cgroup' => 0,
            ])
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $page, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    public function testGrantsAccessToPageFromId(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = $this->createMock(PageModel::class);
        $page
            ->expects($this->once())
            ->method('row')
            ->willReturn([
                'includeChmod' => true,
                'chmod' => ['w1'],
                'cuser' => 0,
                'cgroup' => 0,
            ])
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, 1, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    public function testGrantsAccessToPageFromIdInAttribute(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = $this->createMock(PageModel::class);
        $page
            ->expects($this->once())
            ->method('row')
            ->willReturn([
                'includeChmod' => true,
                'chmod' => ['w1'],
                'cuser' => 0,
                'cgroup' => 0,
            ])
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [ContaoCorePermissions::USER_CAN_EDIT_PAGE.'.1']));
    }

    public function testDeniesAccessToPageIfIdCannotBeFound(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(1)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, 1, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    /**
     * @dataProvider getPageAndArticlePermissions
     */
    public function testPageAndArticlePermissions(string $attribute, array $chmod, int $cuser, int $cgroup, int $expected): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $page = [
            'includeChmod' => true,
            'chmod' => $chmod,
            'cuser' => $cuser,
            'cgroup' => $cgroup,
        ];

        $this->assertSame($expected, $this->voter->vote($token, $page, [$attribute]));
    }

    public function getPageAndArticlePermissions(): \Generator
    {
        yield 'Denies access if tl_page.chmod is empty' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE,
            [],
            0,
            0,
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Grants access if page can be edited by anyone (w = world, 1 = edit page)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE,
            ['w1'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page can be edited by user (u = user, 1 = edit page)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE,
            ['u1'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page can be edited by user (g = group, 1 = edit page)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE,
            ['g1'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page hierarchy can be changed by anyone (w = world, 2 = page hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY,
            ['w2'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page hierarchy can be changed by user (u = user, 2 = page hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY,
            ['u2'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page hierarchy can be changed by user (g = group, 2 = page hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY,
            ['g2'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page can be deleted by anyone (w = world, 3 = delete page)' => [
            ContaoCorePermissions::USER_CAN_DELETE_PAGE,
            ['w3'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page can be deleted by user (u = user, 3 = delete page)' => [
            ContaoCorePermissions::USER_CAN_DELETE_PAGE,
            ['u3'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if page can be deleted by user (g = group, 3 = delete page)' => [
            ContaoCorePermissions::USER_CAN_DELETE_PAGE,
            ['g3'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if articles can be edited by anyone (w = world, 4 = edit articles)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLES,
            ['w4'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if articles can be edited by user (u = user, 4 = edit articles)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLES,
            ['u4'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if articles can be edited by user (g = group, 4 = edit articles)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLES,
            ['g4'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if article hierarchy can be changed by anyone (w = world, 5 = article hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY,
            ['w5'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if article hierarchy can be changed by user (u = user, 5 = article hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY,
            ['u5'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if article hierarchy can be changed by user (g = group, 5 = article hierarchy)' => [
            ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY,
            ['g5'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if articles can be deleted by anyone (w = world, 5 = delete articles)' => [
            ContaoCorePermissions::USER_CAN_DELETE_ARTICLES,
            ['w6'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if 5 can be deleted by user (u = user, 5 = delete articles)' => [
            ContaoCorePermissions::USER_CAN_DELETE_ARTICLES,
            ['u6'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Grants access if 5 can be deleted by user (g = group, 5 = delete 5)' => [
            ContaoCorePermissions::USER_CAN_DELETE_ARTICLES,
            ['g6'],
            1,
            1,
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
