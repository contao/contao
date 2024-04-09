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

use Contao\CoreBundle\Altcha\Config\AlgorithmConfig;
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
        $salt ??= bin2hex(random_bytes(12));
        $number ??= random_int($this->altchaRangeMin, $this->altchaRangeMax);
        $algorithms = array_column(AlgorithmConfig::cases(), 'value');

        if (!\in_array($this->altchaAlgorithm, $algorithms, true)) {
            $strError = 'Invalid algorithm selected. It has to be set to one of these "%s".';

            throw new InvalidAlgorithmException(sprintf($strError, implode('", "', $algorithms)));
        }

        $algorithm = str_replace('-', '', strtolower($this->altchaAlgorithm));

        // Generate the challenge
        $challenge = hash($algorithm, $salt.$number);

        // Generate the signature
        $signature = hash_hmac($algorithm, $challenge, $this->secret);

        // The challenge expires in 1 hour (default) and it must be saved in the database
        // to prevent replay attacks.
        $expiryDate = date('Y-m-d\\TH:i:sP', time() + $this->altchaChallengeExpiry);
        $objAltcha = new AltchaEntity($challenge, new \DateTimeImmutable($expiryDate));

        // Insert record into tl_altcha
        $this->entityManager->persist($objAltcha);
        $this->entityManager->flush();

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

        // Return false, if challenge has expired or has already been solved
        if (1 !== $this->repository->markChallengeAsSolved($challenge)) {
            return false;
        }

        $check = $this->createChallenge($json['salt'], $json['number']);

        return $json['algorithm'] === $check->getAlgorithm()
            && $json['challenge'] === $check->getChallenge()
            && $json['signature'] === $check->getSignature();
    }
}
