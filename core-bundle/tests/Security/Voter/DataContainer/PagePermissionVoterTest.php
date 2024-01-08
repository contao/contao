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
     * @dataProvider voterProvider
     */
    public function testVoter(CreateAction|DeleteAction|ReadAction|UpdateAction $subject, array $decisions, bool $accessGranted, array|null $pagemounts = null): void
    {
        $token = $this->mockToken($pagemounts);

        $framework = $this->mockContaoFrameworkWithDatabase($pagemounts);
        $decisionManager = $this->mockAccessDecisionManager($token, $decisions);

        $voter = new PagePermissionVoter($framework, $decisionManager);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.$subject->getDataSource()]);

        $this->assertSame($accessGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED, $result);
    }

    public function voterProvider(): \Generator
    {
        // NEW BUTTON

        yield 'Allows new page (1)' => [
            new CreateAction('tl_page'),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            true,
            [1, 2, 3],
        ];

        yield 'Allows new page (2)' => [
            new CreateAction('tl_page'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [1, 2, 3],
        ];

        yield 'Allows new page (3)' => [
            new CreateAction('tl_page'),
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
            true,
            [1, 2, 3],
        ];

        yield 'Denies new page (1)' => [
            new CreateAction('tl_page'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
            ],
            false,
            [1, 2, 3],
        ];

        yield 'Denies new page (2)' => [
            new CreateAction('tl_page'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            false,
            [1, 2, 3],
        ];

        yield 'Denies new page (3)' => [
            new CreateAction('tl_page'),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
            ],
            false,
            [1, 2, 3],
        ];

        yield 'Allows new article (1)' => [
            new CreateAction('tl_article'),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 1],
            ],
            true,
            [1, 2, 3],
        ];

        yield 'Allows new article (2)' => [
            new CreateAction('tl_article'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [1, 2, 3],
        ];

        yield 'Allows new article (3)' => [
            new CreateAction('tl_article'),
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
            true,
            [1, 2, 3],
        ];

        yield 'Denies new article (1)' => [
            new CreateAction('tl_article'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
            ],
            false,
            [1, 2, 3],
        ];

        yield 'Denies new article (2)' => [
            new CreateAction('tl_article'),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            false,
            [1, 2, 3],
        ];

        yield 'Denies new article (3)' => [
            new CreateAction('tl_article'),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 1],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 2],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 2],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
            ],
            false,
            [1, 2, 3],
        ];

        // ### COPY BUTTON

        yield 'Can copy page if editable and any pagemount is editable (1)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [3, 2, 1],
        ];

        yield 'Can copy page if editable and any pagemount is editable (2)' => [
            new CreateAction('tl_page', ['id' => 42]),
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
            true,
            [3, 2, 1],
        ];

        yield 'Can copy page if hierarchy and any pagemount is editable (1)' => [
            new CreateAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [3, 2, 1],
        ];

        yield 'Can copy page if hierarchy and any pagemount is editable (2)' => [
            new CreateAction('tl_page', ['id' => 42]),
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
            true,
            [3, 2, 1],
        ];

        yield 'Cannot copy page if not editable' => [
            new CreateAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot copy page if no pagemounts are allowed' => [
            new CreateAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 1],
            ],
            false,
            [3, 2, 1],
        ];

        yield 'Can copy article if editable and any pagemount is editable (1)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [3, 2, 1],
        ];

        yield 'Can copy article if editable and any pagemount is editable (2)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
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
            true,
            [3, 2, 1],
        ];

        yield 'Can copy article if hierarchy and any pagemount is editable (1)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 3],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 3],
            ],
            true,
            [3, 2, 1],
        ];

        yield 'Can copy article if hierarchy and any pagemount is editable (2)' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
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
            true,
            [3, 2, 1],
        ];

        yield 'Cannot copy article if not editable' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot copy article if no pagemounts are allowed' => [
            new CreateAction('tl_article', ['id' => 21, 'pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 3],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 2],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 1],
            ],
            false,
            [3, 2, 1],
        ];

        // PASTE NEW

        yield 'Can paste page if parent is allowed' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot paste page if parent is not editable' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot paste page if parent hierarchy is not editable' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot paste page if parent is not allowed' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Can paste article if parent is allowed' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot paste article if parent is not editable' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            false,
        ];

        yield 'Cannot paste article if parent hierarchy is not editable' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot paste article if parent is not allowed' => [
            new CreateAction('tl_article', ['pid' => 42, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        // READ / INFO BUTTON

        yield 'Can read page' => [
            new ReadAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot read page' => [
            new ReadAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Can read article' => [
            new ReadAction('tl_article', ['pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot read article' => [
            new ReadAction('tl_article', ['pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        // EDIT BUTTON

        yield 'Edit page operation is enabled' => [
            new UpdateAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            true,
        ];

        yield 'Edit page operation is disabled if not in pagemounts' => [
            new UpdateAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Edit page operation is disabled if permission is not given' => [
            new UpdateAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            false,
        ];

        yield 'Edit article operation is enabled' => [
            new UpdateAction('tl_article', ['pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            true,
        ];

        yield 'Edit article operation is disabled if parent page is not in pagemounts' => [
            new UpdateAction('tl_article', ['pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Edit article operation is disabled if permission is not given' => [
            new UpdateAction('tl_article', ['pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            false,
        ];

        // MOVE & PASTE EXISTING RECORD

        yield 'Can move page' => [
            new UpdateAction('tl_page', ['id' => 42], ['sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            true,
        ];

        yield 'Cannot move page if not in pagemounts' => [
            new UpdateAction('tl_page', ['id' => 42], ['sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot move page if hierarchy cannot be changed' => [
            new UpdateAction('tl_page', ['id' => 42], ['sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Can move article' => [
            new UpdateAction('tl_article', ['pid' => 42], ['sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            true,
        ];

        yield 'Cannot move article if parent page is not in pagemounts' => [
            new UpdateAction('tl_article', ['pid' => 42], ['sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot move article if hierarchy cannot be changed' => [
            new UpdateAction('tl_article', ['pid' => 42], ['sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Can move page to new parent' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 21],
            ],
            true,
        ];

        yield 'Cannot move page to new parent if current is not in pagemounts' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot move page to new parent if current hierarchy cannot be changed' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot move page to new parent if new is not in pagemounts' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
            ],
            false,
        ];

        yield 'Cannot move page to new parent if new hierarchy cannot be changed' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 21],
            ],
            false,
        ];

        yield 'Can move article to new parent' => [
            new UpdateAction('tl_article', ['pid' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 21],
            ],
            true,
        ];

        yield 'Cannot move article to new parent if current parent page is not in pagemounts' => [
            new UpdateAction('tl_article', ['pid' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot move article to new parent if current hierarchy cannot be changed' => [
            new UpdateAction('tl_article', ['pid' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
            ],
            false,
        ];

        yield 'Cannot move article to new parent if new parent page is not in pagemounts' => [
            new UpdateAction('tl_article', ['pid' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
            ],
            false,
        ];

        yield 'Cannot move article to new parent if new hierarchy cannot be changed' => [
            new UpdateAction('tl_article', ['pid' => 42], ['pid' => 21, 'sorting' => 128]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, 21],
            ],
            false,
        ];

        // EDIT ACTION

        yield 'Can edit page' => [
            new UpdateAction('tl_page', ['id' => 42], ['foo' => 'bar']),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot edit page if not in pagemounts' => [
            new UpdateAction('tl_page', ['id' => 42], ['foo' => 'bar']),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot edit page if permission is not given' => [
            new UpdateAction('tl_page', ['id' => 42], ['foo' => 'bar']),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            false,
        ];

        yield 'Can edit article' => [
            new UpdateAction('tl_article', ['pid' => 42], ['foo' => 'bar']),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            true,
        ];

        yield 'Cannot edit article if parent page is not in pagemounts' => [
            new UpdateAction('tl_article', ['pid' => 42], ['foo' => 'bar']),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot edit article if permission is not given' => [
            new UpdateAction('tl_article', ['pid' => 42], ['foo' => 'bar']),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_EDIT_ARTICLES, 42],
            ],
            false,
        ];

        // DELETE BUTTON / ACTION

        yield 'Can delete page' => [
            new DeleteAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_DELETE_PAGE, 42],
            ],
            true,
        ];

        yield 'Cannot delete page if not in pagemounts' => [
            new DeleteAction('tl_page', ['id' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot delete page if permission is not given' => [
            new DeleteAction('tl_page', ['id' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_DELETE_PAGE, 42],
            ],
            false,
        ];

        yield 'Can delete article' => [
            new DeleteAction('tl_article', ['pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_DELETE_ARTICLES, 42],
            ],
            true,
        ];

        yield 'Cannot delete article if parent page is not in pagemounts' => [
            new DeleteAction('tl_article', ['pid' => 42]),
            [
                [false, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
            ],
            false,
        ];

        yield 'Cannot delete article if permission is not given' => [
            new DeleteAction('tl_article', ['pid' => 42]),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [false, ContaoCorePermissions::USER_CAN_DELETE_ARTICLES, 42],
            ],
            false,
        ];

        // POTENTIAL API CALL THAT EDITS AND MOVES TO NEW PARENT

        yield 'Can move and edit page' => [
            new UpdateAction('tl_page', ['id' => 42], ['pid' => 21, 'sorting' => 128, 'foo' => 'bar']),
            [
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 42],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 42],
                [true, ContaoCorePermissions::USER_CAN_ACCESS_PAGE, 21],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE_HIERARCHY, 21],
                [true, ContaoCorePermissions::USER_CAN_EDIT_PAGE, 42],
            ],
            true,
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
