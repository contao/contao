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

class MakeEventListener extends AbstractMaker
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
        return 'make:contao:event-listener';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates an event listener for a Contao event')
            ->addArgument('className', InputArgument::OPTIONAL, 'Enter a class name for the event listener (e.g. <fg=yellow>FooListener</>)')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $definition = $command->getDefinition();

        $command->addArgument('event', InputArgument::OPTIONAL, 'Choose an event to listen for');
        $argument = $definition->getArgument('event');
        $events = $this->getAvailableEvents();

        $io->writeln(' <fg=green>Suggested events:</>');
        $io->listing(array_keys($events));

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues(array_keys($events));

        $input->setArgument('event', $io->askQuestion($question));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $availableEvents = $this->getAvailableEvents();
        $event = $input->getArgument('event');
        $name = $input->getArgument('className');

        if (!\array_key_exists($event, $availableEvents)) {
            $io->error(sprintf('Event definition "%s" not found.', $event));

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $availableEvents[$event];
        $signature = $this->signatureGenerator->generate($definition, '__invoke');
        $uses = $this->importExtractor->extract($definition);
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        $this->classGenerator->generate([
            'source' => 'event-listener/EventListener.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'event' => $event,
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
