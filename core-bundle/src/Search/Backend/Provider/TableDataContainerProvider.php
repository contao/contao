<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\Provider;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Event\FormatTableDataContainerDocumentEvent;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\DC_Table;
use Contao\DcaLoader;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @experimental
 */
class TableDataContainerProvider implements ProviderInterface
{
    public const TYPE_PREFIX = 'contao.db.';

    public function __construct(
        private readonly ContaoFramework $contaoFramework,
        private readonly ResourceFinder $resourceFinder,
        private readonly Connection $connection,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DcaUrlAnalyzer $dcaUrlAnalyzer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supportsType(string $type): bool
    {
        return str_starts_with($type, self::TYPE_PREFIX);
    }

    /**
     * @return iterable<Document>
     */
    public function updateIndex(ReindexConfig $config): iterable
    {
        foreach ($this->getTables($config) as $table) {
            try {
                $dcaLoader = new DcaLoader($table);
                $dcaLoader->load();
            } catch (\Exception) {
                continue;
            }

            if (!isset($GLOBALS['TL_DCA'][$table]['config']['dataContainer'])) {
                continue;
            }

            // We intentionally do not update child classes of DC_Table here because they
            // could have different logic (like DC_Multilingual) or a different permission
            // concept etc.
            if (DC_Table::class !== $GLOBALS['TL_DCA'][$table]['config']['dataContainer']) {
                continue;
            }

            // The table is marked to be ignored
            if ($GLOBALS['TL_DCA'][$table]['config']['backendSearchIgnore'] ?? false) {
                continue;
            }

            foreach ($this->findDocuments($table, $config) as $document) {
                yield $document;
            }
        }
    }

    public function convertDocumentToHit(Document $document): Hit|null
    {
        $document = $this->addCurrentRowToDocumentIfNotAlreadyLoaded($document);
        $row = $document->getMetadata()['row'] ?? null;

        // Entry does not exist anymore -> no hit
        if (null === $row) {
            return null;
        }

        $table = $this->getTableFromDocument($document);

        try {
            $editUrl = $this->dcaUrlAnalyzer->getEditUrl($table, (int) $document->getId());
            $viewUrl = $this->dcaUrlAnalyzer->getViewUrl($table, (int) $document->getId());
        } catch (AccessDeniedException) {
            return null;
        }

        // No view URL for the entry could be found
        if (null === $viewUrl) {
            return null;
        }

        $trail = $this->dcaUrlAnalyzer->getTrail($editUrl);
        $title = array_pop($trail)['label'];

        return (new Hit($document, $title, $viewUrl))
            ->withEditUrl($editUrl)
            ->withBreadcrumbs($trail)
            ->withContext($document->getSearchableContent())
            ->withMetadata(['row' => $row]) // Used for permission checks in isHitGranted()
        ;
    }

    public function isDocumentGranted(TokenInterface $token, Document $document): bool
    {
        $document = $this->addCurrentRowToDocumentIfNotAlreadyLoaded($document);
        $row = $document->getMetadata()['row'] ?? null;

        // Entry does not exist anymore -> no access
        if (null === $row) {
            return false;
        }

        $table = $this->getTableFromDocument($document);

        return $this->accessDecisionManager->decide(
            $token,
            [ContaoCorePermissions::DC_PREFIX.$table],
            new ReadAction($table, $row),
        );
    }

    public function convertTypeToVisibleType(string $type): string
    {
        $table = substr($type, \strlen(self::TYPE_PREFIX));

        return $this->translator->trans($table.'.tableLabel', [], 'contao_'.$table);
    }

    private function addCurrentRowToDocumentIfNotAlreadyLoaded(Document $document): Document
    {
        if (isset($document->getMetadata()['row'])) {
            return $document;
        }

        $row = $this->loadRow($this->getTableFromDocument($document), (int) $document->getId());

        return $document->withMetadata([...$document->getMetadata(), 'row' => false === $row ? null : $row]);
    }

    private function getTableFromDocument(Document $document): string
    {
        return $document->getMetadata()['table'] ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function getTables(ReindexConfig $config): array
    {
        $this->contaoFramework->initialize();

        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        $tables = array_unique(array_values(array_map(
            static fn (SplFileInfo $input) => str_replace('.php', '', $input->getRelativePathname()),
            iterator_to_array($files->getIterator()),
        )));

        // No document ID limits, consider all tables
        if ($config->getLimitedDocumentIds()->isEmpty()) {
            return $tables;
        }

        // Only consider tables that were asked for
        return array_filter($tables, fn (string $table): bool => $config->getLimitedDocumentIds()->hasType($this->getTypeFromTable($table)));
    }

    private function findDocuments(string $table, ReindexConfig $reindexConfig): \Generator
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            return [];
        }

        $fieldsConfig = $GLOBALS['TL_DCA'][$table]['fields'];

        $searchableFields = array_filter(
            $fieldsConfig,
            static fn (array $config): bool => isset($config['search']) && true === $config['search'],
        );

        // Only select the rows we need so we don't transfer the entire database when indexing
        $select = array_unique(['id', ...array_keys($searchableFields)]);

        $qb = $this->createQueryBuilderForTable($table, implode(',', $select));

        if ($reindexConfig->getUpdateSince() && isset($GLOBALS['TL_DCA'][$table]['fields']['tstamp'])) {
            $qb->andWhere('tstamp <= ', $qb->createNamedParameter($reindexConfig->getUpdateSince()));
        }

        if ($documentIds = $reindexConfig->getLimitedDocumentIds()->getDocumentIdsForType($this->getTypeFromTable($table))) {
            $qb->expr()->in('id', $qb->createNamedParameter($documentIds, ArrayParameterType::STRING));
        }

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            $document = $this->createDocumentFromRow($table, $row, $fieldsConfig, $searchableFields);

            if ($document) {
                yield $document;
            }
        }
    }

    private function createDocumentFromRow(string $table, array $row, array $fieldsConfig, array $searchableFields): Document|null
    {
        $searchableContent = $this->extractSearchableContent($row, $fieldsConfig, $searchableFields);

        if ('' === $searchableContent) {
            return null;
        }

        return (new Document((string) $row['id'], $this->getTypeFromTable($table), $searchableContent))->withMetadata(['table' => $table]);
    }

    private function getTypeFromTable(string $table): string
    {
        return self::TYPE_PREFIX.$table;
    }

    private function extractSearchableContent(array $row, array $fieldsConfig, array $searchableFields): string
    {
        $searchableContent = [];

        foreach (array_keys($searchableFields) as $field) {
            if ($targetField = ($fieldsConfig[$field]['saveTo'] ?? null)) {
                // Do nothing if target field is empty
                if (!($row[$targetField] ?? null)) {
                    continue;
                }

                // Expand storage of virtual fields
                if (\is_string($row[$targetField])) {
                    try {
                        $row[$targetField] = json_decode($row[$targetField], true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        $row[$targetField] = [];
                    }

                    $row = array_merge($row, $row[$targetField]);
                }
            }

            if (isset($row[$field])) {
                $event = new FormatTableDataContainerDocumentEvent($row[$field], $fieldsConfig[$field] ?? []);
                $this->eventDispatcher->dispatch($event);
                $searchableContent[] = $event->getSearchableContent();
            }
        }

        return implode(' ', array_filter(array_unique($searchableContent)));
    }

    private function loadRow(string $table, int $id): array|false
    {
        // In this case, we want to load the entire row. This method is called only when
        // generating the search result hits of which there are not that many and the
        // entire row can be useful in the EnhanceHitEvent.
        $qb = $this->createQueryBuilderForTable($table, '*');

        return $qb
            ->andWhere('id = '.$qb->createNamedParameter($id, ParameterType::INTEGER))
            ->fetchAssociative()
        ;
    }

    private function createQueryBuilderForTable(string $table, string $select): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select($select)
            ->from($table)
        ;
    }
}
