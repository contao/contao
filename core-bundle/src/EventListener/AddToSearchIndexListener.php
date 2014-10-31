<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Frontend;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Adds a page to the search index
 *
 * @author Leo Feyer <https://contao.org>
 */
class AddToSearchIndexListener
{
    /**
     * Adds a page to the search index
     *
     * @param PostResponseEvent $event The event object
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        global $objPage;

        if (null === $objPage) {
            return;
        }

        Frontend::indexPageIfApplicable($objPage, $event->getResponse());
    }
}
