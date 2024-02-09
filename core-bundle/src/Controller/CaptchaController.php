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
    private const SCRIPT = <<<'EOF'
        (function() {
            const id = document.currentScript.dataset.id;
            const name = document.currentScript.dataset.name;
            const url = document.currentScript.src;

            var e = document.getElementById('ctrl_'+id),
                p = e.parentNode, f = p.parentNode;

            if (f.classList.contains('widget-captcha') || 'fieldset' === f.nodeName.toLowerCase() && 1 === f.children.length) {
                p = f;
            }

            e.required = false;
            p.style.display = 'none';

            setTimeout(() => {
                fetch(url, {cache: 'no-store', headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(r => r.json()).then(d => {
                    e.value = d.sum;
                    e.form.elements[name+'_hash'].value = d.hash.substr(String(d.sum).length);
                    e.form.elements[name+'_hash'].name += 1 + d.sum ** 2;
                    document.getElementById('captcha_text_'+id).textContent = d.question;
                });
            }, 5000);
        })();
        EOF;

    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * @Route("/_contao/captcha/{_locale}", name="contao_frontend_captcha", defaults={"_scope" = "frontend"})
     */
    public function __invoke(Request $request): Response
    {
        if (!$request->isXmlHttpRequest() || !str_contains($request->headers->get('Cache-Control') ?? '', 'no-cache')) {
            return new Response(self::SCRIPT, 200, ['Content-Type' => 'text/javascript', 'Cache-Control' => 'max-age=604800']);
        }

        $this->framework->initialize();
        $this->framework->getAdapter(System::class)->loadLanguageFile('default');

        $captcha = new FormCaptcha();

        return new JsonResponse([
            'question' => html_entity_decode($captcha->question),
            'sum' => $captcha->sum,
            'hash' => $captcha->sum.$captcha->hash,
        ]);
    }
}
