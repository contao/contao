<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Security\DocumentAccessEvaluator;
use Contao\CoreBundle\Search\Backend\Security\VirtualBackendUserFactory;
use Contao\CoreBundle\Security\Authentication\ContaoStrategyContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DocumentAccessEvaluatorTest extends TestCase
{
    public function testEvaluatesAndCachesTheVirtualUserInAContaoContext(): void
    {
        $user = $this->createStub(BackendUser::class);
        $user
            ->method('getUserIdentifier')
            ->willReturn('group-42')
        ;

        $factory = $this->createMock(VirtualBackendUserFactory::class);
        $factory
            ->expects($this->once())
            ->method('createForGroupId')
            ->with(42)
            ->willReturn($user)
        ;

        $strategyContext = $this->createMock(ContaoStrategyContext::class);
        $strategyContext
            ->expects($this->exactly(2))
            ->method('runInContext')
            ->with(ContaoStrategyContext::CONTEXT_BACKEND, $this->isCallable())
            ->willReturnCallback(static fn (string $context, callable $callback): mixed => $callback())
        ;

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->exactly(2))
            ->method('isDocumentGranted')
            ->with(
                $this->callback(
                    static fn (TokenInterface $token): bool => 'group-42' === $token->getUser()->getUserIdentifier(),
                ),
                $this->isInstanceOf(Document::class),
            )
            ->willReturn(true)
        ;

        $evaluator = new DocumentAccessEvaluator($factory, $strategyContext);
        $document = new Document('5', 'type', 'content');

        $this->assertTrue($evaluator->isGrantedForGroup($provider, $document, 42));
        $this->assertTrue($evaluator->isGrantedForGroup($provider, $document, 42));
    }
}
