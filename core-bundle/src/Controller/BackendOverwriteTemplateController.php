<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\Backend;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Form\BackendOverwriteTemplateType;
use Contao\CoreBundle\Form\DTO\OverwriteTemplateDTO;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Twig\Environment;

class BackendOverwriteTemplateController
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var CsrfTokenManager
     */
    private $csrfTokenManager;

    /**
     * @var string
     */
    private $csrfTokenName;

    /**
     * @internal Do not inherit from this class; decorate the "Contao\CoreBundle\Controller\BackendCreateTemplateController" service instead
     */
    public function __construct(RequestStack $requestStack, FormFactory $formFactory, Environment $twig, CsrfTokenManager $csrfTokenManager, string $csrfTokenName)
    {
        $this->requestStack = $requestStack;
        $this->formFactory = $formFactory;
        $this->twig = $twig;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenName = $csrfTokenName;
    }

    public function __invoke(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new InternalServerErrorException('No request object given.');
        }

        $form = $this->formFactory->create(
            BackendOverwriteTemplateType::class,
            null,
            ['showHelp' => Config::get('showHelp')]
        );

        $form->handleRequest($request);

        $referer = Backend::getReferer(true);
        $error = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $error = $this->copyTemplate($form->getData());

            if (null === $error) {
                throw new RedirectResponseException($referer);
            }
        }

        $context = [
            'error' => $error,
            'form' => $form->createView(),
            'contao_csrf_token' => $this->csrfTokenManager->getToken($this->csrfTokenName)->getValue(),
            'referer' => $referer,
        ];

        return new Response(
            $this->twig->render('@ContaoCore/Backend/Template/be_create_template.html.twig', $context)
        );
    }

    private function copyTemplate(OverwriteTemplateDTO $data): ?string
    {
        $source = $data->getSource();
        $target = $data->getTarget();

        $filesystem = new Filesystem();

        if ($filesystem->exists($target)) {
            return sprintf("The selected template already exists in '$target'.");
        }

        try {
            $filesystem->copy($source, $target);
        } catch (\Exception $e) {
            return 'Could not copy template: '.$e->getMessage();
        }

        return null;
    }
}
