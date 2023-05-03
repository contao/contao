<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Contao\ManagerBundle\Dotenv\DotenvDumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Generates an APP_SECRET in the .env.local file.
 *
 * @internal
 */
class GenerateAppSecretCommand extends Command
{
    protected static $defaultName = 'contao:generate-app-secret';

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var null|string
     */
    private $kernelSecret;

    public function __construct(string $projectDir, ?string $kernelSecret)
    {
        parent::__construct();

        $this->projectDir = $projectDir;
        $this->kernelSecret = $kernelSecret;
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the generation of the APP_SECRET')
            ->addOption('length', 'l', InputOption::VALUE_REQUIRED, 'Length of the generated APP_SECRET', 64)
            ->setDescription('Generates an APP_SECRET in the .env.local file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');

        if (!empty($this->kernelSecret) && 'ThisTokenIsNotSoSecretChangeIt' !== $this->kernelSecret && !$force) {
            $io->note('Secret is already set.');

            return 0;
        }

        $length = (int) ceil((int) $input->getOption('length') / 2);

        if ($length <= 0) {
            throw new InvalidOptionException('Length must be greater than zero!');
        }

        $filesystem = new Filesystem();

        $dotenv = new DotenvDumper(Path::join($this->projectDir, '.env.local'), $filesystem);
        $dotenv->setParameter('APP_SECRET', bin2hex(random_bytes($length)));
        $dotenv->dump();

        $io->success('An APP_SECRET was generated and written to your .env.local file.');

        if (!$filesystem->exists($envPath = Path::join($this->projectDir, '.env'))) {
            $filesystem->touch($envPath);

            $io->note('An empty .env file was created.');
        }

        return 0;
    }
}
