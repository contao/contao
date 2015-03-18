<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\Automator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Runs Automator tasks on the command line.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AutomatorCommand extends ContainerAwareCommand
{
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
                new InputArgument(
                    'task',
                    InputArgument::OPTIONAL,
                    "The name of the task:\n  - " . implode("\n  - ", $this->getCommands())
                ),
            ])
            ->setDescription('Runs automator tasks on the command line')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('contao:automator');

        // Set the lock
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        // Run
        try {
            $this->runAutomator($input, $output);
        } catch (\InvalidArgumentException $e) {
            $output->writeln($e->getMessage() . ' (see help contao:automator)');
            $lock->release();

            return 1;
        }

        // Release the lock
        $lock->release();

        return 0;
    }

    /**
     * Runs the Automator.
     *
     * @param InputInterface  $input  The input object
     * @param OutputInterface $output The output object
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
     * @return array The commands array
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
     * @return array The commands array
     */
    private function generateCommandMap()
    {
        return [
            'checkForUpdates',
            'purgeSearchTables',
            'purgeUndoTable',
            'purgeVersionTable',
            'purgeSystemLog',
            'purgeImageCache',
            'purgeScriptCache',
            'purgePageCache',
            'purgeSearchCache',
            'purgeInternalCache',
            'purgeTempFolder',
            'generateXmlFiles',
            'purgeXmlFiles',
            'generateSitemap',
            'rotateLogs',
            'generateSymlinks',
            'generateInternalCache',
            'generateConfigCache',
            'generateDcaCache',
            'generateLanguageCache',
            'generateDcaExtracts',
            'generatePackageCache',
        ];
    }

    /**
     * Returns the task name from the argument list or via an interactive dialog.
     *
     * @param InputInterface  $input  The input context
     * @param OutputInterface $output The output context
     *
     * @return string|null The task name or null
     */
    private function getTaskFromInput(InputInterface $input, OutputInterface $output)
    {
        $commands = $this->getCommands();
        $task     = $input->getArgument('task');

        if (null !== $task) {
            if (!in_array($task, $commands)) {
                throw new \InvalidArgumentException("Invalid task $task");
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
