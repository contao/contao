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
use Contao\Controller;
use Contao\CoreBundle\Event\NewPasswordEvent;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\OptInModel;
use Contao\System;
use Contao\Versions;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class AbstractChangePasswordContentElementController extends AbstractContentElementController
{
    /**
     * @return array<string>
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['security.password_hasher_factory'] = PasswordHasherFactoryInterface::class;

        return $services;
    }

    protected function executeOnloadCallbacks(): void
    {
        $this->container->get('contao.framework')->getAdapter(Controller::class)->loadDataContainer('tl_member');

        if (\is_array($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA']['tl_member']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->container->get('contao.framework')->getAdapter(System::class)->importStatic($callback[0])->{$callback[1]}();
                } elseif (\is_callable($callback)) {
                    $callback();
                }
            }
        }
    }

    /**
     * @template T
     *
     * @param FormInterface<T> $form
     */
    protected function updatePassword(ContentModel $model, MemberModel $member, FormInterface $form): void
    {
        $versions = $this->container->get('contao.framework')->createInstance(Versions::class, ['tl_member', $member->id]);
        $versions->setUsername($member->username);
        $versions->setEditUrl($this->container->get('router')->generate('contao_backend', ['do' => 'member', 'act' => 'edit', 'id' => $member->id]));
        $versions->initialize();

        $passwordHasher = $this->container->get('security.password_hasher_factory')->getPasswordHasher(FrontendUser::class);
        $hashedPassword = $passwordHasher->hash($form->get('newpassword')->getData());

        $member->tstamp = time();
        $member->password = $hashedPassword;
        $member->save();

        // Delete unconfirmed "change password" tokens
        $tokens = $this->container->get('contao.framework')->getAdapter(OptInModel::class)->findUnconfirmedByRelatedTableAndId('tl_member', $member->id);

        foreach ($tokens ?? [] as $token) {
            $token->delete();
        }

        if ($GLOBALS['TL_DCA']['tl_member']['config']['enableVersioning'] ?? null) {
            $versions->create();
        }

        $this->container->get('event_dispatcher')->dispatch(new NewPasswordEvent($member, $form->get('newpassword')->getData(), $hashedPassword, $model));
    }
}
