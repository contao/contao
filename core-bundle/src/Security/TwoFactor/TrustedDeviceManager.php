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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use UAParser\Parser;

class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    private RequestStack $requestStack;
    private TrustedDeviceTokenStorage $trustedTokenStorage;
    private EntityManagerInterface $entityManager;

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

        $userAgent = $this->requestStack->getMainRequest()->headers->get('User-Agent');

        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        try {
            $this->trustedTokenStorage->addTrustedToken((string) $user->id, $firewallName, (int) $user->trustedTokenVersion);
        } catch (InvalidKeyProvided $exception) {
            throw new InvalidKeyProvided('Failed to store trusted token. Make sure your APP_SECRET is at least 32 characters long. '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        $trustedDevice = new TrustedDevice($user);
        $trustedDevice
            ->setCreated(new \DateTime())
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

        return $this->trustedTokenStorage->hasTrustedToken((string) $user->id, $firewallName, (int) $user->trustedTokenVersion);
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

    /**
     * @return Collection<int, TrustedDevice>
     */
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

    public function canSetTrustedDevice($user, Request $request, string $firewallName): bool
    {
        return true;
    }
}
