<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Form\Type;

use Contao\CoreBundle\Altcha\Altcha;
use Contao\CoreBundle\Controller\AltchaController;
use Contao\StringUtil;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<AltchaType>
 */
class AltchaType extends AbstractType
{
    public function __construct(
        private readonly Packages $packages,
        private readonly Altcha $altcha,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'max_number' => $this->altcha->getRangeMax(),
            'challenge_url' => $this->router->generate(AltchaController::class),
            'strings' => $this->getLocalization(),
            'auto' => false,
            'hide_logo' => false,
            'hide_footer' => false,
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $GLOBALS['TL_CSS'][] = $this->packages->getUrl('css/altcha.min.css', 'contao-components/altcha');
        $GLOBALS['TL_BODY'][] = \sprintf('<script src="%s" type="module"></script>', $this->packages->getUrl('js/altcha.min.js', 'contao-components/altcha'));

        $view->vars['max_number'] = $options['max_number'];
        $view->vars['challenge_url'] = $options['challenge_url'];
        $view->vars['strings'] = $options['strings'];
        $view->vars['auto'] = $options['auto'];
        $view->vars['hide_logo'] = $options['hide_logo'];
        $view->vars['hide_footer'] = $options['hide_footer'];

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            $view->vars['can_use_altcha'] = false;

            return;
        }

        $view->vars['can_use_altcha'] = $request->isSecure();

        if (!$request->isSecure()) {
            $host = $request->getHost();

            // The context is also considered secure if the host is 127.0.0.1, localhost or *.localhost.
            // https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts#when_is_a_context_considered_secure
            $view->vars['can_use_altcha'] = \in_array($host, ['127.0.0.1', 'localhost'], true) || str_ends_with($host, '.localhost');
        }
    }

    public function getBlockPrefix(): string
    {
        return 'altcha';
    }

    protected function getLocalization(): string
    {
        return StringUtil::specialchars(json_encode([
            'error' => $this->translator->trans('ERR.altchaWidgetError', [], 'contao_default'),
            'footer' => $this->translator->trans('MSC.altchaFooter', [], 'contao_default'),
            'label' => $this->translator->trans('MSC.altchaLabel', [], 'contao_default'),
            'verified' => $this->translator->trans('MSC.altchaVerified', [], 'contao_default'),
            'verifying' => $this->translator->trans('MSC.altchaVerifying', [], 'contao_default'),
            'waitAlert' => $this->translator->trans('MSC.altchaWaitAlert', [], 'contao_default'),
        ], JSON_THROW_ON_ERROR));
    }
}
