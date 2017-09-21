<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Contao\BackendUser;
use Symfony\Component\EventDispatcher\Event;

class ImageSizesEvent extends Event
{
    /**
     * @var array
     */
    private $imageSizes;

    /**
     * @var BackendUser
     */
    private $user;

    /**
     * @param array            $imageSizes
     * @param BackendUser|null $user
     */
    public function __construct(array $imageSizes, BackendUser $user = null)
    {
        $this->imageSizes = $imageSizes;
        $this->user = $user;
    }

    /**
     * Returns the image sizes.
     *
     * @return array
     */
    public function getImageSizes(): array
    {
        return $this->imageSizes;
    }

    /**
     * Sets the image sizes.
     *
     * @param array $imageSizes
     */
    public function setImageSizes(array $imageSizes): void
    {
        $this->imageSizes = $imageSizes;
    }

    /**
     * Returns the back end user object.
     *
     * @return BackendUser
     */
    public function getUser(): BackendUser
    {
        return $this->user;
    }
}
