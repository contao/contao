<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Altcha;

use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Altcha\Exception\InvalidAlgorithmException;
use Contao\CoreBundle\Repository\AltchaRepository;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManager;

class AltchaTest extends TestCase
{
    public function testCreatesTheChallenge(): void
    {
        $challenge = $this->getAltcha()->createChallenge();

        $this->assertSame(
            [
                'algorithm' => $challenge->getAlgorithm(),
                'challenge' => $challenge->getChallenge(),
                'salt' => $challenge->getSalt(),
                'signature' => $challenge->getSignature(),
            ],
            $challenge->toArray(),
        );
    }

    public function testFailsToCreateTheChallengeIfTheAlgorithmIsInvalid(): void
    {
        $altcha = $this->getAltcha(algorithm: 'SHA-128');

        $this->expectException(InvalidAlgorithmException::class);

        $altcha->createChallenge();
    }

    public function testValidatesThePayload(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->once())
            ->method('isReplay')
            ->willReturn(false)
        ;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $altcha = $this->getAltcha($repository, $entityManager);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;

        $this->assertTrue($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    public function testDoesNotValidateThePayloadIfItIsInvalid(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->never())
            ->method('isReplay')
        ;

        $altcha = $this->getAltcha($repository);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid payload given.');

        $altcha->validate(base64_encode(json_encode($challenge->toArray(), JSON_THROW_ON_ERROR)));
    }

    public function testDoesNotValidateThePayloadUponRelayAttacks(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->once())
            ->method('isReplay')
            ->willReturn(true)
        ;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $altcha = $this->getAltcha($repository, $entityManager);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;

        $this->assertFalse($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    public function testDoesNotValidateThePayloadIfTheChallengeHasExpired(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->never())
            ->method('isReplay')
        ;

        $altcha = $this->getAltcha($repository);
        $challenge = $altcha->createChallenge('salt?expires='.(time() - 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;

        $this->assertFalse($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    public function testDoesNotValidateThePayloadIfTheAlgorithmDoesNotMatch(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->once())
            ->method('isReplay')
            ->willReturn(false)
        ;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $altcha = $this->getAltcha($repository, $entityManager);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;
        $payload['algorithm'] = 'SHA-128';

        $this->assertFalse($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    public function testDoesNotValidateThePayloadIfTheChallengeDoesNotMatch(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->once())
            ->method('isReplay')
            ->willReturn(false)
        ;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $altcha = $this->getAltcha($repository, $entityManager);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;
        $payload['challenge'] = 'foobar';

        $this->assertFalse($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    public function testDoesNotValidateThePayloadIfTheSignatureDoesNotMatch(): void
    {
        $repository = $this->createMock(AltchaRepository::class);
        $repository
            ->expects($this->once())
            ->method('isReplay')
            ->willReturn(false)
        ;

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $altcha = $this->getAltcha($repository, $entityManager);
        $challenge = $altcha->createChallenge('salt?expires='.(time() + 600), 42);

        $payload = $challenge->toArray();
        $payload['number'] = 42;
        $payload['signature'] = 'foobar';

        $this->assertFalse($altcha->validate(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))));
    }

    private function getAltcha(AltchaRepository|null $repository = null, EntityManager|null $entityManager = null, string|null $algorithm = 'SHA-256'): Altcha
    {
        $repository ??= $this->createMock(AltchaRepository::class);
        $entityManager ??= $this->createMock(EntityManager::class);

        return new Altcha($repository, $entityManager, 'secret', $algorithm, 100_000, 3600);
    }
}
