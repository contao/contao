<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Search\Escargot\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\FinishedCrawlingSubscriberInterface;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class CrawlCommand extends Command
{
    /**
     * @var Factory
     */
    private $escargotFactory;

    public function __construct(Factory $escargotFactory)
    {
        $this->escargotFactory = $escargotFactory;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:crawl')
            ->addOption('subscribers', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A list of subscribers to enable.', $this->escargotFactory->getSubscriberNames())
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'The number of concurrent requests that are going to be executed.', 10)
            ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'The number of microseconds to wait between requests. (0 = throttling is disabled)', 0)
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disables the progess bar output')
            ->setDescription('Crawls all Contao root pages plus additional URIs configured using (contao.search.additional_uris) and triggers the desired subscribers.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Contao Crawler');

        $subscribers = $input->getOption('subscribers');
        $queue = new InMemoryQueue();
        $baseUris = $this->escargotFactory->getSearchUriCollection();

        try {
            $escargot = $this->escargotFactory->create($baseUris, $queue, $subscribers);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $logOutput = $output instanceof ConsoleOutput ? $output->section() : $output;

        $escargot = $escargot->withLogger($this->createSourceProvidingConsoleLogger($logOutput));
        $escargot = $escargot->withConcurrency((int) $input->getOption('concurrency'));
        $escargot = $escargot->withRequestDelay((int) $input->getOption('delay'));

        $io->comment('Started crawling...');

        if (!$input->getOption('no-progress')) {
            $this->addProgressBar($escargot, $output);
        }

        $escargot->crawl();

        $output->writeln('');
        $output->writeln('');
        $io->comment('Finished crawling! Find the details for each subscriber below:');

        $errored = false;

        foreach ($this->escargotFactory->getSubscribers($subscribers) as $subscriber) {
            $io->section($subscriber->getName());
            $result = $subscriber->getResult();

            if ($result->wasSuccessful()) {
                $io->success($result->getSummary());
            } else {
                $io->error($result->getSummary());
                $errored = true;
            }

            if ($result->getWarning()) {
                $io->warning($result->getWarning());
            }
        }

        return (int) $errored;
    }

    private function createSourceProvidingConsoleLogger(OutputInterface $output): ConsoleLogger
    {
        return new class($output) extends ConsoleLogger {
            public function log($level, $message, array $context = []): void
            {
                $message = '[{source}] '.$message;

                parent::log($level, $message, $context);
            }
        };
    }

    private function addProgressBar(Escargot $escargot, OutputInterface $output): void
    {
        $processOutput = $output instanceof ConsoleOutput ? $output->section() : $output;

        $progressBar = new ProgressBar($processOutput);
        $progressBar->setFormat("%title%\n%current%/%max% [%bar%] %percent:3s%%");
        $progressBar->setMessage('Starting to crawl...', 'title');

        $progressBar->start();
        $escargot->addSubscriber($this->getProgressSubscriber($progressBar));
    }

    private function getProgressSubscriber(ProgressBar $progressBar): SubscriberInterface
    {
        return new class($progressBar) implements SubscriberInterface, EscargotAwareInterface, FinishedCrawlingSubscriberInterface {
            use EscargotAwareTrait;

            /**
             * @var ProgressBar
             */
            private $progressBar;

            public function __construct(ProgressBar $progressBar)
            {
                $this->progressBar = $progressBar;
            }

            public function shouldRequest(CrawlUri $crawlUri, string $currentDecision): string
            {
                // We advance with every shouldRequest() call to update the progress bar frequently enough
                $this->progressBar->advance(1);
                $this->progressBar->setMaxSteps($this->escargot->getQueue()->countAll($this->escargot->getJobId()));

                return $currentDecision;
            }

            public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk, string $currentDecision): string
            {
                return $currentDecision;
            }

            public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
            {
                // We only update the message here, otherwise too many nonsense URIs will be shown
                $this->progressBar->setMessage((string) $crawlUri->getUri(), 'title');
            }

            public function finishedCrawling(): void
            {
                $this->progressBar->setMessage('Done!', 'title');
                $this->progressBar->finish();
            }
        };
    }
}
