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

class AltchaChallenge
{
    public function __construct(
        protected readonly string $algorithm,
        protected readonly string $challenge,
        protected readonly string $salt,
        protected readonly string $signature,
    ) {
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getChallenge(): string
    {
        return $this->challenge;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function toArray(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'challenge' => $this->challenge,
            'salt' => $this->salt,
            'signature' => $this->signature,
        ];
    }
}
