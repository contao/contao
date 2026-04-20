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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\ThemeContentVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ThemeContentVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new ThemeContentVoter($this->createStub(AccessDecisionManagerInterface::class), $this->createStub(Connection::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsAttribute('foobar'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
    }

    #[DataProvider('checksElementAccessPermissionProvider')]
    public function testChecksElementAccessPermission(CreateAction|DeleteAction|ReadAction|UpdateAction $action, array $parentRecords): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionMap = [
            [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes', true],
            [$token, [ContaoCorePermissions::USER_CAN_ACCESS_THEME_ELEMENTS], null, true],
        ];

        $parentChecks = 1;

        if (
            $action instanceof UpdateAction
            && (isset($action->getNew()['ptable']) || isset($action->getNew()['pid']))
        ) {
            $parentChecks = 2;
        }

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly($parentChecks * 2))
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

        $voter = new ThemeContentVoter($accessDecisionManager, $connection);
        $decision = $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }

    public static function checksElementAccessPermissionProvider(): iterable
    {
        yield 'Check access when creating element in theme' => [
            new CreateAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1]),
            [],
        ];

        yield 'Check access when creating nested element' => [
            new CreateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_theme', 'pid' => 2]]],
        ];

        yield 'Check access when creating deep nested element' => [
            new CreateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_theme', 'pid' => 1]]],
        ];

        yield 'Check access when reading element in theme' => [
            new ReadAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1]),
            [],
        ];

        yield 'Check access when reading nested element' => [
            new ReadAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_theme', 'pid' => 2]]],
        ];

        yield 'Check access when reading deep nested element' => [
            new ReadAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_theme', 'pid' => 1]]],
        ];

        yield 'Check access when updating element in theme' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1]),
            [],
        ];

        yield 'Check access when updating element in theme with new page' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1], ['pid' => 2]),
            [],
        ];

        yield 'Check access when moving nested element to theme' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3], ['ptable' => 'tl_theme', 'pid' => 1]),
            [3 => [['ptable' => 'tl_theme', 'pid' => 2]]],
        ];

        yield 'Check access when moving nested element to other element' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3], ['ptable' => 'tl_content', 'pid' => 4]),
            [3 => [['ptable' => 'tl_theme', 'pid' => 2]], 4 => [['ptable' => 'tl_theme', 'pid' => 1]]],
        ];

        yield 'Check access when deleting element in theme' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1]),
            [],
        ];

        yield 'Check access when deleting nested element' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_theme', 'pid' => 2]]],
        ];

        yield 'Check access when deleting deep nested element' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_content', 'pid' => 3]),
            [3 => [['ptable' => 'tl_content', 'pid' => 2], ['ptable' => 'tl_theme', 'pid' => 1]]],
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

        $voter = new ThemeContentVoter($accessDecisionManager, $this->createStub(Connection::class));
        $decision = $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $decision);
    }
}
