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

use Contao\CoreBundle\HttpKernel\JwtManager;
use Contao\ManagerBundle\Api\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJwtCookieCommand extends Command
{
    /**
     * @var JwtManager
     */
    private $jwtManager;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->jwtManager = new JwtManager($application->getProjectDir());
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('jwt-cookie:generate')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode in the JWT cookie')
            ->setDescription('Generates a JWT cookie for the preview entry point.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $cookie = $this->jwtManager->createCookie(['debug' => $input->getOption('debug')]);

        $output->write((string) $cookie);
    }
}
