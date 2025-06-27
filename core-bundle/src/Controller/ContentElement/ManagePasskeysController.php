<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Repository\WebauthnCredentialRepository;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsContentElement(category: 'user')]
class ManagePasskeysController extends AbstractContentElementController
{
    public function __construct(
        private readonly WebauthnCredentialRepository $credentialRepo,
        private readonly UriSigner $uriSigner,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (!($user = $this->getUser()) instanceof FrontendUser || !$page = $this->getPageModel()) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY', message: 'Full authentication is required to manage the passkeys.');

        if ($request->query->get('edit_new_passkey') && $this->uriSigner->checkRequest($request)) {
            if ($credential = $this->credentialRepo->getLastForUser($user)) {
                $template->edit_passkey_id = $credential->getId();
            }
        }

        if ($request->request->get('FORM_SUBMIT') === 'passkeys_credentials_actions_'.$model->id) {
            if ($deleteCredentialId = $request->request->get('delete_passkey')) {
                if ($credential = $this->credentialRepo->findOneById($deleteCredentialId)) {
                    $this->checkCredentialAccess($credential);

                    $this->credentialRepo->remove($credential);
                }
            } elseif ($editCredentialId = $request->request->get('edit_passkey')) {
                if ($credential = $this->credentialRepo->findOneById($editCredentialId)) {
                    $this->checkCredentialAccess($credential);

                    return new RedirectResponse($this->generateContentUrl($page, ['edit_passkey' => $editCredentialId]));
                }
            }

            return new RedirectResponse($this->generateContentUrl($page));
        }

        if ($request->request->get('FORM_SUBMIT') === 'passkeys_credentials_edit_'.$model->id) {
            if ($saveCredentialId = $request->request->get('credential_id')) {
                if ($credential = $this->credentialRepo->findOneById($saveCredentialId)) {
                    $this->checkCredentialAccess($credential);

                    $credential->name = $request->request->get('passkey_name') ?? '';
                    $this->credentialRepo->saveCredentialSource($credential);
                }
            }

            return new RedirectResponse($this->generateContentUrl($page));
        }

        $template->credentials = $this->credentialRepo->getAllForUser($user);
        $template->edit_passkey_id ??= $request->query->get('edit_passkey');
        $template->redirect = $this->uriSigner->sign($this->generateContentUrl($page, ['edit_new_passkey' => 1], UrlGeneratorInterface::ABSOLUTE_URL));

        return $template->getResponse();
    }

    private function checkCredentialAccess(WebauthnCredential $credential): void
    {
        $this->denyAccessUnlessGranted(
            ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP,
            $credential,
            'Cannot access credential ID '.$credential->getId(),
        );
    }
}
