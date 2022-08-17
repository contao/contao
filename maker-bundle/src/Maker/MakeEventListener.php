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
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

class MakeEventListener extends AbstractMaker
{
    private ClassGenerator $classGenerator;
    private SignatureGenerator $signatureGenerator;
    private ImportExtractor $importExtractor;
    private PhpCompatUtil $phpCompatUtil;

    public function __construct(ClassGenerator $classGenerator, SignatureGenerator $signatureGenerator, ImportExtractor $importExtractor, PhpCompatUtil $phpCompatUtil)
    {
        $this->classGenerator = $classGenerator;
        $this->signatureGenerator = $signatureGenerator;
        $this->importExtractor = $importExtractor;
        $this->phpCompatUtil = $phpCompatUtil;
    }

    public static function getCommandName(): string
    {
        return 'make:contao:event-listener';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new event listener';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('event-class', InputArgument::OPTIONAL, sprintf('Enter a class name for the listener (e.g. <fg=yellow>%sListener</>)', Str::asClassName(Str::getRandomTerm())))
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('event', InputArgument::OPTIONAL);

        $events = $this->getAvailableEvents();

        $io->writeln(' <fg=green>Available events:</>');
        $io->listing(array_keys($events));

        $question = new Question('Choose the event to listen for');
        $question->setAutocompleterValues(array_keys($events));

        $input->setArgument('event', $io->askQuestion($question));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $event = $input->getArgument('event');
        $name = $input->getArgument('event-class');
        $events = $this->getAvailableEvents();

        if (!\array_key_exists($event, $events)) {
            $io->error('Invalid event name: '.$event);

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $events[$event];
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');
        $useAttributes = true;

        // Backwards compatibility with symfony/maker-bundle < 1.44.0
        if (\method_exists($this->phpCompatUtil, 'canUseAttributes')) {
            $useAttributes = $this->phpCompatUtil->canUseAttributes();
        }

        $this->classGenerator->generate([
            'source' => 'event-listener/EventListener.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'uses' => $this->importExtractor->extract($definition),
                'event' => $event,
                'className' => $elementDetails->getShortName(),
                'signature' => $this->signatureGenerator->generate($definition, '__invoke'),
                'body' => $definition->getBody(),
                'use_attributes' => $useAttributes,
            ],
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @return array<string, MethodDefinition>
     */
    private function getAvailableEvents(): array
    {
        $yaml = Yaml::parseFile(__DIR__.'/../Resources/config/events.yaml');
        $events = [];

        foreach ($yaml['events'] as $key => $config) {
            $events[$key] = new MethodDefinition($config['return_type'], $config['arguments']);
        }

        return $events;
    }
}
