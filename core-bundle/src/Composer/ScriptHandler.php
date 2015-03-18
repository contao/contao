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

        self::addEmptyDir('files', $rootDir, $event);
        self::addEmptyDir('system', $rootDir, $event);
        self::addEmptyDir('templates', $rootDir, $event);
        self::addEmptyDir('web/system', $rootDir, $event);

        self::addIgnoredDir('assets/css', $rootDir, $event);
        self::addIgnoredDir('assets/images', $rootDir, $event);
        self::addIgnoredDir('assets/js', $rootDir, $event);
        self::addIgnoredDir('system/cache', $rootDir, $event);
        self::addIgnoredDir('system/config', $rootDir, $event);
        self::addIgnoredDir('system/logs', $rootDir, $event);
        self::addIgnoredDir('system/modules', $rootDir, $event);
        self::addIgnoredDir('system/themes', $rootDir, $event);
        self::addIgnoredDir('system/tmp', $rootDir, $event);
        self::addIgnoredDir('web/share', $rootDir, $event);
        self::addIgnoredDir('web/system/cron', $rootDir, $event);
    }

    /**
     * Adds an empty directory.
     *
     * @param string $path    The path
     * @param string $rootDir The root directory
     * @param Event  $event   The event boject
     */
    private static function addEmptyDir($path, $rootDir, Event $event)
    {
        $fs = new Filesystem();

        if ($fs->exists("$rootDir/$path")) {
            return;
        }

        $fs->mkdir("$rootDir/$path");

        $event->getIO()->write("Created the <info>$path</info> directory.");
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

        self::addEmptyDir($path, $rootDir, $event);

        if ($fs->exists("$rootDir/$path/.gitignore")) {
            return;
        }

        $fs->dumpFile(
            "$rootDir/$path/.gitignore",
            "# Create the folder and ignore its content\n*\n!.gitignore\n"
        );

        $event->getIO()->write("Added the <info>$path/.gitignore</info> file.");
    }
}
