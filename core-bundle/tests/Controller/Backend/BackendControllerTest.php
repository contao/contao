<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Backend;

use Contao\CoreBundle\Controller\Backend\BackendController;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class BackendControllerTest extends TestCase
{
    public function testRedirectsToTheBackendIfTheUserIsFullyAuthenticatedUponLogin(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend')
            ->willReturn('/contao')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->createContaoFrameworkStub());
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->loginAction(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/contao', $response->getTargetUrl());
    }

    public function testRedirectsToTheBackendLoginAfterAUserHasLoggedOut(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login')
            ->willReturn('/contao/login')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->createContaoFrameworkStub());
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->logoutAction();

        $this->assertSame('/contao/login', $response->getTargetUrl());
    }

    public function testReturnsAResponseInThePickerActionMethod(): void
    {
        $picker = $this->createStub(PickerInterface::class);
        $picker
            ->method('getCurrentUrl')
            ->willReturn('/foobar')
        ;

        $builder = $this->createStub(PickerBuilderInterface::class);
        $builder
            ->method('create')
            ->willReturn($picker)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.picker.builder', $builder);

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'page');
        $request->query->set('extras', ['fieldType' => 'radio']);
        $request->query->set('value', '{{link_url::5}}');

        $response = $controller->pickerAction($request);

        $this->assertSame('/foobar', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testDoesNotReturnAResponseInThePickerActionMethodIfThePickerExtrasAreInvalid(): void
    {
        $controller = new BackendController();

        $request = new Request();
        $request->query->set('extras', null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid picker extras');

        $controller->pickerAction($request);
    }

    public function testDoesNotReturnAResponseInThePickerActionMethodIfThePickerContextIsUnsupported(): void
    {
        $builder = $this->createStub(PickerBuilderInterface::class);
        $builder
            ->method('create')
            ->willReturn(null)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.picker.builder', $builder);

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'invalid');
        $request->query->set('value', '');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported picker context');

        $controller->pickerAction($request);
    }

    #[DataProvider('provideErrorTemplateScenarios')]
    public function testRendersFallbackRoute(bool $legacyTemplateExists, string $expectedTemplate): void
    {
        $loader = $this->createStub(LoaderInterface::class);
        $loader
            ->method('exists')
            ->with('@ContaoCore/Error/backend.html.twig')
            ->willReturn($legacyTemplateExists)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->method('getLoader')
            ->willReturn($loader)
        ;

        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                $expectedTemplate,
                [
                    'language' => 'en',
                    'statusName' => 'Page Not Found',
                    'exception' => 'The requested page does not exist.',
                    'template' => $expectedTemplate,
                ],
            )
            ->willReturn('<error-template>')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('twig', $twig);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->backendFallback();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('<error-template>', $response->getContent());
    }

    public static function provideErrorTemplateScenarios(): iterable
    {
        yield 'legacy template' => [true, '@ContaoCore/Error/backend.html.twig'];

        yield 'modern template' => [false, '@Contao/error/backend.html.twig'];
    }
}
