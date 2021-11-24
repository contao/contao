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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\Logger;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerTest extends TestCase
{
    /**
     * @dataProvider contaoContextActionsProvider
     */
    public function testSetsContaoContextForAsContextMethods(string $method, string $action): void
    {
        $message = 'Log message';
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext(__METHOD__, $action);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))
            ->{$method}()
            ->log(LogLevel::INFO, $message)
        ;
    }

    public function contaoContextActionsProvider(): \Generator
    {
        yield 'access' => ['asContaoAccess', ContaoContext::ACCESS];
        yield 'configuration' => ['asContaoConfiguration', ContaoContext::CONFIGURATION];
        yield 'cron' => ['asContaoCron', ContaoContext::CRON];
        yield 'email' => ['asContaoEmail', ContaoContext::EMAIL];
        yield 'error' => ['asContaoError', ContaoContext::ERROR];
        yield 'files' => ['asContaoFiles', ContaoContext::FILES];
        yield 'forms' => ['asContaoForms', ContaoContext::FORMS];
        yield 'general' => ['asContaoGeneral', ContaoContext::GENERAL];
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextOnLog(string $message, string $func = null, string $username = null): void
    {
        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::GENERAL, $username);
        $logger = $this->createMock(LoggerInterface::class);

        $subject = (new Logger($logger))->withContaoAction(ContaoContext::GENERAL);

        if ($func) {
            $subject = $subject->withContaoFunc($func);
        }

        if ($username) {
            $subject = $subject->withContaoUsername($username);
        }

        $context = $subject->getContextByName('contao');

        $this->assertInstanceOf(ContaoContext::class, $context);
        $this->assertSame($expectedContext->getAction(), $context->getAction());
        $this->assertSame($expectedContext->getFunc(), $context->getFunc());
        $this->assertSame($expectedContext->getUsername(), $context->getUsername());
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextWithActionName(string $message, string $func = null, string $username = null): void
    {
        $action = 'Foo';
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, $action, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logActionName($action, $message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForAccess(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::ACCESS, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logAccess($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForConfiguration(string $message, string $func = null, $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::CONFIGURATION, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logConfiguration($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForLogCron(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::CRON, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logCron($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForEmail(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::EMAIL, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logEmail($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForError(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::ERROR, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::ERROR, $message, $expectedContext);

        (new Logger($logger))->logError($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForFiles(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::FILES, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logFiles($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForForms(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::FORMS, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logForms($message, $func, $username);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForGeneral(string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, ContaoContext::GENERAL, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logGeneral($message, $func, $username);
    }

    /**
     * @dataProvider logMessageWithActionProvider
     */
    public function testSetsContaoContextForLogWithCustomAction(string $action, string $message, string $func = null, string $username = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($func ?? __METHOD__, $action, $username);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new Logger($logger))->logActionName($action, $message, $func, $username);
    }

    public function logMessageProvider(): \Generator
    {
        yield 'simple message' => ['Log message'];
        yield 'provide custom function' => ['Log message', 'MyClass::myMethod'];
        yield 'provide custom username' => ['Log message', null, 'admin'];
    }

    public function logMessageWithActionProvider(): \Generator
    {
        yield 'action from ContaoContext' => [ContaoContext::ACCESS, 'Log message', null];
        yield 'custom action' => ['FooAction', 'Log message', null];
        yield 'custom action and custom method' => ['FooAction', 'Log message', 'MyClass::myMethod'];
    }

    private function assertLogActionWithContaoContext(MockObject $logger, string $level, string $message, ContaoContext $expectedContext, int $calls = 1): void
    {
        $logger
            ->expects($this->exactly($calls))
            ->method('log')
            ->with(
                $level,
                $message,
                $this->callback(
                    function (array $context) use ($expectedContext) {
                        /** @var ContaoContext $contaoContext */
                        $contaoContext = $context['contao'] ?? null;

                        $this->assertInstanceOf(ContaoContext::class, $contaoContext);
                        $this->assertSame($expectedContext->getAction(), $contaoContext->getAction());
                        $this->assertSame($expectedContext->getFunc(), $contaoContext->getFunc());
                        $this->assertSame($expectedContext->getUsername(), $contaoContext->getUsername());

                        return true;
                    }
                )
            )
            ->willReturn(null)
        ;
    }
}
