<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class AdministratorEmailListener
{
    public function __construct(private TranslatorInterface $translator, private RouterInterface $router, private RequestStack $requestStack)
    {
    }

    public function __invoke(): string|null
    {
        if (Config::get('adminEmail')) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();

        $settingsUrl = $this->router->generate('contao_backend', [
            'do' => 'settings',
            'ref' => $request->attributes->get('_contao_referer_id'),
        ]);

        return '<p class="tl_error">'.$this->translator->trans('ERR.noAdminEmail', ['settingsUrl' => $settingsUrl], 'contao_default').'</p>';
    }
}
