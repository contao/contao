<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Filesystem;

use Contao\CoreBundle\DependencyInjection\Filesystem\ConfigureFilesystemInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

abstract class FilesystemConfiguringExtension implements ExtensionInterface, ConfigureFilesystemInterface
{
}
