<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RuntimeController
{
    #[Route('/_runtime', name: 'runtime_test')]
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'secure' => $request->isSecure(),
            'marker' => $request->query->get('runtime_marker'),
            'env' => $_SERVER['CONTAO_RUNTIME_ENV'],
            'debug' => '1' === $request->headers->get('X-Runtime-Debug'),
        ]);
    }
}
