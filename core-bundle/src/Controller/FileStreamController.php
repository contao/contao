<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('_file_stream/{path}',
    name: 'contao_file_stream',
    requirements: ['path' => '.+'],
    defaults: ['_scope' => 'frontend'],
    methods: ['GET'],
)]
class FileStreamController
{
    public function __construct(private readonly FileDownloadHelper $fileDownloadHelper)
    {
    }

    public function __invoke(Request $request, string $path): Response
    {
        return $this->fileDownloadHelper->handleRequest($request, $path);
    }
}
