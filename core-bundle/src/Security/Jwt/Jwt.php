<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Jwt;

use Contao\System;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class Jwt
{
    private static $issuedBy = 'Contao';
    private static $permittedFor = 'https://contao.org';

    private static function getDefaultKeyString(): string
    {
        $keyString = System::getContainer()->getParameter('kernel.secret');
        return substr($keyString, 10, 32);
    }

    private static function getConfig(): Configuration
    {
        return Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::getDefaultKeyString()));
    }

    public static function generate(string $jti, int $nbf = 3600, array $claims = array()): string
    {
        $time = time();

        $config = self::getConfig();

        $issuesAt = (new \DateTimeImmutable())->setTimestamp($time);
        $usedAfter = (new \DateTimeImmutable())->setTimestamp($time + 0);
        $expiresAt = (new \DateTimeImmutable())->setTimestamp($time + $nbf);

        $builder = $config->builder();
        $builder->issuedBy(self::$issuedBy); // iss claim
        $builder->permittedFor(self::$permittedFor); // iss claim
        $builder->identifiedBy($jti); // jti claim
        $builder->issuedAt($issuesAt); // iat claim

        $builder->canOnlyBeUsedAfter($usedAfter); // Configures the time that the token can be used (nbf claim)
        if ($nbf > 0) {
            $builder->expiresAt($expiresAt); // Configures the expiration time of the token (exp claim)
        }

        if (\count($claims) > 0) {
            foreach ($claims as $keyClaim => $valueClaim) {
                $builder->withClaim($keyClaim, $valueClaim);
            }
        }

        $token = $builder->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    public static function parse(string $token): Token
    {
        $config = self::getConfig();
        $parser = $config->parser();

        return $parser->parse($token);
    }

    public static function getClaim(string $token, string $name)
    {
        try {

            $tokenObject = self::parse($token);
            $value = $tokenObject->claims()->get($name);

        } catch (\Exception $ex) {
            $value = null;
        }

        return $value;
    }

    public static function validateAndVerify(string $token, string $jti): bool
    {
        $tokenObject = self::parse($token);

        $config = self::getConfig();

        $issuedByConstraints = new IssuedBy(self::$issuedBy);
        $permittedForConstraints = new PermittedFor(self::$permittedFor);
        $identifiedByConstraints = new IdentifiedBy($jti);

        $validator = $config->validator();

        try {

            $signer = new SignedWith($config->signer(), InMemory::plainText(self::getDefaultKeyString()));
            $validator->assert($tokenObject, $signer);

            $value = $validator->validate($tokenObject, $issuedByConstraints, $permittedForConstraints, $identifiedByConstraints);

            if ($value == true) {
                $now = (new \DateTimeImmutable())->setTimestamp(time());
                $value = !$tokenObject->isExpired($now);
            }

        } catch (\Exception $ex) {
            $value = false;
        }

        return $value;
    }
}
