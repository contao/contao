<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\OAuthBundle\Model\OAuthClientModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsFrontendModule(type: 'oauth_connect', category: 'user')]
class OAuthConnectController extends AbstractFrontendModuleController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $clients = $model->getRelated('oauthClients');

        if (null === $clients) {
            return new Response();
        }

        $oauthUrls = [];
        $extension = new KnpUOAuth2ClientExtension();

        foreach ($clients as $client) {
            /** @var OAuthClientModel $client */
            $config = $extension->getConfigurator($client->type);

            $oauthUrls[] = [
                'url' => $this->urlGenerator->generate('contao_oauth_connect', ['moduleId' => (int) $model->id, 'clientId' => (int) $client->id]),
                'name' => $config->getProviderDisplayName(),
            ];
        }

        $template->oauthUrls = $oauthUrls;

        if ($model->jumpTo && null !== ($page = PageModel::findByPk($model->jumpTo))) {
            $redirect = $page->getAbsoluteUrl();
        } else {
            $redirect = System::getReferer() ?: $request->getUri();
        }

        $request->getSession()->set('_oauth_redirect', $redirect);

        return $template->getResponse();
    }
}
