<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Security\Authentication\AccessDecisionManager;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager as SymfonyAccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AccessDecisionManagerTest extends TestCase
{
    public function testLeavesOriginalConfigurationUntouchedIfNoRequestAvailable(): void
    {
        $inner = $this->createMock(SymfonyAccessDecisionManager::class);
        $inner
            ->expects($this->once())
            ->method('decide')
        ;

        $requestStack = new RequestStack();

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            [],
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    public function testLeavesOriginalConfigurationUntouchedIfNotContaoScope(): void
    {
        $inner = $this->createMock(AccessDecisionManagerInterface::class);
        $inner
            ->expects($this->once())
            ->method('decide')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            [],
            $this->mockScopeMatcher(),
            $requestStack
        );

        $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
    }

    /**
     * @dataProvider correctDecisionProvider
     */
    public function testCorrectDecisionForContao(string $scope, array $voters, bool $expectedDecision): void
    {
        $inner = $this->createMock(AccessDecisionManagerInterface::class);
        $inner
            ->expects($this->never())
            ->method('decide')
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], ['_scope' => $scope]));

        $accessDecisionManager = new AccessDecisionManager(
            $inner,
            $voters,
            $this->mockScopeMatcher(),
            $requestStack
        );

        $decision = $accessDecisionManager->decide($this->createMock(TokenInterface::class), []);
        $this->assertSame($expectedDecision, $decision);
    }

    public function correctDecisionProvider(): \Generator
    {
        yield 'Backend scope: Access granted' => [
            'backend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_GRANTED),
                $this->createNonCalledVoter(),
            ],
            true,
        ];

        yield 'Backend scope: Access denied' => [
            'backend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_DENIED),
                $this->createNonCalledVoter(),
            ],
            false,
        ];

        yield 'Backend scope: Must be allowed if all abstain' => [
            'backend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_ABSTAIN),
            ],
            true,
        ];

        yield 'Frontend scope: Access granted' => [
            'frontend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_GRANTED),
                $this->createNonCalledVoter(),
            ],
            true,
        ];

        yield 'Frontend scope: Access denied' => [
            'frontend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_DENIED),
                $this->createNonCalledVoter(),
            ],
            false,
        ];

        yield 'Frontend scope: Must be denied if all abstain' => [
            'frontend',
            [
                $this->createCalledVoter(VoterInterface::ACCESS_ABSTAIN),
            ],
            false,
        ];
    }

    private function createNonCalledVoter()
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter
            ->expects($this->never())
            ->method('vote')
        ;

        return $voter;
    }

    private function createCalledVoter(int $returnValue)
    {
        $voter = $this->createMock(VoterInterface::class);
        $voter
            ->expects($this->once())
            ->method('vote')
            ->willReturn($returnValue)
        ;

        return $voter;
    }
}
