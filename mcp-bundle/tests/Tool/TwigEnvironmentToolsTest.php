<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Tool;

use Contao\McpBundle\Tool\TwigEnvironmentTools;
use Contao\McpBundle\Twig\TwigEnvironmentInspector;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class TwigEnvironmentToolsTest extends TestCase
{
    public function testRequiresAnAdministratorBeforeIntrospection(): void
    {
        $twig = $this->createMock(Environment::class);

        foreach (['getFunctions', 'getFilters', 'getTests', 'getTokenParsers', 'getGlobals'] as $method) {
            $twig
                ->expects($this->never())
                ->method($method)
            ;
        }

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false)
        ;
        $tools = new TwigEnvironmentTools(new TwigEnvironmentInspector($twig), $security);

        foreach (['discover' => [], 'inspect' => ['global', 'app']] as $method => $arguments) {
            try {
                $tools->$method(...$arguments);
                $this->fail('Both tools must require administrator privileges.');
            } catch (ToolCallException $exception) {
                $this->assertSame('Twig environment tools require administrator privileges.', $exception->getMessage());
            }
        }
    }

    public function testReturnsInspectorResultsForAdministrators(): void
    {
        $inspector = new TwigEnvironmentInspector(new Environment(new ArrayLoader()));
        $tools = new TwigEnvironmentTools($inspector, $this->createAdminSecurity());

        $this->assertSame($inspector->discover('function', 'range'), $tools->discover('function', 'range'));
        $this->assertSame($inspector->inspect('function', 'range'), $tools->inspect('function', 'range'));
    }

    #[DataProvider('invalidCalls')]
    public function testConvertsExpectedErrorsToToolCallExceptions(string $method, array $arguments, string $message): void
    {
        $tools = new TwigEnvironmentTools(new TwigEnvironmentInspector(new Environment(new ArrayLoader())), $this->createAdminSecurity());

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage($message);
        $tools->$method(...$arguments);
    }

    public static function invalidCalls(): iterable
    {
        yield 'invalid discovery kind' => ['discover', ['template'], 'Invalid Twig entry kind'];
        yield 'invalid inspection kind' => ['inspect', ['template', 'example'], 'Invalid Twig entry kind'];
        yield 'unknown entry' => ['inspect', ['function', 'missing_function'], 'Use contao_twig_environment_discover first.'];
    }

    public function testPreservesUnexpectedApplicationErrors(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('getFunctions')
            ->willThrowException(new \LogicException('Broken extension.'))
        ;
        $tools = new TwigEnvironmentTools(new TwigEnvironmentInspector($twig), $this->createAdminSecurity());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Broken extension.');
        $tools->discover('function');
    }

    public function testAdvertisesToolNamesSchemasAndReadOnlyAnnotations(): void
    {
        foreach (['discover', 'inspect'] as $method) {
            $reflection = new \ReflectionMethod(TwigEnvironmentTools::class, $method);
            $tool = $reflection->getAttributes(McpTool::class)[0]->newInstance();
            $this->assertSame('contao_twig_environment_'.$method, $tool->name);
            $this->assertTrue($tool->annotations->readOnlyHint);
            $this->assertFalse($tool->annotations->openWorldHint);
            $schema = $reflection->getParameters()[0]->getAttributes(Schema::class)[0]->newInstance();
            $this->assertSame(['filter', 'function', 'test', 'tag', 'global'], 'discover' === $method ? $schema->definition['anyOf'][0]['enum'] : $schema->definition['enum']);
        }
    }

    private function createAdminSecurity(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }
}
