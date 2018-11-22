<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Installs the web entry points for Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class InstallWebDirCommand extends AbstractLockedCommand
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install-web-dir')
            ->setDefinition([
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
                new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Do not install the app_dev.php entry point'),
                new InputOption('user', 'u', InputOption::VALUE_REQUIRED, 'Set a username for app_dev.php', false),
                new InputOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Set a password for app_dev.php', false),
            ])
            ->setDescription('Installs the files in the "web" directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getOption('user');
        $password = $input->getOption('password');

        if ((false !== $user || false !== $password) && true === $input->getOption('no-dev')) {
            throw new \InvalidArgumentException('Cannot set a password in no-dev mode');
        }

        // Return if both username and password are set or both are not set
        if (($user && $password) || (false === $user && false === $password)) {
            return;
        }

        // A password is given on the command line but no user
        if (false === $user && $password) {
            throw new \InvalidArgumentException('Must have username and password');
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        if (false === $user) {
            $input->setOption(
                'user',
                $helper->ask($input, $output, new Question('Please enter a username:'))
            );
        }

        $input->setOption(
            'password',
            $helper->ask($input, $output, (new Question('Please enter a password:'))->setHidden(true))
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $webDir = $this->rootDir.'/'.rtrim($input->getArgument('target'), '/');

        $this->addHtaccess($webDir);
        $this->addFiles($webDir, !$input->getOption('no-dev'));
        $this->removeInstallPhp($webDir);
        $this->storeAppDevAccesskey($input, $this->rootDir);

        return 0;
    }

    /**
     * Adds the .htaccess file or merges it with an existing one.
     *
     * @param string $webDir
     */
    private function addHtaccess($webDir)
    {
        $htaccess = __DIR__.'/../Resources/web/.htaccess';

        if (!file_exists($webDir.'/.htaccess')) {
            $this->fs->copy($htaccess, $webDir.'/.htaccess', true);
            $this->io->writeln('Added the <comment>web/.htaccess</comment> file.');

            return;
        }

        $existingContent = file_get_contents($webDir.'/.htaccess');

        // Return if there already is a rewrite rule
        if (preg_match('/^\s*RewriteRule\s/im', $existingContent)) {
            return;
        }

        $this->fs->dumpFile($webDir.'/.htaccess', $existingContent."\n\n".file_get_contents($htaccess));
        $this->io->writeln('Updated the <comment>web/.htaccess</comment> file.');
    }

    /**
     * Adds files from Resources/web to the application's web directory.
     *
     * @param string $webDir
     * @param bool   $dev
     */
    private function addFiles($webDir, $dev = true)
    {
        /** @var Finder $finder */
        $finder = Finder::create()->files()->in(__DIR__.'/../Resources/web');

        foreach ($finder as $file) {
            if ($this->fs->exists($webDir.'/'.$file->getRelativePathname())) {
                continue;
            }

            if (!$dev && 'app_dev.php' === $file->getRelativePathname()) {
                continue;
            }

            $this->fs->copy($file->getPathname(), $webDir.'/'.$file->getRelativePathname(), true);
            $this->io->writeln(sprintf('Added the <comment>web/%s</comment> file.', $file->getFilename()));
        }
    }

    /**
     * Removes the install.php entry point leftover from Contao <4.4.
     *
     * @param string $webDir
     */
    private function removeInstallPhp($webDir)
    {
        if (!file_exists($webDir.'/install.php')) {
            return;
        }

        $this->fs->remove($webDir.'/install.php');
        $this->io->writeln('Deleted the <comment>web/install.php</comment> file.');
    }

    /**
     * Stores username and password in .env file in the project directory.
     *
     * @param InputInterface $input
     * @param string         $projectDir
     */
    private function storeAppDevAccesskey(InputInterface $input, $projectDir)
    {
        $user = $input->getOption('user');
        $password = $input->getOption('password');

        if (false === $password && false === $user) {
            return;
        }

        if (($user || $password) && true === $input->getOption('no-dev')) {
            throw new \InvalidArgumentException('Cannot set a password in no-dev mode!');
        }

        if (!$user || !$password) {
            throw new \InvalidArgumentException('Must have username and password to set the access key.');
        }

        $accessKey = password_hash(
            $input->getOption('user').':'.$input->getOption('password'),
            PASSWORD_DEFAULT
        );

        $this->addToDotEnv($projectDir, 'APP_DEV_ACCESSKEY', $accessKey);
    }

    /**
     * Appends value to the .env file, removing a line with the given key.
     *
     * @param string $projectDir
     * @param string $key
     * @param string $value
     */
    private function addToDotEnv($projectDir, $key, $value)
    {
        $fs = new Filesystem();

        $path = $projectDir.'/.env';
        $content = '';

        if ($fs->exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES);

            if (false === $lines) {
                throw new \RuntimeException(sprintf('Could not read "%s" file.', $path));
            }

            foreach ($lines as $line) {
                if (0 === strpos($line, $key.'=')) {
                    continue;
                }

                $content .= $line."\n";
            }
        }

        $fs->dumpFile($path, $content.$key."='".str_replace("'", "'\\''", $value)."'\n");
    }
}
