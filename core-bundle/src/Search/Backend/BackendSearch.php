<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\Condition;
use CmsIg\Seal\Search\Facet\Facet;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Search\Backend\Facet as BackendSearchFacet;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Provider\TagProvidingProviderInterface;
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

    public const REINDEX_JOB_TYPE = 'contao_backend_search_reindex';

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
        if (!$this->isAvailable()) {
            return $this;
        }

        if ($groupedDocumentIds->isEmpty()) {
            return $this;
        }

        if ($async) {
            // Split into multiple messages of max 64 KB if needed, otherwise messages with
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

    /**
     * @return string|null The job ID for the job framework if any
     */
    public function reindex(ReindexConfig $config, bool $async = true): string|null
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $job = $config->getJobId() ? $this->jobs->getByUuid($config->getJobId()) : null;

        // Create the job if required
        if (!$job && $config->requiresJob()) {
            $job = $this->jobs->createJob(self::REINDEX_JOB_TYPE);
            $config = $config->withJobId($job->getUuid());
        }

        // Validate the job type just in case it was not created from this method
        if ($job && self::REINDEX_JOB_TYPE !== $job->getType()) {
            throw new \InvalidArgumentException(\sprintf('Provided a custom job type "%s" but must be "%s".', $job->getType(), self::REINDEX_JOB_TYPE));
        }

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

                    // Create child jobs here, so we can track progress across multiple messages
                    if ($job) {
                        $config = $config->withJobId($this->jobs->createChildJob($job)->getUuid());
                    }

                    $this->messageBus->dispatch(new ReindexMessage($config));
                }
            }

            return $config->getJobId();
        }

        // Mark job as pending now
        if ($job) {
            $this->jobs->persist($job->markPending());
        }

        // Seal does not delete unused documents, it just re-indexes. So if some
        // identifier here does no longer exist, it would never get removed. Hence, we
        // remove all those referenced via identifiers first. The ones that still exist
        // will get re-indexed after.
        $this->deleteDocuments($config->getLimitedDocumentIds(), false);

        $this->engine->reindex([$this->reindexProvider], SealUtil::internalReindexConfigToSealReindexConfig($config));

        // Mark job as completed
        if ($job) {
            $this->jobs->persist($job->markCompleted());
        }

        return $config->getJobId();
    }

    public function search(Query $query): Result
    {
        if (!$query->getKeywords() || !$this->isAvailable()) {
            return Result::createEmpty();
        }

        $sb = $this->createSearchBuilder($query);

        $hits = [];
        $hitCount = 0;
        $offset = 0;
        $limit = $query->getPerPage();

        // Stop after 10 iterations
        for ($i = 0; $i <= 10; ++$i) {
            /** @var array $document */
            $result = $sb->offset($offset)->limit($limit)->getResult();

            // Fetch facets for first iteration only (facets do not care about pagination)
            if (0 === $i) {
                $facetCounts['type'] = $result->facets()['type']['count'] ?? [];
                $facetCounts['tags'] = $result->facets()['tags']['count'] ?? [];
            }

            foreach ($sb->offset($offset)->limit($limit)->getResult() as $document) {
                $hit = $this->convertSearchDocumentToProviderHit($document);

                if (!$hit) {
                    // User is e.g. not allowed to see this document -> we have to update the facet stats
                    foreach (['type', 'tags'] as $facet) {
                        if (isset($document[$facet])) {
                            foreach ((array) $document[$facet] as $facetValue) {
                                if (isset($facetCounts[$facet][$facetValue])) {
                                    --$facetCounts[$facet][$facetValue];

                                    if ($facetCounts[$facet][$facetValue] <= 0) {
                                        unset($facetCounts[$facet][$facetValue]);
                                    }
                                }
                            }
                        }
                    }
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

        $typeFacets = [];
        $tagsFacets = [];

        foreach ($facetCounts['type'] ?? [] as $type => $count) {
            $provider = $this->getProviderForType($type);
            if (!$provider) {
                continue;
            }

            $typeFacets[] = new BackendSearchFacet($type, $provider->convertTypeToVisibleType($type), $count);
        }

        if ($query->getType()) {
            $provider = $this->getProviderForType($query->getType());
            if ($provider instanceof TagProvidingProviderInterface) {
                foreach ($facetCounts['tags'] ?? [] as $tag => $count) {
                    $tagsFacets[] = new BackendSearchFacet($tag, $provider->getFacetLabelForTag($tag), $count);
                }
            }
        }

        return new Result($hits, $typeFacets, $tagsFacets);
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
        if (!$this->isAvailable()) {
            return;
        }

        // TODO: We need an API for that in SEAL
        $this->engine->dropIndex(self::SEAL_INTERNAL_INDEX_NAME);
        $this->engine->createIndex(self::SEAL_INTERNAL_INDEX_NAME);
    }

    private function createSearchBuilder(Query $query): SearchBuilder
    {
        $sb = $this->engine->createSearchBuilder(self::SEAL_INTERNAL_INDEX_NAME);

        if ($query->getKeywords()) {
            $sb->addFilter(Condition::search($query->getKeywords()));
        }

        if ($query->getType()) {
            $sb->addFilter(Condition::equal('type', $query->getType()));
        }

        if ($query->getTag()) {
            if (!$query->getType()) {
                throw new \RuntimeException('Cannot search by tag alone; combine it with a type to ensure accurate tag labels.');
            }

            $sb->addFilter(Condition::equal('tags', $query->getTag()));
        }

        // Only add the "type" facet if not already filtered for it (in which case
        // it is pointless)
        if (!$query->getType()) {
            $sb->addFacet(Facet::count('type'));
        }

        // Only add the "tags" facet if not already filtered for it (in which case it is
        // pointless) plus only if there's a type present as we want it to work sort of
        // like a sub filter.
        if ($query->getType() && !$query->getTag()) {
            $sb->addFacet(Facet::count('tags'));
        }

        return $sb;
    }

    private function convertSearchDocumentToProviderHit(array $document): Hit|null
    {
        $provider = $this->getProviderForType($document['type'] ?? '');

        if (!$provider) {
            return null;
        }

        try {
            $document = Document::fromArray(json_decode($document['document'], true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            return null;
        }

        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_DOCUMENT, $document)) {
            return null;
        }

        $hit = $provider->convertDocumentToHit($document);

        // The provider did not find any hit for it anymore, so it must have been removed
        // or expired. Remove from the index.
        if (!$hit) {
            $this->deleteDocuments(new GroupedDocumentIds([$document->getType() => [$document->getId()]]));

            return null;
        }

        $hit = $hit->withVisibleType($provider->convertTypeToVisibleType($document->getType()));

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
