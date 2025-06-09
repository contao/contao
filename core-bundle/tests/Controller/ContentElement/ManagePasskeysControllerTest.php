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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\User;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\NilUuid;
use Webauthn\TrustPath\EmptyTrustPath;

class ManagePasskeysControllerTest extends ContentElementTestCase
{
    public function testReturnsIfThereIsNoUser(): void
    {
        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                new WebauthnCredentialRepository($this->createMock(ManagerRegistry::class)),
                $this->createMock(UriSigner::class),
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

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            new NilUuid(),
            'credentialPublicKey',
            'username',
            1,
        );

        $credential->name = 'Foobar';

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('getAllForUser')
            ->with($user)
            ->willReturn([$credential])
        ;

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true),
            page: $this->createMock(PageModel::class),
        );

        $content = $response->getContent();

        $this->assertStringContainsString('<div class="name">Foobar</div>', $content);
    }

    public function testRendersListAndEditsNewCredential(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            new NilUuid(),
            'credentialPublicKey',
            'username',
            1,
        );

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('getLastForUser')
            ->with($user)
            ->willReturn($credential)
        ;

        $repository
            ->expects($this->once())
            ->method('getAllForUser')
            ->with($user)
            ->willReturn([$credential])
        ;

        $request = Request::create('/passkeys?edit_new_passkey=1');

        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('checkRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $uriSigner,
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true),
            page: $this->createMock(PageModel::class),
            request: $request,
        );

        $this->assertMatchesRegularExpression('/<form method="post" id="form-edit-passkey-74205-[a-zA-Z0-9]+">/', $response->getContent());
    }

    public function testDeletesPasskey(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            new NilUuid(),
            'credentialPublicKey',
            'username',
            1,
        );

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneById')
            ->with('123')
            ->willReturn($credential)
        ;

        $repository
            ->expects($this->once())
            ->method('remove')
            ->with($credential)
        ;

        $request = Request::create(
            '/passkeys',
            'POST',
            [
                'FORM_SUBMIT' => 'passkeys_credentials_actions_74205',
                'delete_passkey' => '123',
            ],
        );

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true, $credential),
            page: $this->createMock(PageModel::class),
            request: $request,
        );

        $this->assertTrue($response instanceof RedirectResponse);
    }

    public function testEditsPasskey(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            new NilUuid(),
            'credentialPublicKey',
            'username',
            1,
        );

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneById')
            ->with('456')
            ->willReturn($credential)
        ;

        $request = Request::create(
            '/passkeys',
            'POST',
            [
                'FORM_SUBMIT' => 'passkeys_credentials_actions_74205',
                'edit_passkey' => '456',
            ],
        );

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true, $credential),
            page: $this->createMock(PageModel::class),
            request: $request,
        );

        $this->assertTrue($response instanceof RedirectResponse);
        $this->assertStringContainsString('edit_passkey=456', $response->getTargetUrl());
    }

    public function testSavesPasskey(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            new NilUuid(),
            'credentialPublicKey',
            'username',
            1,
        );

        $repository = $this->createMock(WebauthnCredentialRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneById')
            ->with('789')
            ->willReturn($credential)
        ;

        $repository
            ->expects($this->once())
            ->method('saveCredentialSource')
            ->with($this->callback(
                function (WebauthnCredential $credential) {
                    $this->assertSame('Changed name', $credential->name);

                    return true;
                },
            ))
        ;

        $request = Request::create(
            '/passkeys',
            'POST',
            [
                'FORM_SUBMIT' => 'passkeys_credentials_edit_74205',
                'credential_id' => '789',
                'passkey_name' => 'Changed name',
            ],
        );

        $response = $this->renderWithModelData(
            new ManagePasskeysController(
                $repository,
                $this->createMock(UriSigner::class),
            ),
            [
                'id' => 74205,
                'type' => 'manage_passkeys',
            ],
            adjustedContainer: $this->getAdjustedContainer($user, true, $credential),
            page: $this->createMock(PageModel::class),
            request: $request,
        );

        $this->assertTrue($response instanceof RedirectResponse);
    }

    private function getAdjustedContainer(User|null $user = null, bool|null $isFullyAuthenticated = null, WebauthnCredential|null $credential = null): ContainerBuilder
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
            ->method('isGranted')
            ->willReturnCallback(
                static function (string $attribute, mixed $subject) use ($isFullyAuthenticated, $credential): bool {
                    if ('IS_AUTHENTICATED_FULLY' === $attribute && null !== $isFullyAuthenticated) {
                        return $isFullyAuthenticated;
                    }

                    return ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP === $attribute && $credential;
                },
            )
        ;

        $container->set('security.authorization_checker', $authChecker);

        $contentUrlGenerator = $this->createMock(ContentUrlGenerator::class);
        $contentUrlGenerator
            ->method('generate')
            ->willReturnCallback(
                static fn (mixed $object, array $parameters): string => '/foobar?'.http_build_query($parameters),
            )
        ;

        $container->set('contao.routing.content_url_generator', $contentUrlGenerator);

        return $container;
    }
}
