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
use Contao\OptInModel;
use Contao\PageModel;
use Contao\System;
use Contao\Versions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ChangePasswordControllerTest extends ContentElementTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback']);
    }

    public function testReturnsIfNoFrontendUser(): void
    {
        $container = $this->getContainerWithFrameworkTemplate();

        $controller = new ChangePasswordController(
            $this->getDefaultFramework(),
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(RouterInterface::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfNoFrontendMember(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            $this->createStub(FrontendUser::class),
        );

        $controller = new ChangePasswordController(
            $this->mockFrameworkWithTemplate(),
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(RouterInterface::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testExecutesOnloadCallbacks(): void
    {
        $member = $this->createClassWithPropertiesStub(MemberModel::class);

        $container = $this->getContainerWithFrameworkTemplate(
            $this->createStub(FrontendUser::class),
            $member,
        );

        $controller = new ChangePasswordController(
            $this->mockFrameworkWithTemplate($member, null, null, null, true),
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(RouterInterface::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] = [
            ['Test\Callback', 'callback'],
            static function (): void {},
        ];

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        unset($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback']);
    }

    public function testReturnsIfWrongOldPassword(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);
        $user->password = 'hashed-password';

        $member = $this->createClassWithPropertiesStub(MemberModel::class);

        $container = $this->getContainerWithFrameworkTemplate($user, $member, false);

        $controller = new ChangePasswordController(
            $this->mockFrameworkWithTemplate($member),
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(RouterInterface::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testChangesPassword(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);
        $user->password = 'hashed-password';

        $member = $this->createClassWithPropertiesStub(MemberModel::class);
        $member->id = 1;
        $member->username = 'username';

        $container = $this->getContainerWithFrameworkTemplate($user, $member, true);

        $optIn = $this->createClassWithPropertiesStub(OptInModel::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        $versions = $this->createMock(Versions::class);
        $versions
            ->expects($this->once())
            ->method('setUsername')
        ;

        $versions
            ->expects($this->once())
            ->method('setEditUrl')
        ;

        $versions
            ->expects($this->once())
            ->method('initialize')
        ;

        $versions
            ->expects($this->once())
            ->method('create')
        ;

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->atMost(1))
            ->method('hash')
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher)
        ;

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/')
        ;

        $controller = new ChangePasswordController(
            $this->mockFrameworkWithTemplate($member, $this->createStub(PageModel::class), $optIn, $versions),
            $passwordHasherFactory,
            $contentUrlGenerator,
            $eventDispatcher,
            $this->createStub(RouterInterface::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $model->jumpTo = 1;

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_change_password_');

        $request->setSession($this->createStub(SessionInterface::class));

        $GLOBALS['TL_DCA']['tl_member']['config']['enableVersioning'] = true;

        $response = $controller($request, $model, 'main');

        unset($GLOBALS['TL_DCA']['tl_member']['config']['enableVersioning']);

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    private function mockFrameworkWithTemplate(MemberModel|null $member = null, PageModel|null $page = null, OptInModel|null $optIn = null, Versions|null $versions = null, bool $hasCallback = false): ContaoFramework|MockObject|Stub
    {
        $template = new FragmentTemplate('change_password', static fn () => new Response());

        $memberAdapter = $this->createAdapterStub(['findById']);
        $memberAdapter
            ->method('findById')
            ->willReturn($member)
        ;

        $pageAdapter = $this->createAdapterStub(['findById']);
        $pageAdapter
            ->method('findById')
            ->willReturn($page)
        ;

        $systemAdapter = $this->createAdapterStub(['importStatic']);

        if ($hasCallback) {
            $onloadCallback = $this->createAdapterMock(['callback']);
            $onloadCallback
                ->expects($this->once())
                ->method('callback')
            ;

            $systemAdapter = $this->createAdapterMock(['importStatic']);
            $systemAdapter
                ->expects($this->once())
                ->method('importStatic')
                ->with('Test\Callback')
                ->willReturn($onloadCallback)
            ;
        }

        $optInAdapter = $this->createAdapterStub(['findUnconfirmedByRelatedTableAndId']);
        $optInModel = $this->createClassWithPropertiesStub(OptInModel::class);

        if ($optIn) {
            $optInAdapter = $this->createAdapterMock(['findUnconfirmedByRelatedTableAndId']);
            $optInAdapter
                ->expects($this->once())
                ->method('findUnconfirmedByRelatedTableAndId')
                ->willReturn([$optInModel])
            ;
        }

        $framework = $this->createContaoFrameworkStub([
            MemberModel::class => $memberAdapter,
            PageModel::class => $pageAdapter,
            Controller::class => $this->createAdapterStub(['loadDataContainer']),
            System::class => $systemAdapter,
            OptInModel::class => $optInAdapter,
        ]);

        if ($versions) {
            $framework
                ->method('createInstance')
                ->with(Versions::class)
                ->willReturn($versions)
            ;
        }

        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function mockTokenStorageWithToken(UserInterface|null $user = null): TokenStorageInterface&Stub
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($user ? new PreAuthenticatedToken($user, 'contao_frontend') : null)
        ;

        return $tokenStorage;
    }

    /**
     * @template T
     *
     * @param FormInterface<T>|null $form
     */
    private function mockFormFactory(FormInterface|null $form = null): FormFactoryInterface
    {
        $formFactory = $this->createStub(FormFactoryInterface::class);

        if ($form) {
            $formFactory = $this->createMock(FormFactoryInterface::class);
            $formFactory
                ->expects($this->once())
                ->method('create')
                ->willReturn($form)
            ;
        }

        return $formFactory;
    }

    private function getContainerWithFrameworkTemplate(UserInterface|null $user = null, MemberModel|null $member = null, bool|null $formIsValid = null): ContainerBuilder
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('handleRequest')
        ;

        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('isSubmitted')
            ->willReturn(true)
        ;

        $form
            ->expects(null !== $formIsValid ? $this->once() : $this->never())
            ->method('isValid')
            ->willReturn((bool) $formIsValid)
        ;

        $formField = $this->createMock(FormInterface::class);
        $formField
            ->expects($formIsValid ? $this->exactly(2) : $this->never())
            ->method('getData')
            ->willReturn('12345678')
        ;

        $form
            ->expects($formIsValid ? $this->exactly(2) : $this->never())
            ->method('get')
            ->willReturn($formField)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));
        $container->set('security.token_storage', $this->mockTokenStorageWithToken($user));
        $container->set('contao.routing.content_url_generator', $this->createStub(ContentUrlGenerator::class));
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('form.factory', $this->mockFormFactory(null !== $formIsValid ? $form : null));

        System::setContainer($container);

        return $container;
    }
}
