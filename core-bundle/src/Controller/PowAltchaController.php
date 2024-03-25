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

use Contao\CoreBundle\Pow\Altcha\Altcha;
use Contao\CoreBundle\Pow\Altcha\Exception\InvalidAlgorithmException;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/_contao_pow_altcha/challenge', name: self::class)]
class PowAltchaController extends AbstractController
{
    public function __construct(
        private readonly Altcha $altcha,
    ) {
    }

    /**
     * @throws InvalidAlgorithmException
     * @throws Exception
     */
    public function __invoke(): JsonResponse
    {
        return $this->json($this->altcha->createChallenge());
    }
}
