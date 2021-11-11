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
use Contao\MakerBundle\ImportExtractor;
use Contao\MakerBundle\MethodDefinition;
use Contao\MakerBundle\SignatureGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class MakeDcaCallback extends AbstractMaker
{
    private ContaoFramework $framework;
    private ClassGenerator $classGenerator;
    private ResourceFinder $resourceFinder;
    private SignatureGenerator $signatureGenerator;
    private ImportExtractor $importExtractor;

    public function __construct(ContaoFramework $framework, ClassGenerator $classGenerator, ResourceFinder $resourceFinder, SignatureGenerator $signatureGenerator, ImportExtractor $importExtractor)
    {
        $this->framework = $framework;
        $this->classGenerator = $classGenerator;
        $this->resourceFinder = $resourceFinder;
        $this->signatureGenerator = $signatureGenerator;
        $this->importExtractor = $importExtractor;
    }

    public static function getCommandName(): string
    {
        return 'make:contao:dca-callback';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a DCA callback')
            ->addArgument('className', InputArgument::REQUIRED, 'Enter a class name for the callback (e.g. <fg=yellow>FooListener</>)')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $requiredValidator = static function ($input) {
            if (null === $input || '' === $input) {
                throw new RuntimeCommandException('This value cannot be blank');
            }

            return $input;
        };

        $definition = $command->getDefinition();

        // Tables
        $command->addArgument('table', InputArgument::REQUIRED, 'Enter a table for the callback');
        $argument = $definition->getArgument('table');
        $tables = $this->getTables();

        $io->writeln(' <fg=green>Suggested tables:</>');
        $io->listing($tables);

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues($tables);
        $input->setArgument('table', $io->askQuestion($question));

        // Targets
        $command->addArgument('target', InputArgument::REQUIRED, 'Enter a target for the callback');
        $argument = $definition->getArgument('target');
        $targets = $this->getTargets();

        $io->writeln(' <fg=green>Suggested targets:</>');
        $io->listing(array_keys($targets));

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues(array_keys($targets));
        $input->setArgument('target', $io->askQuestion($question));

        $target = $input->getArgument('target');

        // Dynamic targets
        if (false !== strpos($target, '{')) {
            $chunks = explode('.', $target);

            foreach ($chunks as $chunk) {
                if ('{' !== $chunk[0]) {
                    continue;
                }

                $command->addArgument(
                    $chunk,
                    InputArgument::REQUIRED,
                    sprintf('Please enter a value for "%s"', $chunk)
                );

                $argument = $definition->getArgument($chunk);

                $question = new Question($argument->getDescription());
                $question->setValidator($requiredValidator);

                $input->setArgument($chunk, $io->askQuestion($question));
            }
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $availableTargets = $this->getTargets();

        $target = $input->getArgument('target');
        $table = $input->getArgument('table');
        $name = $input->getArgument('className');

        if (!\array_key_exists($target, $availableTargets)) {
            $io->error(sprintf('Callback definition "%s" not found.', $target));

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $availableTargets[$target];

        $signature = $this->signatureGenerator->generate($definition, '__invoke');
        $uses = $this->importExtractor->extract($definition);
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        // Dynamic targets
        if (false !== strpos($target, '{')) {
            $chunks = explode('.', $target);

            foreach ($chunks as $chunk) {
                if ('{' !== $chunk[0]) {
                    continue;
                }

                $target = str_replace($chunk, $input->getArgument($chunk), $target);
            }
        }

        $this->classGenerator->generate([
            'source' => 'dca-callback/Callback.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'target' => $target,
                'table' => $table,
                'signature' => $signature,
                'uses' => $uses,
                'body' => $definition->getBody(),
            ],
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
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
        $yaml = Yaml::parseFile(__DIR__.'/../Resources/config/callbacks.yaml');
        $targets = [];

        foreach ($yaml['callbacks'] as $key => $config) {
            $targets[$key] = new MethodDefinition($config['return_type'], $config['arguments']);
        }

        return $targets;
    }
}
