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
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $member;

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
     * @var string
     *
     * @ORM\Column(type="text", name="user_agent")
     */
    protected $userAgent;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $uaFamily;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $osFamily;

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

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMember(): int
    {
        return $this->member;
    }

    public function setMember(int $member): self
    {
        $this->member = $member;

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

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUaFamily(): string
    {
        return $this->uaFamily;
    }

    public function setUaFamily(string $uaFamily): self
    {
        $this->uaFamily = $uaFamily;

        return $this;
    }

    public function getOsFamily(): string
    {
        return $this->osFamily;
    }

    public function setOsFamily(string $osFamily): self
    {
        $this->osFamily = $osFamily;

        return $this;
    }
}
