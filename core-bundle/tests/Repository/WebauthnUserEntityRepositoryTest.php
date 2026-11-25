<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Repository;

use Contao\CoreBundle\Repository\WebauthnUserEntityRepository;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnUserEntityRepositoryTest extends TestCase
{
    #[DataProvider('lookupProvider')]
    public function testOnlyQueriesTheProviderMatchingTheScope(string $method, string $identifier, string|null $scope, string|null $expectedProvider): void
    {
        $user = $this->createClassWithPropertiesStub(User::class);
        $user->username = 'j.doe';

        $user
            ->method('getPasskeyUserHandle')
            ->willReturn($scope.'.42')
        ;
        $user
            ->method('getDisplayName')
            ->willReturn('Jane Doe')
        ;

        $backendUserProvider = $this->createMock(ContaoUserProvider::class);
        $frontendUserProvider = $this->createMock(ContaoUserProvider::class);
        $providerMethod = 'findOneByUsername' === $method ? 'loadUserByIdentifier' : 'loadUserById';
        $providerArgument = 'findOneByUsername' === $method ? $identifier : 42;

        foreach (['backend' => $backendUserProvider, 'frontend' => $frontendUserProvider] as $providerScope => $provider) {
            $provider
                ->expects($expectedProvider === $providerScope ? $this->once() : $this->never())
                ->method($providerMethod)
                ->with($providerArgument)
                ->willReturn($user)
            ;
            $provider
                ->expects($this->never())
                ->method('loadUserByIdentifier' === $providerMethod ? 'loadUserById' : 'loadUserByIdentifier')
            ;
        }

        $scopeMatcher = $this->createStub(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn('backend' === $scope)
        ;
        $scopeMatcher
            ->method('isFrontendRequest')
            ->willReturn('frontend' === $scope)
        ;

        $repository = new WebauthnUserEntityRepository($backendUserProvider, $frontendUserProvider, $scopeMatcher);
        $entity = $repository->$method($identifier);

        if (null === $expectedProvider) {
            $this->assertNull($entity);
        } else {
            $this->assertEquals(new PublicKeyCredentialUserEntity('j.doe', $scope.'.42', 'Jane Doe'), $entity);
        }
    }

    public static function lookupProvider(): iterable
    {
        yield 'frontend username' => ['findOneByUsername', 'j.doe', 'frontend', 'frontend'];
        yield 'backend username' => ['findOneByUsername', 'j.doe', 'backend', 'backend'];
        yield 'username without scope' => ['findOneByUsername', 'j.doe', null, null];
        yield 'username with unsupported scope' => ['findOneByUsername', 'j.doe', 'other', null];

        yield 'frontend handle in frontend scope' => ['findOneByUserHandle', 'frontend.42', 'frontend', 'frontend'];
        yield 'backend handle in backend scope' => ['findOneByUserHandle', 'backend.42', 'backend', 'backend'];
        yield 'backend handle in frontend scope' => ['findOneByUserHandle', 'backend.42', 'frontend', null];
        yield 'frontend handle in backend scope' => ['findOneByUserHandle', 'frontend.42', 'backend', null];
        yield 'frontend handle without scope' => ['findOneByUserHandle', 'frontend.42', null, null];
        yield 'backend handle without scope' => ['findOneByUserHandle', 'backend.42', null, null];
        yield 'frontend handle with unsupported scope' => ['findOneByUserHandle', 'frontend.42', 'other', null];
        yield 'backend handle with unsupported scope' => ['findOneByUserHandle', 'backend.42', 'other', null];
        yield 'unknown handle in frontend scope' => ['findOneByUserHandle', 'other.42', 'frontend', null];
        yield 'unknown handle in backend scope' => ['findOneByUserHandle', 'other.42', 'backend', null];
    }
}
