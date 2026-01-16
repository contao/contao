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
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\CloseAccountController;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CloseAccountControllerTest extends ContentElementTestCase
{
    public function testReturnsIfNoFrontendUser(): void
    {
        $container = $this->getContainerWithFrameworkTemplate();

        $controller = new CloseAccountController(
            $this->getDefaultFramework(),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Security::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfNoMemberModel(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createStub(FrontendUser::class));

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate(),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Security::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRendersTemplate(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createStub(FrontendUser::class));

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($this->createClassWithPropertiesStub(MemberModel::class)),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Security::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShowsErrorMessageWhenInvalidPassword(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);

        $container = $this->getContainerWithFrameworkTemplate($user, false);

        $memberModel = $this->createStub(MemberModel::class);

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($memberModel),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Security::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeactivatesMember(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $memberModel = $this->createMock(MemberModel::class);
        $memberModel
            ->expects($this->once())
            ->method('save')
        ;

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($memberModel),
            $this->mockEventDispatcher(),
            $this->mockSecurity(),
            $this->createStub(ContentUrlGenerator::class),
            $this->mockLogger(),
            $this->createStub(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesMock(ContentModel::class, ['reg_close' => 'close_deactivate']);
        $model
            ->expects($this->once())
            ->method('cloneDetached')
            ->willReturn($this->createStub(ContentModel::class))
        ;

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_close_account_');
        $request->request->set('password', '12345678');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeletesMember(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $memberModel = $this->createClassWithPropertiesStub(MemberModel::class);
        $memberModel->assignDir = true;
        $memberModel->homeDir = 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6';

        $filesModel = $this->createClassWithPropertiesStub(FilesModel::class);
        $filesModel->path = '/path/to/homedir/';

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/')
        ;

        $virtualFileSystem = $this->createMock(VirtualFilesystem::class);
        $virtualFileSystem
            ->expects($this->once())
            ->method('deleteDirectory')
        ;

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($memberModel, $filesModel, $this->createClassWithPropertiesStub(PageModel::class)),
            $this->mockEventDispatcher(),
            $this->mockSecurity(),
            $contentUrlGenerator,
            $this->mockLogger(),
            $virtualFileSystem,
        );

        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $model->reg_close = 'close_delete';
        $model->reg_deleteDir = true;
        $model->jumpTo = 1;

        $model = $this->createClassWithPropertiesMock(ContentModel::class, ['reg_close' => 'close_delete', 'reg_deleteDir' => true, 'jumpTo' => 1]);
        $model
            ->expects($this->once())
            ->method('cloneDetached')
            ->willReturn($this->createStub(ContentModel::class))
        ;

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_close_account_');
        $request->request->set('password', '12345678');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    private function mockSecurity(): Security&MockObject
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('logout')
            ->with(false)
        ;

        return $security;
    }

    private function mockLogger(): LoggerInterface&MockObject
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        return $logger;
    }

    private function mockEventDispatcher(): EventDispatcherInterface&MockObject
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
        ;

        return $eventDispatcher;
    }

    private function mockFrameworkWithTemplate(MemberModel|null $member = null, FilesModel|null $homeDir = null, PageModel|null $jumpTo = null): ContaoFramework|Stub
    {
        $template = new FragmentTemplate('close_account', static fn () => new Response());

        $memberModel = $this->createAdapterStub(['findById']);
        $memberModel
            ->method('findById')
            ->willReturn($member)
        ;

        $filesModel = $this->createAdapterStub(['findByUuid']);
        $filesModel
            ->method('findByUuid')
            ->willReturn($homeDir)
        ;

        $pageModel = $this->createAdapterStub(['findById']);
        $pageModel
            ->method('findById')
            ->willReturn($jumpTo)
        ;

        $framework = $this->createContaoFrameworkStub([
            MemberModel::class => $memberModel,
            FilesModel::class => $filesModel,
            PageModel::class => $pageModel,
        ]);

        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function mockTokenStorageWithToken(UserInterface|null $user = null): Stub|TokenStorageInterface
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

    private function getContainerWithFrameworkTemplate(UserInterface|null $user = null, bool|null $formIsValid = null): ContainerBuilder
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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockFrameworkWithTemplate());
        $container->set('security.token_storage', $this->mockTokenStorageWithToken($user));
        $container->set('contao.routing.content_url_generator', $this->createStub(ContentUrlGenerator::class));
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('form.factory', $this->mockFormFactory(null !== $formIsValid ? $form : null));

        System::setContainer($container);

        return $container;
    }
}
