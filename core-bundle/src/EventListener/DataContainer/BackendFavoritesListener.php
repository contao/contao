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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

#[AsCallback(table: 'tl_favorites', target: 'config.onload')]
class BackendFavoritesListener
{
    public function __construct(private readonly Security $security, private RequestStack $requestStack)
    {
    }

    public function __invoke(): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

        // Always filter the favorites by user
        $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter'][] = ['user=?', $userId];

        // Allow adding new favorites
        if ('create' === $request->query->get('act') && ($data = $request->query->get('data'))) {
            $GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable'] = false;
            $GLOBALS['TL_DCA']['tl_favorites']['fields']['url']['default'] = base64_decode($data, true);
            $GLOBALS['TL_DCA']['tl_favorites']['fields']['user']['default'] = $userId;
        }
    }
}
