<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\BackendDashboard;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\Message;
use Contao\StringUtil;
use Contao\Versions;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Shows the welcome screen in the backend dashboard.
 *
 * @internal
 */
class WelcomeScreenController extends AbstractDashboardWidgetController
{
    private ContaoFramework $framework;
    private TranslatorInterface $translator;

    public function __construct(ContaoFramework $framework, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function __invoke(): Response
    {
        $template = new BackendTemplate('be_welcome');

        $messages = $this->framework->createInstance(Message::class);
        $user = $this->framework->createInstance(BackendUser::class);
        $config = $this->framework->createInstance(Config::class);

        $template->messages = $messages->generateUnwrapped().Backend::getSystemMessages();
        $template->loginMsg = $this->trans('MSC.firstLogin');

        // Add the login message
        if ($user->lastLogin > 0) {
            $formatter = new DateTimeFormatter($this->translator);
            $diff = $formatter->formatDiff(new \DateTime(date('Y-m-d H:i:s', (int) $user->lastLogin)), new \DateTime());

            $template->loginMsg = sprintf($this->trans('MSC.lastLogin.1'), sprintf(
                '<time title="%s">%s</time>',
                Date::parse($config->get('datimFormat'), (int) $user->lastLogin),
                $diff
            ));
        }

        // Add the versions overview
        Versions::addToTemplate($template);

        $template->showDifferences = StringUtil::specialchars(str_replace("'", "\\'", $this->trans('MSC.showDifferences')));
        $template->recordOfTable = StringUtil::specialchars(str_replace("'", "\\'", $this->trans('MSC.recordOfTable')));
        $template->systemMessages = $this->trans('MSC.systemMessages');
        $template->shortcuts = $this->trans('MSC.shortcuts.0');
        $template->shortcutsLink = $this->trans('MSC.shortcuts.1');
        $template->editElement = StringUtil::specialchars($this->trans('MSC.editElement'));

        return $template->getResponse();
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'contao_default');
    }
}
