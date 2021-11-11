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
use Contao\DataContainer;
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
            ->addArgument('className', InputArgument::REQUIRED, 'Enter a class name for the callback')
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
        return [
            'config.onload' => new MethodDefinition(
                'void',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'config.oncreate' => new MethodDefinition(
                'void',
                [
                    'table' => 'string',
                    'insertId' => 'int',
                    'fields' => 'array',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'config.onsubmit' => new MethodDefinition(
                'void',
                // Since there are multiple parameters for multiple calls, we cannot
                // safely assume the correct parameter names and types
                []
            ),
            'config.ondelete' => new MethodDefinition(
                'void',
                [
                    'dataContainer' => DataContainer::class,
                    'id' => 'int',
                ]
            ),
            'config.oncut' => new MethodDefinition(
                'void',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'config.oncopy' => new MethodDefinition(
                'void',
                [
                    'id' => 'int',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'config.oncreate_version' => new MethodDefinition(
                'void',
                [
                    'table' => 'string',
                    'pid' => 'int',
                    'versionNumber' => 'int',
                    'recordData' => 'array',
                ]
            ),
            'config.onrestore_version' => new MethodDefinition(
                'void',
                [
                    'table' => 'string',
                    'pid' => 'int',
                    'versionNumber' => 'int',
                    'recordData' => 'array',
                ]
            ),
            'config.onundo' => new MethodDefinition(
                'void',
                [
                    'table' => 'string',
                    'recordData' => 'array',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'config.oninvalidate_cache_tags' => new MethodDefinition(
                'array',
                [
                    'dataContainer' => DataContainer::class,
                    'tags' => 'array',
                ]
            ),
            'config.onshow' => new MethodDefinition(
                'array',
                [
                    'modalData' => 'array',
                    'recordData' => 'array',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'list.sorting.paste_button' => new MethodDefinition(
                'string',
                [
                    'dataContainer' => DataContainer::class,
                    'recordData' => 'array',
                    'table' => 'string',
                    'isCircularReference' => 'bool',
                    'clipboardData' => 'array',
                    'children' => 'array',
                    'previousLabel' => 'string',
                    'nextLabel' => 'string',
                ]
            ),
            'list.sorting.child_record' => new MethodDefinition(
                'string',
                [
                    'recordData' => 'array',
                ]
            ),
            'list.sorting.header' => new MethodDefinition(
                'array',
                [
                    'currentHeaderLabels' => 'array',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'list.sorting.panel_callback.subpanel' => new MethodDefinition(
                'string',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'list.label.group' => new MethodDefinition(
                'string',
                [
                    'group' => 'string',
                    'mode' => 'string',
                    'field' => 'string',
                    'recordData' => 'array',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'list.label.label' => new MethodDefinition(
                'array',
                [
                    'recordData' => 'array',
                    'currentLabel' => 'string',
                    'dataContainer' => DataContainer::class,
                    // Since there are multiple parameters for multiple calls, we cannot
                    // safely assume the following correct parameter names and types
                ]
            ),
            'list.global_operations.{operation}.button' => new MethodDefinition(
                'string',
                [
                    'buttonHref' => '?string',
                    'label' => 'string',
                    'title' => 'string',
                    'className' => 'string',
                    'htmlAttributes' => 'string',
                    'table' => 'string',
                    'rootRecordIds' => 'array',
                ]
            ),
            'list.operations.{operation}.button' => new MethodDefinition(
                'string',
                [
                    'recordData' => 'array',
                    'buttonHref' => '?string',
                    'label' => 'string',
                    'title' => 'string',
                    'icon' => '?string',
                    'htmlAttributes' => 'string',
                    'table' => 'string',
                    'rootRecordIds' => 'array',
                    'childRecordIds' => 'array',
                    'isCircularReference' => 'bool',
                    'previousLabel' => 'string',
                    'nextLabel' => 'string',
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'fields.{field}.options' => new MethodDefinition(
                'array',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'fields.{field}.input_field' => new MethodDefinition(
                'string',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'fields.{field}.load' => new MethodDefinition(
                null,
                [
                    'currentValue' => null,
                    // Since there are multiple parameters for multiple calls, we cannot
                    // safely assume the following correct parameter names and types
                ]
            ),
            'fields.{field}.save' => new MethodDefinition(
                null,
                [
                    'currentValue' => null,
                    // Since there are multiple parameters for multiple calls, we cannot
                    // safely assume the following correct parameter names and types
                ]
            ),
            'fields.{field}.wizard' => new MethodDefinition(
                'string',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
            'fields.{field}.xlabel' => new MethodDefinition(
                'string',
                [
                    'dataContainer' => DataContainer::class,
                ]
            ),
        ];
    }
}
