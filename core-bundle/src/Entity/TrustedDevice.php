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
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_trusted_device')]
#[Entity]
class TrustedDevice
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected int|null $id = null;

    #[Column(type: 'datetime')]
    protected \DateTimeInterface $created;

    #[Column(type: 'string', nullable: true)]
    protected string|null $userClass = null;

    #[Column(type: 'integer', nullable: true)]
    protected int|null $userId = null;

    #[Column(name: 'user_agent', type: 'text', nullable: true)]
    protected string|null $userAgent = null;

    #[Column(name: 'ua_family', type: 'string', nullable: true)]
    protected string|null $uaFamily = null;

    #[Column(name: 'os_family', type: 'string', nullable: true)]
    protected string|null $osFamily = null;

    #[Column(name: 'device_family', type: 'string', nullable: true)]
    protected string|null $deviceFamily = null;

    public function __construct(User $user)
    {
        $this->userId = $user->id;
        $this->created = new \DateTime();
        $this->userClass = $user::class;
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

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserAgent(string|null $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getUaFamily(): string|null
    {
        return $this->uaFamily;
    }

    public function setUaFamily(string|null $uaFamily): self
    {
        $this->uaFamily = $uaFamily;

        return $this;
    }

    public function getOsFamily(): string|null
    {
        return $this->osFamily;
    }

    public function setOsFamily(string|null $osFamily): self
    {
        $this->osFamily = $osFamily;

        return $this;
    }

    public function getDeviceFamily(): string|null
    {
        if ('Other' === $this->deviceFamily) {
            return '-';
        }

        return $this->deviceFamily;
    }

    public function setDeviceFamily(string|null $deviceFamily): self
    {
        $this->deviceFamily = $deviceFamily;

        return $this;
    }
}
