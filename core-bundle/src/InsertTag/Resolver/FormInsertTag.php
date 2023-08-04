<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class FormInsertTag
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsInsertTag('form_session_data', asFragment: true)]
    public function replaceSessionData(ResolvedInsertTag $insertTag): InsertTagResult
    {
        return new InsertTagResult(
            $this->requestStack->getCurrentRequest()?->getSession()->get(Form::SESSION_KEY)?->getValue()[$insertTag->getParameters()->get(0)],
            OutputType::text,
        );
    }

    #[AsInsertTag('form_confirmation', asFragment: true)]
    public function replaceConfirmation(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $message = '';
        $session = $this->requestStack->getCurrentRequest()?->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $message = $session->getFlashBag()->get(Form::SESSION_CONFIRMATION_KEY)['message'] ?? '';
        }

        return new InsertTagResult($message, OutputType::html);
    }
}
