<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\Config;
use Contao\Encryption;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Resets password for a Contao backend user.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ResetPasswordCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:reset-password')
            ->setDescription('Resets password for a Contao backend user.')
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * @inheritdoc
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null === $input->getArgument('username')) {
            throw new RuntimeException('You must enter a username on the command.');
        }

        if (null !== $input->getOption('password')) {
            return;
        }

        $password = $this->askForPassword('Please enter a password:', $input, $output);
        $confirm  = $this->askForPassword('Please confirm the password:', $input, $output);

        if ($password !== $confirm) {
            throw new RuntimeException('Your passwords do not match');
        }

        $input->setOption('password', $password);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $input->getArgument('username') || null === $input->getOption('password')) {
            return 1;
        }

        $hash = $this->validateAndHashPassword($input->getOption('password'));

        $this->getContainer()
            ->get('database_connection')
            ->update(
                'tl_user',
                ['password' => $hash],
                ['username' => $input->getArgument('username')]
            )
        ;

        return 0;
    }

    /**
     * Ask question with given label and hidden input.
     *
     * @param string          $label
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string
     */
    private function askForPassword($label, InputInterface $input, OutputInterface $output)
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setMaxAttempts(3);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    /**
     * Validates password length and creates hash of password.
     *
     * @param string $password
     *
     * @return string
     *
     * @throws \UnderflowException
     */
    private function validateAndHashPassword($password)
    {
        $framework = $this->getContainer()->get('contao.framework');
        $framework->initialize();

        /** @var Config $confirm */
        $config = $framework->getAdapter('Contao\Config');
        $passwordLength = $config->get('passwordLength');

        if (strlen($password) < $passwordLength) {
            throw new RuntimeException(sprintf('Password must be at least %s characters.', $passwordLength));
        }

        /** @var Encryption $encryption */
        $encryption = $framework->getAdapter('Contao\Encryption');

        return $encryption->hash($password);
    }
}
