<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

/**
 * @experimental
 */
interface OperationDescriptionInterface
{
    /**
     * Returns a human-readable description for this operation, including its
     * prerequisites, request parameters, side effects and intermediary steps.
     */
    public function getDescription(): string;
}
