<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
use PackageVersions\Versions;

class ParallelFileHashProvider implements FileHashProviderInterface
{
    // Note: This is just a quick demo to showcase parallel processing. This
    //       file hash provider doesn't use the flysystem and lacks lots of
    //       checks. I wouldn't ship this functionality in the core anyways.
    //
    // todo: remove

    private const REQUIRED_PACKAGE = 'amphp/parallel-functions';

    /** @var int */
    private $workerCount = 12;

    /** @var string */
    private $basePath;

    public function __construct(string $basePath)
    {
        try {
            Versions::getVersion(self::REQUIRED_PACKAGE);
        } catch (\OutOfBoundsException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Package %s needs to be installed in order to use %s.',
                    self::REQUIRED_PACKAGE,
                    __CLASS__
                )
            );
        }

        $this->basePath = $basePath;
    }

    public function getWorkerCount(): int
    {
        return $this->workerCount;
    }

    public function setWorkerCount(int $workerCount): void
    {
        $this->workerCount = $workerCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashes(array $paths): array
    {
        $workerFunction = static function ($filePathsChunk) {
            $hashes = [];
            foreach ($filePathsChunk as [$path, $absolutePath]) {
                $hashes[$path] = is_file($absolutePath) ? md5_file($absolutePath) : null;
            }

            return $hashes;
        };

        $chunkSize = max(
            (int) (\count($paths) / $this->workerCount) + 1,
            $this->workerCount
        );

        // add full paths information
        $paths = array_map(function (string $path) {
            return [$path, $this->basePath.'/'.$path];
        }, $paths);

        try {
            // process in parallel
            $hashMappingChunks = wait(
                parallelMap(
                    array_chunk($paths, $chunkSize),
                    $workerFunction
                )
            );
        } catch (\Throwable $e) {
            // fallback to processing the whole set as one chunk
            return $workerFunction($paths);
        }

        return array_merge(...$hashMappingChunks);
    }
}
