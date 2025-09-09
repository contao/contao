<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\ChangePasswordController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ChangePasswordControllerTest extends ContentElementTestCase
{
    public function testReturnsIfNoFrontendUser(): void
    {
        $container = $this->getContainerWithFrameworkTemplate();

        $controller = new ChangePasswordController($this->getDefaultFramework());

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testExecutesOnloadCallbacks(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(FrontendUser::class));

        $controller = new ChangePasswordController($this->mockFrameworkWithTemplate(true));

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] = [
            ['Test\Callback', 'callback'],
            static function (): void {},
        ];

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    private function mockFrameworkWithTemplate(bool $hasCallback = false): ContaoFramework&MockObject
    {
        $template = new FragmentTemplate('change_password', static fn () => new Response());

        $memberModel = $this->mockAdapter(['findById']);
        $memberModel
            ->method('findById')
            ->willReturn(null)
        ;

        $pageModel = $this->mockAdapter(['findById']);
        $pageModel
            ->method('findById')
            ->willReturn(null)
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);

        if ($hasCallback) {
            $onloadCallback = $this->mockAdapter(['callback']);
            $onloadCallback
                ->expects($this->once())
                ->method('callback')
            ;

            $systemAdapter
                ->expects($this->once())
                ->method('importStatic')
                ->with('Test\Callback')
                ->willReturn($onloadCallback)
            ;
        }

        $framework = $this->mockContaoFramework([
            MemberModel::class => $memberModel,
            PageModel::class => $pageModel,
            Controller::class => $this->mockAdapter(['loadDataContainer']),
            System::class => $systemAdapter,
        ]);

        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function mockTokenStorageWithToken(UserInterface|null $user = null): TokenStorageInterface&MockObject
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($user ? new PreAuthenticatedToken($user, 'contao_frontend') : null)
        ;

        return $tokenStorage;
    }

    private function getContainerWithFrameworkTemplate(UserInterface|null $user = null, MemberModel|null $member = null): ContainerBuilder
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockFrameworkWithTemplate(false));
        $container->set('security.token_storage', $this->mockTokenStorageWithToken($user));
        $container->set('contao.routing.content_url_generator', $this->createMock(ContentUrlGenerator::class));
        $container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));

        System::setContainer($container);

        return $container;
    }
}
