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
use Contao\CoreBundle\Framework\ContaoFramework;
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
class LanguageFallbackWarningListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly ContaoFramework $contaoFramework,
    ) {
    }

    #[AsCallback('tl_page', 'config.onload')]
    public function onPageLoad(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!\in_array($request?->query->get('act'), ['paste', 'select', null], true)) {
            return;
        }

        $this->contaoFramework->getAdapter(Message::class)->addRaw($this->getMessages());
    }

    #[AsHook('getSystemMessages')]
    public function getMessages(): string
    {
        $time = Date::floorToMinute();
        $roots = [];

        $records = $this->connection->fetchAllAssociative(
            <<<SQL
                SELECT
                    fallback,
                    dns
                FROM tl_page
                WHERE
                    type = 'root'
                    AND published = 1
                    AND (start = '' OR start <= $time)
                    AND (stop = '' OR stop > $time)
                ORDER BY dns
                SQL,
        );

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

        return implode('', array_map(static fn (string $message): string => '<p class="tl_error">'.$message.'</p>', $messages));
    }
}
