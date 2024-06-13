<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\ClearSessionDataListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearSessionDataListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SESSION);

        parent::tearDown();
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testClearsTheFormDataConsidersWaitingTimeCorrectly(int $seconds, string $maxExecutionTime, bool $shouldClear): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $submittedAt = time() - $seconds;
        $buffer = ini_set('max_execution_time', $maxExecutionTime);

        $_SESSION['FORM_DATA'] = ['foo' => 'bar', 'SUBMITTED_AT' => $submittedAt];

        $listener = new ClearSessionDataListener();
        $listener($event);

        if ($shouldClear) {
            $this->assertArrayNotHasKey('FORM_DATA', $_SESSION);
        } else {
            $this->assertArrayHasKey('FORM_DATA', $_SESSION);
        }

        // Reset the max_execution_time value
        ini_set('max_execution_time', $buffer);
    }

    public function formDataProvider(): \Generator
    {
        yield '30 times 2 is lower than 100, should clear' => [
            100,
            '30',
            true,
        ];

        yield '30 times 2 is higher than 50, should not clear' => [
            50,
            '30',
            false,
        ];

        yield '60 times 2 is lower than 150, should clear' => [
            150,
            '60',
            true,
        ];

        yield '60 times 2 is higher than 50, should not clear' => [
            50,
            '60',
            false,
        ];

        yield 'ini-setting is disabled (0) should behave the same as if set to 30 (positive test)' => [
            100,
            '0',
            true,
        ];

        yield 'ini-setting is disabled (0) should behave the same as if set to 30 (negative test)' => [
            50,
            '0',
            false,
        ];
    }

    public function testDoesNotClearTheFormDataUponSubrequests(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('isMethod')
        ;

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response()
        );

        $listener = new ClearSessionDataListener();
        $listener($event);
    }

    public function testDoesNotClearTheFormDataUponPostRequests(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->never())
            ->method('isStarted')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->setMethod('POST');

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $listener = new ClearSessionDataListener();
        $listener($event);
    }

    public function testDoesNotClearTheFormDataIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $_SESSION['FORM_DATA'] = ['foo' => 'bar'];

        $listener = new ClearSessionDataListener();
        $listener($event);

        $this->assertSame(['foo' => 'bar'], $_SESSION['FORM_DATA']);
    }

    public function testClearsTheLegacyAttributeBags(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $_SESSION['BE_DATA'] = new AttributeBag();
        $_SESSION['FE_DATA'] = new AttributeBag();
        $_SESSION['FE_DATA']->set('foo', 'bar');

        $listener = new ClearSessionDataListener();
        $listener($event);

        $this->assertArrayNotHasKey('BE_DATA', $_SESSION);
        $this->assertArrayHasKey('FE_DATA', $_SESSION);
        $this->assertSame('bar', $_SESSION['FE_DATA']->get('foo'));
    }
}
