<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\EventListener;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\DataContainer;
use Contao\NewsletterDenyListModel;
use Contao\NewsletterRecipientsModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class BlockRecipientListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly RouterInterface $router,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly string $csrfTokenName,
    ) {
    }

    #[AsCallback('tl_newsletter_recipients', 'list.operations.block.button')]
    public function onButton(DataContainerOperation $operation): void
    {
        if (!$this->canDelete($operation->getRecord())) {
            $operation->hide();
        }
    }

    /**
     * Add a recipient to the deny list.
     */
    public function blockRecipient(DataContainer $dc): never
    {
        $request = $this->requestStack->getCurrentRequest();

        // Check the request token
        if (
            (!$request || $request->isMethodSafe())
            && !$this->csrfTokenManager->isTokenValid(new CsrfToken($this->csrfTokenName, $request?->query->getString('rt')))
        ) {
            throw new InvalidRequestTokenException('Invalid CSRF token. Please reload the page and try again.');
        }

        $recipient = $this->framework->getAdapter(NewsletterRecipientsModel::class)->findById($dc->id);

        if (!$recipient) {
            throw new NotFoundException('Cannot load record "tl_newsletter_recipient.id='.$dc->id.'".');
        }

        if (!$this->canDelete($recipient->row())) {
            throw new AccessDeniedException('Not enough permissions to delete newsletter recipient ID '.$dc->id);
        }

        $hashedEmail = md5($recipient->email);

        if (!$this->framework->getAdapter(NewsletterDenyListModel::class)->findByHashAndPid($hashedEmail, $recipient->pid)) {
            $objDenyList = $this->framework->createInstance(NewsletterDenyListModel::class);
            $objDenyList->pid = $recipient->pid;
            $objDenyList->hash = $hashedEmail;
            $objDenyList->save();
        }

        // Prepare the redirect URL
        $params = ['do' => 'newsletter', 'id' => $recipient->pid, 'table' => 'tl_newsletter_recipients'];

        $recipient->delete();

        throw new RedirectResponseException($this->router->generate('contao_backend', $params, UrlGeneratorInterface::ABSOLUTE_URL));
    }

    private function canDelete(array $record): bool
    {
        return $this->security->isGranted(
            ContaoCorePermissions::DC_PREFIX.'tl_newsletter_recipients',
            new DeleteAction('tl_newsletter_recipients', $record),
        );
    }
}
