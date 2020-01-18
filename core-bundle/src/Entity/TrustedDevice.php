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

use Contao\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
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

    public function __construct(User $user, int $version)
    {
        $this->userId = (int) $user->id;
        $this->version = $version;
        $this->created = new \DateTime();
        $this->userClass = \get_class($user);
    }

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

    public function getUserId(): int
    {
        return $this->userId;
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
}
