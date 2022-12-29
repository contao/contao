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
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsFrontendModule(type: 'oauth_connect', category: 'user')]
class OAuthConnectController extends AbstractFrontendModuleController
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator, private readonly UriSigner $uriSigner)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $clientModels = $model->getRelated('oauthClients');

        if (null === $clientModels) {
            return new Response();
        }

        $oauthUrls = [];
        $extension = new KnpUOAuth2ClientExtension();

        // Determine redirect URL
        $redirect = $request->getUri();

        if ($model->jumpTo && null !== ($page = PageModel::findByPk($model->jumpTo))) {
            $redirect = $page->getAbsoluteUrl();
        } elseif ($model->redirectBack && $request->query->has('redirect') && $this->uriSigner->check($request->getUri())) {
			$redirect = $request->query->get('redirect');
		}

        foreach ($clientModels as $clientModel) {
            /** @var OAuthClientModel $clientModel */
            $providerConfig = $extension->getConfigurator($clientModel->type);

            $url = $this->urlGenerator->generate('contao_oauth_connect', [
                'moduleId' => (int) $model->id, 
                'clientId' => (int) $clientModel->id, 
                'redirect' => $redirect
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $url = $this->uriSigner->sign($url);

            $oauthUrls[] = [
                'url' => $url,
                'name' => $providerConfig->getProviderDisplayName(),
                'type' => $clientModel->type,
            ];
        }

        $template->oauthUrls = $oauthUrls;

        return $template->getResponse();
    }
}
