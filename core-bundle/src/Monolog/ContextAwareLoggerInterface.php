<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Monolog;

use Psr\Log\LoggerInterface;

interface ContextAwareLoggerInterface extends LoggerInterface
{
    public function addContext(string $key, $context): self;

    public function withContext(array $context): self;

    public function getContext(): array;

    public function getContextByName(string $name = null);
}
