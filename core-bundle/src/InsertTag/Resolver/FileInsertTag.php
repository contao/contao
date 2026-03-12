<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\Filesystem\PublicUri\Options;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Symfony\Component\Uid\Uuid;

#[AsInsertTag('file')]
class FileInsertTag
{
    public function __construct(private readonly VirtualFilesystemInterface $filesStorage)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        try {
            // This throws if the provided parameter is not a valid UUID
            $uuid = Uuid::fromString((string) $insertTag->getParameters()->get(0));

            // Create the public URI with default options (= includes versioning)
            return new InsertTagResult((string) $this->filesStorage->generatePublicUri($uuid, Options::create()));
        } catch (\Exception) {
            return new InsertTagResult('');
        }
    }
}
