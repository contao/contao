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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles insert tags requests. Do not just call this Controller directly!
 * It is supposed to be used within ESI requests that are protected by
 * the fragment uri signer of Symfony. If you call it directly, make sure
 * you check for all permissions needed because insert tags can contain
 * arbitrary data!
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
     * InsertTagsController constructor.
     *
     * @param $framework
     */
    public function __construct($framework)
    {
        $this->framework = $framework;
    }

    /**
     * Renders insert tags.
     *
     * @param string $insertTag
     */
    public function renderAction($insertTag)
    {
        $this->framework->initialize();

        $result = $this->framework->createInstance('Contao\InsertTags')
            ->replace($insertTag, false);

        $response = new Response($result);

        // Never cache these responses
        $response->setPrivate();

        return $response;
    }
}
