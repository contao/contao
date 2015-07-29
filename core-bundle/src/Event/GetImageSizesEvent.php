<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to execute logic when a widget is parsed.
 *
 * @author Kamil Kuzminski <https://github.com/qzminski>
 */
class GetImageSizesEvent extends Event
{
    /**
     * @var array
     */
    private $imageSizes;

    /**
     * @var bool
     */
    private $checkPermission;

    /**
     * Constructor.
     *
     * @param array $imageSizes
     * @param bool  $checkPermission
     */
    public function __construct(array $imageSizes, $checkPermission)
    {
        $this->imageSizes      = $imageSizes;
        $this->checkPermission = $checkPermission;
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
     * Get the check permission flag
     *
     * @return bool
     */
    public function isCheckPermission()
    {
        return $this->checkPermission;
    }

    /**
     * Set the check permission flag
     *
     * @param bool $checkPermission
     */
    public function setCheckPermission($checkPermission)
    {
        $this->checkPermission = $checkPermission;
    }
}
