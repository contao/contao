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
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
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

    public function __construct(RequestStack $requestStack, TrustedDeviceTokenStorage $trustedTokenStorage, EntityManagerInterface $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->trustedTokenStorage = $trustedTokenStorage;
        $this->entityManager = $entityManager;
    }

    public function addTrustedDevice($user, string $firewallName): void
    {
        if (!$user instanceof User) {
            return;
        }

        $version = (int) $user->trustedTokenVersion;
        $oldCookieValue = $this->trustedTokenStorage->getCookieValue();
        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');

        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        $this->trustedTokenStorage->addTrustedToken($user->username, $firewallName, $version);

        // Check if already an earlier version of the trusted device exists
        try {
            $trustedDevice = $this->findExistingTrustedDevice((int) $user->id, $oldCookieValue, $version) ?? new TrustedDevice($user, $version);
        } catch (NonUniqueResultException $exception) {
            $trustedDevice = new TrustedDevice($user, $version);
        }

        $trustedDevice
            ->setCreated(new \DateTime())
            ->setCookieValue($this->trustedTokenStorage->getCookieValue())
            ->setUserAgent($userAgent)
            ->setUaFamily($parsedUserAgent->ua->family)
            ->setOsFamily($parsedUserAgent->os->family)
            ->setDeviceFamily($parsedUserAgent->device->family)
        ;

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
}
