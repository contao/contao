<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\Config;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\CoreBundle\Finder\ResourceFinder;
use Contao\Environment;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Abstract TestCase class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Returns the path to the fixtures directory.
     *
     * @return string The root directory path
     */
    public function getRootDir()
    {
        return __DIR__ . '/Fixtures';
    }

    /**
     * Initializes the Contao framework.
     *
     * @param string $scope The container scope
     */
    protected function bootContaoFramework($scope = 'frontend')
    {
        /** @var Kernel $kernel */
        global $kernel;

        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();
        $router    = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn('/index.html')
        ;

        $listener = new InitializeSystemListener(
            $router,
            $this->getRootDir() . '/app'
        );

        $listener->setContainer($container);

        $container->enterScope($scope);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
    }

    /**
     * Mocks a Contao kernel.
     *
     * @return Kernel The kernel object
     */
    protected function mockKernel()
    {
        Config::set('bypassCache', true);
        Environment::set('httpAcceptLanguage', []);

        $kernel = $this->getMock(
            'Symfony\Component\HttpKernel\Kernel',
            [
                // KernelInterface
                'registerBundles',
                'registerContainerConfiguration',
                'boot',
                'shutdown',
                'getBundles',
                'isClassInActiveBundle',
                'getBundle',
                'locateResource',
                'getName',
                'getEnvironment',
                'isDebug',
                'getRootDir',
                'getContainer',
                'getStartTime',
                'getCacheDir',
                'getLogDir',
                'getCharset',

                // HttpKernelInterface
                'handle',

                // Serializable
                'serialize',
                'unserialize',
            ],
            ['test', false]
        );

        $container = new Container();
        $container->addScope(new Scope('frontend'));
        $container->addScope(new Scope('backend'));

        $container->set(
            'contao.resource_finder',
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao')
        );

        $container->set(
            'contao.resource_locator',
            new FileLocator($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao')
        );

        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container)
        ;

        return $kernel;
    }

    /**
     * Returns the path to the fixtures cache directory.
     *
     * @return string The cache directory path
     */
    public function getCacheDir()
    {
        return __DIR__ . '/Fixtures/app/cache';
    }
}
