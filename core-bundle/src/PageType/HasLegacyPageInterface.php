<?php

declare(strict_types=1);

namespace Contao\CoreBundle\PageType;

interface HasLegacyPageInterface
{
    public function getLegacyPageClass() : string;
}
