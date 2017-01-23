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
use Patchwork\Utf8;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Changes the password of a Contao back end user.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UserPasswordCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:user:password')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the back end user')
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'The new password (using this option is not recommended for security reasons)'
            )
            ->setDescription('Changes the password of a Contao back end user.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null === $input->getArgument('username')) {
            throw new InvalidArgumentException('Please provide the username as argument.');
        }

        if (null !== $input->getOption('password')) {
            return;
        }

        $password = $this->askForPassword('Please enter the new password:', $input, $output);
        $confirm = $this->askForPassword('Please confirm the password:', $input, $output);

        if ($password !== $confirm) {
            throw new RuntimeException('The passwords do not match.');
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

        $affected = $this
            ->getContainer()
            ->get('database_connection')
            ->update(
                'tl_user',
                ['password' => $hash],
                ['username' => $input->getArgument('username')]
            )
        ;

        if (0 === $affected) {
            throw new InvalidArgumentException(sprintf('Invalid username: %s', $input->getArgument('username')));
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('The password has been changed successfully.');

        return 0;
    }

    /**
     * Asks a question with the given label and hides the input.
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
     * Validates the password length and creates the password hash.
     *
     * @param string $password
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    private function validateAndHashPassword($password)
    {
        $framework = $this->getContainer()->get('contao.framework');
        $framework->initialize();

        /** @var Config $confirm */
        $config = $framework->getAdapter(Config::class);
        $passwordLength = $config->get('minPasswordLength') ?: 8;

        if (Utf8::strlen($password) < $passwordLength) {
            throw new InvalidArgumentException(
                sprintf('The password must be at least %s characters long.', $passwordLength)
            );
        }

        /** @var Encryption $encryption */
        $encryption = $framework->getAdapter(Encryption::class);

        return $encryption->hash($password);
    }
}
