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

use Contao\ArrayUtil;
use Contao\CoreBundle\Dca\Provider\ConfigurationProviderInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'debug:dca',
    description: 'Dumps the DCA configuration for a table.',
)]
class DebugDcaCommand extends Command
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ConfigurationProviderInterface $configurationProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('table', InputArgument::REQUIRED, 'The table name')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to a node in the DCA')
            ->addOption('format', null, InputArgument::OPTIONAL, 'The output format (yaml or php)', 'yaml')
            ->addOption('raw', null, InputOption::VALUE_NONE, 'Do not parse the DCA configuration and dump the raw configuration data instead.')
            ->setDescription('Dumps the DCA configuration for a table.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorIo = $io->getErrorStyle();

        $this->framework->initialize();

        $table = $input->getArgument('table');
        $data = $input->getOption('raw') ? $this->getRawConfiguration($table) : $this->configurationProvider->getConfiguration($table);

        if ($path = $input->getArgument('path')) {
            $data = ArrayUtil::get($data, $path, null, true);
        }

        $format = $input->getOption('raw') ? 'php' : $input->getOption('format');

        if ('php' === $format) {
            $dumper = new CliDumper();
            $cloner = new VarCloner();

            $dumper->dump($cloner->cloneVar($data));

            return Command::SUCCESS;
        }

        if ('yaml' === $format && !class_exists(Yaml::class)) {
            $errorIo->error('Setting the "format" option to "yaml" requires the Symfony Yaml component. Try running "composer install symfony/yaml" or use "--format=php" instead.');

            return 1;
        }

        switch ($format) {
            case 'yaml':
                $dumper = new Dumper();
                $data = $dumper->dump($data, 10);
                break;

            default:
                throw new InvalidArgumentException('Only the yaml and php formats are supported.');
        }

        $io->writeln($data);

        return Command::SUCCESS;
    }

    private function getRawConfiguration(string $table): array
    {
        $dcaLoader = $this->framework->createInstance(DcaLoader::class, [$table]);
        $dcaLoader->load();

        if (!isset($GLOBALS['TL_DCA'][$table])) {
            throw new InvalidArgumentException('Invalid table name: '.$table);
        }

        return $GLOBALS['TL_DCA'][$table];
    }
}
