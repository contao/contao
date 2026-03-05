<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
abstract class AbstractDeleteVariantOperation extends DeleteOperation
{
    public function canExecute(OperationContext $context): bool
    {
        if ($context->isThemeContext()) {
            return false;
        }

        return 1 === preg_match('%^'.preg_quote($this->getPrefix(), '%').'/[^/]+/.+$%', $context->getIdentifier());
    }

    public function execute(Request $request, OperationContext $context): Response
    {
        $response = parent::execute($request, $context);
        $this->migrateDatabaseUsages($context->getIdentifier());

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['database_connection'] = Connection::class;

        return $services;
    }

    /**
     * Return the template identifier prefix this operation is targeting (e.g.
     * "content_element").
     */
    abstract protected function getPrefix(): string;

    /**
     * Return a list of database references in the form of `<table>.<field>` storing
     * template identifiers, that should get migrated when the variant is deleted.
     *
     * @return list<string>
     */
    protected function getDatabaseReferencesThatShouldBeMigrated(): array
    {
        return [];
    }

    /**
     * Return true if the database value should be set to the default template instead
     * of being removed.
     */
    protected function shouldSetDatabaseValueToDefaultWhenMigrating(): bool
    {
        return false;
    }

    private function migrateDatabaseUsages(string $from): void
    {
        $to = '';

        if (
            $this->shouldSetDatabaseValueToDefaultWhenMigrating()
            && 1 === preg_match('%^('.preg_quote($this->getPrefix(), '%').'/[^/]+)/%', $from, $matches)
        ) {
            $to = $matches[1];
        }

        $connection = $this->container->get('database_connection');

        foreach ($this->getDatabaseReferencesThatShouldBeMigrated() as $reference) {
            [$table, $field] = explode('.', $reference, 2);

            $connection->update($table, [$field => $to], [$field => $from]);
        }
    }
}
