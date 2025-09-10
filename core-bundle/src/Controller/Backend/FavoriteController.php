<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
        private readonly ContaoCsrfTokenManager $tokenManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return new Response();
        }

        $url = self::getRequestUri($request);
        $id = $this->getCurrentId($url, $user);
        $active = null !== $id;
        $empty = false;
        $stream = false;

        if ($request->isMethod(Request::METHOD_POST) && 'toggle-favorite' === $request->request->get('FORM_SUBMIT')) {
            if (!$active) {
                throw new RedirectResponseException($this->saveAsFavoriteLink($url));
            }

            Controller::loadDataContainer('tl_favorites');
            $dc = new DC_Table('tl_favorites');
            $dc->id = $id;
            $dc->delete(true);

            $active = false;
            $empty = !$this->connection->fetchOne('SELECT COUNT(*) FROM tl_favorites WHERE user=?', [$user->id]);

            if (\in_array('text/vnd.turbo-stream.html', $request->getAcceptableContentTypes(), true)) {
                $stream = true;
            } else {
                throw new RedirectResponseException($request->getUri());
            }
        }

        $response = $this->render('@Contao/backend/chrome/favorite.html.twig', [
            'active' => $active,
            'empty' => $empty,
            'stream' => $stream,
            'id' => $id,
        ]);

        if ($stream) {
            $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
            throw new ResponseException($response);
        }

        return $response;
    }

    public static function getRequestUri(Request $request): string
    {
        if (null !== $qs = $request->getQueryString()) {
            parse_str($qs, $pairs);
            ksort($pairs);

            unset($pairs['rt'], $pairs['ref'], $pairs['revise']);

            if ([] !== $pairs) {
                $qs = '?'.http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
            }
        }

        return $request->getBaseUrl().$request->getPathInfo().$qs;
    }

    private function getCurrentId(string $url, BackendUser $user): int|null
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM tl_favorites WHERE url = :url AND user = :user',
            [
                'url' => $url,
                'user' => $user->id,
            ],
        );

        if (false === $id) {
            return null;
        }

        return (int) $id;
    }

    private function remove(int $id): void
    {

    }

    private function saveAsFavoriteLink(string $url): string
    {
        return System::getContainer()->get('router')->generate('contao_backend', [
            'do' => 'favorites',
            'act' => 'paste',
            'mode' => 'create',
            'data' => base64_encode($url),
            'rt' => $this->tokenManager->getDefaultTokenValue(),
        ]);
    }

    private function removeFavoritesLink(int|string $id): string
    {
        return System::getContainer()->get('router')->generate('contao_backend', [
            'do' => 'favorites',
            'act' => 'delete',
            'id' => $id,
        ]);
    }
}
