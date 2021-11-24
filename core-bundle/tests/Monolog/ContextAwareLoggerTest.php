<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\Monolog\ContextAwareLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ContextAwareLoggerTest extends TestCase
{
    public function testSetsContextOnLoggingLevels(): void
    {
        $levels = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];

        $message = 'Log message';
        $context = ['foo' => 'bar', 'baz' => ['foo' => 'baz']];

        foreach ($levels as $loggerMethod) {
            $logger = $this->createMock(LoggerInterface::class);

            $logger
                ->expects($this->once())
                ->method($loggerMethod)
                ->with($message, $context)
                ->willReturn(null)
            ;

            $logger = (new ContextAwareLogger($logger))->withContext($context);
            $logger->{$loggerMethod}($message);
        }
    }

    public function testImplementsImmutableChaining(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                'Log message',
                []
            )
            ->willReturn(null)
        ;

        $subject = new ContextAwareLogger($logger);

        // These call must not modify the original logger
        $subject->withContext(['foo' => 'bar']);
        $subject->addContext('baz', 'bar');

        $subject->log(LogLevel::INFO, 'Log message');
    }

    public function testReturnsTheContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $subject = new ContextAwareLogger($logger);

        $this->assertSame([], $subject->getContext());
        $this->assertNull($subject->getContextByName('foo'));

        $context = ['foo' => 'bar'];
        $subject = $subject->withContext($context);

        $this->assertSame($context, $subject->getContext());
        $this->assertSame('bar', $subject->getContextByName('foo'));
        $this->assertNull($subject->getContextByName('baz'));
    }

    public function testAddsContexts(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $subject = new ContextAwareLogger($logger);

        $this->assertSame([], $subject->getContext());

        $context = [
            'foo' => 'bar',
            'baz' => ['foo' => 'baz'],
        ];

        $subject = $subject
            ->withContext($context)
            ->addContext('foo', 'foo')
            ->addContext('baz', ['bar' => 'baz'])
        ;

        $this->assertSame('foo', $subject->getContextByName('foo'));
        $this->assertSame(['bar' => 'baz'], $subject->getContextByName('baz'));
    }
}
