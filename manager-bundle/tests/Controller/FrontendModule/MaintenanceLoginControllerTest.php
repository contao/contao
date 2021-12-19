<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Controller\FrontendModule;

use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\FrontendTemplate;
use Contao\ManagerBundle\Controller\FrontendModule\MaintenanceLoginController;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceLoginControllerTest extends ContaoTestCase
{
    public function testReturnsEmptyResponseWithoutJwtManager(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithFrameworkTemplate($this->mockTemplate(false));
        $container->set('request_stack', $requestStack);

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(false));
        $controller->setContainer($container);

        $response = $controller($request, $this->mockClassWithProperties(ModuleModel::class), 'main');

        $this->assertSame('', $response->getContent());
    }

    public function testReturnsEmptyResponseIfMaintenanceIsNotEnabled(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getContainerWithFrameworkTemplate($this->mockTemplate(false));
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->createMock(JwtManager::class);

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(false), $jwtManager);
        $controller->setContainer($container);

        $response = $controller($request, $this->mockClassWithProperties(ModuleModel::class), 'main');

        $this->assertSame('', $response->getContent());
    }

    public function testAlwaysRendersDisabledFormIfPreviewIsEnabled(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->attributes->set('_preview', true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate();

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true));
        $controller->setContainer($container);

        $controller($request, $this->mockClassWithProperties(ModuleModel::class), 'main');

        $this->assertTrue($template->disabled);
    }

    public function testPassesTheUsernameFromRequestToTemplate(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->request->set('username', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate();

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->createMock(JwtManager::class);

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true), $jwtManager);
        $controller->setContainer($container);

        $controller($request, $this->mockClassWithProperties(ModuleModel::class), 'main');

        $this->assertSame('foobar', $template->username);
    }

    public function testAddsResponseCookieOnLogin(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->request->set('FORM_SUBMIT', 'tl_maintenance_login_42');
        $request->request->set('username', 'foo');
        $request->request->set('password', 'bar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate(false);

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->mockJwtManager([]);
        $jwtManager
            ->expects($this->once())
            ->method('addResponseCookie')
            ->with($this->isInstanceOf(Response::class), ['bypass_maintenance' => true, 'debug' => false])
        ;

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true), $jwtManager);
        $controller->setContainer($container);

        $moduleModel = $this->mockClassWithProperties(
            ModuleModel::class,
            [
                'id' => '42',
                'maintenanceUsername' => 'foo',
                'maintenancePassword' => 'bar',
            ]
        );

        $controller($request, $moduleModel, 'main');
    }

    public function testDoesNotAddResponseCookieOnInvalidLogin(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->request->set('FORM_SUBMIT', 'tl_maintenance_login_42');
        $request->request->set('username', 'bar');
        $request->request->set('password', 'foo');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate();

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->mockJwtManager([]);
        $jwtManager
            ->expects($this->never())
            ->method('addResponseCookie')
        ;

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true), $jwtManager);
        $controller->setContainer($container);

        $moduleModel = $this->mockClassWithProperties(
            ModuleModel::class,
            [
                'id' => '42',
                'maintenanceUsername' => 'foo',
                'maintenancePassword' => 'bar',
            ]
        );

        $controller($request, $moduleModel, 'main');

        $this->assertTrue($template->invalidLogin);
    }

    public function testKeepsDebugModeEnabledOnLogin(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->request->set('FORM_SUBMIT', 'tl_maintenance_login_42');
        $request->request->set('username', 'foo');
        $request->request->set('password', 'bar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate(false);

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->mockJwtManager(['debug' => true]);
        $jwtManager
            ->expects($this->once())
            ->method('addResponseCookie')
            ->with($this->isInstanceOf(Response::class), ['bypass_maintenance' => true, 'debug' => true])
        ;

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true), $jwtManager);
        $controller->setContainer($container);

        $moduleModel = $this->mockClassWithProperties(
            ModuleModel::class,
            [
                'id' => '42',
                'maintenanceUsername' => 'foo',
                'maintenancePassword' => 'bar',
            ]
        );

        $controller($request, $moduleModel, 'main');
    }

    public function testClearsTheCookieOnLogout(): void
    {
        $pageModel = $this->mockPageModel(['maintenanceMode' => '1']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);
        $request->request->set('FORM_SUBMIT', 'tl_maintenance_login_42');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $template = $this->mockTemplate(false);

        $container = $this->getContainerWithFrameworkTemplate($template);
        $container->set('request_stack', $requestStack);

        $jwtManager = $this->mockJwtManager(['bypass_maintenance' => true]);
        $jwtManager
            ->expects($this->once())
            ->method('clearResponseCookie')
            ->with($this->isInstanceOf(Response::class))
        ;

        $controller = new MaintenanceLoginController($requestStack, $this->mockTokenManager(true), $jwtManager);
        $controller->setContainer($container);

        $moduleModel = $this->mockClassWithProperties(ModuleModel::class, ['id' => '42']);

        $controller($request, $moduleModel, 'main');
    }

    /**
     * @return ContaoCsrfTokenManager&MockObject
     */
    private function mockTokenManager(bool $expectFrontendToken): ContaoCsrfTokenManager
    {
        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenManager
            ->expects($expectFrontendToken ? $this->once() : $this->never())
            ->method('getFrontendTokenValue')
            ->willReturn('--token--')
        ;

        return $tokenManager;
    }

    private function getContainerWithFrameworkTemplate(FrontendTemplate $template = null): ContainerBuilder
    {
        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->method('findByPk')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $framework
            ->method('createInstance')
            ->with(FrontendTemplate::class, ['mod_maintenance_login'])
            ->willReturn($template ?? $this->mockTemplate())
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('translator', $this->createMock(TranslatorInterface::class));
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($container);

        return $container;
    }

    /**
     * @return FrontendTemplate&MockObject
     */
    private function mockTemplate(bool $expectResponse = true): FrontendTemplate
    {
        $template = $this->mockClassWithProperties(FrontendTemplate::class);

        $template
            ->expects($expectResponse ? $this->once() : $this->never())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        return $template;
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageModel(array $properties): PageModel
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, $properties);

        $pageModel
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        return $pageModel;
    }

    /**
     * @return JwtManager&MockObject
     */
    private function mockJwtManager(array $cookieData): JwtManager
    {
        $jwtManager = $this->createMock(JwtManager::class);

        $jwtManager
            ->expects($this->atLeastOnce())
            ->method('parseRequest')
            ->willReturn($cookieData)
        ;

        return $jwtManager;
    }
}
