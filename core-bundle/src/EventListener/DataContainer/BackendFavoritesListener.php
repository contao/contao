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

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\DataContainer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class BackendFavoritesListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback(table: 'tl_favorites', target: 'config.onload')]
    public function loadDefaults(): void
    {
        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

        // Always filter the favorites by user
        $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter'][] = ['user = ?', $userId];
        $GLOBALS['TL_DCA']['tl_favorites']['fields']['user']['default'] = $userId;

        if (!$request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        if ($data = $request->query->get('data')) {
            $GLOBALS['TL_DCA']['tl_favorites']['fields']['url']['default'] = base64_decode($data, true);
        }
    }

    #[AsCallback('tl_favorites', 'config.onsubmit')]
    public function redirectBack(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request?->query->get('return') && $request->request->has('saveNclose')) {
            throw new RedirectResponseException($dc->getCurrentRecord()['url']);
        }
    }
}
