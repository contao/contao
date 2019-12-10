<?php

namespace Contao\NewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoNewBundle extends Bundle
{
    public function getPath()
    {
        return \dirname(__DIR__);
    }
}
