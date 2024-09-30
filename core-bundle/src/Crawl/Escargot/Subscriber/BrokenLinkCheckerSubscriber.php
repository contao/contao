<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Escargot\Subscriber;

use Nyholm\Psr7\Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\ExceptionSubscriberInterface;
use Terminal42\Escargot\Subscriber\SubscriberInterface;
use Terminal42\Escargot\SubscriberLoggerTrait;

class BrokenLinkCheckerSubscriber implements EscargotSubscriberInterface, EscargotAwareInterface, ExceptionSubscriberInterface, LoggerAwareInterface
{
    use EscargotAwareTrait;
    use LoggerAwareTrait;
    use SubscriberLoggerTrait;

    final public const TAG_SKIP = 'skip-broken-link-checker';

    private array $stats = ['ok' => 0, 'error' => 0];

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getName(): string
    {
        return 'broken-link-checker';
    }

    public function shouldRequest(CrawlUri $crawlUri): string
    {
        if ($crawlUri->hasTag(self::TAG_SKIP)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Did not check because it was marked to be skipped using the data-skip-broken-link-checker attribute.',
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Only check URIs that are part of our base collection or were found on one
        $fromBaseUriCollection = $this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost());

        $foundOnBaseUriCollection = $crawlUri->getFoundOn()
            && ($originalCrawlUri = $this->escargot->getCrawlUri($crawlUri->getFoundOn()))
            && $this->escargot->getBaseUris()->containsHost($originalCrawlUri->getUri()->getHost());

        if (!$fromBaseUriCollection && !$foundOnBaseUriCollection) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Did not check because it is not part of the base URI collection or was not found on one of that is.',
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 400) {
            $this->logError($crawlUri, 'HTTP Status Code: '.$statusCode);

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        ++$this->stats['ok'];

        // Skip any redirected URLs that are now outside our base hosts (#4213)
        $actualHost = (new Uri($response->getInfo('url')))->getHost();

        if ($crawlUri->getUri()->getHost() !== $actualHost && !$this->escargot->getBaseUris()->containsHost($actualHost)) {
            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // When URI is part of the base uri collection, request content. This is needed
        // to make sure HtmlCrawlerSubscriber::onLastChunk() is triggered.
        if ($this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost())) {
            return SubscriberInterface::DECISION_POSITIVE;
        }

        return SubscriberInterface::DECISION_NEGATIVE;
    }

    public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
    {
        // noop
    }

    public function getResult(SubscriberResult|null $previousResult = null): SubscriberResult
    {
        $stats = $this->stats;

        if ($previousResult) {
            $stats['ok'] += $previousResult->getInfo('stats')['ok'];
            $stats['error'] += $previousResult->getInfo('stats')['error'];
        }

        $result = new SubscriberResult(
            0 === $stats['error'],
            $this->translator->trans('CRAWL.brokenLinkChecker.summary', [$stats['ok'], $stats['error']], 'contao_default'),
        );

        $result->addInfo('stats', $stats);

        return $result;
    }

    public function onTransportException(CrawlUri $crawlUri, TransportExceptionInterface $exception, ResponseInterface $response): void
    {
        $this->logError($crawlUri, 'Could not request properly: '.$exception->getMessage());
    }

    public function onHttpException(CrawlUri $crawlUri, HttpExceptionInterface $exception, ResponseInterface $response, ChunkInterface $chunk): void
    {
        $this->logError($crawlUri, 'HTTP Status Code: '.$response->getStatusCode());
    }

    private function logError(CrawlUri $crawlUri, string $message): void
    {
        ++$this->stats['error'];

        $this->logWithCrawlUri($crawlUri, LogLevel::ERROR, \sprintf('Broken link! %s.', $message));
    }
}
