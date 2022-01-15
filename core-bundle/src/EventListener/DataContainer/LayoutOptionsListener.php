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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;

/**
 * @Callback(table="tl_page", target="fields.layout.options")
 * @Callback(table="tl_page", target="fields.subpagesLayout.options")
 */
class LayoutOptionsListener
{
    private Connection $connection;

    private ?array $layouts = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): array
    {
        if (null === $this->layouts) {
            $this->layouts = [];
            $layouts = $this->connection->fetchAllAssociative('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name');

            foreach ($layouts as $layout) {
                $this->layouts[$layout['theme']][$layout['id']] = $layout['name'];
            }
        }

        return $this->layouts;
    }
}
