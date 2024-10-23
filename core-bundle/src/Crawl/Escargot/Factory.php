<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Escargot;

use Contao\CoreBundle\Crawl\Escargot\Subscriber\EscargotSubscriberInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Exception\InvalidJobIdException;
use Terminal42\Escargot\Queue\DoctrineQueue;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Queue\LazyQueue;
use Terminal42\Escargot\Queue\QueueInterface;
use Terminal42\Escargot\Subscriber\HtmlCrawlerSubscriber;
use Terminal42\Escargot\Subscriber\RobotsSubscriber;

class Factory
{
    final public const USER_AGENT = 'contao/crawler';

    /**
     * @var array<EscargotSubscriberInterface>
     */
    private array $subscribers = [];

    /**
     * @var \Closure(array<string, mixed>): HttpClientInterface
     */
    private readonly \Closure $httpClientFactory;

    /**
     * @param array<string>                                            $additionalUris
     * @param \Closure(array<string, mixed>): HttpClientInterface|null $httpClientFactory
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly RequestStack $requestStack,
        private readonly UserProviderInterface $userProvider,
        private readonly LoginLinkHandlerInterface $loginLinkHandler,
        private readonly HttpClientInterface $httpClient,
        private readonly UriSigner $uriSigner,
        private readonly array $additionalUris = [],
        private readonly array $defaultHttpClientOptions = [],
        \Closure|null $httpClientFactory = null,
    ) {
        $this->httpClientFactory = $httpClientFactory ?? static fn (array $defaultOptions) => HttpClient::create($defaultOptions);
    }

    public function addSubscriber(EscargotSubscriberInterface $subscriber): self
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    /**
     * @return array<EscargotSubscriberInterface>
     */
    public function getSubscribers(array $selectedSubscribers = []): array
    {
        if (!$selectedSubscribers) {
            return $this->subscribers;
        }

        return array_filter(
            $this->subscribers,
            static fn (EscargotSubscriberInterface $subscriber): bool => \in_array($subscriber->getName(), $selectedSubscribers, true),
        );
    }

    /**
     * @return array<string>
     */
    public function getSubscriberNames(): array
    {
        return array_map(
            static fn (EscargotSubscriberInterface $subscriber): string => $subscriber->getName(),
            $this->subscribers,
        );
    }

    public function createLazyQueue(): LazyQueue
    {
        return new LazyQueue(
            new InMemoryQueue(),
            new DoctrineQueue($this->connection, static fn (): string => Uuid::v4()->toRfc4122(), 'tl_crawl_queue'),
        );
    }

    public function getDefaultHttpClientOptions(): array
    {
        return $this->defaultHttpClientOptions;
    }

    public function getCrawlUriCollection(): BaseUriCollection
    {
        return $this->getRootPageUriCollection()->mergeWith($this->getAdditionalCrawlUriCollection());
    }

    public function getAdditionalCrawlUriCollection(): BaseUriCollection
    {
        $collection = new BaseUriCollection();

        foreach ($this->additionalUris as $additionalUri) {
            $collection->add(new Uri($additionalUri));
        }

        return $collection;
    }

    public function getRootPageUriCollection(): BaseUriCollection
    {
        $this->framework->initialize();

        $collection = new BaseUriCollection();
        $pageModel = $this->framework->getAdapter(PageModel::class);

        if (!$rootPages = $pageModel->findPublishedRootPages()) {
            return $collection;
        }

        foreach ($rootPages as $rootPage) {
            try {
                $collection->add(new Uri($this->urlGenerator->generate($rootPage, [], UrlGeneratorInterface::ABSOLUTE_URL)));
            } catch (ExceptionInterface) {
            }
        }

        return $collection;
    }

    public function create(BaseUriCollection $baseUris, QueueInterface $queue, array $selectedSubscribers, array $clientOptions = [], string $username = null): Escargot
    {
        if ($username) {
            $clientOptions['headers']['Cookie'] = $this->getAuthenticatedCookie($baseUris->all(), $username);
        }

        $escargot = Escargot::create($baseUris, $queue)->withHttpClient($this->createHttpClient($clientOptions));

        $this->registerDefaultSubscribers($escargot);
        $this->registerSubscribers($escargot, $this->validateSubscribers($selectedSubscribers));

        return $escargot;
    }

    /**
     * @throws InvalidJobIdException
     */
    public function createFromJobId(string $jobId, QueueInterface $queue, array $selectedSubscribers, array $clientOptions = [], string $username = null): Escargot
    {
        if ($username) {
            $clientOptions['headers']['Cookie'] = $this->getAuthenticatedCookie($queue->getBaseUris($jobId)->all(), $username);
        }

        $escargot = Escargot::createFromJobId($jobId, $queue)->withHttpClient($this->createHttpClient($clientOptions));

        $this->registerDefaultSubscribers($escargot);
        $this->registerSubscribers($escargot, $this->validateSubscribers($selectedSubscribers));

        return $escargot;
    }

    private function createHttpClient(array $options = []): HttpClientInterface
    {
        $options = array_merge_recursive(
            [
                'headers' => [
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'user-agent' => self::USER_AGENT,
                ],
                'max_duration' => 10, // Ignore requests that take longer than 10 seconds
            ],
            array_merge_recursive($this->getDefaultHttpClientOptions(), $options),
        );

        // Make sure confidential headers force a scoped client so external domains do
        // not leak data
        $cleanOptions = $this->cleanOptionsFromConfidentialData($options);

        if ($options === $cleanOptions) {
            return ($this->httpClientFactory)($options);
        }

        $scopedOptionsByRegex = [];

        // All options including the confidential headers for our root page collection
        foreach ($this->getRootPageUriCollection()->all() as $rootPageUri) {
            $scopedOptionsByRegex[preg_quote($this->getOriginFromUri($rootPageUri))] = $options;
        }

        // Closing the session is necessary here as otherwise we might run into our own
        // session lock
        if ($this->requestStack->getMainRequest()?->hasSession()) {
            $this->requestStack->getMainRequest()->getSession()->save();
        }

        return new ScopingHttpClient(($this->httpClientFactory)($cleanOptions), $scopedOptionsByRegex);
    }

    private function getOriginFromUri(UriInterface $uri): string
    {
        $origin = $uri->getScheme().'://'.$uri->getHost();

        if ($uri->getPort()) {
            $origin .= ':'.$uri->getPort();
        }

        return $origin.'/';
    }

    private function cleanOptionsFromConfidentialData(array $options): array
    {
        $cleanOptions = [];

        foreach ($options as $k => $v) {
            if ('headers' === $k) {
                foreach ($v as $header => $value) {
                    if (\in_array(strtolower($header), ['authorization', 'cookie'], true)) {
                        continue;
                    }

                    $cleanOptions['headers'][$header] = $value;
                }

                continue;
            }

            if ('basic_auth' === $k || 'bearer_auth' === $k) {
                continue;
            }

            $cleanOptions[$k] = $v;
        }

        return $cleanOptions;
    }

    private function registerDefaultSubscribers(Escargot $escargot): void
    {
        $escargot->addSubscriber(new RobotsSubscriber());
        $escargot->addSubscriber(new HtmlCrawlerSubscriber());
    }

    private function registerSubscribers(Escargot $escargot, array $selectedSubscribers): void
    {
        foreach ($this->subscribers as $subscriber) {
            if (\in_array($subscriber->getName(), $selectedSubscribers, true)) {
                $escargot->addSubscriber($subscriber);
            }
        }
    }

    private function validateSubscribers(array $selectedSubscribers): array
    {
        $selectedSubscribers = array_intersect($this->getSubscriberNames(), $selectedSubscribers);

        if (!$selectedSubscribers) {
            throw new \InvalidArgumentException('You have to specify at least one valid subscriber name. Valid subscribers are: '.implode(', ', $this->getSubscriberNames()));
        }

        return $selectedSubscribers;
    }

    private function getAuthenticatedCookie(array $uris, string $username): ?Cookie
    {
        $mainRequest = $this->requestStack->getMainRequest();
        $cookieJar = new CookieJar();

        foreach ($uris as $uri) {
            $request = Request::create((string)$uri);

            if (null === $mainRequest) {
                $this->requestStack->push($request);
            }

            $user = $this->userProvider->loadUserByIdentifier($username);

            $loginLink = $this->loginLinkHandler->createLoginLink($user, $request);
            $loginUri = new Uri($loginLink->getUrl());

            parse_str($loginUri->getQuery(), $query);

            $query['_target_path'] = base64_encode($request->getUri());

            $url = $this->uriSigner->sign((string)$loginUri->withQuery(http_build_query($query)));

            try {
                $response = $this->httpClient->request('GET', $url);

                if (200 !== $response->getStatusCode()) {
                    continue;
                }

                $headers = $response->getHeaders();
            } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface) {
                continue;
            }

            if (\array_key_exists('set-cookie', $headers)) {
                $cookieJar->updateFromSetCookie($headers['set-cookie']);

                break;
            }
        }

        return $cookieJar->get(session_name());
    }
}
