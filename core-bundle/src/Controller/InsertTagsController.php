<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\InsertTags;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
     * @param Request $request
     * @param string  $insertTag
     *
     * @return Response
     */
    public function renderAction(Request $request, $insertTag)
    {
        $this->framework->initialize();

        /** @var InsertTags $it */
        $it = $this->framework->createInstance(InsertTags::class);

        $response = Response::create($it->replace($insertTag, false));
        $response->setPrivate(); // always private

        if ($clientCache = $request->query->getInt('clientCache')) {
            $response->setMaxAge($clientCache);
        } else {
            $response->headers->addCacheControlDirective('no-store');
        }

        return $response;
    }
}
