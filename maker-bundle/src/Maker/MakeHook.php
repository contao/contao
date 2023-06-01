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
use Symfony\Component\Yaml\Yaml;

class MakeHook extends AbstractMaker
{
    public function __construct(
        private ClassGenerator $classGenerator,
        private SignatureGenerator $signatureGenerator,
        private ImportExtractor $importExtractor,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:contao:hook';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new hook listener';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('hook-class', InputArgument::REQUIRED, sprintf('Enter a class name for the listener (e.g. <fg=yellow>%sListener</>)', Str::asClassName(Str::getRandomTerm())))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('hook', InputArgument::REQUIRED);

        $hooks = $this->getAvailableHooks();

        $io->writeln(' <fg=green>Available hooks:</>');
        $io->listing(array_keys($hooks));

        $question = new Question('Choose the hook to listen for');
        $question->setAutocompleterValues(array_keys($hooks));
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('hook', $io->askQuestion($question));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $hook = $input->getArgument('hook');
        $name = $input->getArgument('hook-class');
        $hooks = $this->getAvailableHooks();

        if (!\array_key_exists($hook, $hooks)) {
            $io->error('Invalid hook name: '.$hook);

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $hooks[$hook];
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        $this->classGenerator->generate([
            'source' => 'hook/Hook.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'uses' => $this->importExtractor->extract($definition),
                'hook' => $hook,
                'className' => $elementDetails->getShortName(),
                'signature' => $this->signatureGenerator->generate($definition, '__invoke'),
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
        $yaml = Yaml::parseFile(__DIR__.'/../../config/hooks.yaml');
        $hooks = [];

        foreach ($yaml['hooks'] as $key => $config) {
            $hooks[$key] = new MethodDefinition($config['return_type'], $config['arguments']);
        }

        return $hooks;
    }
}
