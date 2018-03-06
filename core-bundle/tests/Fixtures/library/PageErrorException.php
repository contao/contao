<?php

namespace Contao\Fixtures;

class PageErrorException
{
    public function getResponse()
    {
        throw new \Exception('foo');
    }
}
