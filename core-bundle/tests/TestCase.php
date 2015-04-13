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
use Contao\CoreBundle\Config\ConfigAdapter;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\InitializeSystemListener;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
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
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

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
     */
    protected function bootContaoFramework(InitializeSystemListener $listener)
    {
        Config::preload();

        /** @var Kernel $kernel */
        global $kernel;

        $kernel = $this->mockKernel();
        $router = $this->getMock('Symfony\\Component\\Routing\\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn('/index.html')
        ;

        $listener->onConsoleCommand();
    }

    /**
     * Mocks a Contao kernel.
     *
     * @return Kernel The kernel object
     */
    protected function mockKernel()
    {
        $kernel = $this->getMock(
            'Symfony\\Component\\HttpKernel\\Kernel',
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
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));

        $container->set(
            'contao.resource_finder',
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao')
        );

        $container->set(
            'contao.resource_locator',
            new FileLocator($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao')
        );

        $request = new Request();
        $request->server->set('REMOTE_ADDR', '123.456.789.0');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container->set(
            'request_stack',
            $requestStack
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
        $router = $this->getMock('Symfony\\Component\\Routing\\RouterInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }

    /**
     * Mocks a CSRF token manager.
     *
     * @return CsrfTokenManagerInterface The token manager object
     */
    protected function mockTokenManager()
    {
        $tokenManager = $this
            ->getMockBuilder('Symfony\\Component\\Security\\Csrf\\CsrfTokenManagerInterface')
            ->setMethods(['getToken'])
            ->getMockForAbstractClass()
        ;

        $tokenManager
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'))
        ;

        $tokenManager
            ->expects($this->any())
            ->method('refreshToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'))
        ;

        return $tokenManager;
    }

    /**
     * Mocks a Symfony session containing the Contao attribute bags.
     *
     * @return SessionInterface The session object
     */
    protected function mockSession()
    {
        $session = new Session(new MockArraySessionStorage());

        $beBag = new AttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');

        $session->registerBag($beBag);

        $feBag = new AttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $session->registerBag($feBag);

        return $session;
    }

    /**
     * Mocks a Config adapter.
     *
     * @return ConfigAdapter
     */
    protected function mockConfig()
    {
        $config = $this->getMock('Contao\\CoreBundle\\Config\\ConfigAdapter');

        $config->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        return $config;

    }
}
