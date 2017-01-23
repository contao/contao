<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\Automator;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Runs Automator tasks on the command line.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AutomatorCommand extends AbstractLockedCommand
{
    use FrameworkAwareTrait;

    /**
     * @var array
     */
    private $commands = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:automator')
            ->setDefinition([
                new InputArgument('task', InputArgument::OPTIONAL, $this),
            ])
            ->setDescription('Runs automator tasks on the command line.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->framework->initialize();

        try {
            $this->runAutomator($input, $output);
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('%s (see help contao:automator).', $e->getMessage()));

            return 1;
        }

        return 0;
    }

    /**
     * Returns the help text.
     *
     * By using the __toString() method, we ensure that the help text is lazy loaded at
     * a time where the autoloader is available (required by $this->getCommands()).
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("The name of the task:\n  - %s", implode("\n  - ", $this->getCommands()));
    }

    /**
     * Runs the Automator.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function runAutomator(InputInterface $input, OutputInterface $output)
    {
        $task = $this->getTaskFromInput($input, $output);

        $automator = new Automator();
        $automator->$task();
    }

    /**
     * Returns a list of available commands.
     *
     * @return array
     */
    private function getCommands()
    {
        if (empty($this->commands)) {
            $this->commands = $this->generateCommandMap();
        }

        return $this->commands;
    }

    /**
     * Generates the command map from the Automator class.
     *
     * @return array
     */
    private function generateCommandMap()
    {
        $this->framework->initialize();

        $commands = [];

        // Find all public methods
        $class = new \ReflectionClass('Contao\Automator');
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getDeclaringClass() == $class && !$method->isConstructor()) {
                $commands[] = $method->name;
            }
        }

        return $commands;
    }

    /**
     * Returns the task name from the argument list or via an interactive dialog.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string|null
     */
    private function getTaskFromInput(InputInterface $input, OutputInterface $output)
    {
        $commands = $this->getCommands();
        $task = $input->getArgument('task');

        if (null !== $task) {
            if (!in_array($task, $commands)) {
                throw new \InvalidArgumentException(sprintf('Invalid task "%s"', $task)); // no full stop here
            }

            return $task;
        }

        $question = new ChoiceQuestion('Please select a task:', $commands, 0);
        $question->setMaxAttempts(1);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }
}
