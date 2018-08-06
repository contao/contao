<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ScriptHandler
{
    public const RANDOM_SECRET_NAME = 'CONTAO_RANDOM_SECRET';

    public static function addDirectories(Event $event): void
    {
        self::executeCommand('contao:install', $event);
    }

    public static function generateSymlinks(Event $event): void
    {
        self::executeCommand('contao:symlinks', $event);
    }

    /**
     * Sets the environment variable with the random secret.
     */
    public static function generateRandomSecret(Event $event): void
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extra['incenteev-parameters']) || !self::canGenerateSecret($extra['incenteev-parameters'])) {
            return;
        }

        if (!\function_exists('random_bytes')) {
            self::loadRandomCompat($event);
        }

        putenv(static::RANDOM_SECRET_NAME.'='.bin2hex(random_bytes(32)));
    }

    /**
     * @throws \RuntimeException
     */
    private static function executeCommand(string $cmd, Event $event): void
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $process = new Process(
            sprintf(
                '%s %s/console %s %s%s%s',
                $phpPath,
                self::getBinDir($event),
                $cmd,
                self::getWebDir($event),
                $event->getIO()->isDecorated() ? ' --ansi' : '',
                self::getVerbosityFlag($event)
            )
        );

        $process->run(
            function (string $type, string $buffer) use ($event): void {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred while executing the "%s" command.', $cmd));
        }
    }

    private static function getBinDir(Event $event): string
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        // Symfony assumes the new directory structure if symfony-var-dir is set
        if (isset($extra['symfony-var-dir']) && is_dir($extra['symfony-var-dir'])) {
            return $extra['symfony-bin-dir'] ?? 'bin';
        }

        return $extra['symfony-app-dir'] ?? 'app';
    }

    private static function getWebDir(Event $event): string
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        return $extra['symfony-web-dir'] ?? 'web';
    }

    private static function getVerbosityFlag(Event $event): string
    {
        $io = $event->getIO();

        switch (true) {
            case $io->isDebug():
                return ' -vvv';

            case $io->isVeryVerbose():
                return ' -vv';

            case $io->isVerbose():
                return ' -v';

            default:
                return '';
        }
    }

    /**
     * Checks if there is at least one config file defined but none of the files exists.
     */
    private static function canGenerateSecret(array $config): bool
    {
        if (isset($config['file'])) {
            return !is_file($config['file']);
        }

        foreach ($config as $v) {
            if (\is_array($v) && isset($v['file']) && is_file($v['file'])) {
                return false;
            }
        }

        return !empty($config);
    }

    private static function loadRandomCompat(Event $event): void
    {
        $composer = $event->getComposer();

        $package = $composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage('paragonie/random_compat', '*')
        ;

        if (null === $package) {
            return;
        }

        $autoload = $package->getAutoload();

        if (empty($autoload['files'])) {
            return;
        }

        $path = $composer->getInstallationManager()->getInstaller('library')->getInstallPath($package);

        foreach ($autoload['files'] as $file) {
            include_once $path.'/'.$file;
        }
    }
}
