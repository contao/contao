<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\Base64EncodedRedirectController;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\UriSigner;

class Base64EncodedRedirectControllerTest extends TestCase
{
    public function testReturnsBadRequestIfNoRedirectQueryParameterWasProvided(): void
    {
        $request = Request::create('https://contao.org/_contao/base64_redirect?foobar=nonsense');
        $controller = new Base64EncodedRedirectController(new UriSigner('secret'));

        $this->expectException(BadRequestHttpException::class);

        $controller->renderAction($request);
    }

    public function testReturnsBadRequestIfUrlSigningWasIncorrect(): void
    {
        $redirect = base64_encode('https://contao.org/preview.php/about-contao.html');
        $request = Request::create('https://contao.org/_contao/base64_redirect?_hash=nonsense&redirect='.$redirect);
        $controller = new Base64EncodedRedirectController(new UriSigner('secret'));

        $this->expectException(BadRequestHttpException::class);

        $controller->renderAction($request);
    }

    public function testRedirectsToCorrectUrlSigningMatches(): void
    {
        $redirectUrl = 'https://contao.org/preview.php/about-contao.html';

        $uriSigner = new UriSigner('secret');
        $signedUri = $uriSigner->sign('https://contao.org/_contao/base64_redirect?redirect='.base64_encode($redirectUrl));

        $controller = new Base64EncodedRedirectController($uriSigner);

        /** @var RedirectResponse $response */
        $response = $controller->renderAction(Request::create($signedUri));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($redirectUrl, $response->getTargetUrl());
    }
}
