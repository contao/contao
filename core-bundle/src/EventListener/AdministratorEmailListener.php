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
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(TranslatorInterface $translator, RouterInterface $router, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function __invoke(): ?string
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
