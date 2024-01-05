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
use Contao\CoreBundle\Security\Voter\DataContainer\FormFieldAccessVoter;
use Contao\CoreBundle\Security\Voter\DataContainer\PageTypeAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PageTypeAccessVoterTest extends TestCase
{
    public function testSupport(): void
    {
        $voter = new PageTypeAccessVoter(
            $this->createMock(AccessDecisionManagerInterface::class),
            $this->createMock(Connection::class),
        );

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_article'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(FormFieldAccessVoter::class));
    }

    public function testAbstainsForReadAction(): void
    {
        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->expects($this->never())
            ->method($this->anything())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $token = $this->createMock(TokenInterface::class);
        $subject = new ReadAction('tl_page', []);

        $voter = new PageTypeAccessVoter($decisionManager, $connection);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_page']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * @dataProvider decidesAccessOnPageTypeInActionProvider
     */
    public function testDecidesAccessOnPageTypeInAction(CreateAction|DeleteAction|ReadAction|UpdateAction $subject, array $types, int $expected): void
    {
        $token = $this->createMock(TokenInterface::class);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->expects($this->exactly(\count($types)))
            ->method('decide')
            ->withConsecutive(...array_map(static fn ($type) => [$token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE], $type], array_keys($types)))
            ->willReturnOnConsecutiveCalls(...array_values($types))
        ;

        $connection = $this->createMock(Connection::class);

        $voter = new PageTypeAccessVoter($decisionManager, $connection);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_page']);

        $this->assertSame($expected, $result);
    }

    public function decidesAccessOnPageTypeInActionProvider(): \Generator
    {
        yield [
            new CreateAction('tl_page', ['type' => 'regular']),
            ['regular' => true],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'forward']),
            ['forward' => true],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'forward']),
            ['forward' => false],
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'forward'], ['type' => 'redirect']),
            ['forward' => true, 'redirect' => true],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'forward'], ['type' => 'redirect']),
            ['forward' => true, 'redirect' => false],
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            new DeleteAction('tl_page', ['pid' => 42, 'type' => 'forward']),
            ['forward' => true],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            new DeleteAction('tl_page', ['pid' => 42, 'type' => 'forward']),
            ['forward' => false],
            VoterInterface::ACCESS_DENIED,
        ];
    }

    /**
     * @dataProvider errorPagesAreOnlyAllowedInRootPageProvider
     */
    public function testErrorPagesAreOnlyAllowedInRootPage(CreateAction|UpdateAction $subject, array $queryResult, int $expected): void
    {
        $token = $this->createMock(TokenInterface::class);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->method('decide')
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(\count($queryResult)))
            ->method('fetchOne')
            ->withConsecutive(...array_map(fn ($p) => [$this->anything(), (array) $p], array_column($queryResult, 0)))
            ->willReturnOnConsecutiveCalls(...array_column($queryResult, 1))
        ;

        $voter = new PageTypeAccessVoter($decisionManager, $connection);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_page']);

        $this->assertSame($expected, $result);
    }

    public function errorPagesAreOnlyAllowedInRootPageProvider(): \Generator
    {
        $errorTypes = ['error_401', 'error_403', 'error_404', 'error_503'];

        yield 'Abstain if new type is not an error page' => [
            new CreateAction('tl_page', ['pid' => 42, 'type' => 'regular']),
            [],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Abstain if current type is not an error page' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'regular']),
            [],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Abstain if current and new type is not an error page' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'regular'], ['type' => 'forward']),
            [],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Allow to create error page if no same type exists in root page' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128, 'type' => 'error_404']),
            [
                [42, 'root'],
                [['error_404', 42], false],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Deny to create error page if same type exists in root page' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128, 'type' => 'error_404']),
            [
                [42, 'root'],
                [['error_404', 42], 21],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny to create error page if parent is not root page' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128, 'type' => 'error_404']),
            [[42, 'regular']],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny to create error page if parent is not found' => [
            new CreateAction('tl_page', ['pid' => 42, 'sorting' => 128, 'type' => 'error_404']),
            [[42, false]],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny to create error page if pid is not set' => [
            new CreateAction('tl_page', ['type' => 'error_404', 'foo' => 'bar']),
            [],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Allow to change page type if no such error type exists in root page' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'regular'], ['type' => 'error_404']),
            [
                [42, 'root'],
                [['error_404', 42], false],
            ],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Deny to change page type if error type exists in root page' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'regular'], ['type' => 'error_404']),
            [
                [42, 'root'],
                [['error_404', 42], 21],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny to change page type if current parent is not a root page' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'regular'], ['type' => 'error_404']),
            [[42, 'regular']],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny to change page type if new parent is not a root page' => [
            new UpdateAction('tl_page', ['pid' => 21, 'type' => 'regular'], ['pid' => 42, 'type' => 'error_404']),
            [[42, 'regular']],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Change page type ignores current type' => [
            new UpdateAction('tl_page', ['pid' => 21, 'type' => 'error_404'], ['type' => 'regular']),
            [],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Abstain if parent page is not changed' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'error_404', 'foo' => 'bar']),
            [],
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Deny if only parent ID is changed' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'error_404'], ['pid' => 21]),
            [
                [21, 'root'],
                [['error_404', 21], 1],
            ],
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'Deny if only sorting is changed' => [
            new UpdateAction('tl_page', ['pid' => 42, 'type' => 'error_404'], ['sorting' => 256]),
            [
                [42, 'root'],
                [['error_404', 42], 1],
            ],
            VoterInterface::ACCESS_DENIED,
        ];
    }

    public function testRootPageMustBeAtTopLevel(): void
    {
        // TODO: implement tests
    }

    public function testChangeToRootTypeIsOnlyAllowedAtTopLevel(): void
    {
        // TODO: implement tests
    }
}
