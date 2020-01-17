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
use Contao\CoreBundle\Repository\TrustedDeviceRepository;
use Contao\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Scheb\TwoFactorBundle\Model\TrustedDeviceInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use UAParser\Parser;

class TrustedDeviceManager implements TrustedDeviceManagerInterface
{
    private const DEFAULT_TOKEN_VERSION = 0;

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
        if (!$user instanceof User || !$user instanceof TrustedDeviceInterface) {
            return;
        }

        /** @var TrustedDeviceRepository $trustedDeviceRepository */
        $trustedDeviceRepository = $this->entityManager->getRepository(TrustedDevice::class);

        $username = $user->getUsername();
        $version = $this->getTrustedTokenVersion($user);
        $oldCookieValue = $this->trustedTokenStorage->getCookieValue();

        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');
        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        $this->trustedTokenStorage->addTrustedToken($username, $firewallName, $version);

        // Check if already an earlier version of the trusted device exists
        try {
            $trustedDevice = $trustedDeviceRepository->findExisting((int) $user->id, $oldCookieValue, $version) ?? new TrustedDevice();
        } catch (NonUniqueResultException $exception) {
            $trustedDevice = new TrustedDevice();
        }

        $trustedDevice
            ->setUserId((int) $user->id)
            ->setUserClass(\get_class($user))
            ->setCreated(new \DateTime())
            ->setCookieValue($this->trustedTokenStorage->getCookieValue())
            ->setUserAgent($userAgent)
            ->setUaFamily($parsedUserAgent->ua->family)
            ->setOsFamily($parsedUserAgent->os->family)
            ->setDeviceFamily($parsedUserAgent->device->family)
            ->setVersion($version)
        ;

        $this->entityManager->persist($trustedDevice);
        $this->entityManager->flush();
    }

    public function isTrustedDevice($user, string $firewallName): bool
    {
        if (!($user instanceof User)) {
            return false;
        }

        $username = $user->getUsername();
        $version = $this->getTrustedTokenVersion($user);

        return $this->trustedTokenStorage->hasTrustedToken($username, $firewallName, $version);
    }

    public function clearTrustedDevices(User $user): void
    {
        $trustedDevices = $this->getTrustedDevices($user);

        foreach ($trustedDevices as $trustedDevice) {
            $this->entityManager->remove($trustedDevice);
        }

        $this->entityManager->flush();

        ++$user->trustedVersion;
        $user->save();
    }

    public function getTrustedDevices(User $user)
    {
        /** @var TrustedDeviceRepository $trustedDeviceRepository */
        $trustedDeviceRepository = $this->entityManager->getRepository(TrustedDevice::class);

        return $trustedDeviceRepository->findForUser($user);
    }

    private function getTrustedTokenVersion($user): int
    {
        if ($user instanceof TrustedDeviceInterface) {
            return $user->getTrustedTokenVersion();
        }

        return self::DEFAULT_TOKEN_VERSION;
    }
}
