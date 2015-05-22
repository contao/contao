<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs the required Contao directories.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallCommand extends AbstractLockedCommand
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var array
     */
    private $emptyDirs = [
        'files',
        'system',
        'templates',
        'web/system',
    ];

    /**
     * @var array
     */
    private $ignoredDirs = [
        'assets/css',
        'assets/images',
        'assets/js',
        'system/cache',
        'system/config',
        'system/modules',
        'system/themes',
        'system/tmp',
        'web/share',
        'web/system/cron',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install')
            ->setDescription('Installs the required Contao directories')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->fs      = new Filesystem();
        $this->output  = $output;
        $this->rootDir = dirname($this->getContainer()->getParameter('kernel.root_dir'));

        $this->addEmptyDirs();
        $this->addIgnoredDirs();
        $this->addInitializePhp();

        return 0;
    }

    /**
     * Adds the empty directories.
     */
    private function addEmptyDirs()
    {
        foreach ($this->emptyDirs as $path) {
            $this->addEmptyDir($this->rootDir . '/' . $path);
        }
    }

    /**
     * Adds an empty directory.
     *
     * @param string $path The path
     */
    private function addEmptyDir($path)
    {
        if ($this->fs->exists($path)) {
            return;
        }

        $this->fs->mkdir($path);

        $this->output->writeln('Created the <comment>' . $path . '</comment> directory.');
    }

    /**
     * Adds the ignored directories.
     */
    private function addIgnoredDirs()
    {
        foreach ($this->ignoredDirs as $path) {
            $this->addIgnoredDir($this->rootDir . '/' . $path);
        }
    }

    /**
     * Adds a directory with a .gitignore file.
     *
     * @param string $path The path
     */
    private function addIgnoredDir($path)
    {
        $this->addEmptyDir($path);

        if ($this->fs->exists($path . '/.gitignore')) {
            return;
        }

        $this->fs->dumpFile(
            $path . '/.gitignore',
            "# Create the folder and ignore its content\n*\n!.gitignore\n"
        );

        $this->output->writeln('Added the <comment>' . $path . '/.gitignore</comment> file.');
    }

    /**
     * Adds the initialize.php file.
     */
    private function addInitializePhp()
    {
        if ($this->fs->exists($this->rootDir . '/system/initialize.php')) {
            return;
        }

        $this->fs->dumpFile(
            $this->rootDir . '/system/initialize.php',
            <<<'EOF'
<?php

// Deprecated since Contao 4.0, to be removed in Contao 5.0.

use Symfony\\Component\\HttpFoundation\\Request;

if (!defined('TL_SCRIPT')) {
    die('Your script is not compatible with Contao 4.');
}

$loader = require_once __DIR__ . '/../app/bootstrap.php.cache';

require_once __DIR__ . '/../app/AppKernel.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();
$kernel->handle(Request::create('/_initialize', 'GET', [], [], [], $_SERVER));

EOF

        );

        $this->output->writeln('Added the <comment>' . $this->rootDir . '/system/initialize.php</comment> file.');
    }
}
