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

use Contao\CoreBundle\Entity\TrustedDevice;
use Contao\CoreBundle\Repository\TrustedDeviceRepository;
use Contao\User;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Exception;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
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

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(RequestStack $requestStack, TrustedDeviceTokenStorage $trustedTokenStorage, EntityManagerInterface $entityManager, MessageFactory $messageFactory, HttpClient $httpClient)
    {
        $this->requestStack = $requestStack;
        $this->trustedTokenStorage = $trustedTokenStorage;
        $this->entityManager = $entityManager;
        $this->messageFactory = $messageFactory;
        $this->httpClient = $httpClient;
    }

    public function addTrustedDevice($user, string $firewallName): void
    {
        if (!$user instanceof User || !$user instanceof TrustedDeviceInterface) {
            return;
        }

        $username = $user->getUsername();
        $version = $this->getTrustedTokenVersion($user);

        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');
        $parser = Parser::create();
        $parsedUserAgent = $parser->parse($userAgent);

        $geolocation = $this->getGeoLocation();
        $country = null;

        if (\array_key_exists('country', $geolocation)) {
            $country = $geolocation['country'];
        }

        $this->trustedTokenStorage->addTrustedToken($username, $firewallName, $version);

        $trustedDevice = new TrustedDevice();
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
            ->setCountry(strtolower($country))
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
        /** @var TrustedDeviceRepository $trustedDeviceRepository */
        $trustedDeviceRepository = $this->entityManager->getRepository(TrustedDevice::class);
        $trustedDevices = $trustedDeviceRepository->findForUser($user);

        foreach ($trustedDevices as $trustedDevice) {
            $this->entityManager->remove($trustedDevice);
        }

        $this->entityManager->flush();

        ++$user->trustedVersion;
        $user->save();
    }

    private function getTrustedTokenVersion($user): int
    {
        if ($user instanceof TrustedDeviceInterface) {
            return $user->getTrustedTokenVersion();
        }

        return self::DEFAULT_TOKEN_VERSION;
    }

    private function getGeoLocation(): array
    {
        $geolocation = [];

        try {
            $response = $this->httpClient->sendRequest(
                $this->messageFactory->createRequest('GET', 'https://ipinfo.io/geo')
            );
        } catch (Exception $exception) {
            return $geolocation;
        } catch (\Exception $exception) {
            return $geolocation;
        }

        if (200 === $response->getStatusCode()) {
            $geolocation = json_decode($response->getBody()->getContents(), true);
        }

        return $geolocation;
    }
}
