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
        if (!$request->isMethod('POST') || 0 !== strpos((string) $request->request->get('FORM_SUBMIT'), 'tl_login')) {
            return false;
        }

        return parent::requiresAuthentication($request);
    }
}
