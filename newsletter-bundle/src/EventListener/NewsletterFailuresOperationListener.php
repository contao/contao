<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\EventListener;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_newsletter', target: 'list.operations.failures.button')]
class NewsletterFailuresOperationListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(DataContainerOperation $operation): void
    {
        if (!$this->connection->fetchOne('SELECT TRUE FROM tl_newsletter_failures WHERE pid = ? LIMIT 1', [$operation->getRecord()['id']])) {
            $operation->disable();
            $operation['icon'] = 'bundles/contaonewsletter/failures_.svg';
        }
    }
}
