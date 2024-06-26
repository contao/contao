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

use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Altcha\Exception\InvalidAlgorithmException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/_contao/altcha_challenge')]
class AltchaController extends AbstractController
{
    public function __construct(private readonly Altcha $altcha)
    {
    }

    /**
     * @throws InvalidAlgorithmException
     */
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->altcha->createChallenge()->toArray());
    }
}
