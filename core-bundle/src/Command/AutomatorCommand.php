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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Lock\LockInterface;

/**
 * Runs Contao automator tasks on the command line.
 */
class AutomatorCommand extends Command
{
    /**
     * @var array
     */
    private $commands = [];

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var LockInterface
     */
    private $lock;

    public function __construct(ContaoFramework $framework, LockInterface $lock)
    {
        $this->framework = $framework;
        $this->lock = $lock;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:automator')
            ->setDefinition([
                new InputArgument(
                    'task',
                    InputArgument::OPTIONAL,
                    sprintf("The name of the task:\n  - %s", implode("\n  - ", $this->getCommands()))
                ),
            ])
            ->setDescription('Runs automator tasks on the command line.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $return = 0;

        $this->framework->initialize();

        try {
            $this->runAutomator($input, $output);
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('%s (see help contao:automator).', $e->getMessage()));
            $return = 1;
        }

        $this->lock->release();

        return $return;
    }

    private function runAutomator(InputInterface $input, OutputInterface $output): void
    {
        $task = $this->getTaskFromInput($input, $output);

        $automator = new Automator();
        $automator->$task();
    }

    /**
     * @return string[]
     */
    private function getCommands(): array
    {
        if (empty($this->commands)) {
            $this->commands = $this->generateCommandMap();
        }

        return $this->commands;
    }

    /**
     * @return string[]
     */
    private function generateCommandMap(): array
    {
        $this->framework->initialize();

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
                throw new \InvalidArgumentException(sprintf('Invalid task "%s"', $task)); // no full stop here
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
