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
use Contao\CoreBundle\Exception\BadRequestException;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsController]
#[Route('%contao.backend.route_prefix%/_favorites', 'contao_backend_favorites', defaults: ['_scope' => 'backend'])]
class FavoriteController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof BackendUser) {
            return new Response();
        }

        if ($request->isMethod(Request::METHOD_POST) && 'remove-favorite' === $request->request->get('FORM_SUBMIT')) {
            $url = UrlUtil::getNormalizePathAndQuery($request->request->get('target_path'));
            $id = $this->getCurrentId($url, $user);

            if (!$id) {
                throw new BadRequestException();
            }

            Controller::loadDataContainer('tl_favorites');
            $dc = new DC_Table('tl_favorites');
            $dc->id = $id;
            $dc->delete(true);

            if ('turbo_stream' === $request->getPreferredFormat()) {
                $request->setRequestFormat('turbo_stream');

                return $this->renderBlock(
                    '@Contao/backend/chrome/favorite.html.twig',
                    'success_stream',
                    [
                        'id' => $id,
                        'active' => false,
                        'action' => $this->saveAsFavoriteLink($url),
                        'empty' => !$this->connection->fetchOne('SELECT COUNT(*) FROM tl_favorites WHERE user=?', [$user->id]),
                    ],
                );
            }

            return $this->redirect($url);
        }

        if (!($targetPath = $request->get('target_path'))) {
            throw new BadRequestException();
        }

        $url = UrlUtil::getNormalizePathAndQuery($targetPath);
        $id = $this->getCurrentId($url, $user);
        $active = null !== $id;

        return $this->renderBlock(
            '@Contao/backend/chrome/favorite.html.twig',
            'form',
            [
                'action' => $active ? $this->urlGenerator->generate(self::class) : $this->saveAsFavoriteLink($url),
                'target_path' => $url,
                'active' => $active,
            ],
        );
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

    private function saveAsFavoriteLink(string $url): string
    {
        return $this->generateUrl('contao_backend', [
            'do' => 'favorites',
            'act' => 'paste',
            'mode' => 'create',
            'data' => base64_encode($url),
            'return' => '1',
        ]);
    }
}
