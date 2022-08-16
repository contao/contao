<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Util\UrlEncoder;

class CommonMarkExtension implements ExtensionInterface
{
    private InsertTagParser $insertTagParser;

    public function __construct(InsertTagParser $insertTagParser)
    {
        $this->insertTagParser = $insertTagParser;
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            function (DocumentParsedEvent $e): void {
                foreach ($e->getDocument()->iterator() as $link) {
                    if (!$link instanceof Link) {
                        continue;
                    }

                    // Parser already encodes link contents, so we have to decode it first in order to replace
                    // insert tags
                    $url = rawurldecode($link->getUrl());
                    $url = $this->insertTagParser->replaceInline($url);
                    $link->setUrl(UrlEncoder::unescapeAndEncode($url));
                }
            }
        );
    }
}
