<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Composer;

use Composer\Composer;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Sets up the Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandler
{
    /**
     * Adds the web and console entry points.
     *
     * @param Event $event The event object
     *
     * @throws \RuntimeException
     */
    public static function addEntryPoints(Event $event)
    {
        $fs = new Filesystem();
        $composer = $event->getComposer();
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        $extra = array_merge(
            [
                'symfony-bin-dir' => 'system/bin',
                'symfony-web-dir' => 'web',
                'symfony-var-dir' => 'system'
            ],
            $composer->getPackage()->getExtra()
        );

        $binDir = getcwd() . '/' . trim($extra['symfony-bin-dir'], '/');
        $varDir = getcwd() . '/' . trim($extra['symfony-var-dir'], '/');

        $fs->ensureDirectoryExists($binDir);
        $fs->ensureDirectoryExists($varDir);

        $consoleInstalled = static::installContaoConsole(
            static::findContaoConsole($composer),
            $binDir . '/console',
            $fs->findShortestPath($binDir, $vendorDir, true) . '/autoload.php',
            $fs->findShortestPath($binDir, $varDir, true)
        );

        if ($consoleInstalled) {
            $event->getIO()->write(' Added the console entry point.', false);

            self::executeCommand(
                sprintf(
                    '%s/console contao:generate-entry-points --ansi --web-dir=%s --var-dir=%s --vendor-dir=%s --force',
                    escapeshellarg($extra['symfony-bin-dir']),
                    escapeshellarg($extra['symfony-web-dir']),
                    escapeshellarg($extra['symfony-var-dir']),
                    escapeshellarg($fs->findShortestPath(getcwd(), $vendorDir, true))
                ),
                $event
            );
        }
    }

    private static function installContaoConsole($filePath, $installTo, $autoloadPath, $kernelRootDir)
    {
        if (!is_file($filePath)) {
            throw new \UnderflowException(sprintf('%s is not a valid file.', $filePath));
        }

        $content = str_replace(
            ['../../../../vendor/autoload.php', '../../../../system'],
            [$autoloadPath, $kernelRootDir],
            file_get_contents($filePath)
        );

        if (file_put_contents($installTo, $content) > 0) {
            chmod($installTo, 0755);

            return true;
        }

        return false;
    }

    private static function findContaoConsole(Composer $composer)
    {
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ('contao/manager-bundle' === $package->getName()) {
                return $composer->getInstallationManager()->getInstallPath($package) . '/bin/contao-console';
            }
        }

        throw new \UnderflowException('Contao console script could not be installed');
    }

    /**
     * Executes a command.
     *
     * @param string $cmd   The command
     * @param Event  $event The event object
     *
     * @throws \RuntimeException If the PHP executable cannot be found or the command cannot be executed
     */
    private static function executeCommand($cmd, Event $event)
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $process = new Process(sprintf('%s %s', $phpPath, $cmd));

        $process->run(
            function ($type, $buffer) use ($event) {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred while executing the "%s" command.', $cmd));
        }
    }
}
