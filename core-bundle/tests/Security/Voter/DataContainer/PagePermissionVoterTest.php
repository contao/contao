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

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FormFieldAccessVoter;
use Contao\CoreBundle\Security\Voter\DataContainer\PagePermissionVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PagePermissionVoterTest extends TestCase
{
    public function testSupport(): void
    {
        $voter = new PagePermissionVoter(
            $this->mockContaoFramework(),
            $this->createMock(AccessDecisionManagerInterface::class),
        );

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_article'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(FormFieldAccessVoter::class));
    }

    public function testAllowsAllForAdmin(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(true)
        ;

        $voter = new PagePermissionVoter($this->mockContaoFramework(), $decisionManager);
        $result = $voter->vote($token, new CreateAction('tl_page'), [ContaoCorePermissions::DC_PREFIX.'tl_page']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * Tests the "new" global operation, which does not contain any data.
     *
     * @dataProvider newOperationProvider
     */
    public function testNewOperation(string $table, array $pagemounts, array $decisions, int $expected): void
    {
        $subject = new CreateAction($table);
        $token = $this->mockToken($pagemounts);

        $framework = $this->mockContaoFrameworkWithDatabase($pagemounts);
        $decisionManager = $this->mockAccessDecisionManager($token, $decisions);

        $voter = new PagePermissionVoter($framework, $decisionManager);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.$table]);

        $this->assertSame($expected, $result);
    }

    public function newOperationProvider(): \Generator
    {
        yield 'Allows new page (1)' => [
            'tl_page',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Allows new page (2)' => [
            'tl_page',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Allows new page (3)' => [
            'tl_page',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Denies new page (1)' => [
            'tl_page',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Denies new page (2)' => [
            'tl_page',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Denies new page (3)' => [
            'tl_page',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Allows new article (1)' => [
            'tl_article',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Allows new article (2)' => [
            'tl_article',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Allows new article (3)' => [
            'tl_article',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Denies new article (1)' => [
            'tl_article',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Denies new article (2)' => [
            'tl_article',
            [1, 2, 3],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Denies new article (3)' => [
            'tl_article',
            [1, 2, 3],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
            ],
            VoterInterface::ACCESS_DENIED,
        ];
    }

    /**
     * Tests the "copy" operation.
     *
     * @dataProvider copyOperationProvider
     */
    public function testCopyOperation(CreateAction $subject, array|null $pagemounts, array $decisions, int $expected): void
    {
        $token = $this->mockToken($pagemounts);

        $framework = $this->mockContaoFrameworkWithDatabase($pagemounts);
        $decisionManager = $this->mockAccessDecisionManager($token, $decisions);

        $voter = new PagePermissionVoter($framework, $decisionManager);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.$subject->getDataSource()]);

        $this->assertSame($expected, $result);
    }

    public function copyOperationProvider(): \Generator
    {
        yield 'Can copy page if editable and any pagemount is editable (1)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [3, 2, 1],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy page if editable and any pagemount is editable (2)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [3, 2, 1],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy page if hierarchy and any pagemount is editable (1)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy page if hierarchy and any pagemount is editable (2)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Cannot copy page if not editable' => [
            new CreateAction('tl_page', ['id' => 42]),
            null,
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot copy page if no pagemounts are allowed' => [
            new CreateAction('tl_page', ['id' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Can copy article if editable and any pagemount is editable (1)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [3, 2, 1],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy article if editable and any pagemount is editable (2)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [3, 2, 1],
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy article if hierarchy and any pagemount is editable (1)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Can copy article if hierarchy and any pagemount is editable (2)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Cannot copy article if not editable' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            null,
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot copy article if no pagemounts are allowed' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [3, 2, 1],
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
            ],
            VoterInterface::ACCESS_DENIED,
        ];
    }

    /**
     * Tests the "paste" operation.
     *
     * @dataProvider pasteOperationProvider
     */
    public function testPasteOperation(CreateAction $subject, array $decisions, int $expected): void
    {
        $token = $this->mockToken();

        $framework = $this->mockContaoFrameworkWithDatabase();
        $decisionManager = $this->mockAccessDecisionManager($token, $decisions);

        $voter = new PagePermissionVoter($framework, $decisionManager);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.$subject->getDataSource()]);

        $this->assertSame($expected, $result);
    }

    public function pasteOperationProvider(): \Generator
    {
        yield 'Can paste page if parent is allowed' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Cannot paste page if parent is not editable' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot paste page if parent hierarchy is not editable' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot paste page if parent is not allowed' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Can paste article if parent is allowed' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Cannot paste article if parent is not editable' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot paste article if parent hierarchy is not editable' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Cannot paste article if parent is not allowed' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            VoterInterface::ACCESS_DENIED,
        ];
    }

    private function mockToken(array|null $pagemounts = null): TokenInterface&MockObject
    {
        $token = $this->createMock(TokenInterface::class);

        if (null === $pagemounts) {
            $token
                ->expects($this->never())
                ->method('getUser')
            ;
        } else {
            $backendUser = $this->mockClassWithProperties(BackendUser::class, ['id' => 42, 'pagemounts' => $pagemounts]);

            $token
                ->expects($this->atLeastOnce())
                ->method('getUser')
                ->willReturn($backendUser)
            ;
        }

        return $token;
    }

    private function mockContaoFrameworkWithDatabase(array|null $pagemounts = null): ContaoFramework&MockObject
    {
        $database = $this->createMock(Database::class);

        if (null === $pagemounts) {
            $database
                ->expects($this->never())
                ->method('getChildRecords')
            ;
        } else {
            $database
                ->expects($this->once())
                ->method('getChildRecords')
                ->with($pagemounts, 'tl_page', false, $pagemounts)
                ->willReturn($pagemounts)
            ;
        }

        return $this->mockContaoFramework([], [Database::class => $database]);
    }

    private function mockAccessDecisionManager(TokenInterface $token, array $decisions): AccessDecisionManagerInterface&MockObject
    {
        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        array_unshift($decisions, [false, 'ROLE_ADMIN']);

        $with = array_map(static fn ($decision) => isset($decision[2]) ? [$token, [$decision[1]], $decision[2]] : [$token, [$decision[1]]], $decisions);
        $return = array_column($decisions, 0);

        $decisionManager
            ->expects($this->exactly(\count($decisions)))
            ->method('decide')
            ->withConsecutive(...$with)
            ->willReturnOnConsecutiveCalls(...$return)
        ;

        return $decisionManager;
    }
}
