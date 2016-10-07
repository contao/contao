<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs the web entry points for Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class EntryPointCommand extends AbstractLockedCommand
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:generate-entry-points')
            ->setDescription('Generates entry points in /web directory.')
            ->addArgument('root-dir', InputArgument::OPTIONAL, 'The installation root directory (default to the current dir).', getcwd())
            ->addOption('web-dir', '', InputOption::VALUE_REQUIRED, '', 'web')
            ->addOption('var-dir', '', InputOption::VALUE_REQUIRED, '', 'var')
            ->addOption('vendor-dir', '', InputOption::VALUE_REQUIRED, '', 'vendor')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite entry points if they exist.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $rootDir = $input->getArgument('root-dir');
        $webDir = $this->makePath($rootDir, $input->getOption('web-dir'));
        $varDir = $this->makePath($rootDir, $input->getOption('var-dir'));
        $vendorDir = $this->makePath($rootDir, $input->getOption('vendor-dir'));
        $force = (bool) $input->getOption('force');

        $pathToSystem = rtrim($this->fs->makePathRelative($varDir, $webDir), '/');
        $pathToAutoload = rtrim($this->fs->makePathRelative($vendorDir, $webDir), '/') . '/autoload.php';

        $this->addAppPhp($webDir, $pathToSystem, $pathToAutoload, $force);
        $this->addAppDevPhp($webDir, $pathToSystem, $pathToAutoload, $force);
        $this->addInstallPhp($webDir, $pathToSystem, $pathToAutoload, $force);

        return 0;
    }

    private function makePath($rootDir, $path)
    {
        return realpath(rtrim($rootDir, '/') . '/' . trim($path, '/'));
    }

    /**
     * Adds the app.php entry point.
     *
     * @param string $webDir
     * @param string $pathToSystem
     * @param string $pathToAutoload
     * @param bool   $force
     */
    private function addAppPhp($webDir, $pathToSystem, $pathToAutoload, $force = false)
    {
        if ($this->fs->exists($webDir.'/app.php') && !$force) {
            return;
        }

        $this->fs->dumpFile(
            $webDir.'/app.php',
            <<<EOF
<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\\ManagerBundle\\HttpKernel\\ContaoCache;
use Contao\\ManagerBundle\\HttpKernel\\ContaoKernel;
use Doctrine\\Common\\Annotations\\AnnotationRegistry;
use Symfony\\Component\\HttpFoundation\\Request;

/**
 * @var Composer\\Autoload\\ClassLoader
 */
\$loader = require __DIR__.'/$pathToAutoload';

AnnotationRegistry::registerLoader([\$loader, 'loadClass']);

\$kernel = new ContaoKernel('prod', false);
\$kernel->setRootDir(__DIR__ . '/$pathToSystem');
\$kernel->loadClassCache();

// Enable the Symfony reverse proxy
\$kernel = new ContaoCache(\$kernel);

// Handle the request
\$request = Request::createFromGlobals();
\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);

EOF

        );

        $this->io->text('Added the <comment>app.php</comment> entry point.');
    }

    /**
     * Adds the app_dev.php entry point.
     *
     * @param string $webDir
     * @param string $pathToSystem
     * @param string $pathToAutoload
     * @param bool   $force
     */
    private function addAppDevPhp($webDir, $pathToSystem, $pathToAutoload, $force = false)
    {
        if ($this->fs->exists($webDir.'/app_dev.php') && !$force) {
            return;
        }

        $this->fs->dumpFile(
            $webDir.'/app_dev.php',
            <<<EOF
<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\\ManagerBundle\\HttpKernel\\ContaoKernel;
use Doctrine\\Common\\Annotations\\AnnotationRegistry;
use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\Debug\\Debug;

// Access to debug front controllers is only allowed on localhost or with username and password.
// The username and password need to be separated by a colon and then converted to a SHA512 hash.
//
// Example: username:password
// SHA512:  9a83c7ec28250be89cef48d7698d68f4cd6e368e29c1339...6010ef50ed7d869de3cf0ccc65aa600e980818
//
// You can e.g. use http://www.hashgenerator.de to generate the SHA512 hash online.
\$accessKey = '';

if (isset(\$_SERVER['HTTP_CLIENT_IP'])
    || isset(\$_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@\$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1']) || php_sapi_name() === 'cli-server')
) {
    if ('' === \$accessKey) {
        header('HTTP/1.0 403 Forbidden');
        die(sprintf('You are not allowed to access this file. Check %s for more information.', basename(__FILE__)));
    }

    if (!isset(\$_SERVER['PHP_AUTH_USER'])
        || !isset(\$_SERVER['PHP_AUTH_PW'])
        || hash('sha512', \$_SERVER['PHP_AUTH_USER'].':'.\$_SERVER['PHP_AUTH_PW']) !== \$accessKey
    ) {
        header('WWW-Authenticate: Basic realm="Contao debug"');
        header('HTTP/1.0 401 Unauthorized');
        die(sprintf('You are not allowed to access this file. Check %s for more information.', basename(__FILE__)));
    }
}

unset(\$accessKey);

/**
 * @var Composer\\Autoload\\ClassLoader
 */
\$loader = require __DIR__.'/$pathToAutoload';

AnnotationRegistry::registerLoader([\$loader, 'loadClass']);
Debug::enable();

\$kernel = new ContaoKernel('dev', true);
\$kernel->setRootDir(__DIR__ . '/$pathToSystem');
\$kernel->loadClassCache();

// Handle the request
\$request = Request::createFromGlobals();
\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);

EOF

        );

        $this->io->text('Added the <comment>app_dev.php</comment> entry point.');
    }

    /**
     * Adds the install.php entry point.
     *
     * @param string $webDir
     * @param string $pathToSystem
     * @param string $pathToAutoload
     * @param bool   $force
     */
    private function addInstallPhp($webDir, $pathToSystem, $pathToAutoload, $force = false)
    {
        if ($this->fs->exists($webDir.'/install.php') && !$force) {
            return;
        }

        $this->fs->dumpFile(
            $webDir.'/install.php',
            <<<EOF
<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\\InstallationBundle\\HttpKernel\\InstallationKernel;
use Contao\\InstallationBundle\\HttpKernel\\ManagedInstallationKernel;
use Symfony\\Component\\HttpFoundation\\Request;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/**
 * @var Composer\\Autoload\\ClassLoader
 */
\$loader = require __DIR__.'/$pathToAutoload';

\$kernel = new ManagedInstallationKernel('dev', false);
\$kernel->setRootDir(__DIR__ . '/$pathToSystem');
\$kernel->loadClassCache();

// Handle the request
\$request = Request::createFromGlobals();
\$response = \$kernel->handle(\$request);
\$response->send();
\$kernel->terminate(\$request, \$response);

EOF

        );

        $this->io->text('Added the <comment>install.php</comment> entry point.');
    }
}
