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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FormCaptcha;
use Contao\System;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class CaptchaController extends AbstractController
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * @Route("/_contao/captcha/{_locale}", name="contao_frontend_captcha", defaults={"_scope" = "frontend"})
     */
    public function __invoke(Request $request): Response
    {
        $this->framework->initialize();
        $this->framework->getAdapter(System::class)->loadLanguageFile('default');

        $captcha = new FormCaptcha();

        return new JsonResponse([
            'question' => html_entity_decode($captcha->question),
            'sum' => $captcha->sum,
            'hash' => $captcha->hash,
        ]);
    }
}
