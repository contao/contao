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
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
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
            $this->createMock(PasswordHasherFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfNoMemberModel(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(FrontendUser::class));

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate(),
            $this->createMock(PasswordHasherFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRendersTemplate(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(FrontendUser::class));

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($this->mockClassWithProperties(MemberModel::class)),
            $this->createMock(PasswordHasherFactoryInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testShowsErrorMessageWhenInvalidPassword(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->password = 'hashed-password';

        $container = $this->getContainerWithFrameworkTemplate($user);

        $memberModel = $this->createMock(MemberModel::class);

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($memberModel),
            $this->mockPasswordHasherFactory(false),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(Security::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_close_account_');
        $request->request->set('password', '12345678');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeactivatesMember(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->password = 'hashed-password';

        $container = $this->getContainerWithFrameworkTemplate($user);

        $memberModel = $this->createMock(MemberModel::class);
        $memberModel
            ->expects($this->once())
            ->method('save')
        ;

        $controller = new CloseAccountController(
            $this->mockFrameworkWithTemplate($memberModel),
            $this->mockPasswordHasherFactory(true),
            $this->mockEventDispatcher(),
            $this->mockSecurity(),
            $this->createMock(ContentUrlGenerator::class),
            $this->mockLogger(),
            $this->createMock(VirtualFilesystem::class),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $model->reg_close = 'close_deactivate';

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_close_account_');
        $request->request->set('password', '12345678');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeletesMember(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->password = 'hashed-password';

        $container = $this->getContainerWithFrameworkTemplate($user);

        $memberModel = $this->mockClassWithProperties(MemberModel::class);
        $memberModel->assignDir = true;
        $memberModel->homeDir = 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6';

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
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
            $this->mockFrameworkWithTemplate($memberModel, $filesModel, $this->mockClassWithProperties(PageModel::class)),
            $this->mockPasswordHasherFactory(true),
            $this->mockEventDispatcher(),
            $this->mockSecurity(),
            $contentUrlGenerator,
            $this->mockLogger(),
            $virtualFileSystem,
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $model->reg_close = 'close_delete';
        $model->reg_deleteDir = true;
        $model->jumpTo = 1;

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

    private function mockPasswordHasherFactory(bool $willVerify): PasswordHasherFactoryInterface&MockObject
    {
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('verify')
            ->willReturn($willVerify)
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher)
        ;

        return $passwordHasherFactory;
    }

    private function mockFrameworkWithTemplate(MemberModel|null $member = null, FilesModel|null $homeDir = null, PageModel|null $jumpTo = null): ContaoFramework&MockObject
    {
        $template = new FragmentTemplate('close_account', static fn () => new Response());

        $memberModel = $this->mockAdapter(['findById']);
        $memberModel
            ->method('findById')
            ->willReturn($member)
        ;

        $filesModel = $this->mockAdapter(['findByUuid']);
        $filesModel
            ->method('findByUuid')
            ->willReturn($homeDir)
        ;

        $pageModel = $this->mockAdapter(['findById']);
        $pageModel
            ->method('findById')
            ->willReturn($jumpTo)
        ;

        $framework = $this->mockContaoFramework([
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
        $container->set('contao.framework', $this->mockFrameworkWithTemplate($member));
        $container->set('security.token_storage', $this->mockTokenStorageWithToken($user));
        $container->set('contao.routing.content_url_generator', $this->createMock(ContentUrlGenerator::class));
        $container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));

        System::setContainer($container);

        return $container;
    }
}
