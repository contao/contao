<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return string
     */
    protected function getRootDir(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'Fixtures';
    }

    /**
     * @return string
     */
    protected function getCacheDir(): string
    {
        return $this->getRootDir().'/var/cache';
    }

    /**
     * Mocks the Contao framework.
     *
     * @param array $adapters
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContaoFramework(array $adapters = []): ContaoFrameworkInterface
    {
        if (!isset($adapters[Config::class])) {
            $adapters[Config::class] = $this->mockConfigAdapter();
        }

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($adapters): ?Adapter {
                    return $adapters[$key] ?? null;
                }
            )
        ;

        return $framework;
    }

    /**
     * Mocks the container.
     *
     * @return ContainerBuilder
     */
    protected function mockContainer()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.cache_dir', $this->getCacheDir());
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');

        // Load the default configuration
        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        return $container;
    }

    /**
     * Mocks an adapter.
     *
     * @param array $methods
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockAdapter(array $methods): Adapter
    {
        return $this->createPartialMock(Adapter::class, $methods);
    }

    /**
     * Mocks a configured adapter.
     *
     * @param array $configuration
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockConfiguredAdapter(array $configuration)
    {
        $adapter = $this->mockAdapter(array_keys($configuration));

        foreach ($configuration as $method => $return) {
            $adapter->method($method)->willReturn($return);
        }

        return $adapter;
    }

    /**
     * Mocks a request scope matcher.
     *
     * @return ScopeMatcher
     */
    protected function mockScopeMatcher(): ScopeMatcher
    {
        return new ScopeMatcher(
            new RequestMatcher(null, null, null, null, ['_scope' => 'backend']),
            new RequestMatcher(null, null, null, null, ['_scope' => 'frontend'])
        );
    }

    /**
     * Mocks a session containing the Contao attribute bags.
     *
     * @return SessionInterface
     */
    protected function mockSession(): SessionInterface
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setId('test-id');

        $beBag = new ArrayAttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');

        $session->registerBag($beBag);

        $feBag = new ArrayAttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $session->registerBag($feBag);

        return $session;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter()
    {
        include __DIR__.'/../src/Resources/contao/config/default.php';

        $adapter = $this->mockAdapter(['isComplete', 'get']);

        $adapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $adapter
            ->method('get')
            ->willReturnCallback(
                function (string $key) {
                    return $GLOBALS['TL_CONFIG'][$key] ?? null;
                }
            )
        ;

        return $adapter;
    }
}
