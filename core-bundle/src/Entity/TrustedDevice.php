<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Contao\CoreBundle\Repository\TrustedDeviceRepository")
 * @ORM\Table(name="tl_trusted_device")
 */
class TrustedDevice
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $userClass;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(type="text", name="cookie_value")
     */
    protected $cookieValue;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $version;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", name="user_agent", nullable=true)
     */
    protected $userAgent;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="ua_family", nullable=true)
     */
    protected $uaFamily;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="os_family", nullable=true)
     */
    protected $osFamily;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="device_family", nullable=true)
     */
    protected $deviceFamily;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $country;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $city;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    public function setCreated(\DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUserClass(): string
    {
        return $this->userClass;
    }

    public function setUserClass(?string $userClass): self
    {
        $this->userClass = $userClass;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCookieValue(): string
    {
        return $this->cookieValue;
    }

    public function setCookieValue(string $cookieValue): self
    {
        $this->cookieValue = $cookieValue;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUaFamily(): ?string
    {
        return $this->uaFamily;
    }

    public function setUaFamily(?string $uaFamily): self
    {
        $this->uaFamily = $uaFamily;

        return $this;
    }

    public function getOsFamily(): ?string
    {
        return $this->osFamily;
    }

    public function setOsFamily(?string $osFamily): self
    {
        $this->osFamily = $osFamily;

        return $this;
    }

    public function getDeviceFamily(): ?string
    {
        return $this->deviceFamily;
    }

    public function setDeviceFamily(?string $deviceFamily): self
    {
        $this->deviceFamily = $deviceFamily;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }
}
