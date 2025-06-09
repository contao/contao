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

use Contao\BackendUser;
use Contao\CoreBundle\Controller\ContentElement\ManagePasskeysController;
use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Repository\WebauthnCredentialRepository;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

class ManagePasskeysControllerTest extends ContentElementTestCase
{
    public function testReturnsIfThereIsNoUser(): void
    {
        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                new WebauthnCredentialRepository($this->createMock(ManagerRegistry::class)),
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer(),
            page: $this->createMock(PageModel::class),
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfThereIsNoFrontendUser(): void
    {
        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                new WebauthnCredentialRepository($this->createMock(ManagerRegistry::class)),
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(BackendUser::class)),
            page: $this->createMock(PageModel::class),
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfThereIsNoPage(): void
    {
        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                new WebauthnCredentialRepository($this->createMock(ManagerRegistry::class)),
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class)),
            page: null,
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDeniesAccessIfNotAuthenticatedFully(): void
    {
        $this->expectException(AccessDeniedException::class);

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                new WebauthnCredentialRepository($this->createMock(ManagerRegistry::class)),
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($this->createMock(FrontendUser::class), false),
            page: $this->createMock(PageModel::class),
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testRendersEmptyList(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('getAllForUser')
            ->with($user)
            ->willReturn([])
        ;

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true),
            page: $this->createMock(PageModel::class),
        );

        $content = $response->getContent();

        $this->assertStringContainsString('<div class="content-manage-passkeys">', $content);
        $this->assertStringContainsString('<button type="button" class="create">translated(contao_default:MSC.addPasskey)</button>', $content);
        $this->assertStringNotContainsString('<ul>', $content);
    }

    public function testRendersList(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $entity = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            'credentialPublicKey',
            'username',
            1,
        );

        $entity->name = 'Foobar';

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('getAllForUser')
            ->with($user)
            ->willReturn([$entity])
        ;

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
                $this->createMock(ContentUrlGenerator::class),
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true),
            page: $this->createMock(PageModel::class),
        );

        $content = $response->getContent();

        $this->assertStringContainsString('<div class="content-manage-passkeys">', $content);
        $this->assertStringContainsString('<button type="button" class="create">translated(contao_default:MSC.addPasskey)</button>', $content);
        $this->assertStringContainsString('<li class="passkey">', $content);
        $this->assertStringContainsString('<div class="name">Foobar</div>', $content);
    }

    private function getAdjustedContainer(User|null $user = null, bool|null $isFullyAuthenticated = null): ContainerBuilder
    {
        $container = new ContainerBuilder();

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        } else {
            $token = null;
        }

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $container->set('security.token_storage', $tokenStorage);

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker
            ->expects(null !== $isFullyAuthenticated ? $this->once() : $this->never())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY', null)
            ->willReturn($isFullyAuthenticated ?? false)
        ;

        $container->set('security.authorization_checker', $authChecker);

        return $container;
    }
}
