<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\EventListener\DataContainer;

use Composer\InstalledVersions;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Message;
use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks whether the corresponding packages for the used OAuth types are installed,
 * and displays a message otherwise.
 */
#[AsCallback('tl_oauth_client', 'config.onload')]
class OAuthClientCheckListener
{
    public function __construct(private readonly Connection $db, private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(): void
    {
        $extension = new KnpUOAuth2ClientExtension();
        $records = $this->db->fetchAllAssociative("SELECT type FROM tl_oauth_client WHERE type != '' AND tstamp > 0 ORDER BY id ASC");

        foreach ($records as $record) {
            try {
                $config = $extension->getConfigurator($record['type']);
            } catch (\ErrorException) {
                // Ignore invalid types
                continue;
            }

            $packageName = $config->getPackagistName();

            try {
                InstalledVersions::getVersion($packageName);
            } catch (\OutOfBoundsException) {
                Message::addError($this->translator->trans('ERR.oauthPackageNotInstalled', [$config->getProviderDisplayName(), $packageName], 'contao_default'));
            }
        }
    }
}
