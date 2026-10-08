<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Twig\Studio\Operation\AbstractOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AbstractOperationTest extends TestCase
{
    public function testReturnsTheRenderContextForApiRequests(): void
    {
        $request = new Request(attributes: ['_contao_api' => true]);

        $container = new Container();
        $container->set('request_stack', new RequestStack([$request]));

        $operation = new class() extends AbstractOperation {
            public function canExecute(OperationContext $context): bool
            {
                return true;
            }

            public function execute(Request $request, OperationContext $context): Response|null
            {
                return null;
            }

            public function renderForTest(): Response
            {
                return $this->render('unused.html.twig', ['identifier' => 'content_element/text']);
            }
        };

        $operation->setContainer($container);

        $response = $operation->renderForTest();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(['identifier' => 'content_element/text'], json_decode($response->getContent(), true));
    }
}
