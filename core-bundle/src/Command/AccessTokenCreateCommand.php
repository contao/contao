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

use Contao\CoreBundle\Repository\AccessTokenRepository;
use Contao\CoreBundle\Security\AccessTokenHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:access-token:create',
    description: 'Create a new access token for a given username.',
)]
class AccessTokenCreateCommand extends Command
{
    public function __construct(
        private readonly AccessTokenHandler $accessTokenHandler,
        private readonly AccessTokenRepository $accessTokenRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'The username to create the access token for')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null === $input->getOption('username')) {
            $question = new Question('Please enter the username: ');
            $question->setMaxAttempts(3);

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $username = $helper->ask($input, $output, $question);

            $input->setOption('username', $username);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $username = $input->getOption('username')) {
            $io->error('Please provide at least and each of: username, name, email, password');

            return Command::FAILURE;
        }

        $token = $this->accessTokenHandler->createTokenForUsername($username);
        $accessToken = $this->accessTokenRepository->findByToken($token);

        $io->success([
            sprintf('Access token for user %s created:', $username),
            $token,
            sprintf('Valid until: %s', $accessToken->getExpiresAt()->format('Y-m-d H:i:s')),
        ]);

        return Command::SUCCESS;
    }
}
