<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Security;

use Contao\OAuthBundle\AuthorizationServer\Repository\AccessTokenRepository;
use Contao\OAuthBundle\KeyProvider;
use Contao\OAuthBundle\ResourceContext;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Symfony\Component\Clock\NativeClock;

/**
 * @internal
 */
class AccessTokenValidator
{
    public function __construct(
        private readonly KeyProvider $keys,
        private readonly ResourceContext $context,
        private readonly AccessTokenRepository $accessTokens,
    ) {
    }

    public function validate(string $jwt): array
    {
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->keys->getSigningKey()->getKeyContents()));

        try {
            $token = $config->parser()->parse($jwt);
        } catch (\Throwable $e) {
            throw new InvalidAccessTokenException('Malformed access token.', 0, $e);
        }

        if (!$token instanceof UnencryptedToken) {
            throw new InvalidAccessTokenException('Unexpected token type.');
        }

        $constraints = [
            new SignedWith($config->signer(), $config->verificationKey()),
            new StrictValidAt(new NativeClock()),
            new IssuedBy($this->context->getIssuer()),
            new PermittedFor($this->context->getResource()),
        ];

        if (!$config->validator()->validate($token, ...$constraints)) {
            throw new InvalidAccessTokenException('The access token is invalid.');
        }

        $claims = $token->claims();
        $jti = (string) $claims->get('jti');

        if ($this->accessTokens->isAccessTokenRevoked($jti)) {
            throw new InvalidAccessTokenException('The access token has been revoked.');
        }

        return [
            'user' => (string) $claims->get('sub'),
            'client_id' => (string) $claims->get('client_id'),
            'jti' => $jti,
        ];
    }
}
