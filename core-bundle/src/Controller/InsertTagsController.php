<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\InsertTags;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles insert tag requests.
 *
 * Do not just call this Controller directly! It is supposed to be used within ESI requests that are protected by
 * the fragment uri signer of Symfony. If you call it directly, make sure you check for all permissions needed because
 * insert tags can contain arbitrary data!
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class InsertTagsController extends Controller
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param $framework
     */
    public function __construct($framework)
    {
        $this->framework = $framework;
    }

    /**
     * Renders an insert tag.
     *
     * @param string $insertTag
     *
     * @return Response
     */
    public function renderAction($insertTag)
    {
        $this->framework->initialize();

        /** @var InsertTags $it */
        $it = $this->framework->createInstance('Contao\InsertTags');

        // Never cache these responses
        return (new Response($it->replace($insertTag, false)))->setPrivate();
    }
}
