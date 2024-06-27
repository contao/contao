<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Altcha;

use Contao\CoreBundle\Altcha\Config\Algorithm;
use Contao\CoreBundle\Altcha\Exception\ChallengeExpiredException;
use Contao\CoreBundle\Altcha\Exception\InvalidAlgorithmException;
use Contao\CoreBundle\Entity\Altcha as AltchaEntity;
use Contao\CoreBundle\Repository\AltchaRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @internal
 */
class Altcha
{
    public function __construct(
        private readonly AltchaRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $secret,
        private readonly string $altchaAlgorithm,
        private readonly int $altchaRangeMin,
        private readonly int $altchaRangeMax,
        private readonly int $altchaChallengeExpiry,
    ) {
    }

    /**
     * @throws InvalidAlgorithmException
     */
    public function createChallenge(string|null $salt = null, int|null $number = null): AltchaChallenge
    {
        $algorithms = Algorithm::values();

        if (!\in_array($this->altchaAlgorithm, $algorithms, true)) {
            throw new InvalidAlgorithmException(sprintf('Invalid algorithm selected. It has to be one of these: %s', implode(', ', $algorithms)));
        }

        if (!$salt) {
            $salt = bin2hex(random_bytes(12)).'?expires='.strtotime("now +$this->altchaChallengeExpiry seconds");
        } else {
            parse_str(explode('?', $salt, 2)[1] ?? '', $parameters);

            if ((int) ($parameters['expires'] ?? 0) < time()) {
                throw new ChallengeExpiredException('The given challange has expired. Please try again.');
            }
        }

        $number ??= random_int($this->altchaRangeMin, $this->altchaRangeMax);

        $algorithm = str_replace('-', '', strtolower($this->altchaAlgorithm));
        $challenge = hash($algorithm, $salt.$number);
        $signature = hash_hmac($algorithm, $challenge, $this->secret);

        return new AltchaChallenge($this->altchaAlgorithm, $challenge, $salt, $signature);
    }

    public function validate(string $payload): bool
    {
        $json = json_decode(base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);

        if (null === $json) {
            return false;
        }

        $challenge = $json['challenge'] ?? '';

        if ($this->repository->isReplay($challenge)) {
            return false;
        }

        $check = $this->createChallenge($json['salt'], $json['number']);

        if ($json['algorithm'] !== $check->getAlgorithm()) {
            return false;
        }

        if ($json['challenge'] !== $check->getChallenge()) {
            return false;
        }

        if ($json['signature'] !== $check->getSignature()) {
            return false;
        }

        $entity = new AltchaEntity($challenge, new \DateTimeImmutable("now +$this->altchaChallengeExpiry seconds"));

        // Save the solved challenge in the database to prevent replay attacks
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return true;
    }
}
