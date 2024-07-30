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
        private readonly int $altchaRangeMax,
        private readonly int $altchaChallengeExpiry,
    ) {
    }

    public function getRangeMax(): int
    {
        return $this->altchaRangeMax;
    }

    /**
     * @throws InvalidAlgorithmException
     */
    public function createChallenge(string|null $salt = null, int|null $number = null): AltchaChallenge
    {
        $algorithms = Algorithm::values();

        if (!\in_array($this->altchaAlgorithm, $algorithms, true)) {
            throw new InvalidAlgorithmException(\sprintf('Invalid algorithm selected. It has to be one of these: %s', implode(', ', $algorithms)));
        }

        if (!$salt) {
            $salt = bin2hex(random_bytes(12)).'?expires='.strtotime("now +$this->altchaChallengeExpiry seconds");
        }

        $number ??= random_int(0, $this->altchaRangeMax);

        $algorithm = str_replace('-', '', strtolower($this->altchaAlgorithm));
        $challenge = hash($algorithm, $salt.$number);
        $signature = hash_hmac($algorithm, $challenge, $this->secret);

        return new AltchaChallenge($this->altchaAlgorithm, $challenge, $salt, $signature);
    }

    public function validate(string $payload): bool
    {
        $json = json_decode(base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);

        foreach (['challenge', 'salt', 'algorithm', 'signature', 'number'] as $key) {
            if (!isset($json[$key])) {
                throw new \InvalidArgumentException('Invalid payload given.');
            }
        }

        parse_str(explode('?', $json['salt'], 2)[1] ?? '', $parameters);
        $expiry = (int) ($parameters['expires'] ?? 0);

        if ($expiry < time()) {
            return false;
        }

        if ($this->repository->isReplay($json['challenge'])) {
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

        $entity = new AltchaEntity($json['challenge'], (new \DateTimeImmutable())->setTimestamp($expiry));

        // Save the solved challenge in the database to prevent replay attacks
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return true;
    }
}
