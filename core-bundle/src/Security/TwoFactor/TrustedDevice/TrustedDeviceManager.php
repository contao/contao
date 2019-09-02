<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor\TrustedDevice;

use Contao\BackendUser;
use Contao\CoreBundle\Entity\TrustedDevice;
use Contao\FrontendUser;
use Contao\User;
use Doctrine\ORM\EntityManagerInterface;
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
        if (!($user instanceof User)) {
            return;
        }

        $username = $user->getUsername();
        $version = $this->getTrustedTokenVersion($user);

        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');
        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        $geolocation = json_decode(file_get_contents('https://ipinfo.io/geo'), true);
        $country = null;

        if (\array_key_exists('country', $geolocation)) {
            $country = $geolocation['country'];
        }

        $this->trustedTokenStorage->addTrustedToken($username, $firewallName, $version);

        $trustedDevice = new TrustedDevice();
        $trustedDevice
            ->setCreated(new \DateTime())
            ->setCookieValue($this->trustedTokenStorage->getCookieValue())
            ->setUserAgent($userAgent)
            ->setUaFamily($parsedUserAgent->ua->family)
            ->setOsFamily($parsedUserAgent->os->family)
            ->setDeviceFamily($parsedUserAgent->device->family)
            ->setVersion($version)
            ->setCountry(strtoupper($country))
        ;

        if ($user instanceof BackendUser) {
            $trustedDevice->setUser((int) $user->id);
        }

        if ($user instanceof FrontendUser) {
            $trustedDevice->setMember((int) $user->id);
        }

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

    private function getTrustedTokenVersion($user): int
    {
        if ($user instanceof TrustedDeviceInterface) {
            return $user->getTrustedTokenVersion();
        }

        return self::DEFAULT_TOKEN_VERSION;
    }
}
