<?php

namespace Contao\Fixtures;

use Symfony\Component\HttpFoundation\RedirectResponse;

class FrontendShare
{
    public function run()
    {
        return new RedirectResponse('http://localhost');
    }
}
