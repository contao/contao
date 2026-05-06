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
use Contao\Database;
use Contao\PageModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackendAccessVoterTest extends TestCase
{
    private BackendAccessVoter $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new BackendAccessVoter($this->createContaoFrameworkStub());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_MOD']);

        parent::tearDown();
    }

    public function testAbstainsIfTheAttributeIsContaoUser(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', ['contao_foo']));
    }

    public function testAbstainsIfTheContaoUserAttributeHasNoProperty(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', ['contao_user']));
    }

    public function testAbstainsIfTheAttributeIsNotAString(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $attributes = [new Expression('!is_granted("ROLE_MEMBER")')];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', $attributes));
    }

    public function testAbstainsIfThereIsNoContaoUserAttribute(): void
    {
        $token = $this->createStub(TokenInterface::class);
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

    public function testGrantsAccessIfTheSubjectIsNullAndTheFieldIsNotEmpty(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createClassWithPropertiesStub(BackendUser::class, ['fields' => ['text', 'select']]))
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, null, ['contao_user.fields']));
    }

    public function testDeniesAccessIfTheSubjectIsNullAndTheFieldIsEmpty(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createClassWithPropertiesStub(BackendUser::class, ['fields' => []]))
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, null, ['contao_user.fields']));
    }

    public function testDeniesAccessIfTheSubjectIsNotAScalarOrArray(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createStub(BackendUser::class))
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new \stdClass(), ['contao_user.alexf']));
    }

    #[DataProvider('userDataProvider')]
    public function testGrantsAccessIfTheUserDataIntersects(array $userData, string $attribute, int|string|null $subject): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, $userData);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $subject, [$attribute]));
    }

    #[DataProvider('userDataProvider')]
    public function testDeniesAccessIfTheUserDataDoesNotIntersect(array $userData, string $attribute, int|string|null $subject): void
    {
        $userData = array_fill_keys(array_keys($userData), []);
        $userData['id'] = 42;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createClassWithPropertiesStub(BackendUser::class, $userData))
        ;

        $database = $this->createStub(Database::class);
        $database
            ->method('getChildRecords')
            ->willReturn([])
        ;

        $voter = new BackendAccessVoter($this->createContaoFrameworkStub([], [Database::class => $database]));

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [$attribute]));
    }

    public static function userDataProvider(): iterable
    {
        yield 'Check access on table fields' => [
            ['id' => 42, 'alexf' => ['tl_user.field']],
            ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE,
            'tl_user.field',
        ];

        yield 'Check access on content elements' => [
            ['id' => 42, 'elements' => ['text']],
            ContaoCorePermissions::USER_CAN_ACCESS_ELEMENT_TYPE,
            'text',
        ];

        yield 'Check access on front end module' => [
            ['id' => 42, 'frontendModules' => ['navigation']],
            ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE,
            'navigation',
        ];

        yield 'Compares numeric strings and integers' => [
            ['id' => 42, 'forms' => [15]],
            ContaoCorePermissions::USER_CAN_EDIT_FORM,
            '15',
        ];

        yield 'Uses subject from permission' => [
            ['id' => 42, 'themes' => ['theme_export', 'theme_import']],
            ContaoCorePermissions::USER_CAN_EXPORT_THEMES,
            null,
        ];

        yield 'Check permission on mounted folder' => [
            ['id' => 42, 'filemounts' => ['files/foobar']],
            ContaoCorePermissions::USER_CAN_ACCESS_PATH,
            'files/foobar',
        ];

        yield 'Check permission on mounted pages' => [
            ['id' => 42, 'pagemounts' => [17]],
            ContaoCorePermissions::USER_CAN_ACCESS_PAGE,
            17,
        ];
    }

    public function testGrantsAccessToSubfolders(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['filemounts' => ['/foo/bar']]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, '/foo/bar/baz', [ContaoCorePermissions::USER_CAN_ACCESS_PATH]),
        );
    }

    public function testGrantsAccessToChildPages(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'pagemounts' => [1, 2, 3]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $database = $this->createMock(Database::class);
        $database
            ->expects($this->once())
            ->method('getChildRecords')
            ->willReturn([4, 5, 6])
        ;

        $voter = new BackendAccessVoter($this->createContaoFrameworkStub([], [Database::class => $database]));

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, 5, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE]));
    }

    public function testDeniesAccessIfUserCannotEditFieldsOfTable(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['alexf' => ['tl_bar::foo']]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['alexf' => [], 'isAdmin' => true]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['alexf' => ['tl_foobar::foo']]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['isAdmin' => true]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

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

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, 1, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    public function testGrantsAccessToPageFromIdInAttribute(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

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

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($page)
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [ContaoCorePermissions::USER_CAN_EDIT_PAGE.'.1']));
    }

    public function testDeniesAccessToPageIfIdCannotBeFound(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]);
        $voter = new BackendAccessVoter($framework);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, 1, [ContaoCorePermissions::USER_CAN_EDIT_PAGE]));
    }

    #[DataProvider('getPageAndArticlePermissions')]
    public function testPageAndArticlePermissions(string $attribute, array $chmod, int $cuser, int $cgroup, int $expected): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'groups' => [1]]);

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

    public static function getPageAndArticlePermissions(): iterable
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

    #[DataProvider('getBackendModulePermissions')]
    public function testBackendModulePermissions(array $allowedModules, string $requestedModule, array $config, int $expected): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1, 'modules' => $allowedModules]);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $GLOBALS['BE_MOD'] = $config;

        $this->assertSame($expected, $this->voter->vote($token, $requestedModule, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE]));
    }

    public static function getBackendModulePermissions(): iterable
    {
        yield 'Denies access if module is not allowed for user' => [
            ['foo'],
            'bar',
            [],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Allows access if module is allowed for user' => [
            ['foo'],
            'foo',
            [],
            VoterInterface::ACCESS_GRANTED,
        ];

        yield 'Allows access if module does not need permission checks' => [
            ['foo'],
            'ipsum',
            [
                'lorem' => [
                    'ipsum' => [
                        'disablePermissionChecks' => true,
                    ],
                ],
            ],
            VoterInterface::ACCESS_GRANTED,
        ];
    }
}
