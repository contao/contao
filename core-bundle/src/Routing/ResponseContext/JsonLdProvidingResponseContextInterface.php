<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;

interface JsonLdProvidingResponseContextInterface extends ResponseContextInterface
{
    public function getJsonLdManager(): JsonLdManager;
}
