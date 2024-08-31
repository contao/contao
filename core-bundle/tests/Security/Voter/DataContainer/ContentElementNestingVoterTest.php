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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentElementNestingVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $voter = new ContentElementNestingVoter(
            $this->createMock(Connection::class),
            $this->createMock(FragmentCompositor::class),
        );

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_foobar'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));

        $token = $this->createMock(TokenInterface::class);

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

    /**
     * @dataProvider nestedElementsProvider
     */
    public function testNestedElements(CreateAction|DeleteAction|ReadAction|UpdateAction $action, string|false $databaseResult, bool $supportsNesting, bool $isGranted): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchOne')
            ->with('SELECT type FROM tl_content WHERE id=?', [42])
            ->willReturn($databaseResult)
        ;

        $fragmentCompositor = $this->createMock(FragmentCompositor::class);
        $fragmentCompositor
            ->expects($databaseResult ? $this->once() : $this->never())
            ->method('supportsNesting')
            ->with(ContentElementReference::TAG_NAME.'.'.$databaseResult)
            ->willReturn($supportsNesting)
        ;

        $voter = new ContentElementNestingVoter($connection, $fragmentCompositor);
        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(
            $isGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED,
            $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']),
        );
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

        yield 'Denies update action if new parent does not supports nesting' => [
            new UpdateAction('tl_content', ['pid' => 21, 'ptable' => 'tl_content'], ['pid' => 42, 'ptable' => 'tl_content']),
            'foo',
            false,
            false,
        ];
    }
}
