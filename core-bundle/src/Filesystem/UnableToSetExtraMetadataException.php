<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

use League\Flysystem\FilesystemException;

class UnableToSetExtraMetadataException extends \RuntimeException implements FilesystemException
{
    public function __construct(string $location, \Throwable $previous)
    {
        parent::__construct("Unable to set extra metadata for location '$location'.", 0, $previous);
    }
}
