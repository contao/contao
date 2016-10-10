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
use Contao\CoreBundle\Composer\ScriptHandler as BaseScriptHandler;

/**
 * Sets up the Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandler extends BaseScriptHandler
{
    const BIN_DIR = 'bin';
    const VAR_DIR = 'var';
    const WEB_DIR = 'web';
    const VENDOR_DIR = 'vendor';

    /**
     * @var string[]
     */
    private static $symfonyDirs;

    /**
     * @var Filesystem
     */
    private static $filesystem;

    /**
     * Adds the web and console entry points.
     *
     * @param Event $event The event object
     *
     * @throws \RuntimeException
     */
    public static function addEntryPoints(Event $event)
    {
        $composer = $event->getComposer();

        if (null === static::$symfonyDirs) {
            static::loadSymfonyDirs($composer);
        }

        $binDir = static::getPath(static::BIN_DIR);

        static::installContaoConsole(
            static::findContaoConsole($composer),
            $binDir . '/console',
            static::getPath(static::VENDOR_DIR, $binDir),
            static::getPath(static::VAR_DIR, $binDir)
        );

        $event->getIO()->write(' Added the console entry point.', false);

        static::executeCommand(
            sprintf(
                'contao:install-web-dir --web-dir=%s --var-dir=%s --vendor-dir=%s --force',
                escapeshellarg(static::getPath(static::WEB_DIR, getcwd())),
                escapeshellarg(static::getPath(static::VAR_DIR, getcwd())),
                escapeshellarg(static::getPath(static::VENDOR_DIR, getcwd()))
            ),
            $event
        );
    }

    /**
     * @inheritdoc
     */
    protected static function getConsoleScript(Event $event)
    {
        if (null === static::$symfonyDirs) {
            static::loadSymfonyDirs($event->getComposer());
        }

        return static::getPath(static::BIN_DIR, getcwd()) . '/console';
    }

    /**
     * Installs the console and replaces given paths to adjust for installation.
     *
     * @param string $filePath
     * @param string $installTo
     * @param string $vendorDir
     * @param string $kernelRootDir
     */
    private static function installContaoConsole($filePath, $installTo, $vendorDir, $kernelRootDir)
    {
        if (!is_file($filePath)) {
            throw new \UnderflowException(sprintf('%s is not a valid file.', $filePath));
        }

        $content = str_replace(
            ['../../../../vendor', '../../../../system'],
            [$vendorDir, $kernelRootDir],
            file_get_contents($filePath)
        );

        if (file_put_contents($installTo, $content) > 0) {
            @chmod($installTo, 0755);
            return;
        }

        throw new \UnderflowException('Contao console script could not be installed.');
    }

    /**
     * Finds the Contao console script in the manager bundle from Composer.
     *
     * @param Composer $composer
     *
     * @return string
     */
    private static function findContaoConsole(Composer $composer)
    {
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ('contao/manager-bundle' === $package->getName()) {
                return $composer->getInstallationManager()->getInstallPath($package) . '/bin/contao-console';
            }
        }

        throw new \UnderflowException('Contao console script was not found.');
    }

    /**
     * Gets the path to a directory by given name (see class constants).
     *
     * @param string      $name
     * @param string|null $relativeFrom
     *
     * @return string
     */
    private static function getPath($name, $relativeFrom = null)
    {
        if (null === static::$symfonyDirs) {
            throw new \UnderflowException('Symfony directories are not loaded.');
        }

        if (!array_key_exists($name, static::$symfonyDirs)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid Symfony directory name.', $name));
        }

        if (null !== $relativeFrom) {
            return static::$filesystem->findShortestPath($relativeFrom, static::$symfonyDirs[$name], true);
        }

        return static::$symfonyDirs[$name];
    }

    /**
     * Loads Symfony default directories or from Composer's extra section.
     *
     * @param Composer   $composer
     * @param Filesystem $filesystem
     */
    private static function loadSymfonyDirs(Composer $composer, Filesystem $filesystem = null)
    {
        if (null !== static::$symfonyDirs) {
            throw new \RuntimeException('Symfony directores already loaded.');
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        $extra = array_merge(
            [
                'symfony-bin-dir' => 'system/bin',
                'symfony-web-dir' => 'web',
                'symfony-var-dir' => 'system'
            ],
            $composer->getPackage()->getExtra()
        );

        static::$symfonyDirs = [
            static::BIN_DIR => getcwd() . '/' . trim($extra['symfony-bin-dir'], '/'),
            static::VAR_DIR => getcwd() . '/' . trim($extra['symfony-var-dir'], '/'),
            static::WEB_DIR => getcwd() . '/' . trim($extra['symfony-web-dir'], '/'),
            static::VENDOR_DIR => $composer->getConfig()->get('vendor-dir'),
        ];

        static::$filesystem = $filesystem;

        foreach (static::$symfonyDirs as $dir) {
            $filesystem->ensureDirectoryExists($dir);
        }
    }
}
