<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor;

use Contao\CoreBundle\Entity\TrustedDevice;
use Contao\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\JwtTokenEncoder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RequestStack;
use UAParser\Parser;

class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TrustedDeviceTokenStorage
     */
    private $trustedTokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JwtTokenEncoder
     */
    private $jwtTokenEncoder;

    /**
     * @var FirewallMap
     */
    private $firewallMap;

    public function __construct(RequestStack $requestStack, TrustedDeviceTokenStorage $trustedTokenStorage, EntityManagerInterface $entityManager, JwtTokenEncoder $jwtTokenEncoder, FirewallMap $firewallMap)
    {
        $this->requestStack = $requestStack;
        $this->trustedTokenStorage = $trustedTokenStorage;
        $this->entityManager = $entityManager;
        $this->jwtTokenEncoder = $jwtTokenEncoder;
        $this->firewallMap = $firewallMap;
    }

    public function addTrustedDevice($user, string $firewallName): void
    {
        if (!$user instanceof User) {
            return;
        }

        $version = (int) $user->trustedTokenVersion;
        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');

        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        $this->trustedTokenStorage->addTrustedToken($user->username, $firewallName, $version);
        $currentTrustedDeviceToken = $this->getCurrentTrustedDeviceToken($user);

        // Check if already an earlier version of the trusted device exists
        $trustedDevice = $this->getCurrentTrustedDevice($user) ?? new TrustedDevice($user, $version);

        $trustedDevice
            ->setCreated(new \DateTime())
            ->setUserAgent($userAgent)
            ->setUaFamily($parsedUserAgent->ua->family)
            ->setOsFamily($parsedUserAgent->os->family)
            ->setDeviceFamily($parsedUserAgent->device->family)
        ;

        // Store the serialized cookie value if available
        if ($currentTrustedDeviceToken instanceof TrustedDeviceToken) {
            $trustedDevice->setCookieValue($currentTrustedDeviceToken->serialize());
        }

        $this->entityManager->persist($trustedDevice);
        $this->entityManager->flush();
    }

    public function isTrustedDevice($user, string $firewallName): bool
    {
        if (!($user instanceof User)) {
            return false;
        }

        $username = $user->username;
        $version = (int) $user->trustedTokenVersion;

        return $this->trustedTokenStorage->hasTrustedToken($username, $firewallName, $version);
    }

    public function clearTrustedDevices(User $user): void
    {
        $trustedDevices = $this->getTrustedDevices($user);

        foreach ($trustedDevices as $trustedDevice) {
            $this->entityManager->remove($trustedDevice);
        }

        $this->entityManager->flush();

        ++$user->trustedTokenVersion;
        $user->save();
    }

    public function getTrustedDevices(User $user)
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('td')
            ->from(TrustedDevice::class, 'td')
            ->andWhere('td.userClass = :userClass')
            ->andWhere('td.userId = :userId')
            ->setParameter('userClass', \get_class($user))
            ->setParameter('userId', (int) $user->id)
            ->getQuery()
            ->execute()
        ;
    }

    public function findExistingTrustedDevice(int $userId, string $cookieValue, int $version)
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('td')
            ->from(TrustedDevice::class, 'td')
            ->andWhere('td.userId = :userId')
            ->andWhere('td.cookieValue = :cookieValue')
            ->andWhere('td.version = :version')
            ->setParameter('userId', $userId)
            ->setParameter('cookieValue', $cookieValue)
            ->setParameter('version', $version)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getCurrentTrustedDevice(User $user): ?TrustedDevice
    {
        $trustedDeviceToken = $this->getCurrentTrustedDeviceToken($user);

        if ($trustedDeviceToken instanceof TrustedDeviceToken) {
            try {
                return $this->findExistingTrustedDevice((int) $user->id, $trustedDeviceToken->serialize(), (int) $user->trustedTokenVersion);
            } catch (NonUniqueResultException $exception) {
                return null;
            }
        }

        return null;
    }

    public function getCurrentTrustedDeviceToken(User $user): ?TrustedDeviceToken
    {
        $trustedTokenList = $this->getTrustedTokenList();

        /** @var FirewallConfig $firewallConfig */
        $firewallConfig = $this->firewallMap->getFirewallConfig($this->requestStack->getMasterRequest());

        /** @var TrustedDeviceToken $token */
        foreach ($trustedTokenList as $token) {
            if ($token->versionMatches((int) $user->trustedTokenVersion) && $token->authenticatesRealm($user->username, $firewallConfig->getName())) {
                return $token;
            }
        }

        return null;
    }

    private function getTrustedTokenList(): array
    {
        $cookie = $this->trustedTokenStorage->getCookieValue();

        $trustedTokenList = [];
        $trustedTokenEncodedList = explode(TrustedDeviceTokenStorage::TOKEN_DELIMITER, $cookie);

        foreach ($trustedTokenEncodedList as $trustedTokenEncoded) {
            $trustedToken = $this->jwtTokenEncoder->decodeToken($trustedTokenEncoded);

            if ($trustedToken && !$trustedToken->isExpired()) {
                $trustedTokenList[] = new TrustedDeviceToken($trustedToken);
            }
        }

        return $trustedTokenList;
    }
}
