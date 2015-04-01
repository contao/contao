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
use Contao\CoreBundle\Config\CombinedFileLocator;
use Contao\CoreBundle\Config\FileLocator;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Contao\Environment;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
     * Returns the path to the fixtures cache directory.
     *
     * @return string The cache directory path
     */
    public function getCacheDir()
    {
        return __DIR__ . '/Fixtures/app/cache';
    }


    /**
     * Initializes the Contao framework.
     *
     * @param InitializeSystemListener $listener The listener instance to be used
     * @param string                   $scope The container scope
     */
    protected function bootContaoFramework(
        InitializeSystemListener $listener,
        $scope = 'frontend'
    ) {
        /** @var Kernel $kernel */
        global $kernel;

        $kernel    = $this->mockKernel();
        $container = $kernel->getContainer();

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
        Config::set('timeZone', 'GMT');
        Config::set('characterSet', 'UTF-8');
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

        $locator = new FileLocator([
            'TestBundle' => $this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao',
            'foobar'     => $this->getRootDir() . '/system/modules/foobar'
        ]);

        $container->set(
            'contao.resource_locator',
            $locator
        );

        $container->set(
            'contao.cached_resource_locator',
            new CombinedFileLocator($this->getCacheDir(), $locator)
        );

        $kernel
            ->expects($this->any())
            ->method('getContainer')
            ->willReturn($container)
        ;

        return $kernel;
    }

    /**
     * Mocks a router returning the given URL.
     *
     * @param string $url The URL to return
     *
     * @return RouterInterface The router object
     */
    protected function mockRouter($url)
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }

    /**
     * Mocks a CSRF token manager
     *
     * @return CsrfTokenManagerInterface The token manager object
     */
    protected function mockTokenManager()
    {
        $tokenManager = $this
            ->getMockBuilder('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')
            ->setMethods(array('getToken'))
            ->getMockForAbstractClass();

        $tokenManager
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'));

        return $tokenManager;
    }

    /**
     * Mocks a Symfony session containing the Contao attribute bags
     *
     * @return SessionInterface
     */
    protected function mockSession()
    {
        $session = new Session(
            new MockArraySessionStorage()
        );

        $beBag = new AttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');
        $feBag = new AttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $session->registerBag($beBag);
        $session->registerBag($feBag);

        return $session;
    }
}
