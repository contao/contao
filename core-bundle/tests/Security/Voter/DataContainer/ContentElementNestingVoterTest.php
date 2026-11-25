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

use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\ContentElementNestingVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentElementNestingVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $voter = new ContentElementNestingVoter(
            $this->createStub(Connection::class),
            $this->createStub(FragmentCompositor::class),
            $this->createStub(DcaHierarchy::class),
        );

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_foobar'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        $token = $this->createStub(TokenInterface::class);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new UpdateAction('foo', ['id' => 42, 'type' => 'navigation']),
                ['whatever'],
            ),
        );

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_content', ['id' => 42, 'type' => 'navigation']),
                [ContaoCorePermissions::DC_PREFIX.'tl_content'],
            ),
        );
    }

    #[DataProvider('nestedElementsProvider')]
    public function testNestedElements(CreateAction|DeleteAction|ReadAction|UpdateAction $action, false|string $databaseResult, bool $supportsNesting, bool $isGranted): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturnMap([['SELECT type FROM tl_content WHERE id = ?', [42], $databaseResult]])
        ;

        $fragmentCompositor = $this->createMock(FragmentCompositor::class);
        $fragmentCompositor
            ->expects($databaseResult ? $this->once() : $this->never())
            ->method('supportsNesting')
            ->with(ContentElementReference::TAG_NAME.'.'.$databaseResult)
            ->willReturn($supportsNesting)
        ;

        $voter = new ContentElementNestingVoter($connection, $fragmentCompositor, $this->createStub(DcaHierarchy::class));
        $token = $this->createStub(TokenInterface::class);

        $this->assertSame(
            $isGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED,
            $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']),
        );
    }

    #[DataProvider('circularReferenceProvider')]
    public function testDeniesMovingAnElementIntoItselfOrItsChildren(UpdateAction $action, int $newPid, array $parentIds, bool $isGranted): void
    {
        $dcaHierarchy = $this->createMock(DcaHierarchy::class);
        $dcaHierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                $newPid,
                'tl_content',
                $this->callback(static fn (ParentTraversalOptions $options): bool => $options->includesBoundaryRow()),
            )
            ->willReturn(array_map(static fn (int $id): array => ['id' => $id], $parentIds))
        ;

        $fragmentCompositor = $this->createStub(FragmentCompositor::class);
        $fragmentCompositor
            ->method('supportsNesting')
            ->willReturn(true)
        ;

        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturn('element_group')
        ;

        $voter = new ContentElementNestingVoter($connection, $fragmentCompositor, $dcaHierarchy);

        $this->assertSame(
            $isGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED,
            $voter->vote($this->createStub(TokenInterface::class), $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']),
        );
    }

    public static function circularReferenceProvider(): iterable
    {
        $move = static fn (int $pid): UpdateAction => new UpdateAction('tl_content', ['id' => 21, 'pid' => 1, 'ptable' => 'tl_content'], ['pid' => $pid, 'ptable' => 'tl_content']);

        yield 'Denies moving an element into itself' => [$move(21), 21, [21], false];
        yield 'Denies moving an element into one of its nested elements' => [$move(42), 42, [42, 21], false];
        yield 'Allows moving an element into an unrelated element' => [$move(42), 42, [42, 7], true];
    }

    public static function nestedElementsProvider(): iterable
    {
        yield 'Allow access if element supports nesting' => [
            new ReadAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            'text',
            true,
            true,
        ];

        yield 'Denies access if element does not support nesting' => [
            new ReadAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            'text',
            false,
            false,
        ];

        yield 'Denies access if element cannot be found in database' => [
            new ReadAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            false,
            true,
            false,
        ];

        yield 'Always allows read action on element' => [
            new DeleteAction('tl_content', ['id' => 21, 'pid' => 42, 'type' => 'foo', 'ptable' => 'tl_content']),
            false,
            false,
            true,
        ];

        yield 'Always allows delete action on element' => [
            new DeleteAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            false,
            false,
            true,
        ];

        yield 'Always allows update action without new data' => [
            new UpdateAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            false,
            false,
            true,
        ];

        yield 'Allows creating an element if parent supports nesting' => [
            new CreateAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            'foo',
            true,
            true,
        ];

        yield 'Denies creating an element if parent does not supports nesting' => [
            new CreateAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            'foo',
            false,
            false,
        ];

        yield 'Denies creating an element if parent is not found in database' => [
            new CreateAction('tl_content', ['pid' => 42, 'ptable' => 'tl_content']),
            false,
            true,
            false,
        ];

        yield 'Allows update action if new parent supports nesting' => [
            new UpdateAction('tl_content', ['pid' => 21, 'ptable' => 'tl_content'], ['pid' => 42, 'ptable' => 'tl_content']),
            'foo',
            true,
            true,
        ];

        yield 'Allows copying an element into itself' => [
            new CreateAction('tl_content', ['id' => 42, 'pid' => 42, 'ptable' => 'tl_content']),
            'element_group',
            true,
            true,
        ];

        yield 'Denies update action if new parent does not supports nesting' => [
            new UpdateAction('tl_content', ['pid' => 21, 'ptable' => 'tl_content'], ['pid' => 42, 'ptable' => 'tl_content']),
            'foo',
            false,
            false,
        ];
    }
}
