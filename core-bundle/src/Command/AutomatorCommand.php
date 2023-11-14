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

use Contao\Automator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * @internal
 */
#[AsCommand(
    name: 'contao:automator',
    description: 'Runs automator tasks on the command line.',
)]
class AutomatorCommand extends Command
{
    private array $commands = [];

    public function __construct(private readonly ContaoFramework $framework)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('task', InputArgument::OPTIONAL, "The name of the task:\n  - ".implode("\n  - ", $this->getCommands()));
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        try {
            $this->runAutomator($input, $output);
        } catch (InvalidArgumentException $e) {
            $output->writeln(sprintf('%s (see help contao:automator).', $e->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function runAutomator(InputInterface $input, OutputInterface $output): void
    {
        $task = $this->getTaskFromInput($input, $output);

        $automator = new Automator();
        $automator->$task();
    }

    /**
     * @return array<string>
     */
    private function getCommands(): array
    {
        if (!$this->commands) {
            $this->commands = $this->generateCommandMap();
        }

        return $this->commands;
    }

    /**
     * @return array<string>
     */
    private function generateCommandMap(): array
    {
        $commands = [];

        // Find all public methods
        $class = new \ReflectionClass(Automator::class);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (!$method->isConstructor() && $method->getDeclaringClass()->getName() === $class->getName()) {
                $commands[] = $method->name;
            }
        }

        return $commands;
    }

    /**
     * Returns the task name from the argument list or via an interactive dialog.
     */
    private function getTaskFromInput(InputInterface $input, OutputInterface $output): string
    {
        $commands = $this->getCommands();
        $task = $input->getArgument('task');

        if (null !== $task) {
            if (!\in_array($task, $commands, true)) {
                throw new InvalidArgumentException(sprintf('Invalid task "%s"', $task)); // no full stop here
            }

            return $task;
        }

        $question = new ChoiceQuestion('Please select a task:', $commands);
        $question->setMaxAttempts(1);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }
}
