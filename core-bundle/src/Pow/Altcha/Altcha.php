<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Pow\Altcha;

use Contao\CoreBundle\Entity\PowAltcha;
use Contao\CoreBundle\Pow\Altcha\Config\AlgorithmConfig;
use Contao\CoreBundle\Pow\Altcha\Exception\InvalidAlgorithmException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

class Altcha
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
        private readonly string $secret,
        private readonly string $altchaAlgorithm,
        private readonly int $altchaRangeMin,
        private readonly int $altchaRangeMax,
        private readonly int $altchaChallengeExpiry,
    ) {
    }

    /**
     * @throws InvalidAlgorithmException
     * @throws Exception
     */
    public function createChallenge(string|null $salt = null, int|null $number = null): AltchaChallenge
    {
        $salt = $salt ?? bin2hex(random_bytes(12));
        $number = $number ?? random_int($this->altchaRangeMin, $this->altchaRangeMax);

        if (!\in_array($this->altchaAlgorithm, AlgorithmConfig::ALGORITHM_ALL, true)) {
            $strError = 'Invalid algorithm selected. It has to be set to one of these "%s".';

            throw new InvalidAlgorithmException(sprintf($strError, implode('", "', AlgorithmConfig::ALGORITHM_ALL)));
        }

        $algorithm = str_replace('-', '', strtolower($this->altchaAlgorithm));

        // Generate the challenge
        $challenge = hash($algorithm, $salt.$number);

        // Generate the signature
        $signature = hash_hmac($algorithm, $challenge, $this->secret);

        // The challenge expires in 1 hour (default) and it must be saved in the database
        // to prevent replay attacks.
        $expiryDate = date('Y-m-d\\TH:i:sP', time() + $this->altchaChallengeExpiry);
        $objPowAltcha = new PowAltcha($challenge, new \DateTimeImmutable($expiryDate));

        // Insert record into tl_pow_altcha
        $this->entityManager->persist($objPowAltcha);
        $this->entityManager->flush();

        return new AltchaChallenge($this->altchaAlgorithm, $challenge, $salt, $signature);
    }

    public function isValidPayload(string $payload): bool
    {
        $json = json_decode(base64_decode($payload, true), true);

        if (null === $json) {
            return false;
        }

        if ($this->isReplay($json)) {
            return false;
        }

        $rowsAffected = $this->connection->executeStatement(
            'UPDATE tl_pow_altcha SET solved = :solved_true WHERE challenge = :challenge AND expires > :expires AND solved = :solved_false',
            [
                'solved_true' => true,
                'challenge' => $json['challenge'],
                'expires' => new \DateTimeImmutable(),
                'solved_false' => false,
            ],
            [
                'solved_true' => Types::BOOLEAN,
                'challenge' => Types::STRING,
                'expires' => Types::DATE_IMMUTABLE,
                'solved_false' => Types::BOOLEAN,
            ],
        );

        // Return false, if challenge has expired or has already been solved
        if (1 !== $rowsAffected) {
            return false;
        }

        $check = $this->createChallenge($json['salt'], $json['number']);

        return $json['algorithm'] === $check->getAlgorithm()
            && $json['challenge'] === $check->getChallenge()
            && $json['signature'] === $check->getSignature();
    }

    private function isReplay(array $json): bool
    {
        $challenge = $json['challenge'] ?? '';

        return false !== $this->connection->fetchOne(
            'SELECT id FROM tl_pow_altcha WHERE challenge = :challenge AND solved = :solved',
            [
                'challenge' => $challenge,
                'solved' => true,
            ],
            [
                'challenge' => Types::STRING,
                'solved' => Types::BOOLEAN,
            ],
        );
    }
}
