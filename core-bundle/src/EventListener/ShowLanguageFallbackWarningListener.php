<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Date;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generates the warning messages related to misconfigured website roots.
 *
 * @internal
 */
class ShowLanguageFallbackWarningListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsCallback('tl_page', 'config.onload')]
    public function onPageLoad(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!\in_array($request?->query->get('act'), ['paste', 'select', null], true)) {
            return;
        }

        foreach ($this->getMessages() as $message) {
            Message::addError($message);
        }
    }

    #[AsHook('getSystemMessages')]
    public function onGetSystemMessages(): string
    {
        $return = '';

        foreach ($this->getMessages() as $message) {
            $return .= '<p class="tl_error">'.$message.'</p>';
        }

        return $return;
    }

    /**
     * @return list<string>
     */
    private function getMessages(): array
    {
        $time = Date::floorToMinute();
        $records = $this->connection->fetchAllAssociative("SELECT fallback, dns FROM tl_page WHERE type='root' AND published=1 AND (start='' OR start<=$time) AND (stop='' OR stop>$time) ORDER BY dns");
        $roots = [];

        foreach ($records as $root) {
            $dns = $root['dns'] ?: '*';

            if (true === ($roots[$dns] ?? null)) {
                continue;
            }

            $roots[$dns] = (bool) $root['fallback'];
        }

        $messages = [];

        foreach ($roots as $k => $v) {
            if ($v) {
                continue;
            }

            if ('*' === $k) {
                $messages[] = $this->translator->trans('ERR.noFallbackEmpty', [], 'contao_default');
            } else {
                $messages[] = $this->translator->trans('ERR.noFallbackDns', [$k], 'contao_default');
            }
        }

        return $messages;
    }
}
