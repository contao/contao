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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

#[AsCallback(table: 'tl_favorites', target: 'config.onload')]
class BackendFavoritesListener
{
    public function __construct(
        private readonly Security $security,
        private RequestStack $requestStack,
        private Connection $connection
    ) {
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

        // Always filter the favorites by user
        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['filter'][] = ['user=?', $userId];

        switch ((string) $request->query->get('act')) {
            case '': // empty
            case 'paste':
            case 'create':
            case 'select':
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
                $allowedIds = $this->connection->fetchFirstColumn(
                    'SELECT id FROM tl_favorites WHERE user = :userId',
                    ['userId' => $userId]
                );

                $session = $this->requestStack->getSession();
                $sessionData = $session->all();
                $sessionData['CURRENT']['IDS'] = array_intersect((array) $sessionData['CURRENT']['IDS'], $allowedIds);
                $session->replace($sessionData);
                break;

            case 'edit':
            case 'toggle':
            case 'delete':
            default:
                $createdBy = (int) $this->connection->fetchOne(
                    'SELECT user FROM tl_favorites WHERE id = :id',
                    ['id' => $dc->id]
                );

                if ($createdBy !== $userId) {
                    throw new AccessDeniedException(sprintf('Favorite ID %s does not belong to user ID %s', $dc->id, $userId));
                }
                break;
        }

        // Allow adding new favorites
        if ('create' === $request->query->get('act') && $request->query->has('data')) {
            $GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable'] = false;
            $GLOBALS['TL_DCA']['tl_favorites']['fields']['url']['default'] = base64_decode($request->query->get('data'), true);
        }
    }
}
