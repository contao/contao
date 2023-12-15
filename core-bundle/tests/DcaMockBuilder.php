<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests;

use Contao\ArrayUtil;
use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\DcaFactory;
use Contao\CoreBundle\Dca\Schema\Dca;
use Contao\CoreBundle\Dca\Schema\SchemaInterface;
use Contao\CoreBundle\Dca\SchemaFactory;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Builder class to generate a DcaFactory with predefined data and mockable and spyable schema nodes.
 */
final class DcaMockBuilder
{
    private readonly SchemaFactory $schemaFactory;

    private array $dcas = [];

    private array $dcaData = [];

    private array $spies = [];

    private array $mocks = [];

    public function __construct(
        private readonly TestCase $testCase,
        ContaoFramework $framework,
        Container $container,
    ) {
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['service_container', $container],
                ['contao.framework', $framework],
            ])
        ;

        $this->schemaFactory = new SchemaFactory($locator);
    }

    public function addDcaData(string $resource, array $data): self
    {
        $this->dcaData[$resource] = $data;
        $this->addResource($resource);

        return $this;
    }

    public function addSpies(string $resource, array $spies): self
    {
        $this->spies[$resource] = array_merge_recursive($this->spies[$resource] ?? [], $spies);
        $this->addResource($resource);

        return $this;
    }

    public function addMocks(string $resource, array $mocks): self
    {
        $this->mocks[$resource] = array_merge_recursive($this->mocks[$resource] ?? [], $mocks);
        $this->addResource($resource);

        return $this;
    }

    public function getMock(): MockObject&DcaFactory
    {
        $factory = $this->getMockBuilder(DcaFactory::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock()
        ;

        $resourceMap = [];

        foreach ($this->dcas as $resource) {
            $source = [];

            foreach ($this->dcaData[$resource] ?? [] as $k => $v) {
                $source = ArrayUtil::set($source, $k, $v);
            }

            $dca = $this->getDcaMock($resource, $source);
            $mocks = $this->getMocks($resource, $dca);
            $dca = $this->attachMocksToDca($resource, $dca, $mocks, $dca);

            $resourceMap[] = [$resource, $dca];
        }

        $factory
            ->method('get')
            ->willReturnMap($resourceMap)
        ;

        return $factory;
    }

    private function getDcaMock(string $resource, array $source): MockObject&Dca
    {
        return $this->testCase
            ->getMockBuilder(Dca::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setConstructorArgs([$resource, new Data($source), $this->schemaFactory])
            ->onlyMethods([])
            ->getMock()
        ;
    }

    private function attachMocksToDca(string $resource, SchemaInterface $dca, array $mocks, SchemaInterface|null $original = null): MockObject
    {
        $new = $this
            ->getMockBuilder($dca::class)
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setConstructorArgs([$resource, $dca->getData(), $this->schemaFactory])
            ->onlyMethods(array_keys($mocks))
            ->setProxyTarget($dca)
            ->getMock()
        ;

        foreach ($mocks as $method => $mock) {
            if (!empty($mock['_mocks'] ?? null)) {
                /** @var SchemaInterface $originalSchema */
                $originalSchema = $original ? $original->{$method}() : $dca->{$method}();

                if (isset($mock['_mock'])) {
                    throw new \LogicException(sprintf('You cannot define a spy on a child node of another spy at "%s"', $method.'.'.array_key_first($mock['_mocks'])));
                }

                $mock['_mock'] = $this->testCase
                    ->getMockBuilder($originalSchema::class)
                    ->disableOriginalClone()
                    ->disableArgumentCloning()
                    ->disallowMockingUnknownTypes()
                    ->setConstructorArgs([$resource, $originalSchema->getData(), $this->schemaFactory])
                    ->onlyMethods(array_keys($mock['_mocks']))
                    ->getMock()
                ;

                $mock['_mock'] = $this->attachMocksToDca($resource, $mock['_mock'], $mock['_mocks'], $originalSchema);
            }

            if (isset($mock['_mock'])) {
                if (\is_array($mock['_mock'])) {
                    $new
                        ->method($method)
                        ->willReturnMap($mock['_mock'])
                    ;

                    continue;
                }

                $new
                    ->method($method)
                    ->willReturn($mock['_mock'])
                ;
            }
        }

        return $new;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return MockBuilder<T>
     */
    private function getMockBuilder(string $className): MockBuilder
    {
        return $this->testCase->getMockBuilder($className);
    }

    private function addResource(string $resource): void
    {
        if (!\in_array($resource, $this->dcas, true)) {
            $this->dcas[] = $resource;
        }
    }

    private function getMocks(string $resource, MockObject&Dca $dca): array
    {
        $mocks = [];
        $spies = [];

        foreach ($this->mocks[$resource] ?? [] as $path => $mock) {
            $parsed = ArrayUtil::set([], $path, $mock);

            foreach ($parsed as $k => $v) {
                $mocks = $this->addMock($mocks, $k, $v);
            }
        }

        foreach ($this->spies[$resource] ?? [] as $path => $count) {
            $check = ArrayUtil::pathToArray($path);
            array_pop($check);
            $check[] = '_mock';

            $existing = ArrayUtil::get($mocks, $check);

            if ($existing) {
                throw new \LogicException(sprintf('Spy at position "%s" would replace an existing mock. Add the spy to the mock.', $path));
            }

            if (!str_contains($path, '.')) {
                throw new \LogicException(sprintf('You cannot add a spy to the root level of the DCA. Add a mock for "%s" if necessary.', $path));
            }

            $parsed = ArrayUtil::set([], $path, $count);

            foreach ($parsed as $k => $v) {
                $spies = $this->parseSpies($spies, $k, $v);
            }
        }

        foreach ($spies as $path => $spy) {
            $mocks = $this->addSpy($mocks, $path, $spy, $dca);
        }

        return $mocks;
    }

    /**
     * @param array<MockObject>|MockObject $mock
     */
    private function addMock(array $mocks, string $path, mixed $mock): array
    {
        if (\is_array($mock)) {
            foreach ($mock as $k => $v) {
                if (!isset($mocks[$path]['_mocks'])) {
                    $mocks[$path]['_mocks'] = [];
                }

                $mocks[$path]['_mocks'] = $this->addMock($mocks[$path]['_mocks'], $k, $v);
            }
        } elseif ($this->isArgumentPath($path)) {
            $arguments = $this->parseArgumentPath($path);
            $path = $arguments['path'];

            $mocks[$path]['_mock'][] = array_merge($arguments['arguments'], [$mock]);
        } else {
            $mocks[$path]['_mock'] = $mock;
        }

        return $mocks;
    }

    /**
     * @param array<InvocationOrder>|InvocationOrder $spy
     */
    private function parseSpies(array $spies, string $path, $spy): array
    {
        if (\is_array($spy)) {
            foreach ($spy as $k => $v) {
                if (\is_array($v)) {
                    if (!isset($spies[$path]['_children'])) {
                        $spies[$path]['_children'] = [];
                    }

                    $spies[$path]['_children'] = $this->parseSpies($spies[$path]['_children'], $k, $v);
                } else {
                    $spies[$path]['_spies'][$k] = $v;
                }
            }
        } else {
            $arguments = [];

            if (preg_match('/\[(.*)\]/', $path, $arguments)) {
                $path = str_replace($arguments[0], '', $path);

                $spies['_spies'][$path][] = array_merge(explode(',', $arguments[1]), [$spy]);
            } else {
                $spies['_spies'][$path] = $spy;
            }
        }

        return $spies;
    }

    private function addSpy(array $mocks, string $path, mixed $spy, MockObject&SchemaInterface $dca): array
    {
        $mock = $this->createSpy($dca, $path);

        if (!empty($spy['_spies'] ?? null)) {
            foreach ($spy['_spies'] as $method => $times) {
                $mock
                    ->expects($times)
                    ->method($method)
                ;
            }

            if ($this->isArgumentPath($path)) {
                $arguments = $this->parseArgumentPath($path);
                $path = $arguments['path'];

                $mocks[$path]['_mock'][] = array_merge($arguments['arguments'], [$mock]);
            } else {
                $mocks[$path]['_mock'] = $mock;
            }
        }

        if (!empty($spy['_children'] ?? null)) {
            foreach ($spy['_children'] as $k => $v) {
                if (!isset($mocks[$path]['_mocks'])) {
                    $mocks[$path]['_mocks'] = [];
                }

                $mocks[$path]['_mocks'] = $this->addSpy($mocks[$path]['_mocks'], $k, $v, $mock);
            }
        }

        return $mocks;
    }

    private function createSpy(MockObject&SchemaInterface $dca, string $path): MockObject&SchemaInterface
    {
        if ($this->isArgumentPath($path)) {
            $arguments = $this->parseArgumentPath($path);
            $target = $dca->{$arguments['path']}(...$arguments['arguments']);
        } else {
            $target = $dca->{$path}();
        }

        if (!$target instanceof SchemaInterface) {
            throw new \LogicException(sprintf('Invalid spy path with parent "%s".', $dca->getName().'.'.$path));
        }

        return $target instanceof MockObject ? $target : $this
            ->getMockBuilder($target::class)
            ->setConstructorArgs([$path, $target->getData(), $this->schemaFactory])
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;
    }

    private function isArgumentPath(string $path): bool
    {
        return 1 === preg_match('/\[(.*)\]/', $path);
    }

    private function parseArgumentPath(string $path): array
    {
        $arguments = [];
        preg_match('/\[(.*)\]/', $path, $arguments);

        $path = str_replace($arguments[0], '', $path);

        return [
            'path' => str_replace($arguments[0], '', $path),
            'arguments' => explode(',', $arguments[1]),
        ];
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $originalClassName
     *
     * @return MockObject&T
     */
    private function createMock(string $originalClassName): MockObject
    {
        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->getMock()
        ;
    }
}
