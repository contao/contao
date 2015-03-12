<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Sets up the Contao environment in a Symfony app.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ScriptHandler
{
    /**
     * Adds the Contao directories.
     *
     * @param Event $event The event object
     */
    public static function addContaoDirectories(Event $event)
    {
        $rootDir = getcwd();

        static::addAssetDirs($rootDir, $event);
        static::addFilesDir($rootDir, $event);
        static::addSystemDirs($rootDir, $event);
        static::addTemplatesDir($rootDir, $event);
    }

    /**
     * Adds the assets directories.
     *
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addAssetDirs($rootDir, Event $event)
    {
        $fs = new Filesystem();

        // Assets
        if (!$fs->exists($rootDir . '/assets')) {
            $fs->mkdir($rootDir . '/assets');
            $event->getIO()->write("Created the assets directory");
        }

        static::addIgnoredDir('assets/css', $rootDir, $event);
        static::addIgnoredDir('assets/images', $rootDir, $event);
        static::addIgnoredDir('assets/js', $rootDir, $event);
    }

    /**
     * Adds the files directory.
     *
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addFilesDir($rootDir, Event $event)
    {
        $fs = new Filesystem();

        if (!$fs->exists($rootDir . '/files')) {
            $fs->mkdir($rootDir . '/files');
            $event->getIO()->write("Created the files directory");
        }
    }

    /**
     * Adds the system directories.
     *
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addSystemDirs($rootDir, Event $event)
    {
        $fs = new Filesystem();

        if (!$fs->exists($rootDir . '/system')) {
            $fs->mkdir($rootDir . '/system');
            $event->getIO()->write("Created the system directory");
        }

        static::addIgnoredDir('system/cache', $rootDir, $event);
        static::addIgnoredDir('system/config', $rootDir, $event);
        static::addIgnoredDir('system/logs', $rootDir, $event);
        static::addIgnoredDir('system/modules', $rootDir, $event);
        static::addIgnoredDir('system/themes', $rootDir, $event);
        static::addIgnoredDir('system/tmp', $rootDir, $event);
    }

    /**
     * Adds the templates directory.
     *
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addTemplatesDir($rootDir, Event $event)
    {
        $fs = new Filesystem();

        if (!$fs->exists($rootDir . '/templates')) {
            $fs->mkdir($rootDir . '/templates');
            $event->getIO()->write("Created the system directory");
        }
    }

    /**
     * Adds a directory with a .gitignore file.
     *
     * @param string $path    The path
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addIgnoredDir($path, $rootDir, Event $event)
    {
        $fs = new Filesystem();

        if (!$fs->exists("$rootDir/$path")) {
            $fs->mkdir("$rootDir/$path");
            $event->getIO()->write("Created the $path directory");
        }

        if (!$fs->exists("$rootDir/$path/.gitignore")) {
            $fs->dumpFile(
                "$rootDir/$path/.gitignore",
                "# Create the folder and ignore its content\n*\n!.gitignore\n"
            );
            $event->getIO()->write("Added the $path/.gitignore file");
        }
    }
}
