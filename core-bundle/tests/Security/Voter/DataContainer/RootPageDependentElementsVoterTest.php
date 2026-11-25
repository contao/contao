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
use Contao\CoreBundle\Security\Voter\DataContainer\RootPageDependentElementsVoter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class RootPageDependentElementsVoterTest extends TestCase
{
    #[DataProvider('actionProvider')]
    public function testVotes(CreateAction|DeleteAction|ReadAction|UpdateAction $action, bool $isGranted): void
    {
        $voter = new RootPageDependentElementsVoter();

        $this->assertSame(
            $isGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED,
            $voter->vote($this->createStub(TokenInterface::class), $action, [ContaoCorePermissions::DC_PREFIX.'tl_content']),
        );
    }

    public static function actionProvider(): iterable
    {
        yield 'Allows creating in a theme' => [
            new CreateAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1, 'type' => 'root_page_dependent_elements']),
            true,
        ];

        yield 'Denies creating in an article' => [
            new CreateAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1, 'type' => 'root_page_dependent_elements']),
            false,
        ];

        yield 'Denies creating nested' => [
            new CreateAction('tl_content', ['ptable' => 'tl_content', 'pid' => 1, 'type' => 'root_page_dependent_elements']),
            false,
        ];

        yield 'Denies moving to an article' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_theme', 'pid' => 1, 'type' => 'root_page_dependent_elements'], ['ptable' => 'tl_article', 'pid' => 2]),
            false,
        ];

        yield 'Denies changing the type in an article' => [
            new UpdateAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1, 'type' => 'text'], ['type' => 'root_page_dependent_elements']),
            false,
        ];

        yield 'Ignores other types' => [
            new CreateAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1, 'type' => 'text']),
            true,
        ];

        yield 'Always allows reading' => [
            new ReadAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1, 'type' => 'root_page_dependent_elements']),
            true,
        ];

        yield 'Always allows deleting' => [
            new DeleteAction('tl_content', ['ptable' => 'tl_article', 'pid' => 1, 'type' => 'root_page_dependent_elements']),
            true,
        ];
    }
}
