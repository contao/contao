<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[AsCronJob('daily')]
class PurgeTempFolderCron
{
    public function __construct(
        private Filesystem $filesystem,
        private string $projectDir,
        private LoggerInterface|null $logger,
    ) {
    }

    public function __invoke(): void
    {
        $finder = Finder::create()->in(Path::join($this->projectDir, 'system/tmp'));

        $this->filesystem->remove($finder->getIterator());

        $this->logger?->info('Purged the temp folder');
    }
}
