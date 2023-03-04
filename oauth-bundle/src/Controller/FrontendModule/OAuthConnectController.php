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
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Session\Attribute\AutoExpiringAttribute;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Contao\OAuthBundle\Model\OAuthClientModel;
use Contao\OAuthBundle\OAuthClientGenerator;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(type: 'oauth_connect', category: 'user')]
class OAuthConnectController extends AbstractFrontendModuleController
{
    private const SESSION_TTL = 300;

    public function __construct(
        private readonly UriSigner $uriSigner, 
        private readonly ContaoCsrfTokenManager $csrfTokenManager, 
        private readonly Connection $db, 
        private readonly OAuthClientGenerator $clientGenerator,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface|null $logger
    )
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $formId = 'module-'.$model->type.'-'.$model->id;

        if ($request->isMethod(Request::METHOD_POST) && $formId === $request->request->get('FORM_SUBMIT')) {
            $clientId = (int) $request->request->get('client');
            $moduleId = (int) $model->id;

            $clientConfig = $this->db->fetchAssociative('SELECT * FROM tl_oauth_client WHERE id = ?', [$clientId]);

            if (false === $clientConfig) {
                throw new \InvalidArgumentException('Invalid client ID.');
            }

            $oauthClient = $this->clientGenerator->getClient($clientConfig);
            $scopes = StringUtil::deserialize($clientConfig['scopes'], true);

            // Add default scopes depending on the client
            if (empty($scopes)) {
                $scopes = $this->clientGenerator->getDefaultScopes($oauthClient);
            }

            $session = $request->getSession();
            $session->set('_oauth_module_id', new AutoExpiringAttribute(self::SESSION_TTL, $moduleId));
            $session->set('_oauth_client_id', new AutoExpiringAttribute(self::SESSION_TTL, $clientId));
            $session->set('_oauth_failure_url', new AutoExpiringAttribute(self::SESSION_TTL, $request->getUri()));

            if ($redirectUrl = $request->request->get('_target_path')) {
                $session->set('_oauth_redirect', new AutoExpiringAttribute(self::SESSION_TTL, $redirectUrl));
            }

            if ($request->request->get('autologin')) {
                $session->set('_oauth_remember_me', new AutoExpiringAttribute(self::SESSION_TTL, true));
            }

            return $oauthClient->redirect($scopes);
        }

        $clientModels = $model->getRelated('oauthClients');

        if (null === $clientModels) {
            return new Response();
        }

        $buttons = [];
        $extension = new KnpUOAuth2ClientExtension();

        foreach ($clientModels as $clientModel) {
            /** @var OAuthClientModel $clientModel */
            $providerConfig = $extension->getConfigurator($clientModel->type);

            $buttons[] = [
                'client' => (int) $clientModel->id,
                'name' => $providerConfig->getProviderDisplayName(),
                'type' => $clientModel->type,
            ];
        }

        // Determine redirect URL
        $targetPath = $request->getUri();

        if ($model->jumpTo && null !== ($page = PageModel::findByPk($model->jumpTo))) {
            $targetPath = $page->getAbsoluteUrl();
        } elseif ($model->redirectBack && $request->query->has('redirect') && $this->uriSigner->checkRequest($request)) {
            $targetPath = $request->query->get('redirect');
        }

        $template->buttons = $buttons;
        $template->requestToken = $this->csrfTokenManager->getDefaultTokenValue();
        $template->autologin = $model->autologin;
        $template->targetPath = $targetPath;
        $template->formId = $formId;

        if ($request->hasSession() && $exception = $this->authenticationUtils->getLastAuthenticationError()) {
            $template->message = $this->translator->trans('ERR.oauthFailed', [], 'contao_default');

            if (null !== $this->logger) {
                $this->logger?->debug('OAuth authentication failed.', ['exception' => $exception]);
            }
        }

        return $template->getResponse();
    }
}
