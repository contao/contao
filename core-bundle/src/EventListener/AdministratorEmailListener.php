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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class AdministratorEmailListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): string|null
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if ($config->get('adminEmail')) {
            return null;
        }

        if ($this->canAccessSettings()) {
            $request = $this->requestStack->getCurrentRequest();

            $settingsUrl = $this->router->generate('contao_backend', [
                'do' => 'settings',
                'ref' => $request->attributes->get('_contao_referer_id'),
            ]);

            $message = $this->translator->trans('ERR.noAdminEmailUrl', ['settingsUrl' => $settingsUrl], 'contao_default');
        } else {
            $message = $this->translator->trans('ERR.noAdminEmail', [], 'contao_default');
        }

        return '<p class="tl_error">'.$message.'</p>';
    }

    private function canAccessSettings(): bool
    {
        if (!isset($GLOBALS['BE_MOD']['system']['settings'])) {
            return false;
        }

        return $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'settings');
    }
}
