<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\NewsBundle\Security\Voter\NewsContentVoter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class NewsContentVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new NewsContentVoter($this->createStub(AccessDecisionManagerInterface::class), $this->createStub(Connection::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsAttribute('foobar'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
    }

    #[DataProvider('checksElementAccessPermissionProvider')]
    public function testChecksElementAccessPermission(CreateAction|DeleteAction|ReadAction|UpdateAction $action, array $parentRecords, array $newsArchives): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionMap = [[$token, [ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true]];

        foreach ($newsArchives as $archiveId) {
            $accessDecisionMap[] = [$token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], (int) $archiveId, true];
        }

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(max(\count($parentRecords), \count($newsArchives)) * 2))
            ->method('decide')
            ->willReturnMap($accessDecisionMap)
        ;

        $fetchAllAssociativeMap = [];
        $fetchAssociativeMap = [];

        foreach ($parentRecords as $id => &$records) {
            if (\count($records) > 1 && 'tl_content' !== end($records)['ptable']) {
                $parent = array_pop($records);

                $fetchAssociativeMap[] = [
                    'SELECT id, pid, ptable FROM tl_content WHERE id = ?',
                    [(int) end($records)['pid']],
                    [],
                    $parent,
                ];
            }

            $fetchAllAssociativeMap[] = [
                'SELECT id, @pid := pid AS pid, ptable FROM tl_content WHERE id = :id'.str_repeat(' UNION SELECT id, @pid := pid AS pid, ptable FROM tl_content WHERE id = @pid AND ptable = :ptable', 9),
                ['id' => $id, 'ptable' => 'tl_content'],
                [],
                $records,
            ];
        }

        $fetchOneMap = [];

        foreach ($newsArchives as $id => $pid) {
            $fetchOneMap[] = [
                'SELECT pid FROM tl_news WHERE id = ?',
                [$id],
                [],
                $pid,
            ];
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($parentRecords)))
            ->method('fetchAllAssociative')
            ->willReturnMap($fetchAllAssociativeMap)
        ;

        $connection
            ->expects($this->exactly(\count($fetchAssociativeMap)))
            ->method('fetchAssociative')
            ->willReturnMap($fetchAssociativeMap)
        ;

        $connection
            ->expects($this->exactly(\count($newsArchives)))
            ->method('fetchOne')
            ->willReturnMap($fetchOneMap)
        ;

        $voter = new NewsContentVoter($accessDecisionManager, $connection);
        $decision = $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public static function checksElementAccessPermissionProvider(): iterable
    {
        yield 'Check access to news archive when creating element in news' => [
            new CreateAction('tl_content', ['ptable' => 'tl_news', 'pid' => 1]),
            [],
            [1 => '1'],
        ];

        yield 'Check access to news archive when creating nested element' => [
            new CreateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_news', 'pid' => 2]]],
            [2 => 1],
        ];

        yield 'Check access to news archive when creating deep nested element' => [
            new CreateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_news', 'pid' => 1]]],
            [1 => 1],
        ];

        yield 'Check access to news archive when reading element in news' => [
            new ReadAction('tl_content', ['ptable' => 'tl_news', 'pid' => 1]),
            [],
            [1 => 1],
        ];

        yield 'Check access to news archive when reading nested element' => [
            new ReadAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_news', 'pid' => 2]]],
            [2 => 1],
        ];

        yield 'Check access to news archive when reading deep nested element' => [
            new ReadAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_news', 'pid' => 1]]],
            [1 => 1],
        ];

        yield 'Check access to current news archive when updating element in news' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_news', 'pid' => 1]),
            [],
            [1 => 1],
        ];

        yield 'Check access to current and new news archive when updating element in news' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_news', 'pid' => 1], ['pid' => 2]),
            [],
            [1 => 1, 2 => 2],
        ];

        yield 'Check access to news archive when moving nested element to news' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3], ['ptable' => 'tl_news', 'pid' => 1]),
            [3 => [['ptable' => 'tl_news', 'pid' => 2]]],
            [2 => 2, 1 => 1],
        ];

        yield 'Check access to news archive when moving nested element to other element' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3], ['ptable' => 'tl_content', 'pid' => 4]),
            [3 => [['ptable' => 'tl_news', 'pid' => 2]], 4 => [['ptable' => 'tl_news', 'pid' => 1]]],
            [2 => 2, 1 => 1],
        ];

        yield 'Check access when deleting element in news' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_news', 'pid' => 1]),
            [],
            [1 => 1],
        ];

        yield 'Check access when deleting nested element' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_news', 'pid' => 2]]],
            [2 => 1],
        ];

        yield 'Check access when deleting deep nested element' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_news', 'pid' => 1]]],
            [1 => 1],
        ];
    }

    public function testIgnoresOtherParentTables(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->never())
            ->method('decide')
        ;

        $action = new CreateAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1]);

        $voter = new NewsContentVoter($accessDecisionManager, $this->createStub(Connection::class));
        $decision = $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }
}
