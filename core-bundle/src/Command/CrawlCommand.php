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

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Crawl\Monolog\CrawlCsvLogHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\GroupHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Exception\InvalidJobIdException;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\FinishedCrawlingSubscriberInterface;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

#[AsCommand(
    name: 'contao:crawl',
    description: 'Crawls the Contao root pages with the desired subscribers.',
)]
class CrawlCommand extends Command
{
    private Escargot|null $escargot = null;

    public function __construct(
        private readonly Factory $escargotFactory,
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function getEscargot(): Escargot
    {
        return $this->escargot;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('job', InputArgument::OPTIONAL, 'An optional existing job ID')
            ->addOption('queue', null, InputArgument::OPTIONAL, 'Queue to use ("memory" or "doctrine")', 'memory')
            ->addOption('subscribers', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A list of subscribers to enable', $this->escargotFactory->getSubscriberNames())
            ->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'The number of concurrent requests that are going to be executed', '5')
            ->addOption('delay', null, InputOption::VALUE_REQUIRED, 'The number of microseconds to wait between requests (0 = throttling is disabled)', '0')
            ->addOption('max-requests', null, InputOption::VALUE_REQUIRED, 'The maximum number of requests to execute (0 = no limit)', '0')
            ->addOption('max-depth', null, InputOption::VALUE_REQUIRED, 'The maximum depth to crawl for (0 = no limit)', '3')
            ->addOption('no-progress', null, InputOption::VALUE_NONE, 'Disables the progress bar output')
            ->addOption('enable-debug-csv', null, InputOption::VALUE_NONE, 'Writes the crawl debug log into a separate CSV file')
            ->addOption('debug-csv-path', null, InputOption::VALUE_REQUIRED, 'The path of the debug log CSV file', Path::join(getcwd(), 'crawl_debug_log.csv'))
            ->setHelp('You can add additional URIs via the <info>contao.crawl.additional_uris</info> parameter.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Contao Crawler');

        $subscribers = $input->getOption('subscribers');
        $baseUris = $this->escargotFactory->getCrawlUriCollection();

        if ($baseUris->containsHost('localhost')) {
            $io->warning('You are going to crawl localhost URIs. This is likely not desired and due to a missing domain configuration in your root page settings. You may also configure a fallback request context using "router.request_context.*" if you want to execute all CLI commands with the same request context.');
        }

        switch ($input->getOption('queue')) {
            case 'memory':
                $queue = new InMemoryQueue();
                break;

            case 'doctrine':
                $queue = $this->escargotFactory->createLazyQueue();
                break;

            default:
                $io->error('Only "memory" or "doctrine" are allowed for the "queue" option.');

                return 1;
        }

        try {
            if ($jobId = $input->getArgument('job')) {
                $this->escargot = $this->escargotFactory->createFromJobId($jobId, $queue, $subscribers);
            } else {
                $this->escargot = $this->escargotFactory->create($baseUris, $queue, $subscribers);
            }
        } catch (InvalidJobIdException) {
            $io->error('Could not find the given job ID.');

            return Command::FAILURE;
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $logOutput = $output instanceof ConsoleOutput ? $output->section() : $output;

        $this->escargot = $this->escargot
            ->withLogger($this->createLogger($logOutput, $input))
            ->withConcurrency((int) $input->getOption('concurrency'))
            ->withRequestDelay((int) $input->getOption('delay'))
            ->withMaxRequests((int) $input->getOption('max-requests'))
            ->withMaxDepth((int) $input->getOption('max-depth'))
        ;

        if (!$input->getOption('no-progress')) {
            $this->addProgressBar($output);
        }

        $this->escargot->crawl();

        $output->writeln('');
        $output->writeln('');

        $io->comment(
            \sprintf(
                '[Job ID: %s] Finished crawling! Find the details for each subscriber below:',
                $this->escargot->getJobId(),
            ),
        );

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

        return $errored ? Command::FAILURE : Command::SUCCESS;
    }

    private function createLogger(OutputInterface $output, InputInterface $input): LoggerInterface
    {
        $handlers = [];

        if ($input->getOption('enable-debug-csv')) {
            // Delete file if it already exists
            if ($this->filesystem->exists($input->getOption('debug-csv-path'))) {
                $this->filesystem->remove($input->getOption('debug-csv-path'));
            }

            $csvDebugHandler = new CrawlCsvLogHandler($input->getOption('debug-csv-path'), Level::Debug);
            $handlers[] = $csvDebugHandler;
        }

        $outputHandler = new ConsoleHandler($output);
        $outputHandler->setFormatter(new LineFormatter("[%context.source%] [%context.crawlUri%] %message%\n"));
        $handlers[] = $outputHandler;

        $groupHandler = new GroupHandler($handlers);

        $logger = new Logger('crawl-logger');
        $logger->pushHandler($groupHandler);

        return $logger;
    }

    private function addProgressBar(OutputInterface $output): void
    {
        $processOutput = $output instanceof ConsoleOutput ? $output->section() : $output;

        $progressBar = new ProgressBar($processOutput);
        $progressBar->setFormat("%title%\n%current%/%max% [%bar%] %percent:3s%%");
        $progressBar->setMessage('Crawling…', 'title');
        $progressBar->start();

        $this->escargot->addSubscriber($this->getProgressSubscriber($progressBar));
    }

    private function getProgressSubscriber(ProgressBar $progressBar): SubscriberInterface
    {
        return new class($progressBar) implements SubscriberInterface, EscargotAwareInterface, FinishedCrawlingSubscriberInterface {
            use EscargotAwareTrait;

            public function __construct(private readonly ProgressBar|null $progressBar)
            {
            }

            public function shouldRequest(CrawlUri $crawlUri): string
            {
                // We advance with every shouldRequest() call to update the progress bar
                // frequently enough
                $this->progressBar->advance();
                $this->progressBar->setMaxSteps($this->escargot->getQueue()->countAll($this->escargot->getJobId()));

                return SubscriberInterface::DECISION_ABSTAIN;
            }

            public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
            {
                return SubscriberInterface::DECISION_ABSTAIN;
            }

            public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
            {
                // noop
            }

            public function finishedCrawling(): void
            {
                $this->progressBar->setMessage('Done!', 'title');
                $this->progressBar->finish();
                $this->progressBar->display();
            }
        };
    }
}
