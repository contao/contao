<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Dotenv;

use Symfony\Component\Dotenv\Command\DotenvDumpCommand;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;

class DotenvDumpCommandFactory
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $environment,
    ) {
    }

    public function __invoke(): DotenvDumpCommand
    {
        $env = $this->environment;

        if (file_exists($filePath = Path::join($this->projectDir, '.env'))) {
            $globalsBackup = [$_SERVER, $_ENV];

            try {
                unset($_SERVER['APP_ENV']);
                $_ENV = [];
                (new Dotenv())->loadEnv($filePath, 'APP_ENV', 'jwt');
                $env = $_ENV['APP_ENV'];
            } finally {
                [$_SERVER, $_ENV] = $globalsBackup;
            }
        }

        return new DotenvDumpCommand($this->projectDir, $env);
    }
}
