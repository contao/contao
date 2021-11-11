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

use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\ImportExtractor;
use Contao\MakerBundle\MethodDefinition;
use Contao\MakerBundle\SignatureGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class MakeHook extends AbstractMaker
{
    private ClassGenerator $classGenerator;
    private SignatureGenerator $signatureGenerator;
    private ImportExtractor $importExtractor;

    public function __construct(ClassGenerator $classGenerator, SignatureGenerator $signatureGenerator, ImportExtractor $importExtractor)
    {
        $this->classGenerator = $classGenerator;
        $this->signatureGenerator = $signatureGenerator;
        $this->importExtractor = $importExtractor;
    }

    public static function getCommandName(): string
    {
        return 'make:contao:hook';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a hook')
            ->addArgument('className', InputArgument::OPTIONAL, 'Enter a class name for the hook')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $definition = $command->getDefinition();

        $command->addArgument('hook', InputArgument::OPTIONAL, 'Choose a hook to implement');
        $argument = $definition->getArgument('hook');
        $hooks = $this->getAvailableHooks();

        $io->writeln(' <fg=green>Suggested hooks:</>');
        $io->listing(array_keys($hooks));

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues(array_keys($hooks));

        $input->setArgument('hook', $io->askQuestion($question));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $availableHooks = $this->getAvailableHooks();
        $hook = $input->getArgument('hook');
        $name = $input->getArgument('className');

        if (!\array_key_exists($hook, $availableHooks)) {
            $io->error(sprintf('Hook definition "%s" not found.', $hook));

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $availableHooks[$hook];

        $signature = $this->signatureGenerator->generate($definition, '__invoke');
        $uses = $this->importExtractor->extract($definition);
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        $this->classGenerator->generate([
            'source' => 'hook/Hook.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'hook' => $hook,
                'signature' => $signature,
                'uses' => $uses,
                'body' => $definition->getBody(),
            ],
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @return array<string, MethodDefinition>
     */
    private function getAvailableHooks(): array
    {
        $yaml = Yaml::parseFile(__DIR__.'/../Resources/config/hooks.yaml');
        $hooks = [];

        foreach ($yaml['hooks'] as $key => $config) {
            $hooks[$key] = new MethodDefinition($config['return_type'], $config['arguments']);
        }

        return $hooks;
    }
}
