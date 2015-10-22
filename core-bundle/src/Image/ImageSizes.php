<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\ImageSizesEvent;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ImageSizes
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Kamil Kuzminski <https://github.com/qzminski>
 */
class ImageSizes
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $options;

    /**
     * Constructor.
     *
     * @param Connection               $db
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(Connection $db, EventDispatcherInterface $eventDispatcher)
    {
        $this->db              = $db;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getAllOptions()
    {
        $this->loadOptions();

        $event = new ImageSizesEvent($this->options);
        $this->eventDispatcher->dispatch(ContaoCoreEvents::IMAGE_SIZES_ALL, $event);

        return $event->getImageSizes();
    }

    public function getOptionsForUser(BackendUser $user)
    {
        $this->loadOptions();

        $options = $user->isAdmin ? $this->options : $this->filterOptions(deserialize($user->imageSizes, true));

        $event = new ImageSizesEvent($options, $user);
        $this->eventDispatcher->dispatch(ContaoCoreEvents::IMAGE_SIZES_USER, $event);

        return $event->getImageSizes();
    }

    private function loadOptions()
    {
        if (null !== $this->options) {
            return;
        }

        try {
            $sizes = array();

            $rows = $this->db->fetchAll(
                "SELECT id, name, width, height FROM tl_image_size ORDER BY pid, name"
            );

            foreach ($rows as $imageSize) {
                $sizes[$imageSize['id']] = $imageSize['name'];
                $sizes[$imageSize['id']] .= ' (' . $imageSize['width'] . 'x' . $imageSize['height'] . ')';
            }

            $this->options = array_merge(array('image_sizes' => $sizes), $GLOBALS['TL_CROP']);

        } catch (\Exception $e) {
            $this->options = $GLOBALS['TL_CROP'];
        }
    }

    private function filterOptions(array $allowedSizes)
    {
        if (empty($allowedSizes)) {
            return [];
        }

        $filteredSizes = [];

        foreach ($this->options as $group => $sizes) {
            foreach ($sizes as $k => $v) {
                // Dynamic sizes
                if ($group == 'image_sizes') {
                    if (in_array($k, $allowedSizes)) {
                        $filteredSizes[$group][$k] = $v;
                    }

                    continue;
                }

                if (in_array($v, $allowedSizes)) {
                    $filteredSizes[$group][] = $v;
                }
            }
        }

        return $filteredSizes;
    }
}
