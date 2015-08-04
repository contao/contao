<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Tests the ContaoFramework class.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class ContaoFrameworkTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $framework = $this->mockContaoFramework(
            new RequestStack(),
            $this->mockRouter('/')
        );

        $this->assertInstanceOf('Contao\\CoreBundle\\ContaoFramework', $framework);
    }

    /**
     * Tests initializing the framework with a front end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testFrontendRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/index.html'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertFalse(defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('FE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('', TL_REFERER_ID);
        $this->assertEquals('index.html', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
        $this->assertEquals('en', $GLOBALS['TL_LANGUAGE']);
        $this->assertInstanceOf('Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag', $_SESSION['BE_DATA']);
        $this->assertInstanceOf('Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag', $_SESSION['FE_DATA']);
    }

    /**
     * Tests initializing the framework with a back end request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testBackendRequest()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setLocale('de');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/install'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('foobar', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
        $this->assertEquals('de', $GLOBALS['TL_LANGUAGE']);
    }

    /**
     * Tests initializing the framework without a request.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutRequest()
    {
        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->set('request_stack', new RequestStack());

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/install'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertNull(TL_REFERER_ID);
        $this->assertEquals('console', TL_SCRIPT);
        $this->assertNull(TL_PATH);
    }

    /**
     * Tests initializing the framework without a scope.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithoutScope()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/install'));
        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertFalse(defined('BE_USER_LOGGED_IN'));
        $this->assertFalse(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertNull(TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('foobar', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
    }

    /**
     * Tests that the framework is not initialized twice.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testNotInitializedTwice()
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        // Ensure to use the fixtures class
        Config::preload();

        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\\CoreBundle\\ContaoFramework')
            ->setConstructorArgs([
                $container->get('request_stack'),
                $this->mockRouter('/contao/install'),
                $this->mockSession(),
                $this->getRootDir() . '/app',
                new CsrfTokenManager(
                    $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenGenerator\\TokenGeneratorInterface'),
                    $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenStorage\\TokenStorageInterface')
                ),
                'contao_csrf_token',
                $this->mockConfig(),
                error_reporting(),
            ])
            ->setMethods(['isInitialized'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturnOnConsecutiveCalls(false, true)
        ;

        $framework->setContainer($container);
        $framework->initialize();
        $framework->initialize();
    }

    /**
     * Tests that the error level will get updated when configured.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testErrorLevelOverride()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/install'));
        $framework->setContainer($container);

        $errorReporting = error_reporting();
        error_reporting(E_ALL ^ E_USER_NOTICE);

        $this->assertNotEquals(
            $errorReporting,
            error_reporting(),
            'Test is invalid, error level has not changed.'
        );

        $framework->initialize();

        $this->assertEquals($errorReporting, error_reporting());

        error_reporting($errorReporting);
    }

    /**
     * Tests initializing the framework with a valid request token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testValidRequestToken()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foobar');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $tokenGenerator = $this->getMock(
            'Symfony\\Component\\Security\\Csrf\\TokenGenerator\\TokenGeneratorInterface',
            ['generateToken']
        );

        $tokenGenerator
            ->expects($this->any())
            ->method('generateToken')
            ->willReturn('foobar')
        ;

        $tokenManager = $this->getMock(
            'Symfony\\Component\\Security\\Csrf\\CsrfTokenManager',
            ['isTokenValid'],
            [
                $tokenGenerator,
                $this->getMock('Symfony\\Component\\Security\\Csrf\\TokenStorage\\TokenStorageInterface'),
            ]
        );

        $tokenManager
            ->expects($this->any())
            ->method('isTokenValid')
            ->willReturn('true')
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/install'),
            $tokenManager
        );

        $framework->setContainer($container);
        $framework->initialize();

        $this->assertTrue(defined('TL_MODE'));
        $this->assertTrue(defined('TL_START'));
        $this->assertTrue(defined('TL_ROOT'));
        $this->assertTrue(defined('TL_REFERER_ID'));
        $this->assertTrue(defined('TL_SCRIPT'));
        $this->assertTrue(defined('BE_USER_LOGGED_IN'));
        $this->assertTrue(defined('FE_USER_LOGGED_IN'));
        $this->assertTrue(defined('TL_PATH'));
        $this->assertEquals('BE', TL_MODE);
        $this->assertEquals($this->getRootDir(), TL_ROOT);
        $this->assertEquals('', TL_REFERER_ID);
        $this->assertEquals('contao/install', TL_SCRIPT);
        $this->assertEquals('', TL_PATH);
    }

    /**
     * Tests initializing the framework with an invalid request token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Contao\CoreBundle\Exception\InvalidRequestTokenException
     */
    public function testInvalidRequestToken()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->setMethod('POST');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $framework = $this->mockContaoFramework($container->get('request_stack'), $this->mockRouter('/contao/install'));
        $framework->setContainer($container);
        $framework->initialize();
    }

    /**
     * Tests initializing the framework with an incomplete installation.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException \Contao\CoreBundle\Exception\IncompleteInstallationException
     */
    public function testIncompleteInstallation()
    {
        $request = new Request();
        $request->attributes->set('_route', 'dummy');

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $container->get('request_stack')->push($request);

        $config = $this->getMock('Contao\\CoreBundle\\Adapter\\ConfigAdapter', ['isComplete']);

        $config
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(false)
        ;

        $config
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            })
        ;

        $framework = $this->mockContaoFramework(
            $container->get('request_stack'),
            $this->mockRouter('/contao/install'),
            null,
            $config
        );

        $framework->setContainer($container);
        $framework->initialize();
    }
}
