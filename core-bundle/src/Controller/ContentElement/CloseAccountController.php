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
use Contao\CoreBundle\Event\CloseAccountEvent;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Form\Type\CloseAccountType;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\Idna;
use Contao\MemberModel;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'user')]
class CloseAccountController extends AbstractContentElementController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly LoggerInterface $logger,
        private readonly VirtualFilesystem $storage,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof FrontendUser) {
            return $template->getResponse();
        }

        $this->framework->initialize();

        $memberModelAdapter = $this->framework->getAdapter(MemberModel::class);
        $member = $memberModelAdapter->findById($user->id);

        if (!$member instanceof MemberModel) {
            return $template->getResponse();
        }

        $form = $this->createForm(CloseAccountType::class, [], ['attr' => ['id' => 'tl_close_account'.$model->id]]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher->dispatch(new CloseAccountEvent($member, $model->reg_close, $model));

            if ('close_delete' === $model->reg_close) {
                $filesModelAdapter = $this->framework->getAdapter(FilesModel::class);
                $homeDir = $filesModelAdapter->findByUuid($member->homeDir);

                if ($model->reg_deleteDir && $member->assignDir && $homeDir) {
                    $this->storage->deleteDirectory($homeDir->path);
                }

                $member->delete();

                $this->logger->info('User account ID '.$user->id.' ('.Idna::decodeEmail($user->email).') has been deleted');
            }

            if ('close_deactivate' === $model->reg_close) {
                $member->disable = true;
                $member->tstamp = time();
                $member->save();

                $this->logger->info('User account ID '.$user->id.' ('.Idna::decodeEmail($user->email).') has been deactivated');
            }

            // Logout user, ignore response
            $this->security->logout(false);

            if ($model->jumpTo) {
                $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
                $page = $pageModelAdapter->findById($model->jumpTo);

                if ($page instanceof PageModel) {
                    return new RedirectResponse($this->contentUrlGenerator->generate($page));
                }
            }
        }

        $template->set('has_member', $this->getUser() instanceof FrontendUser);
        $template->set('form', $form->createView());

        return $template->getResponse();
    }
}
