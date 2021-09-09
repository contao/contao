<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\BackendUser;
use Symfony\Contracts\EventDispatcher\Event;

class ImageSizesEvent extends Event
{
    private array $imageSizes;
    private ?BackendUser $user;

    public function __construct(array $imageSizes, BackendUser $user = null)
    {
        $this->imageSizes = $imageSizes;
        $this->user = $user;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getImageSizes(): array
    {
        return $this->imageSizes;
    }

    public function setImageSizes(array $imageSizes): void
    {
        $this->imageSizes = $imageSizes;
    }

    public function getUser(): BackendUser
    {
        return $this->user;
    }
}
