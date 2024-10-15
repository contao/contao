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
use Contao\CoreBundle\DataContainer\RecordLabeler;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\IndexUpdateConfigInterface;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\DC_Table;
use Contao\DcaLoader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

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
        private readonly RecordLabeler $recordLabeler,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsType(string $type): bool
    {
        return str_starts_with($type, self::TYPE_PREFIX);
    }

    /**
     * @return iterable<Document>
     */
    public function updateIndex(IndexUpdateConfigInterface $trigger): iterable
    {
        if (!$trigger instanceof UpdateAllProvidersConfig) {
            return new \EmptyIterator();
        }

        foreach ($this->getTables() as $table) {
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

            foreach ($this->findDocuments($table, $trigger) as $document) {
                yield $document;
            }
        }
    }

    public function convertDocumentToHit(Document $document): Hit|null
    {
        // TODO: service for view and edit URLs
        $viewUrl = 'https://todo.com?view='.$document->getId();
        $editUrl = 'https://todo.com?edit='.$document->getId();

        $row = $this->loadRow($this->getTableFromDocument($document), (int) $document->getId());

        // Entry does not exist anymore -> no hit
        if (false === $row) {
            return null;
        }

        $title = $this->recordLabeler->getLabel(\sprintf('contao.db.%s.id', $this->getTableFromDocument($document)), $row);

        return (new Hit($document, $title, $viewUrl))
            ->withEditUrl($editUrl)
            ->withContext($document->getSearchableContent())
            ->withMetadata(['row' => $row]) // Used for permission checks in isHitGranted()
        ;
    }

    public function isHitGranted(TokenInterface $token, Hit $hit): bool
    {
        $table = $this->getTableFromDocument($hit->getDocument());
        $row = $hit->getMetadata()['row'] ?? null;

        if (null === $row) {
            return false;
        }

        return $this->accessDecisionManager->decide(
            $token,
            [ContaoCorePermissions::DC_PREFIX.$table],
            new ReadAction($table, $row),
        );
    }

    private function getTableFromDocument(Document $document): string
    {
        return $document->getMetadata()['table'] ?? '';
    }

    /**
     * @return array<int, string>
     */
    private function getTables(): array
    {
        $this->contaoFramework->initialize();

        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        $tables = array_map(
            static fn (SplFileInfo $input) => str_replace('.php', '', $input->getRelativePathname()),
            iterator_to_array($files->getIterator()),
        );

        $tables = array_values($tables);

        return array_unique($tables);
    }

    private function findDocuments(string $table, IndexUpdateConfigInterface $indexUpdateConfig): \Generator
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['fields'])) {
            return [];
        }

        $searchableFields = array_filter(
            $GLOBALS['TL_DCA'][$table]['fields'] ?? [],
            static fn (array $config): bool => isset($config['search']) && true === $config['search'],
        );

        $qb = $this->createQueryBuilderForTable($table);

        if ($indexUpdateConfig->getUpdateSince() && isset($GLOBALS['TL_DCA'][$table]['fields']['tstamp'])) {
            $qb->andWhere('tstamp <= ', $qb->createNamedParameter($indexUpdateConfig->getUpdateSince()));
        }

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            $document = $this->createDocumentFromRow($table, $row, $searchableFields);

            if ($document) {
                yield $document;
            }
        }
    }

    private function createDocumentFromRow(string $table, array $row, array $searchableFields): Document|null
    {
        $searchableContent = $this->extractSearchableContent($row, $searchableFields);

        if ('' === $searchableContent) {
            return null;
        }

        return (new Document((string) $row['id'], $this->getTypeFromTable($table), $searchableContent))->withMetadata(['table' => $table]);
    }

    private function getTypeFromTable(string $table): string
    {
        return self::TYPE_PREFIX.$table;
    }

    private function extractSearchableContent(array $row, array $searchableFields): string
    {
        $searchableContent = [];

        foreach (array_keys($searchableFields) as $field) {
            if (isset($row[$field])) {
                // TODO: Decode, optimize serialized data maybe? Strip HTML tags? Event for e.g. RSCE?
                $searchableContent[] = $row[$field];
            }
        }

        return implode(' ', array_filter(array_unique($searchableContent)));
    }

    private function loadRow(string $table, int $id): array|false
    {
        $qb = $this->createQueryBuilderForTable($table);

        return $qb
            ->andWhere('id = '.$qb->createNamedParameter($id, ParameterType::INTEGER))
            ->fetchAssociative()
        ;
    }

    private function createQueryBuilderForTable(string $table): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from($table)
        ;
    }
}
