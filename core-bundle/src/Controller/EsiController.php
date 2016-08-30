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
 * Handles the Contao ESI requests. This might be subject to change
 * in the very near future which is why this class is declared final.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
final class EsiController extends Controller
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * EsiController constructor.
     *
     * @param $framework
     */
    public function __construct($framework)
    {
        $this->framework = $framework;
    }

    /**
     * Renders non cacheable insert tags (such as {{request_token}}).
     *
     * @param string $insertTag
     */
    public function renderNonCacheableInsertTag($insertTag)
    {
        $this->framework->initialize();

        $result = $this->framework->createInstance('Contao\InsertTags')
            ->replace($insertTag, false);

        $response = new Response($result);

        // Never cache non cacheable insert tags
        $response->setPrivate();

        return $response;
    }
}
