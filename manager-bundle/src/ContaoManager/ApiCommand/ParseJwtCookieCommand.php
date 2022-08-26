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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class ParseJwtCookieCommand extends Command
{
    protected static $defaultName = 'jwt-cookie:parse';
    protected static $defaultDescription = 'Parses the content of the preview entry point cookie.';

    private JwtManager $jwtManager;

    public function __construct(Application $application, JwtManager $jwtManager = null)
    {
        parent::__construct();

        $this->jwtManager = $jwtManager ?: new JwtManager($application->getProjectDir());
    }

    protected function configure(): void
    {
        $this->addArgument('content', InputArgument::REQUIRED, 'The JWT cookie content');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $payload = $this->jwtManager->parseCookie($input->getArgument('content'));

        $output->write(json_encode($payload));

        return 0;
    }
}
