<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;

interface FilesystemAdapterAndPublicUrlGeneratorInterface extends FilesystemAdapter, PublicUrlGenerator
{
}
