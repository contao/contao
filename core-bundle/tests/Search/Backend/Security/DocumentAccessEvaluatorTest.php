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
use PHPUnit\Framework\TestCase;

class DocumentAccessEvaluatorTest extends TestCase
{
    public function testEvaluatesAndCachesTheVirtualUserInAContaoContext(): void
    {
        $user = $this->createStub(BackendUser::class);
        $document = new Document('5', 'type', 'content');

        $factory = $this->createMock(VirtualBackendUserFactory::class);
        $factory
            ->expects($this->once())
            ->method('createForGroupId')
            ->with(42)
            ->willReturn($user)
        ;

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->exactly(2))
            ->method('isDocumentGranted')
            ->with($this->identicalTo($user), $this->identicalTo($document))
            ->willReturn(true)
        ;

        $evaluator = new DocumentAccessEvaluator($factory);

        $this->assertTrue($evaluator->isGrantedForGroup($provider, $document, 42));
        $this->assertTrue($evaluator->isGrantedForGroup($provider, $document, 42));
    }
}
