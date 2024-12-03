<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Event\BackendSearch\IndexDocumentEvent;
use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Schranz\Search\SEAL\EngineInterface;
use Schranz\Search\SEAL\Schema\Field\IdentifierField;
use Schranz\Search\SEAL\Schema\Field\TextField;
use Schranz\Search\SEAL\Schema\Index;
use Schranz\Search\SEAL\Schema\Schema;
use Schranz\Search\SEAL\Search\Condition\EqualCondition;
use Schranz\Search\SEAL\Search\Condition\SearchCondition;
use Schranz\Search\SEAL\Search\SearchBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @experimental
 */
class BackendSearch
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly Security $security,
        private readonly EngineInterface $engine,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
        private readonly WebWorker $webWorker,
        private readonly string $indexName,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->webWorker->hasCliWorkersRunning();
    }

    /**
     * @param array<string, array<string>> $documentTypesAndIds The document IDs grouped by type
     */
    public function deleteDocuments(array $documentTypesAndIds, bool $async = true): self
    {
        if ($async) {
            $this->messageBus->dispatch(new DeleteDocumentsMessage($documentTypesAndIds));

            return $this;
        }

        $documentIds = [];

        foreach ($documentTypesAndIds as $type => $ids) {
            foreach ($ids as $id) {
                $documentIds[] = $this->getGlobalIdForTypeAndDocumentId($type, $id);
            }
        }

        // TODO: Use bulk endpoint as soon as SEAL supports this
        foreach ($documentIds as $documentId) {
            $this->engine->deleteDocument($this->indexName, $documentId);
        }

        return $this;
    }

    public function reindex(ReindexConfig $config, bool $async = true): self
    {
        if ($async) {
            $this->messageBus->dispatch(new ReindexMessage($config->getUpdateSince()?->format(\DateTimeInterface::ATOM)));

            return $this;
        }

        // TODO: Use bulk endpoint as soon as SEAL supports this
        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            /** @var Document $document */
            foreach ($provider->updateIndex($config) as $document) {
                $event = new IndexDocumentEvent($document);
                $this->eventDispatcher->dispatch($event);

                if (!$document = $event->getDocument()) {
                    continue;
                }

                $this->engine->saveDocument(
                    $this->indexName,
                    $this->convertProviderDocumentForSearchIndex($document),
                );
            }
        }

        return $this;
    }

    /**
     * TODO: This Query API object will change for sure because we might want to
     * introduce searching for multiple tags which is currently not supported by SEAL.
     * It's a matter of putting in some work there but it will affect the signature of
     * this object.
     */
    public function search(Query $query): Result
    {
        $sb = $this->createSearchBuilder($query);

        $hits = [];
        $hitCount = 0;
        $offset = 0;
        $limit = $query->getPerPage();

        // Stop after 10 iterations
        for ($i = 0; $i <= 10; ++$i) {
            /** @var array $document */
            foreach ($sb->offset($offset)->limit($limit)->getResult() as $document) {
                $hit = $this->convertSearchDocumentToProviderHit($document);

                if (!$hit) {
                    continue;
                }

                $hits[] = $hit;
                ++$hitCount;

                if ($hitCount >= $limit) {
                    break 2;
                }
            }

            $offset += $limit;
        }

        return new Result($hits);
    }

    public static function getSearchEngineSchema(string $indexName): Schema
    {
        return new Schema([
            $indexName => new Index($indexName, [
                'id' => new IdentifierField('id'),
                'type' => new TextField('type', filterable: true),
                'searchableContent' => new TextField('type', searchable: true),
                'tags' => new TextField('tags', multiple: true, filterable: true),
                'document' => new TextField('document'),
            ]),
        ]);
    }

    public function clear(): void
    {
        // TODO: We need an API for that in SEAL
        $this->engine->dropIndex($this->indexName);
        $this->engine->createIndex($this->indexName);
    }

    private function createSearchBuilder(Query $query): SearchBuilder
    {
        $sb = $this->engine
            ->createSearchBuilder()
            ->addIndex($this->indexName)
        ;

        if ($query->getKeywords()) {
            $sb->addFilter(new SearchCondition($query->getKeywords()));
        }

        if ($query->getType()) {
            $sb->addFilter(new EqualCondition('type', $query->getType()));
        }

        if ($query->getTag()) {
            $sb->addFilter(new EqualCondition('tags', $query->getTag()));
        }

        return $sb;
    }

    private function convertSearchDocumentToProviderHit(array $document): Hit|null
    {
        $fileProvider = $this->getProviderForType($document['type'] ?? '');

        if (!$fileProvider) {
            return null;
        }

        $document = Document::fromArray(json_decode($document['document'], true, 512, JSON_THROW_ON_ERROR));
        $hit = $fileProvider->convertDocumentToHit($document);

        // The provider did not find any hit for it anymore so it must have been removed
        // or expired. Remove from the index.
        if (!$hit) {
            $this->deleteDocuments([$document->getType() => [$document->getId()]]);

            return null;
        }

        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT, $hit)) {
            return null;
        }

        $event = new EnhanceHitEvent($hit);
        $this->eventDispatcher->dispatch($event);

        return $event->getHit();
    }

    private function getProviderForType(string $type): ProviderInterface|null
    {
        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            if ($provider->supportsType($type)) {
                return $provider;
            }
        }

        return null;
    }

    private function convertProviderDocumentForSearchIndex(Document $document): array
    {
        return [
            'id' => $this->getGlobalIdForTypeAndDocumentId($document->getType(), $document->getId()),
            'type' => $document->getType(),
            'searchableContent' => $document->getSearchableContent(),
            'tags' => $document->getTags(),
            'document' => json_encode($document->toArray(), JSON_THROW_ON_ERROR),
        ];
    }

    private function getGlobalIdForTypeAndDocumentId(string $type, string $id): string
    {
        // Ensure the ID is global across the search index by prefixing the id
        return $type.'_'.$id;
    }
}
