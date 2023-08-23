<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Maker;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\Reflection\ImportExtractor;
use Contao\MakerBundle\Reflection\MethodDefinition;
use Contao\MakerBundle\Reflection\SignatureGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class MakeDcaCallback extends AbstractMaker
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ClassGenerator $classGenerator,
        private readonly ResourceFinder $resourceFinder,
        private readonly SignatureGenerator $signatureGenerator,
        private readonly ImportExtractor $importExtractor,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:contao:dca-callback';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new DCA callback listener';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('callback-class', InputArgument::REQUIRED, sprintf('Enter a class name for the callback (e.g. <fg=yellow>%sListener</>)', Str::asClassName(Str::getRandomTerm())))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->askForTable($input, $io, $command);
        $this->askForTarget($input, $io, $command);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $table = $input->getArgument('table');
        $target = $input->getArgument('target');
        $name = $input->getArgument('callback-class');

        $targets = $this->getTargets();

        if (!\array_key_exists($target, $targets)) {
            $io->error('Invalid DCA callback: '.$target);

            return;
        }

        $definition = $targets[$target];
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\DataContainer\\');

        if (str_contains((string) $target, '{')) {
            $chunks = explode('.', (string) $target);

            foreach ($chunks as $chunk) {
                if ('{' === $chunk[0]) {
                    $target = str_replace($chunk, $input->getArgument($chunk), $target);
                }
            }
        }

        $this->classGenerator->generate([
            'source' => 'dca-callback/Callback.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'uses' => $this->importExtractor->extract($definition),
                'table' => $table,
                'target' => $target,
                'className' => $elementDetails->getShortName(),
                'signature' => $this->signatureGenerator->generate($definition, '__invoke'),
                'body' => $definition->getBody(),
            ],
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    private function askForTable(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('table', InputArgument::REQUIRED);

        $tables = $this->getTables();

        $io->writeln(' <fg=green>Suggested tables:</>');
        $io->listing($tables);

        $question = new Question('Enter a table for the callback');
        $question->setAutocompleterValues($tables);
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('table', $io->askQuestion($question));
    }

    private function askForTarget(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('target', InputArgument::REQUIRED);

        $targets = $this->getTargets();

        $io->writeln(' <fg=green>Suggested targets:</>');
        $io->listing(array_keys($targets));

        $question = new Question('Enter a target for the callback');
        $question->setAutocompleterValues(array_keys($targets));
        $question->setValidator(Validator::notBlank(...));

        $target = $io->askQuestion($question);

        if (str_contains((string) $target, '{')) {
            $chunks = explode('.', (string) $target);

            foreach ($chunks as $chunk) {
                if ('{' !== $chunk[0]) {
                    continue;
                }

                $command->addArgument($chunk, InputArgument::OPTIONAL);

                $question = new Question(sprintf('Please enter a value for "%s"', $chunk));
                $question->setValidator(Validator::notBlank(...));

                $input->setArgument($chunk, $io->askQuestion($question));
            }
        }

        $input->setArgument('target', $target);
    }

    /**
     * @return array<int, string>
     */
    private function getTables(): array
    {
        $this->framework->initialize();

        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        $tables = array_map(
            static fn (SplFileInfo $input) => str_replace('.php', '', $input->getRelativePathname()),
            iterator_to_array($files->getIterator())
        );

        $tables = array_values($tables);

        return array_unique($tables);
    }

    /**
     * @return array<string, MethodDefinition>
     */
    private function getTargets(): array
    {
        $yaml = Yaml::parseFile(__DIR__.'/../../config/callbacks.yaml');
        $targets = [];

        foreach ($yaml['callbacks'] as $key => $config) {
            $targets[$key] = new MethodDefinition($config['return_type'], $config['arguments']);
        }

        return $targets;
    }
}
