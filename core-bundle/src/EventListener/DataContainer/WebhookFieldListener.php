<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Webhook\SecretEncryption;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Contao\DataContainer;
use Contao\Message;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookFieldListener
{
    private const GENERATED_SECRET_SESSION_KEY = 'contao.webhook_secret';

    private const SECRET_PLACEHOLDER = '********';

    private string|null $generatedSecret = null;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly WebhookRepository $repository,
        private readonly SecretEncryption $encryption,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'config.onbeforesubmit')]
    public function generateSecret(array $record, DataContainer $dc): array
    {
        if (empty($record['secret']) && empty($dc->getCurrentRecord()['secret'])) {
            $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            $record['secret'] = $this->encryption->encrypt($secret);
            $this->generatedSecret = $secret;
        }

        return $record;
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'config.onsubmit')]
    public function storeGeneratedSecret(DataContainer $dc): void
    {
        if (null === $this->generatedSecret) {
            return;
        }

        $this->requestStack->getSession()->set(self::GENERATED_SECRET_SESSION_KEY, $this->generatedSecret);
        $this->generatedSecret = null;
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'config.onload')]
    public function showGeneratedSecret(DataContainer $dc): void
    {
        $session = $this->requestStack->getSession();
        $secret = $session->get(self::GENERATED_SECRET_SESSION_KEY);

        if (null === $secret) {
            return;
        }

        $session->remove(self::GENERATED_SECRET_SESSION_KEY);
        Message::addInfo(\sprintf($this->translator->trans('MSC.generatedWebhookSecret', [], 'contao_default'), $secret));
    }

    #[AsCallback(table: 'tl_webhook_outgoing', target: 'fields.url.save')]
    public function saveUrl(string $value): string
    {
        $parts = parse_url($value);

        if (false === $parts || 'https' !== strtolower($parts['scheme'] ?? '') || isset($parts['user']) || isset($parts['pass'])) {
            throw new \InvalidArgumentException('Webhook destinations must use HTTPS and must not contain credentials.');
        }

        return $value;
    }

    #[AsCallback(table: 'tl_webhook_outgoing', target: 'fields.secret.save')]
    public function saveSecret(string $value, DataContainer $dc): string
    {
        return $this->saveEncryptedSecret($value, $dc);
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'fields.webhookUrl.load')]
    public function loadWebhookUrl(mixed $value, DataContainer $dataContainer): string
    {
        $token = $this->repository->getPublicToken((int) $dataContainer->id);

        return null === $token ? '' : $this->urlGenerator->generate('contao_webhook_incoming', ['endpointToken' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'fields.secret.save')]
    public function saveIncomingSecret(string $value, DataContainer $dc): string
    {
        return $this->saveEncryptedSecret($value, $dc);
    }

    #[AsCallback(table: 'tl_webhook_ingoing', target: 'fields.secret.load')]
    public function loadIncomingSecret(mixed $value): string
    {
        return '' !== $value ? self::SECRET_PLACEHOLDER : '';
    }

    private function saveEncryptedSecret(string $value, DataContainer $dc): string
    {
        if (self::SECRET_PLACEHOLDER === $value) {
            return (string) ($dc->getCurrentRecord()['secret'] ?? '');
        }

        if ('' === $value) {
            return $value;
        }

        try {
            $this->encryption->decrypt($value);

            return $value;
        } catch (\RuntimeException) {
            return $this->encryption->encrypt($value);
        }
    }
}
