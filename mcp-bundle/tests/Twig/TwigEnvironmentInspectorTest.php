<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Twig;

use Contao\McpBundle\Twig\TwigEnvironmentInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;
use Twig\DeprecatedCallableInfo;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Twig\TokenParser\DoTokenParser;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class TwigEnvironmentInspectorTest extends TestCase
{
    public function testDiscoversEveryCategoryFromTheActiveEnvironment(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new class() extends AbstractExtension implements GlobalsInterface {
            public function getFilters(): array
            {
                return [new TwigFilter('inspection_filter', 'strtoupper')];
            }

            public function getFunctions(): array
            {
                return [new TwigFunction('inspection_function', 'strlen')];
            }

            public function getTests(): array
            {
                return [new TwigTest('inspection_test', 'is_string')];
            }

            public function getTokenParsers(): array
            {
                return [new class() extends AbstractTokenParser {
                    public function parse(Token $token): Node
                    {
                        throw new \LogicException('Inspection must not parse tags.');
                    }

                    public function getTag(): string
                    {
                        return 'inspection_tag';
                    }
                }];
            }

            public function getGlobals(): array
            {
                return ['inspection_global' => null];
            }
        });

        $this->assertSame(['items' => [
            ['kind' => 'filter', 'name' => 'inspection_filter', 'deprecated' => false],
            ['kind' => 'function', 'name' => 'inspection_function', 'deprecated' => false],
            ['kind' => 'global', 'name' => 'inspection_global'],
            ['kind' => 'tag', 'name' => 'inspection_tag'],
            ['kind' => 'test', 'name' => 'inspection_test', 'deprecated' => false],
        ]], new TwigEnvironmentInspector($twig)->discover(query: 'INSPECTION'));
    }

    public function testFiltersAndSortsNames(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('inspection_z', null));
        $twig->addFunction(new TwigFunction('inspection_a', null));
        $twig->addFilter(new TwigFilter('inspection_filter', null));

        $inspector = new TwigEnvironmentInspector($twig);

        $this->assertSame(['items' => [
            ['kind' => 'function', 'name' => 'inspection_a', 'deprecated' => false],
            ['kind' => 'function', 'name' => 'inspection_z', 'deprecated' => false],
        ]], $inspector->discover('function', 'INSPECTION'));
        $this->assertSame(['items' => []], $inspector->discover(query: 'unregistered_inspection_entry'));
    }

    public function testReportsDeprecatedEntries(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('inspection_old', 'strlen', ['deprecation_info' => new DeprecatedCallableInfo('test/package', '1.0', 'inspection_new')]));

        $inspector = new TwigEnvironmentInspector($twig);

        $this->assertSame(['items' => [['kind' => 'function', 'name' => 'inspection_old', 'deprecated' => true]]], $inspector->discover('function', 'inspection_old'));
        $data = $inspector->inspect('function', 'inspection_old');
        $this->assertTrue($data['deprecated']);
    }

    public function testInspectsEffectiveOverrides(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addExtension(new class() extends AbstractExtension {
            public function getFunctions(): array
            {
                return [new TwigFunction('inspection_override', 'strlen')];
            }
        });
        $twig->addFunction(new TwigFunction('inspection_override', 'strtoupper'));

        $data = new TwigEnvironmentInspector($twig)->inspect('function', 'inspection_override');
        $this->assertSame('strtoupper', $data['implementation']);
    }

    public function testRemovesHiddenArgumentsAndSeparatesFilterInput(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFilter(new TwigFilter(
            'inspection_filter',
            static function (string $charset, Environment $environment, array $context, string|null $value, float|int $limit = 1, string ...$suffix): string {
                throw new \LogicException('Inspection must not execute the filter.');
            },
            ['needs_charset' => true, 'needs_environment' => true, 'needs_context' => true, 'is_variadic' => true],
        ));

        $data = new TwigEnvironmentInspector($twig)->inspect('filter', 'inspection_filter');
        $this->assertSame(
            [
                'status' => 'best_effort',
                'input' => ['name' => 'value', 'type' => '?string', 'optional' => false, 'variadic' => false],
                'parameters' => [
                    ['name' => 'limit', 'type' => 'int|float', 'optional' => true, 'variadic' => false],
                    ['name' => 'suffix', 'type' => 'string', 'optional' => true, 'variadic' => true],
                ],
                'return_type' => 'string',
            ],
            $data['signature'],
        );
        $this->assertSame(
            [
                'needs_environment' => true,
                'needs_context' => true,
                'needs_charset' => true,
                'needs_is_sandboxed' => false,
                'is_variadic' => true,
            ],
            $data['requirements'],
        );
        $this->assertSame('Closure', $data['implementation']);
    }

    #[RequiresMethod(TwigFunction::class, 'needsIsSandboxed')]
    public function testRemovesHiddenSandboxArgument(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction(
            'inspection_sandbox',
            static function (bool $isSandboxed, string $value): string {
                throw new \LogicException('Inspection must not execute the function.');
            },
            ['needs_is_sandboxed' => true],
        ));

        $data = new TwigEnvironmentInspector($twig)->inspect('function', 'inspection_sandbox');

        $this->assertTrue($data['requirements']['needs_is_sandboxed']);
        $this->assertSame(
            [
                'status' => 'best_effort',
                'input' => null,
                'parameters' => [['name' => 'value', 'type' => 'string', 'optional' => false, 'variadic' => false]],
                'return_type' => 'string',
            ],
            $data['signature'],
        );
    }

    /**
     * @param 'filter'|'function'|'test' $kind
     */
    #[DataProvider('arrayVariadicKinds')]
    public function testDoesNotReportArrayVariadicCollectorsAsOrdinaryParameters(string $kind): void
    {
        $twig = new Environment(new ArrayLoader());
        $callable = static function (mixed $value, array $arguments = []): never {
            throw new \LogicException('Inspection must not execute the callable.');
        };

        $options = ['is_variadic' => true];

        match ($kind) {
            'filter' => $twig->addFilter(new TwigFilter('inspection_variadic', $callable, $options)),
            'function' => $twig->addFunction(new TwigFunction('inspection_variadic', $callable, $options)),
            'test' => $twig->addTest(new TwigTest('inspection_variadic', $callable, $options)),
        };

        $data = new TwigEnvironmentInspector($twig)->inspect($kind, 'inspection_variadic');

        $this->assertSame(
            [
                'status' => 'unavailable',
                'reason' => 'Array-based variadic arguments have no directly reflectable template-facing signature.',
            ],
            $data['signature'],
        );
        $this->assertTrue($data['requirements']['is_variadic']);
    }

    public static function arrayVariadicKinds(): iterable
    {
        yield 'filter' => ['filter'];
        yield 'function' => ['function'];
        yield 'test' => ['test'];
    }

    public function testSeparatesTestInputAndReportsMissingTypes(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addTest(new TwigTest(
            'inspection_test',
            static function ($value, $expected = null) {
                throw new \LogicException('Inspection must not execute the test.');
            },
        ));

        $data = new TwigEnvironmentInspector($twig)->inspect('test', 'inspection_test');
        $this->assertSame(['name' => 'value', 'type' => null, 'optional' => false, 'variadic' => false], $data['signature']['input']);
        $this->assertSame([['name' => 'expected', 'type' => null, 'optional' => true, 'variadic' => false]], $data['signature']['parameters']);
        $this->assertNull($data['signature']['return_type']);
        $this->assertArrayNotHasKey('escaping', $data);
    }

    public function testReflectsRuntimeMethodsWithoutResolvingTheRuntime(): void
    {
        $runtime = new class() {
            public function build(\Countable&\Stringable $value): \Countable&\Stringable
            {
                throw new \LogicException('Inspection must not execute the runtime.');
            }
        };

        $twig = new Environment(new ArrayLoader());
        $twig->addRuntimeLoader(new class() implements RuntimeLoaderInterface {
            public function load(string $class): object|null
            {
                throw new \LogicException('Inspection must not resolve runtimes.');
            }
        });

        $method = 'build';

        foreach (['inspection_array' => [$runtime::class, $method], 'inspection_object' => [$runtime, $method], 'inspection_string' => $runtime::class.'::build'] as $name => $callable) {
            $twig->addFunction(new TwigFunction($name, $callable));
        }

        $inspector = new TwigEnvironmentInspector($twig);

        foreach (['inspection_array', 'inspection_object', 'inspection_string'] as $name) {
            $data = $inspector->inspect('function', $name);
            $this->assertSame('class@anonymous::build', $data['implementation']);
            $this->assertSame([['name' => 'value', 'type' => 'Countable&Stringable', 'optional' => false, 'variadic' => false]], $data['signature']['parameters']);
            $this->assertSame('Countable&Stringable', $data['signature']['return_type']);
            $this->assertNull($data['signature']['input']);
        }
    }

    public function testReflectsInvokableObjectsWithoutCallingThem(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('inspection_invoke', new class() {
            public function __invoke(string $value): string
            {
                throw new \LogicException('Inspection must not invoke the object.');
            }
        }));

        $data = new TwigEnvironmentInspector($twig)->inspect('function', 'inspection_invoke');
        $this->assertSame('class@anonymous::__invoke', $data['implementation']);
        $this->assertSame('best_effort', $data['signature']['status']);
        $this->assertSame([['name' => 'value', 'type' => 'string', 'optional' => false, 'variadic' => false]], $data['signature']['parameters']);
        $this->assertSame('string', $data['signature']['return_type']);
    }

    public function testDoesNotExecuteSafetyCallbacksOrClaimStaticSafety(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('inspection_static_safe', 'strlen', ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('inspection_dynamic_safe', 'strtoupper', [
            'is_safe_callback' => static function (): array {
                throw new \LogicException('Inspection must not execute safety callbacks.');
            },
            'pre_escape' => 'html',
            'preserves_safety' => ['html'],
        ]));

        $inspector = new TwigEnvironmentInspector($twig);

        $data = $inspector->inspect('function', 'inspection_static_safe');
        $this->assertArrayNotHasKey('escaping', $data);

        $data = $inspector->inspect('filter', 'inspection_dynamic_safe');
        $this->assertSame(['pre_escape' => 'html', 'preserves_safety' => ['html']], $data['escaping']);
    }

    public function testReportsUnavailableSignatures(): void
    {
        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('inspection_*', 'strlen'));
        $twig->addFunction(new TwigFunction('inspection_node', 'strlen', ['node_class' => ConstantExpression::class]));
        $twig->addFunction(new TwigFunction('inspection_missing', [Environment::class, 'missingImplementation']));
        $twig->addFunction(new TwigFunction('inspection_null', null));

        $inspector = new TwigEnvironmentInspector($twig);

        foreach (['inspection_*', 'inspection_node', 'inspection_missing', 'inspection_null'] as $name) {
            $data = $inspector->inspect('function', $name);
            $this->assertSame('unavailable', $data['signature']['status']);
            $this->assertNotNull($data['signature']['reason']);
            $this->assertSame(['status', 'reason'], array_keys($data['signature']));
        }
    }

    public function testReportsTagParserWithoutGuessingItsGrammar(): void
    {
        $data = new TwigEnvironmentInspector(new Environment(new ArrayLoader()))->inspect('tag', 'do');

        $this->assertSame(['kind' => 'tag', 'name' => 'do', 'parser_class' => DoTokenParser::class], $data);
    }

    public function testReflectsGlobalApisWithoutReadingValuesOrInvokingMembers(): void
    {
        $global = new class() {
            public string|null $uninitialized;
            public string $secret = 'inspection-secret-value';
            public static string $staticSecret = 'inspection-static-secret-value';
            private string $privateSecret = 'inspection-private-secret-value';

            public function getToken(): string
            {
                throw new \LogicException($this->privateMethod());
            }

            public function accept(int $value = 0): never
            {
                throw new \LogicException('Inspection must not invoke methods.');
            }

            public static function staticMethod(): never
            {
                throw new \LogicException('Inspection must not invoke static methods.');
            }

            public function __toString(): string
            {
                throw new \LogicException('Inspection must not stringify globals.');
            }

            private function privateMethod(): string
            {
                return $this->privateSecret;
            }
        };

        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('inspection_object', $global);
        $twig->addGlobal('inspection_scalar', 'inspection-secret-value');
        $twig->addGlobal('inspection_array', ['nested' => $global]);
        $twig->addGlobal('inspection_null', null);
        $twig->addGlobal('inspection_empty_object', new \stdClass());
        $twig->addGlobal('inspection_callable', new TwigFunction('not_a_registered_function', 'strlen'));

        $inspector = new TwigEnvironmentInspector($twig);

        $data = $inspector->inspect('global', 'inspection_object');
        $this->assertSame('class@anonymous', $data['type']);
        $this->assertSame(
            [
                ['name' => 'secret', 'type' => 'string'],
                ['name' => 'uninitialized', 'type' => '?string'],
            ],
            $data['properties'],
        );
        $this->assertSame(
            [
                ['name' => 'accept', 'parameters' => [['name' => 'value', 'type' => 'int', 'optional' => true, 'variadic' => false]], 'return_type' => 'never'],
                ['name' => 'getToken', 'parameters' => [], 'return_type' => 'string'],
            ],
            $data['methods'],
        );

        $this->assertStringNotContainsString('inspection-secret-value', json_encode($data, JSON_THROW_ON_ERROR));

        foreach (['inspection_scalar' => 'string', 'inspection_array' => 'array', 'inspection_null' => 'null'] as $name => $type) {
            $this->assertSame(['kind' => 'global', 'name' => $name, 'type' => $type], $inspector->inspect('global', $name));
        }

        $this->assertSame(
            ['kind' => 'global', 'name' => 'inspection_empty_object', 'type' => \stdClass::class, 'properties' => [], 'methods' => []],
            $inspector->inspect('global', 'inspection_empty_object'),
        );
        $this->assertSame(['items' => [['kind' => 'global', 'name' => 'inspection_callable']]], $inspector->discover('global', 'inspection_callable'));
    }

    public function testDoesNotInitializeLazyGlobalObjects(): void
    {
        $object = new class() {
            public string $secret;

            public function getSecret(): string
            {
                throw new \LogicException('Inspection must not invoke getters.');
            }
        };
        $class = new \ReflectionClass($object);
        $global = $class->newLazyGhost(
            static function (): void {
                throw new \LogicException('Inspection must not initialize lazy objects.');
            },
        );
        $twig = new Environment(new ArrayLoader());
        $twig->addGlobal('inspection_lazy', $global);

        $data = new TwigEnvironmentInspector($twig)->inspect('global', 'inspection_lazy');
        $this->assertSame([['name' => 'secret', 'type' => 'string']], $data['properties']);
        $this->assertTrue($class->isUninitializedLazyObject($global));
    }

    public function testRejectsInvalidKinds(): void
    {
        $inspector = new TwigEnvironmentInspector(new Environment(new ArrayLoader()));

        foreach (['discover' => ['template'], 'inspect' => ['template', 'inspection']] as $method => $arguments) {
            try {
                $inspector->$method(...$arguments);
                $this->fail('Invalid kinds must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('Expected one of: filter, function, test, tag, global', $exception->getMessage());
            }
        }
    }

    #[DataProvider('unknownEntries')]
    public function testRejectsUnknownNamesWithoutResolvingUndefinedCallbacks(string $kind, string $name): void
    {
        $twig = new Environment(new ArrayLoader());
        $callback = static function (): never {
            throw new \LogicException('Inspection must not resolve guessed names.');
        };
        $twig->registerUndefinedFunctionCallback($callback);
        $twig->registerUndefinedFilterCallback($callback);
        $twig->addFunction(new TwigFunction('inspection_*', 'strlen'));

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Use contao_twig_environment_discover first.');
        new TwigEnvironmentInspector($twig)->inspect($kind, $name);
    }

    public static function unknownEntries(): iterable
    {
        yield 'unknown function' => ['function', 'missing_inspection_function'];
        yield 'unknown filter' => ['filter', 'missing_inspection_filter'];
        yield 'wildcard expansion' => ['function', 'inspection_concrete'];
        yield 'case mismatch' => ['function', 'RANGE'];
        yield 'unknown test' => ['test', 'missing_inspection_test'];
        yield 'unknown tag' => ['tag', 'missing_inspection_tag'];
        yield 'unknown global' => ['global', 'missing_inspection_global'];
    }
}
