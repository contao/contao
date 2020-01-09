<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Escargot\Subscriber;

use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\ExceptionSubscriberInterface;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class BrokenLinkCheckerSubscriber implements EscargotSubscriberInterface, EscargotAwareInterface, ExceptionSubscriberInterface
{
    use EscargotAwareTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var array
     */
    private $stats = ['ok' => 0, 'error' => 0];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'broken-link-checker';
    }

    public function shouldRequest(CrawlUri $crawlUri): string
    {
        // Only check URIs that are part of our base collection or were found on one
        $fromBaseUriCollection = $this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost());

        $foundOnBaseUriCollection = null !== $crawlUri->getFoundOn()
            && ($originalCrawlUri = $this->escargot->getCrawlUri($crawlUri->getFoundOn()))
            && $this->escargot->getBaseUris()->containsHost($originalCrawlUri->getUri()->getHost());

        if (!$fromBaseUriCollection && !$foundOnBaseUriCollection) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Did not check because it is not part of the base URI collection or was not found on one of that is.'),
                ['source' => static::class]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
    {
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            $this->logError($crawlUri, 'HTTP Status Code: '.$response->getStatusCode());
        } else {
            ++$this->stats['ok'];
        }

        return SubscriberInterface::DECISION_NEGATIVE;
    }

    public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
    {
        // noop
    }

    public function getResult(SubscriberResult $previousResult = null): SubscriberResult
    {
        $stats = $this->stats;

        if (null !== $previousResult) {
            $stats['ok'] += $previousResult->getInfo('stats')['ok'];
            $stats['error'] += $previousResult->getInfo('stats')['error'];
        }

        $result = new SubscriberResult(
            0 === $stats['error'],
            $this->translator->trans('CRAWL.brokenLinkChecker.summary', [$stats['ok'], $stats['error']], 'contao_default')
        );

        $result->addInfo('stats', $stats);

        return $result;
    }

    public function onException(CrawlUri $crawlUri, ExceptionInterface $exception, ResponseInterface $response, ChunkInterface $chunk = null): void
    {
        if ($exception instanceof TransportExceptionInterface) {
            $this->logError($crawlUri, 'Could not request properly: '.$exception->getMessage());

            return;
        }

        try {
            $isLastChunk = null !== $chunk && $chunk->isLast();
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $exception) {
            $this->logError($crawlUri, 'Could not request properly: '.$exception->getMessage());

            return;
        }

        // Only log on last chunk for HttpExceptions, otherwise we have a log entry on every chunk that arrives.
        if ($exception instanceof HttpExceptionInterface && null !== $chunk && !$isLastChunk) {
            return;
        }

        $this->logError($crawlUri, 'HTTP Status Code: '.$statusCode);
    }

    private function logError(CrawlUri $crawlUri, string $message): void
    {
        ++$this->stats['error'];

        $this->escargot->log(
            LogLevel::ERROR,
            $crawlUri->createLogMessage(sprintf('Broken link! %s.', $message)),
            ['source' => static::class]
        );
    }
}
