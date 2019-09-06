<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search;

use Contao\CoreBundle\Search\EventListener\EscargotEventSubscriber;
use Doctrine\DBAL\Driver\Connection;
use Nyholm\Psr7\Uri;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Exception\InvalidJobIdException;
use Terminal42\Escargot\Queue\DoctrineQueue;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Queue\LazyQueue;
use Terminal42\Escargot\Queue\QueueInterface;

class EscargotFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EscargotEventSubscriber[]
     */
    private $subscribers = [];

    /**
     * @var array<string>
     */
    private $additionalUris = [];

    public function __construct(Connection $connection, UrlGeneratorInterface $urlGenerator, array $additionalUris = [])
    {
        $this->connection = $connection;
        $this->urlGenerator = $urlGenerator;
        $this->additionalUris = $additionalUris;
    }

    public function addSubscriber(EscargotEventSubscriber $eventSubscriber): self
    {
        $this->subscribers[] = $eventSubscriber;

        return $this;
    }

    /**
     * @return EscargotEventSubscriber[]
     */
    public function getSubscribers(array $selectedSubscribers = []): array
    {
        if (0 === \count($selectedSubscribers)) {
            return $this->subscribers;
        }

        return array_filter(
            $this->subscribers,
            static function (EscargotEventSubscriber $subscriber) use ($selectedSubscribers) {
                return \in_array($subscriber->getName(), $selectedSubscribers, true);
            }
        );
    }

    public function getSubscriberNames(): array
    {
        return array_map(static function (EscargotEventSubscriber $subscriber) {
            return $subscriber->getName();
        },
            $this->subscribers
        );
    }

    public function createLazyQueue(): LazyQueue
    {
        return new LazyQueue(new InMemoryQueue(), new DoctrineQueue(
            $this->connection,
            static function () {
                return (string) Uuid::uuid4();
            },
            'tl_search_index_queue'
        ));
    }

    public function getSearchUriCollection(): BaseUriCollection
    {
        return $this->getRootPageUriCollection()->mergeWith($this->getAdditionalSearchUriCollection());
    }

    public function getAdditionalSearchUriCollection(): BaseUriCollection
    {
        $collection = new BaseUriCollection();

        foreach ($this->additionalUris as $additionalUri) {
            $collection->add(new Uri($additionalUri));
        }

        return $collection;
    }

    public function getRootPageUriCollection(): BaseUriCollection
    {
        $stmt = $this->connection->prepare('SELECT id, alias, language, dns, useSSL FROM tl_page WHERE type = :type');
        $stmt->execute([':type' => 'root']);
        $rootPages = $stmt->fetchAll();

        foreach ($rootPages as $rootPage) {
            $uri = $this->urlGenerator->generate(
                $rootPage['alias'] ?: $rootPage['id'],
                [
                    '_locale' => $rootPage['language'],
                    '_domain' => $rootPage['dns'],
                    '_ssl' => (bool) $rootPage['useSSL'],
                ]
            );
        }

        return new BaseUriCollection(); // TODO: find out how to exactly generate the root URLs and use them here
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function create(BaseUriCollection $baseUris, QueueInterface $queue, array $selectedSubscribers, HttpClientInterface $client = null): Escargot
    {
        $selectedSubsribers = $this->validateSubscribers($selectedSubscribers);

        $escargot = Escargot::create($baseUris, $queue, $client);

        $this->registerSubscribers($escargot, $selectedSubsribers);

        return $escargot;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws InvalidJobIdException
     */
    public function createFromJobId(string $jobId, QueueInterface $queue, array $selectedSubscribers, HttpClientInterface $client = null): Escargot
    {
        $selectedSubsribers = $this->validateSubscribers($selectedSubscribers);

        $escargot = Escargot::createFromJobId($jobId, $queue, $client);

        $this->registerSubscribers($escargot, $selectedSubsribers);

        return $escargot;
    }

    private function registerSubscribers(Escargot $escargot, array $selectedSubsribers): void
    {
        foreach ($this->subscribers as $subscriber) {
            if (\in_array($subscriber->getName(), $selectedSubsribers, true)) {
                $escargot->addSubscriber($subscriber);
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateSubscribers(array $selectedSubscribers): array
    {
        $msg = sprintf(
            'You have to specify at least one valid subscriber name. Valid subscribers are: %s',
            implode(', ', $this->getSubscriberNames())
        );

        if (0 === \count($selectedSubscribers)) {
            throw new \InvalidArgumentException($msg);
        }

        $selectedSubscribers = array_intersect($this->getSubscriberNames(), $selectedSubscribers);

        if (0 === \count($selectedSubscribers)) {
            throw new \InvalidArgumentException($msg);
        }

        return $selectedSubscribers;
    }
}
