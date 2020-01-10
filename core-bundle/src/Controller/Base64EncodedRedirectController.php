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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class Base64EncodedRedirectController
{
    /**
     * @var UriSigner
     */
    private $uriSigner;

    public function __construct(UriSigner $uriSigner)
    {
        $this->uriSigner = $uriSigner;
    }

    /**
     * @Route("/_contao/base64_redirect", name="contao_base64_redirect")
     */
    public function renderAction(Request $request): Response
    {
        if (!$request->query->has('redirect')) {
            throw new BadRequestHttpException();
        }

        // We cannot use $request->getUri() here as we want to work with the original URI (no query string reordering)
        if (!$this->uriSigner->check($request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : ''))) {
            throw new BadRequestHttpException();
        }

        return new RedirectResponse(base64_decode($request->query->get('redirect'), true));
    }
}
