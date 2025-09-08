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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\Folder;
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
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

#[AsContentElement(category: 'miscellaneous')]
class CloseAccountController extends AbstractContentElementController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $formId = 'tl_close_account_'.$model->id;
        $user = $this->getUser();

        if (!$user instanceof FrontendUser) {
            return $template->getResponse();
        }

        $this->framework->initialize();

        $memberModel = $this->framework->getAdapter(MemberModel::class);
        $member = $memberModel->findById($user->id);

        if (!$member instanceof MemberModel) {
            return $template->getResponse();
        }

        $template->set('has_member', $this->getUser() instanceof FrontendUser);
        $template->set('form_id', $formId);

        if ($formId === $request->request->get('FORM_SUBMIT')) {
            $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(FrontendUser::class);

            if (!$passwordHasher->verify($user->password, $request->request->get('password'))) {
                $template->set('error', true);

                return $template->getResponse();
            }

            $this->eventDispatcher->dispatch(new CloseAccountEvent($member, $model->reg_close));

            if ('close_delete' === $model->reg_close) {
                $filesModel = $this->framework->getAdapter(FilesModel::class);
                $homeDir = $filesModel->findByUuid($member->homeDir);

                if ($model->reg_deleteDir && $member->assignDir && $homeDir) {
                    $folder = new Folder($homeDir->path);
                    $folder->delete();
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
                $pageModel = $this->framework->getAdapter(PageModel::class);
                $page = $pageModel->findById($model->jumpTo);

                if ($page instanceof PageModel) {
                    return new RedirectResponse($this->contentUrlGenerator->generate($page));
                }
            }
        }

        return $template->getResponse();
    }
}
