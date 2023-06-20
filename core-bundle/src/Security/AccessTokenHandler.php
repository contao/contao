<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Entity\AccessToken;
use Contao\CoreBundle\Repository\AccessTokenRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    /**
     * @param non-empty-string $secret
     */
    public function __construct(
        private readonly AccessTokenRepository $accessTokenRepository,
        string $secret,
        private Configuration|null $configuration = null,
    ) {
        $this->configuration = $configuration ?: Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
        $this->configuration->setValidationConstraints(new SignedWith($this->configuration->signer(), $this->configuration->signingKey()));
    }

    public function createTokenForUsername(string $username): string
    {
        $plainToken = $this->issueToken(['username' => $username]);
        $claims = $plainToken->claims();

        $accessToken = new AccessToken($plainToken->toString(), $username, $claims->get(RegisteredClaims::EXPIRATION_TIME));
        $this->accessTokenRepository->persist($accessToken);

        return $plainToken->toString();
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
        } catch (CannotDecodeContent|InvalidTokenStructure|UnsupportedHeaderFound) {
            return null;
        }

        return $token;
    }

    public function validateToken(UnencryptedToken $token, string $username): bool
    {
        $claims = $token->claims();
        $now = new \DateTimeImmutable();

        if (!$claims->has(RegisteredClaims::EXPIRATION_TIME)) {
            return false;
        }

        if ($now >= $claims->get(RegisteredClaims::EXPIRATION_TIME)) {
            return false;
        }

        if (!$claims->has('username')) {
            return false;
        }

        if ($username !== $claims->get('username')) {
            return false;
        }

        return true;
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $token = $this->accessTokenRepository->findByToken($accessToken);
        $unencryptedToken = $this->parseToken($token->getToken());

        if (null === $unencryptedToken || !$this->validateToken($unencryptedToken, $token->getUsername())) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($token->getUsername());
    }
}
