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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Patchwork\Utf8;
use Symfony\Component\Console\Command\Command;
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
 */
class UserPasswordCommand extends Command
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
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
    protected function interact(InputInterface $input, OutputInterface $output): void
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
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (null === $input->getArgument('username') || null === $input->getOption('password')) {
            return 1;
        }

        $hash = $this->validateAndHashPassword($input->getOption('password'));

        $affected = $this->connection->update(
            'tl_user',
            ['password' => $hash],
            ['username' => $input->getArgument('username')]
        );

        if (0 === $affected) {
            throw new InvalidArgumentException(sprintf('Invalid username: %s', $input->getArgument('username')));
        }

        $io = new SymfonyStyle($input, $output);
        $io->success('The password has been changed successfully.');

        return 0;
    }

    /**
     * Asks a question with the given label and hides the input.
     */
    private function askForPassword(string $label, InputInterface $input, OutputInterface $output): string
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setMaxAttempts(3);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        return $helper->ask($input, $output, $question);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateAndHashPassword(string $password): string
    {
        $this->framework->initialize();

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);
        $passwordLength = $config->get('minPasswordLength') ?: 8;

        if (Utf8::strlen($password) < $passwordLength) {
            throw new InvalidArgumentException(
                sprintf('The password must be at least %s characters long.', $passwordLength)
            );
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }
}
