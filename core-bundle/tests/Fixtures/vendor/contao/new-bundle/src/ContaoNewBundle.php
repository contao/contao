<?php

namespace Contao\NewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoNewBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
