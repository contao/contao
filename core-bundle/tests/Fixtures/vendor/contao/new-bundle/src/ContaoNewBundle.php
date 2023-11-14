<?php

namespace Contao\NewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoNewBundle extends Bundle
{
    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
