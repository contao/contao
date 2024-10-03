<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\IndexUpdateConfigInterface;
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
        private readonly string $indexName,
    ) {
    }

    public function triggerUpdate(IndexUpdateConfigInterface $trigger): void
    {
        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            /** @var Document $document */
            foreach ($provider->updateIndex($trigger) as $document) {
                // TODO: Dispatch an event here. This will allow third-party developers to
                // add/modify a document from a pre-existing provider. E.g. adding your own data
                // from files to the existing provider

                $this->engine->saveDocument($this->indexName, $this->convertProviderDocumentForSearchIndex($document));
            }
        }
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

        if (!$hit) {
            // TODO: delete this document from the index
            return null;
        }

        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT, $hit)) {
            return null;
        }

        return $hit;
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
            // Ensure the ID is global across the search index by prefixing the id
            'id' => $document->getType().'_'.$document->getId(),
            'type' => $document->getType(),
            'searchableContent' => $document->getSearchableContent(),
            'tags' => $document->getTags(),
            'document' => json_encode($document->toArray(), JSON_THROW_ON_ERROR),
        ];
    }
}
