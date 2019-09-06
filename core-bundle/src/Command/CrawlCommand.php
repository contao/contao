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

use Contao\CoreBundle\Search\EscargotFactory;
use Nyholm\Psr7\Uri;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Terminal42\Escargot\Event\AbstractResponseEvent;
use Terminal42\Escargot\Event\FinishedCrawlingEvent;
use Terminal42\Escargot\Event\SuccessfulResponseEvent;
use Terminal42\Escargot\Event\UnsuccessfulResponseEvent;
use Terminal42\Escargot\Queue\InMemoryQueue;

class CrawlCommand extends Command
{
    /**
     * @var EscargotFactory
     */
    private $escargotFactory;

    public function __construct(EscargotFactory $escargotFactory)
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
        //$baseUris->add(new Uri('https://www.terminal42.ch')); // TODO: debug
        $baseUris->add(new Uri('https://contao.org')); // TODO: debug

        try {
            $escargot = $this->escargotFactory->create($baseUris, $queue, $subscribers);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->comment('Started crawling...');

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat("%title%\n%current%/%max% [%bar%] %percent:3s%%");
        $progressBar->setMessage('Starting to crawl...', 'title');

        $progressBar->start();
        $progressSubscriber = $this->getProgressSubscriber($progressBar);

        $escargot->setConcurrency((int) $input->getOption('concurrency'));
        $escargot->setRequestDelay((int) $input->getOption('delay'));
        $escargot->addSubscriber($progressSubscriber);

        $escargot->crawl();

        if ($progressSubscriber->isFinished()) {
            $output->writeln('');
            $output->writeln('');
            $io->success('Finished crawling! Find the details for each subscriber below:');

            foreach ($this->escargotFactory->getSubscribers($subscribers) as $subscriber) {
                $io->section($subscriber->getName());
                $subscriber->addResultToConsole($escargot, $output);
            }
        }

        return 0;
    }

    private function getProgressSubscriber(ProgressBar $progressBar): EventSubscriberInterface
    {
        return new class($progressBar) implements EventSubscriberInterface {
            /**
             * @var ProgressBar
             */
            private $progressBar;

            /**
             * @var bool
             */
            private $isFinished = false;

            public function __construct(ProgressBar $progressBar)
            {
                $this->progressBar = $progressBar;
            }

            public function isFinished(): bool
            {
                return $this->isFinished;
            }

            public function onResponse(AbstractResponseEvent $event): void
            {
                $escargot = $event->getEscargot();

                $this->progressBar->setMessage((string) $event->getCrawlUri()->getUri(), 'title');
                $this->progressBar->setMaxSteps($escargot->getQueue()->countAll($escargot->getJobId()));
                $this->progressBar->advance(1);
            }

            public function onFinished(FinishedCrawlingEvent $event): void
            {
                $this->progressBar->setMessage('Done!', 'title');
                $this->progressBar->finish();
                $this->isFinished = true;
            }

            public static function getSubscribedEvents()
            {
                return [
                    SuccessfulResponseEvent::class => 'onResponse',
                    UnsuccessfulResponseEvent::class => 'onResponse',
                    FinishedCrawlingEvent::class => 'onFinished',
                ];
            }
        };
    }
}
