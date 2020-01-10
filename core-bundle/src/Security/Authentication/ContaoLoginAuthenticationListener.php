<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;

class ContaoLoginAuthenticationListener extends UsernamePasswordFormAuthenticationListener
{
    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->request->has('FORM_SUBMIT')
            && 0 === strncmp($request->request->get('FORM_SUBMIT'), 'tl_login', 8);
    }
}
