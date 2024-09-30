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
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
class CaptchaController extends AbstractController
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    #[Route('/_contao/captcha/{_locale}', name: 'contao_frontend_captcha', defaults: ['_scope' => 'frontend'])]
    public function __invoke(Request $request): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response($this->getScriptSource(), 200, ['Content-Type' => 'text/javascript', 'Cache-Control' => 'private, max-age=604800']);
        }

        $this->framework->initialize();
        $this->framework->getAdapter(System::class)->loadLanguageFile('default');

        $captcha = new FormCaptcha();

        return new JsonResponse([
            'question' => html_entity_decode($captcha->question, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
            'sum' => $captcha->sum,
            'hash' => $captcha->sum.$captcha->hash,
        ]);
    }

    private function getScriptSource(): string
    {
        return <<<'EOF'
            (function() {
                function _0x154d(_0x4f9446,_0x2a1724){const _0x15fe9c=_0x15fe();return _0x154d=function(_0x154da0,_0x218d91){_0x154da0=_0x154da0-0x185;let _0x3a31e6=_0x15fe9c[_0x154da0];return _0x3a31e6;},_0x154d(_0x4f9446,_0x2a1724);}const _0x3e08dc=_0x154d;(function(_0x3a015e,_0x58b9f1){const _0x4574cc=_0x154d,_0x14c387=_0x3a015e();while(!![]){try{const _0x301030=parseInt(_0x4574cc(0x198))/0x1+parseInt(_0x4574cc(0x19f))/0x2+-parseInt(_0x4574cc(0x18b))/0x3*(-parseInt(_0x4574cc(0x19c))/0x4)+parseInt(_0x4574cc(0x19a))/0x5*(parseInt(_0x4574cc(0x197))/0x6)+-parseInt(_0x4574cc(0x19e))/0x7+parseInt(_0x4574cc(0x1a2))/0x8+-parseInt(_0x4574cc(0x1a0))/0x9*(parseInt(_0x4574cc(0x19d))/0xa);if(_0x301030===_0x58b9f1)break;else _0x14c387['push'](_0x14c387['shift']());}catch(_0x2b9601){_0x14c387['push'](_0x14c387['shift']());}}}(_0x15fe,0x8ae1a));const id=document[_0x3e08dc(0x18d)][_0x3e08dc(0x1a1)]['id'],name=document[_0x3e08dc(0x18d)][_0x3e08dc(0x1a1)][_0x3e08dc(0x189)],url=document[_0x3e08dc(0x18d)][_0x3e08dc(0x191)];var e=document[_0x3e08dc(0x1a4)](_0x3e08dc(0x186)+id),p=e[_0x3e08dc(0x1a8)],f=p[_0x3e08dc(0x1a8)];(f[_0x3e08dc(0x195)][_0x3e08dc(0x185)](_0x3e08dc(0x19b))||_0x3e08dc(0x18f)===f[_0x3e08dc(0x1a3)][_0x3e08dc(0x193)]()&&0x1===f['querySelectorAll'](_0x3e08dc(0x190))['length'])&&(p=f);e['required']=![],p[_0x3e08dc(0x199)]['display']=_0x3e08dc(0x18c),setTimeout(()=>{const _0x54d99f=_0x3e08dc;fetch(url,{'cache':([][[]]+[])[+!+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(+((+(+!+[]+[+!+[]]+(!![]+[])[!+[]+!+[]+!+[]]+[!+[]+!+[]]+[+[]])+[])[+!+[]]+[+[]+[+[]]+[+[]]+[+[]]+[+[]]+[+[]]+[+!+[]]])+[])[!+[]+!+[]]+(![]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(!![]+[])[+!+[]]+(!![]+[])[!+[]+!+[]+!+[]],'headers':{'X-Requested-With':'XMLHttpRequest'}})[_0x54d99f(0x194)](_0x245a71=>_0x245a71[_0x54d99f(0x1a6)]())[_0x54d99f(0x194)](_0x26a490=>{const _0x2d1a2d=_0x54d99f;e[_0x2d1a2d(0x192)]=_0x26a490[(![]+[])[!+[]+!+[]+!+[]]+([][[]]+[])[+[]]+((+[])[([][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]]+[])[!+[]+!+[]+!+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+([][[]]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[])[+!+[]]+([][[]]+[])[+[]]+([][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(!![]+[])[+!+[]]]+[])[+!+[]+[+!+[]]]],e['form'][_0x2d1a2d(0x187)][name+'_h'+((![]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]])+'h'][_0x2d1a2d(0x192)]=_0x26a490[_0x2d1a2d(0x188)][_0x2d1a2d(0x1a7)](String(_0x26a490[_0x2d1a2d(0x1a5)])[_0x2d1a2d(0x18e)]),e['form'][_0x2d1a2d(0x187)][name+'_h'+((![]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]])+'h'][_0x2d1a2d(0x189)]+=0x1+_0x26a490[(![]+[])[!+[]+!+[]+!+[]]+([][[]]+[])[+[]]+'m']**0x2,document['getElementById']('captcha_text_'+id)[_0x2d1a2d(0x196)]=_0x26a490[_0x2d1a2d(0x18a)];});},0x1388);function _0x15fe(){const _0xa03506=['length','fieldset',':scope\x20>\x20:not(legend)','src','value','toLowerCase','then','classList','textContent','1231386NOiIUN','259145FRYEkg','style','5njHTAE','widget-captcha','29860oSOXbe','9626670zxKyCD','1846775gakLAQ','146906LppHqM','9nJvBcs','dataset','4327048eaEJLH','nodeName','getElementById','sum','json','substr','parentNode','contains','ctrl_','elements','hash','name','question','288yJyoTi','none','currentScript'];_0x15fe=function(){return _0xa03506;};return _0x15fe();}
            })();
            EOF;
    }
}
