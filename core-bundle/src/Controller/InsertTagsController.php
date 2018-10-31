<?php

declare(strict_types=1);

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
 * @internal Do not use this controller in your code
 *
 * It is supposed to be used within ESI requests that are protected by the
 * Symfony fragment URI signer. If you use it directly, make sure to add a
 * permission check, because insert tags can contain arbitrary data!
 */
class InsertTagsController extends Controller
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function renderAction(Request $request, string $insertTag): Response
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
