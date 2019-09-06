<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\EventListener;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\Escargot\Escargot;

abstract class AbstractFileLoggingListener implements EscargotEventSubscriber, ControllerResultProvidingSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $logFile;

    /**
     * @var array
     */
    protected $logLines = [];

    public function __construct(RouterInterface $router, Filesystem $filesystem = null)
    {
        $this->router = $router;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    abstract public function getName(): string;

    abstract public function getResultAsHtml(Escargot $escargot): string;

    abstract public function addResultToConsole(Escargot $escargot, OutputInterface $output): void;

    public function controllerAction(Request $request, string $jobId): Response
    {
        $this->initLogFile($jobId);

        $response = new BinaryFileResponse($this->logFile);
        $response->setPrivate();
        $response->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $this->getFileName());

        return $response;
    }

    protected function writeLogLinesToFile(array $headers): void
    {
        $handle = fopen($this->logFile, 'a');

        // Check if we need to add the headlines
        if (0 === filesize($this->logFile)) {
            fputcsv($handle, $headers);
        }

        foreach ($this->logLines as $line) {
            fputcsv($handle, $line);
        }

        fclose($handle);
    }

    protected function initLogFile(string $jobId): void
    {
        if (null !== $this->logFile) {
            return;
        }

        $this->logFile = sprintf('%s/%s-%s',
            sys_get_temp_dir(),
            $jobId,
            $this->getFileName()
        );

        if (!$this->filesystem->exists($this->logFile)) {
            $this->filesystem->dumpFile($this->logFile, '');
        }
    }

    abstract protected function getFileName(): string;

    protected function getDownloadLink(string $jobId, array $query = []): string
    {
        return $this->router->generate('contao_escargot_subscriber', array_merge([
            'subscriber' => $this->getName(),
            'jobId' => $jobId,
        ], $query));
    }
}
