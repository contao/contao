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
use Contao\MakerBundle\Code\ImportExtractor;
use Contao\MakerBundle\Code\SignatureGenerator;
use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\Model\CallbackDefinition;
use Contao\MakerBundle\Model\MethodDefinition;
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
            ->setDescription('Creates a dca callback')
            ->addArgument('className', InputArgument::REQUIRED, sprintf('Choose a class name for your callback'))
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
        $command->addArgument('table', InputArgument::REQUIRED, 'Choose a table for this callback');
        $argument = $definition->getArgument('table');

        $tables = $this->getTables();

        $io->writeln(' <fg=green>Suggested Tables:</>');
        $io->listing($tables);

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues($tables);

        $input->setArgument('table', $io->askQuestion($question));

        // Targets
        $command->addArgument('target', InputArgument::REQUIRED, 'Choose a target for this callback');
        $argument = $definition->getArgument('target');

        $targets = $this->getTargets();

        $io->writeln(' <fg=green>Suggested Targets:</>');
        $io->listing(array_keys($targets));

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues(array_keys($targets));

        $input->setArgument('target', $io->askQuestion($question));

        // Dependencies
        $target = $input->getArgument('target');

        /** @var CallbackDefinition $callback */
        $callback = $targets[$target];

        $callbackDependencies = $callback->getDependencies();

        if (\count($callbackDependencies) > 0) {
            foreach ($callbackDependencies as $callbackDependency) {
                $command
                    ->addArgument($callbackDependency, InputArgument::REQUIRED, sprintf('Please choose a value for "%s"', $callbackDependency))
                ;

                $argument = $definition->getArgument($callbackDependency);

                $question = new Question($argument->getDescription());
                $question->setValidator($requiredValidator);

                $input->setArgument($callbackDependency, $io->askQuestion($question));
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

        /** @var CallbackDefinition $callback */
        $callback = $availableTargets[$target];
        $method = $callback->getMethodDefinition();

        $signature = $this->signatureGenerator->generate($method, '__invoke');
        $uses = $this->importExtractor->extract($method);

        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        foreach ($callback->getDependencies() as $dependencyName) {
            $target = str_replace('{'.$dependencyName.'}', $input->getArgument($dependencyName), $target);
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
     * @return array<string, CallbackDefinition>
     */
    private function getTargets(): array
    {
        return [
            'config.onload' => new CallbackDefinition(new MethodDefinition('void', [
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'config.oncreate' => new CallbackDefinition(new MethodDefinition('void', [
                'table' => 'string',
                'insertId' => 'int',
                'fields' => 'array',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'config.onsubmit' => new CallbackDefinition(new MethodDefinition('void', [
                // Since there is multiple parameters for multiple calls
                // we can't safely assume the correct parameter names and types
            ])),
            'config.ondelete' => new CallbackDefinition(new MethodDefinition('void', [
                'dataContainer' => 'Contao\DataContainer',
                'id' => 'int',
            ])),
            'config.oncut' => new CallbackDefinition(new MethodDefinition('void', [
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'config.oncopy' => new CallbackDefinition(new MethodDefinition('void', [
                'id' => 'int',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'config.oncreate_version' => new CallbackDefinition(new MethodDefinition('void', [
                'table' => 'string',
                'pid' => 'int',
                'versionNumber' => 'int',
                'recordData' => 'array',
            ])),
            'config.onrestore_version' => new CallbackDefinition(new MethodDefinition('void', [
                'table' => 'string',
                'pid' => 'int',
                'versionNumber' => 'int',
                'recordData' => 'array',
            ])),
            'config.onundo' => new CallbackDefinition(new MethodDefinition('void', [
                'table' => 'string',
                'recordData' => 'array',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'config.oninvalidate_cache_tags' => new CallbackDefinition(new MethodDefinition('array', [
                'dataContainer' => 'Contao\DataContainer',
                'tags' => 'array',
            ])),
            'config.onshow' => new CallbackDefinition(new MethodDefinition('array', [
                'modalData' => 'array',
                'recordData' => 'array',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'list.sorting.paste_button' => new CallbackDefinition(new MethodDefinition('string', [
                'dataContainer' => 'Contao\DataContainer',
                'recordData' => 'array',
                'table' => 'string',
                'isCircularReference' => 'bool',
                'clipboardData' => 'array',
                'children' => 'array',
                'previousLabel' => 'string',
                'nextLabel' => 'string',
            ])),
            'list.sorting.child_record' => new CallbackDefinition(new MethodDefinition('string', [
                'recordData' => 'array',
            ])),
            'list.sorting.header' => new CallbackDefinition(new MethodDefinition('array', [
                'currentHeaderLabels' => 'array',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'list.sorting.panel_callback.subpanel' => new CallbackDefinition(new MethodDefinition('string', [
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'list.label.group' => new CallbackDefinition(new MethodDefinition('string', [
                'group' => 'string',
                'mode' => 'string',
                'field' => 'string',
                'recordData' => 'array',
                'dataContainer' => 'Contao\DataContainer',
            ])),
            'list.label.label' => new CallbackDefinition(new MethodDefinition('array', [
                'recordData' => 'array',
                'currentLabel' => 'string',
                'dataContainer' => 'Contao\DataContainer',

                // Since there is multiple parameters for multiple calls
                // we can't safely assume the following correct parameter names and types
            ])),
            'list.global_operations.{operation}.button' => new CallbackDefinition(new MethodDefinition('string', [
                'buttonHref' => '?string',
                'label' => 'string',
                'title' => 'string',
                'className' => 'string',
                'htmlAttributes' => 'string',
                'table' => 'string',
                'rootRecordIds' => 'array',
            ]), ['operation']),
            'list.operations.{operation}.button' => new CallbackDefinition(new MethodDefinition('string', [
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
                'dataContainer' => 'Contao\DataContainer',
            ]), ['operation']),
            'fields.{field}.options' => new CallbackDefinition(new MethodDefinition('array', [
                'dataContainer' => 'Contao\DataContainer',
            ]), ['field']),
            'fields.{field}.input_field' => new CallbackDefinition(new MethodDefinition('string', [
                'dataContainer' => 'Contao\DataContainer',
            ]), ['field']),
            'fields.{field}.load' => new CallbackDefinition(new MethodDefinition(null, [
                'currentValue' => null,

                // Since there is multiple parameters for multiple calls
                // we can't safely assume the following correct parameter names and types
            ]), ['field']),
            'fields.{field}.save' => new CallbackDefinition(new MethodDefinition(null, [
                'currentValue' => null,

                // Since there is multiple parameters for multiple calls
                // we can't safely assume the following correct parameter names and types
            ]), ['field']),
            'fields.{field}.wizard' => new CallbackDefinition(new MethodDefinition('string', [
                'dataContainer' => 'Contao\DataContainer',
            ]), ['field']),
            'fields.{field}.xlabel' => new CallbackDefinition(new MethodDefinition('string', [
                'dataContainer' => 'Contao\DataContainer',
            ]), ['field']),
        ];
    }
}
