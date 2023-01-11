<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_page', target: 'config.onload')]
class PageUseSslDefaultListener
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();

        // Set useSSL to HTTP if the current request does not use HTTPS
        if ($request && !$request->isSecure()) {
            $GLOBALS['TL_DCA']['tl_page']['fields']['useSSL']['default'] = false;
        }
    }
}
