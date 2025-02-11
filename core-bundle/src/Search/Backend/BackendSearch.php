<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Seal\SealReindexProvider;
use Contao\CoreBundle\Search\Backend\Seal\SealUtil;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @experimental
 */
class BackendSearch
{
    public const SEAL_INTERNAL_INDEX_NAME = 'contao_backend_search';

    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly Security $security,
        private readonly EngineInterface $engine,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MessageBusInterface $messageBus,
        private readonly Jobs $jobs,
        private readonly WebWorker $webWorker,
        private readonly SealReindexProvider $reindexProvider,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->webWorker->hasCliWorkersRunning();
    }

    public function deleteDocuments(GroupedDocumentIds $groupedDocumentIds, bool $async = true): self
    {
        if ($groupedDocumentIds->isEmpty()) {
            return $this;
        }

        if ($async) {
            // Split into multiple messages of max 64kb if needed, otherwise messages with
            // hundreds of IDs would fail
            foreach ($groupedDocumentIds->split(65536) as $group) {
                $this->messageBus->dispatch(new DeleteDocumentsMessage($group));
            }

            return $this;
        }

        $documentIds = [];

        foreach ($groupedDocumentIds->getTypes() as $type) {
            foreach ($groupedDocumentIds->getDocumentIdsForType($type) as $id) {
                $documentIds[] = SealUtil::getGlobalDocumentId($type, $id);
            }
        }

        $this->engine->bulk(self::SEAL_INTERNAL_INDEX_NAME, [], $documentIds);

        return $this;
    }

    public function reindex(ReindexConfig $config, bool $async = true): self
    {
        $job = $config->getJobId() ? $this->jobs->getByUuid($config->getJobId()) : null;

        if ($async) {
            // Split into multiple messages of max 64kb if needed, otherwise messages with
            // hundreds of IDs would fail
            $documentIdGroups = $config->getLimitedDocumentIds()->split(65536);

            // No special handling needed if it's just one group still (= no splitting needed)
            if (1 === \count($documentIdGroups)) {
                $this->messageBus->dispatch(new ReindexMessage($config));
            } else {
                foreach ($documentIdGroups as $documentIdGroup) {
                    // Create a new config with the chunk of this document ID group
                    $config = $config->limitToDocumentIds($documentIdGroup);

                    // If a job was given, we have to create child jobs here, so we can track
                    // progress across multiple messages
                    if ($job) {
                        $config = $config->withJobId($this->jobs->createChild($job)->getUuid());
                    }

                    $this->messageBus->dispatch(new ReindexMessage($config));
                }
            }

            return $this;
        }

        // If we have a job, mark it as pending now.
        if ($job) {
            $this->jobs->persist($job->markPending());
        }

        // Seal does not delete unused documents, it just re-indexes. So if some
        // identifier here does no longer exist, it would never get removed. Hence, we
        // remove all those referenced via identifiers first. The ones that still exist
        // will get re-indexed after.
        $this->deleteDocuments($config->getLimitedDocumentIds(), false);

        $this->engine->reindex([$this->reindexProvider], SealUtil::internalReindexConfigToSealReindexConfig($config));

        // If we have a job, mark it as finished.
        if ($job) {
            $this->jobs->persist($job->markFinished());
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
            self::SEAL_INTERNAL_INDEX_NAME => new Index($indexName, [
                'id' => new IdentifierField('id'),
                'type' => new TextField('type', searchable: false, filterable: true),
                'searchableContent' => new TextField('searchableContent', searchable: true),
                'tags' => new TextField('tags', multiple: true, searchable: false, filterable: true),
                'document' => new TextField('document', searchable: false),
            ]),
        ]);
    }

    public function clear(): void
    {
        // TODO: We need an API for that in SEAL
        $this->engine->dropIndex(self::SEAL_INTERNAL_INDEX_NAME);
        $this->engine->createIndex(self::SEAL_INTERNAL_INDEX_NAME);
    }

    private function createSearchBuilder(Query $query): SearchBuilder
    {
        $sb = $this->engine->createSearchBuilder(self::SEAL_INTERNAL_INDEX_NAME);

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

        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_DOCUMENT, $document)) {
            return null;
        }

        $hit = $fileProvider->convertDocumentToHit($document);

        // The provider did not find any hit for it anymore so it must have been removed
        // or expired. Remove from the index.
        if (!$hit) {
            $this->deleteDocuments(new GroupedDocumentIds([$document->getType() => [$document->getId()]]));

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
}
