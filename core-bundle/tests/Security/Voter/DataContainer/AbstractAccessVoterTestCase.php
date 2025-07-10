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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class AbstractAccessVoterTestCase extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->never())
            ->method('decide')
        ;

        $class = $this->getVoterClass();
        $voter = new $class($accessDecisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.$this->getTable()));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_foobar'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType($class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction($this->getTable(), ['id' => 42]),
                ['whatever'],
            ),
        );
    }

    #[DataProvider('votesProvider')]
    public function testVotes(array $current, array $decisions, bool $accessGranted): void
    {
        $token = $this->createMock(TokenInterface::class);

        foreach ($decisions as &$decision) {
            array_unshift($decision, $token);
        }

        unset($decision);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(\count($decisions)))
            ->method('decide')
            ->willReturnMap($decisions)
        ;

        $class = $this->getVoterClass();
        $voter = new $class($accessDecisionManager);

        $this->assertSame(
            $accessGranted ? VoterInterface::ACCESS_ABSTAIN : VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction($this->getTable(), $current),
                [ContaoCorePermissions::DC_PREFIX.$this->getTable()],
            ),
        );
    }

    abstract public static function votesProvider(): iterable;

    abstract protected function getVoterClass(): string;

    abstract protected function getTable(): string;
}
