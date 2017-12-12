<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InteractiveLoginListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ContaoFrameworkInterface $framework
     * @param LoggerInterface          $logger
     */
    public function __construct(ContaoFrameworkInterface $framework, LoggerInterface $logger)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    /**
     * Logs successful login attempts.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $user->lastLogin = $user->currentLogin;
        $user->currentLogin = time();
        $user->loginCount = $config->get('loginCount');
        $user->save();

        $this->logger->info(
            sprintf('User "%s" has logged in', $user->getUsername()),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
        );

        $this->triggerPostLoginHook($user);
    }

    /**
     * Triggers the postLogin hook.
     *
     * @param User $user
     */
    private function triggerPostLoginHook(User $user): void
    {
        $this->framework->initialize();

        if (empty($GLOBALS['TL_HOOKS']['postLogin']) || !\is_array($GLOBALS['TL_HOOKS']['postLogin'])) {
            return;
        }

        @trigger_error('Using the "postLogin" hook has been deprecated and will no longer work in Contao 5.0. Use the security.interactive_login event instead.', E_USER_DEPRECATED);

        foreach ($GLOBALS['TL_HOOKS']['postLogin'] as $callback) {
            $this->framework->createInstance($callback[0])->{$callback[1]}($user);
        }
    }
}
