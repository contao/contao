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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\Escargot\Escargot;

abstract class AbstractFileHandlingListener implements EscargotEventSubscriber
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(RouterInterface $router, Filesystem $filesystem = null)
    {
        $this->router = $router;
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    abstract public function getName(): string;

    abstract public function getResultAsHtml(Escargot $escargot): string;

    abstract public function addResultToConsole(Escargot $escargot, OutputInterface $output): void;

    protected function createBinaryFileResponseForDownload(string $jobId, string $filename): BinaryFileResponse
    {
        $file = $this->initFile($jobId, $filename);

        $response = new BinaryFileResponse($file);
        $response->setPrivate();
        $response->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);

        return $response;
    }

    protected function writeCsv(string $jobId, string $filename, array $headers, array $lines): void
    {
        $file = $this->initFile($jobId, $filename);

        $handle = fopen($file, 'a');

        // Check if we need to add the headlines
        if (0 === filesize($file)) {
            fputcsv($handle, $headers);
        }

        foreach ($lines as $line) {
            fputcsv($handle, $line);
        }

        fclose($handle);
    }

    protected function initFile(string $jobId, string $filename): string
    {
        $file = sprintf(
            '%s/%s-%s',
            sys_get_temp_dir(),
            $jobId,
            $filename
        );

        if (!$this->filesystem->exists($file)) {
            $this->filesystem->dumpFile($file, '');
        }

        return $file;
    }

    protected function generateControllerLink(string $jobId, array $parameters = []): string
    {
        return $this->router->generate('contao_escargot_subscriber', array_merge([
            'subscriber' => $this->getName(),
            'jobId' => $jobId,
        ], $parameters), UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
