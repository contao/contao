<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoFramework;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

/**
 * Test for Class Contao\CoreBundle\ContaoFramework
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 *
 * @FIXME Write a test for initialize()
 * @FIXME Write a test that determines that framework cannot be initialized twice
 */
class ContaoFrameworkTest extends TestCase
{
    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenManager;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var ConfigAdapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var string
     */
    private $csrfTokenName;

    /**
     * @var int
     */
    private $errorLevel;

    /**
     * @var TokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenGenerator;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * {@inheritdoc}
     */
    public function setup()
    {
        global $kernel;

        $kernel = $this->mockKernel();
        $this->container = $kernel->getContainer();
        $this->requestStack = new RequestStack();

        $request = Request::create('/foo');
        $this->requestStack->push($request);

        $this->router = $this->mockRouter('/index.html');
        $this->session = $this->mockSession();
        $this->rootDir = $this->getRootDir();
        $this->tokenGenerator = $this->getMock('Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface');
        $this->tokenStorage = $this->getMock('Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface');
        $this->tokenManager = new CsrfTokenManager($this->tokenGenerator, $this->tokenStorage);
        $this->csrfTokenName = 'contao_csrf_token';
        $this->config = $this->getMock('Contao\CoreBundle\Adapter\ConfigAdapter');
        $this->errorLevel = error_reporting();
    }

    /**
     * Test instantiate ContaoFramework-Object
     */
    public function testInstantiate()
    {
        $framework = $this->getContaoFramework();
        $this->assertInstanceOf('Contao\CoreBundle\ContaoFramework', $framework);
    }

    public function testInitialize()
    {
    }

    /**
     * Test method setConstants
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testSetConstants()
    {
        $refererId = uniqid();
        $this->requestStack->getCurrentRequest()->attributes->set('_contao_referer_id', $refererId);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('setConstants');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$this->requestStack->getCurrentRequest()]);

        $this->assertDefined('TL_MODE');
        $this->assertEquals(TL_MODE, 'FE');

        $this->assertDefined('TL_START');

        $this->assertDefined('TL_ROOT');
        $this->assertEquals(TL_ROOT, dirname($this->getRootDir()));

        $this->assertDefined('TL_REFERER_ID');
        $this->assertEquals(TL_REFERER_ID, $refererId);

        $this->assertDefined('TL_PATH');
    }

    /**
     * Test method setConstants with Backend-Scope
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testSetConstantsWithBackendScope()
    {
        $refererId = uniqid();
        $this->requestStack->getCurrentRequest()->attributes->set('_contao_referer_id', $refererId);
        $this->requestStack->getCurrentRequest()->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('setConstants');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$this->requestStack->getCurrentRequest()]);

        $this->assertDefined('TL_MODE');
        $this->assertEquals(TL_MODE, 'BE');

        $this->assertDefined('TL_START');

        $this->assertDefined('TL_ROOT');
        $this->assertEquals(TL_ROOT, dirname($this->getRootDir()));

        $this->assertDefined('TL_REFERER_ID');
        $this->assertEquals(TL_REFERER_ID, $refererId);

        $this->assertDefined('TL_PATH');

        $this->assertDefined('BE_USER_LOGGED_IN');
        $this->assertDefined('FE_USER_LOGGED_IN');
    }

    /**
     * Test method validateInstalation will not throw a Exeption without a Request
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testValidateInstallationNoExceptionWithoutRequest()
    {
        $this->config->expects($this->any())->method('isComplete')->willReturn(false);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('validateInstallation');
        $method->setAccessible(true);
        $method->invoke($this->getContaoFramework());

        $this->assertTrue(true, 'No exception');
    }

    /**
     * Test method validateInstalation will not throw a Exception on Install-Tool
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testValidateInstallationNoExceptionForInstallTool()
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_backend_install');

        $this->config->expects($this->any())->method('isComplete')->willReturn(false);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('validateInstallation');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);

        $this->assertTrue(true, 'No exception');
    }

    /**
     * Test method validateInstalation throws a IncompleteInstallationException if Config is not complete
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testValidateInstallationThrowsIncompleteInstallationException()
    {
        $this->setExpectedException('\Contao\CoreBundle\Exception\IncompleteInstallationException');

        $request = Request::create('/foo', 'GET');
        $this->config->expects($this->any())->method('isComplete')->willReturn(false);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('validateInstallation');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);
    }

    /**
     * Test method triggerInitializeSystemHook triggers hook
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testTriggerInitializeSystemHook()
    {
        $GLOBALS['TL_HOOKS']['initializeSystem'][] = [__CLASS__, 'initializeSystemHook'];

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('triggerInitializeSystemHook');
        $method->setAccessible(true);
        $method->invoke($this->getContaoFramework());
    }

    /**
     * Callback for testTriggerInitializeSystemHook()
     */
    public function initializeSystemHook()
    {
        $this->assertEquals(
            'Contao\CoreBundle\Test\ContaoFrameworkTest',
            get_called_class()
        );
    }

    /**
     * Test setDefaultLanguage
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testSetDefaultLanguage()
    {
        $request = Request::create('/index.html', 'GET');

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('setDefaultLanguage');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);

        $this->assertArrayHasKey('TL_LANGUAGE', $GLOBALS);
        $this->assertArrayHasKey('TL_LANGUAGE', $_SESSION);
    }

    /**
     * Test initialization of legacy-acces to Session via $_SESSION[]
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testInitializeLegacySessionAccess()
    {
        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('initializeLegacySessionAccess');
        $method->setAccessible(true);
        $method->invoke($this->getContaoFramework());

        $this->assertArrayHasKey('FE_DATA', $_SESSION);
        $this->assertArrayHasKey('BE_DATA', $_SESSION);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Session\SessionBagInterface', $_SESSION['FE_DATA']);
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Session\SessionBagInterface', $_SESSION['BE_DATA']);
    }

    /**
     * Test if handleRequestToken works properly
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testHandleRequestToken()
    {
        $this->tokenStorage->expects($this->any())
            ->method('hasToken')
            ->with($this->csrfTokenName)
            ->willReturn(true);

        $this->tokenGenerator->expects($this->any())
            ->method('generateToken')
            ->will($this->returnValue('TOKEN'));

        $this->tokenStorage->expects($this->any())
            ->method('setToken')
            ->with($this->csrfTokenName, 'TOKEN');

        $token = $this->tokenManager->getToken($this->csrfTokenName);

        // $_POST must be present
        $_POST['REQUEST_TOKEN'] = $token->getValue();
        \Input::setPost('REQUEST_TOKEN', $token->getValue());

        $request = Request::create('/index.html', 'POST', ['REQUEST_TOKEN' => $token->getValue()]);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('handleRequestToken');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);
    }

    /**
     * Test if handleRequestToken throws InvalidRequestTokenException
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testHandleRequestThrowsInvalidTokenException()
    {
        $this->setExpectedException('Contao\CoreBundle\Exception\InvalidRequestTokenException');

        $this->tokenStorage->expects($this->any())
            ->method('hasToken')
            ->with($this->csrfTokenName)
            ->willReturn(false);

        $this->tokenGenerator->expects($this->any())
            ->method('generateToken')
            ->will($this->returnValue('TOKEN'));

        $this->tokenStorage->expects($this->any())
            ->method('setToken')
            ->with($this->csrfTokenName, 'TOKEN');

        $token = $this->tokenManager->getToken($this->csrfTokenName);

        // $_POST must be present
        $_POST['REQUEST_TOKEN'] = $token->getValue();
        \Input::setPost('REQUEST_TOKEN', $token->getValue());

        $request = Request::create('/index.html', 'POST', ['REQUEST_TOKEN' => $token->getValue()]);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('handleRequestToken');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);
    }

    /**
     * Test if handleRequestToken throws AjaxRedirectResponseException
     *
     * @runInSeparateProcess
     * @backupGlobals disable
     */
    public function testHandleRequestThrowsAjaxRedirectException()
    {
        $this->setExpectedException('Contao\CoreBundle\Exception\AjaxRedirectResponseException');

        $this->tokenStorage->expects($this->any())
            ->method('hasToken')
            ->with($this->csrfTokenName)
            ->willReturn(false);

        $this->tokenGenerator->expects($this->any())
            ->method('generateToken')
            ->will($this->returnValue('TOKEN'));

        $this->tokenStorage->expects($this->any())
            ->method('setToken')
            ->with($this->csrfTokenName, 'TOKEN');

        $token = $this->tokenManager->getToken($this->csrfTokenName);

        // $_POST must be present
        $_POST['REQUEST_TOKEN'] = $token->getValue();
        \Input::setPost('REQUEST_TOKEN', $token->getValue());

        $request = Request::create(
            '/index.html',
            'POST',
            ['REQUEST_TOKEN' => $token->getValue()]
        );

        $request->headers->add([
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $ref = $this->getReflectionOfContaoFramework();
        $method = $ref->getMethod('handleRequestToken');
        $method->setAccessible(true);
        $method->invokeArgs($this->getContaoFramework(), [$request]);
    }

    /**
     * Get ContaoFramework
     *
     * @return ContaoFramework
     */
    public function getContaoFramework()
    {
        return new ContaoFramework(
            $this->requestStack,
            $this->router,
            $this->session,
            $this->rootDir,
            $this->tokenManager,
            $this->csrfTokenName,
            $this->config,
            $this->errorLevel
        );
    }

    /**
     * @return \ReflectionClass
     */
    public function getReflectionOfContaoFramework()
    {
        return new \ReflectionClass($this->getContaoFramework());
    }

    /**
     * Get a Mock of ContaoFramework
     *
     * @return ContaoFramework|\PHPUnit_Framework_MockObject_MockObject
     */
    public function mockContaoFramework()
    {
        return $this->getMockBuilder('Contao\CoreBundle\ContaoFramework')
            ->setConstructorArgs(
                $this->requestStack,
                $this->router,
                $this->session,
                $this->rootDir,
                $this->tokenManager,
                $this->csrfTokenName,
                $this->config,
                $this->errorLevel
            )->getMock();
    }

    /**
     * Shortcut for asserting that constant is defined
     *
     * @param $constant
     */
    private function assertDefined($constant)
    {
        $this->assertTrue(defined($constant));
    }
}
