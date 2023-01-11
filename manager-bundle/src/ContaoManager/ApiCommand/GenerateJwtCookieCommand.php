<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'jwt-cookie:generate',
    description: 'Generates a JWT cookie for the preview entry point.'
)]
class GenerateJwtCookieCommand extends Command
{
    private JwtManager $jwtManager;

    public function __construct(Application $application, JwtManager $jwtManager = null)
    {
        parent::__construct();

        $this->jwtManager = $jwtManager ?: new JwtManager($application->getProjectDir());
    }

    protected function configure(): void
    {
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode in the JWT cookie');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cookie = $this->jwtManager->createCookie(['debug' => $input->getOption('debug')]);

        $output->write((string) $cookie);

        return 0;
    }
}
