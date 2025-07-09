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
use Contao\CoreBundle\Security\Voter\DataContainer\ContentAliasDeleteVoter;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentAliasDeleteVoterTest extends TestCase
{
    #[DataProvider('voteProvider')]
    public function testVote(array $aliases, CreateAction|DeleteAction|ReadAction|UpdateAction $action, bool $granted): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllKeyValue')
            ->with("SELECT id, cteAlias FROM tl_content WHERE type = 'alias'")
            ->willReturn($aliases)
        ;

        $voter = new ContentAliasDeleteVoter($connection, $this->createMock(TranslatorInterface::class));
        $result = $voter->vote($this->createMock(TokenInterface::class), $action, [ContaoCorePermissions::DC_PREFIX.$action->getDataSource()]);

        $this->assertSame($granted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED, $result);
    }

    public static function voteProvider(): \Generator
    {
        yield 'Abstains if dataSource is not tl_content' => [
            [],
            new DeleteAction('tl_content', ['id' => 42]),
            true,
        ];

        yield 'Abstains if subject is not DeleteAction' => [
            [42],
            new ReadAction('tl_content', ['id' => 42]),
            true,
        ];

        yield 'Abstains if there are no alias elements' => [
            [],
            new DeleteAction('tl_content', ['id' => 42]),
            true,
        ];

        yield 'Abstains if the elements is not an alias' => [
            [21],
            new DeleteAction('tl_content', ['id' => 42]),
            true,
        ];

        yield 'Denies if the elements is an alias' => [
            [42],
            new DeleteAction('tl_content', ['id' => 42]),
            false,
        ];
    }
}
