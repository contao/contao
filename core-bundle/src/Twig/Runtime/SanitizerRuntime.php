<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Twig\Extension\RuntimeExtensionInterface;

final class SanitizerRuntime implements RuntimeExtensionInterface
{
    private const ALLOWED_TAGS = <<<'EOD'
        <a><abbr><acronym><address><area><article><aside><audio>
        <b><bdi><bdo><big><blockquote><br><button>
        <caption><cite><code><col><colgroup>
        <data><dd><del><details><dfn><div><dl><dt>
        <em>
        <figcaption><figure><footer>
        <h1><h2><h3><h4><h5><h6><header><hgroup><hr>
        <i><img><ins>
        <kbd>
        <li>
        <map><mark><menu>
        <nav>
        <ol><output>
        <p><picture><pre>
        <q>
        <s><samp><section><small><source><span><strong><style><sub><summary><sup>
        <table><tbody><td><tfoot><th><thead><time><tr><tt>
        <u><ul>
        <var><video>
        <wbr>
        EOD;

    private const ALLOWED_ATTRIBUTES = [
        ['key' => '*', 'value' => 'data-*,id,class,style,title,dir,lang,aria-*,hidden,translate,itemid,itemprop,itemref,itemscope,itemtype'],
        ['key' => 'a', 'value' => 'href,hreflang,rel,target,download,referrerpolicy'],
        ['key' => 'img', 'value' => 'src,crossorigin,srcset,sizes,width,height,alt,loading,decoding,ismap,usemap,referrerpolicy'],
        ['key' => 'map', 'value' => 'name'],
        ['key' => 'area', 'value' => 'coords,shape,alt,href,hreflang,rel,target,download'],
        ['key' => 'video', 'value' => 'src,crossorigin,width,height,autoplay,controls,controlslist,loop,muted,poster,preload,playsinline'],
        ['key' => 'audio', 'value' => 'src,crossorigin,autoplay,controls,loop,muted,preload'],
        ['key' => 'source', 'value' => 'src,srcset,media,sizes,type'],
        ['key' => 'ol', 'value' => 'reversed,start,type'],
        ['key' => 'table', 'value' => 'border,cellspacing,cellpadding,width,height'],
        ['key' => 'col', 'value' => 'span'],
        ['key' => 'colgroup', 'value' => 'span'],
        ['key' => 'td', 'value' => 'rowspan,colspan,width,height'],
        ['key' => 'th', 'value' => 'rowspan,colspan,width,height'],
        ['key' => 'style', 'value' => 'media'],
        ['key' => 'time', 'value' => 'datetime'],
        ['key' => 'details', 'value' => 'open'],
    ];

    /**
     * @internal
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function sanitizeHtml(string $html): string
    {
        $this->framework->initialize();

        return Input::stripTags($html, self::ALLOWED_TAGS, self::ALLOWED_ATTRIBUTES);
    }
}
