<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\Seal;

use CmsIg\Seal\Reindex\ReindexConfig as SealReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Contao\CoreBundle\Event\BackendSearch\IndexDocumentEvent;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Integrates our own provider abstraction with SEAL. Having one central provider
 * that then calls our own providers with our own interfaces again "shields"
 * Contao developers from having to know or deal with SEAL directly. It also gives
 * us the ability to be compatible with multiple SEAL versions or implement BC
 * layers etc.
 */
class SealReindexProvider implements ReindexProviderInterface
{
    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function total(): int|null
    {
        return null;
    }

    public function provide(SealReindexConfig $reindexConfig): \Generator
    {
        // Not our index
        if (null !== $reindexConfig->getIndex() && BackendSearch::SEAL_INTERNAL_INDEX_NAME !== $reindexConfig->getIndex()) {
            return;
        }

        $internalConfig = SealUtil::sealReindexConfigToInternalReindexConfig($reindexConfig);

        /** @var ProviderInterface $provider */
        foreach ($this->providers as $provider) {
            /** @var Document $document */
            foreach ($provider->updateIndex($internalConfig) as $document) {
                $event = new IndexDocumentEvent($document);
                $this->eventDispatcher->dispatch($event);

                if (!$document = $event->getDocument()) {
                    continue;
                }

                yield SealUtil::convertProviderDocumentForSearchIndex($document);
            }
        }
    }

    public static function getIndex(): string
    {
        return BackendSearch::SEAL_INTERNAL_INDEX_NAME;
    }
}
