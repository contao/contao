<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Contao\BackendUser;
use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to filter image sizes.
 *
 * @author Kamil Kuzminski <https://github.com/qzminski>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
     * Constructor.
     *
     * @param array       $imageSizes
     * @param BackendUser $user
     */
    public function __construct(array $imageSizes, BackendUser $user = null)
    {
        $this->imageSizes = $imageSizes;
        $this->user       = $user;
    }

    /**
     * Get the image sizes
     *
     * @return array
     */
    public function getImageSizes()
    {
        return $this->imageSizes;
    }

    /**
     * Set the image sizes
     *
     * @param array $imageSizes
     */
    public function setImageSizes(array $imageSizes)
    {
        $this->imageSizes = $imageSizes;
    }

    /**
     * Gets the backend user if available.
     *
     * @return BackendUser|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
