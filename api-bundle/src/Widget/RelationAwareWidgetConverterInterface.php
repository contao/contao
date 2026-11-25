<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Widget;

interface RelationAwareWidgetConverterInterface extends WidgetConverterInterface
{
    /**
     * Returns relation metadata derived from the DCA field configuration.
     *
     * The conversion methods expose and accept the related identifiers. The API layer
     * replaces those identifiers with IRIs after conversion.
     */
    public function getRelation(array $config): object|null;
}
