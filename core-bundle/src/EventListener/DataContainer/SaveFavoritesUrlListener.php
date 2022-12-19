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
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_favorites', target: 'config.oncreate')]
class SaveFavoritesUrlListener
{
    public function __construct(private RequestStack $requestStack, private Connection $connection)
    {
    }

    public function __invoke(string $table, int $insertId): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$data = $request->query->get('data')) {
            return;
        }

        $this->connection->executeQuery(
            'UPDATE tl_favorites SET url = :url WHERE id = :id',
            [
                'url' => base64_decode($data, true),
                'id' => $insertId,
            ]
        );
    }
}
