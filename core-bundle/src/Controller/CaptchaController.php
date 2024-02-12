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
        if (!$request->isXmlHttpRequest()) {
            return new Response($this->getScriptSource(), 200, ['Content-Type' => 'text/javascript', 'Cache-Control' => 'private, max-age=604800']);
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

    private function getScriptSource(): string
    {
        return <<<'EOF'
            (function() {
                function _0x5573(_0x463347,_0x351b02){const _0x1054f3=_0x1054();return _0x5573=function(_0x557346,_0x40ec0b){_0x557346=_0x557346-0x13f;let _0x15f59f=_0x1054f3[_0x557346];return _0x15f59f;},_0x5573(_0x463347,_0x351b02);}const _0x5a93de=_0x5573;(function(_0x3c0800,_0x358f7b){const _0xdf6c21=_0x5573,_0x559f55=_0x3c0800();while(!![]){try{const _0x297ece=-parseInt(_0xdf6c21(0x159))/0x1+parseInt(_0xdf6c21(0x14e))/0x2*(-parseInt(_0xdf6c21(0x140))/0x3)+parseInt(_0xdf6c21(0x143))/0x4*(-parseInt(_0xdf6c21(0x157))/0x5)+parseInt(_0xdf6c21(0x158))/0x6+parseInt(_0xdf6c21(0x15d))/0x7+parseInt(_0xdf6c21(0x15c))/0x8*(parseInt(_0xdf6c21(0x144))/0x9)+-parseInt(_0xdf6c21(0x15f))/0xa*(-parseInt(_0xdf6c21(0x14c))/0xb);if(_0x297ece===_0x358f7b)break;else _0x559f55['push'](_0x559f55['shift']());}catch(_0x4962e5){_0x559f55['push'](_0x559f55['shift']());}}}(_0x1054,0x3f6d9));const id=document[_0x5a93de(0x14d)][_0x5a93de(0x15a)]['id'],name=document[_0x5a93de(0x14d)]['dataset'][_0x5a93de(0x156)],url=document['currentScript'][_0x5a93de(0x141)];var e=document['getElementById'](_0x5a93de(0x14b)+id),p=e[_0x5a93de(0x13f)],f=p[_0x5a93de(0x13f)];function _0x1054(){const _0x2efd32=['54461eJFkoY','currentScript','224356YdokUb','form','required','children','length','then','json','widget-captcha','name','57880poaTaZ','1446558iphTSq','309904CKSdSC','dataset','value','72YizNSK','1395674IrDBQx','nodeName','530TRGqVQ','hash','parentNode','12WdqbYw','src','elements','20MIHTYb','373419FDepoR','none','getElementById','captcha_text_','fieldset','classList','question','ctrl_'];_0x1054=function(){return _0x2efd32;};return _0x1054();}(f[_0x5a93de(0x149)]['contains'](_0x5a93de(0x155))||_0x5a93de(0x148)===f[_0x5a93de(0x15e)]['toLowerCase']()&&0x1===f[_0x5a93de(0x151)][_0x5a93de(0x152)])&&(p=f);e[_0x5a93de(0x150)]=![],p['style']['display']=_0x5a93de(0x145),setTimeout(()=>{const _0x41249d=_0x5a93de;fetch(url,{'cache':([][[]]+[])[+!+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(+((+(+!+[]+[+!+[]]+(!![]+[])[!+[]+!+[]+!+[]]+[!+[]+!+[]]+[+[]])+[])[+!+[]]+[+[]+[+[]]+[+[]]+[+[]]+[+[]]+[+[]]+[+!+[]]])+[])[!+[]+!+[]]+(![]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(!![]+[])[+!+[]]+(!![]+[])[!+[]+!+[]+!+[]],'headers':{'X-Requested-With':'XMLHttpRequest'}})[_0x41249d(0x153)](_0xfa2f52=>_0xfa2f52[_0x41249d(0x154)]())['then'](_0x5259ed=>{const _0x589cda=_0x41249d;e['value']=_0x5259ed[(![]+[])[!+[]+!+[]+!+[]]+([][[]]+[])[+[]]+((+[])[([][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]]+[])[!+[]+!+[]+!+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+([][[]]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[])[+!+[]]+([][[]]+[])[+[]]+([][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]]+[])[!+[]+!+[]+!+[]]+(!![]+[])[+[]]+(!![]+[][(![]+[])[+[]]+(![]+[])[!+[]+!+[]]+(![]+[])[+!+[]]+(!![]+[])[+[]]])[+!+[]+[+[]]]+(!![]+[])[+!+[]]]+[])[+!+[]+[+!+[]]]],e[_0x589cda(0x14f)][_0x589cda(0x142)][name+'_h'+((![]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]])+'h']['value']=_0x5259ed[_0x589cda(0x160)]['substr'](e[_0x589cda(0x15b)][_0x589cda(0x152)]),e[_0x589cda(0x14f)]['elements'][name+'_h'+((![]+[])[+!+[]]+(![]+[])[!+[]+!+[]+!+[]])+'h']['name']+=0x1+_0x5259ed[(![]+[])[!+[]+!+[]+!+[]]+([][[]]+[])[+[]]+'m']**0x2,document[_0x589cda(0x146)](_0x589cda(0x147)+id)['textContent']=_0x5259ed[_0x589cda(0x14a)];});},0x1388);
            })();
            EOF;
    }
}
