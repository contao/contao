<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JwtManager
{
    /**
     * @param non-empty-string $secret
     */
    public function __construct(string $secret, private Configuration|null $configuration = null)
    {
        $this->configuration = $configuration ?: Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
        $this->configuration->setValidationConstraints(new SignedWith($this->configuration->signer(), $this->configuration->signingKey()));
    }

    public function getJwtTokenForUsername(string $username): string
    {
        return $this->issueToken(['username' => $username])->toString();
    }

    public function issueToken(array $payload = []): Plain
    {
        $builder = $this->configuration->builder();

        foreach ($payload as $k => $v) {
            $builder = $builder->withClaim($k, $v);
        }

        return $builder
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('now +30 minutes'))
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
        ;
    }

    public function parseToken(string $data): UnencryptedToken|null
    {
        $parser = new Parser(new JoseEncoder());

        try {
            /** @var UnencryptedToken $token */
            $token = $parser->parse($data);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound) {
            return null;
        }

        return $token;
    }
}
