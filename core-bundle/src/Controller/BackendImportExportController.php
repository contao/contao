<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendAlerts;
use Contao\BackendConfirm;
use Contao\BackendHelp;
use Contao\BackendIndex;
use Contao\BackendMain;
use Contao\BackendPassword;
use Contao\BackendPopup;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\DataContainer;
use Contao\DcaLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Route('%contao.backend.route_prefix%', defaults: ['_scope' => 'backend', '_token_check' => true])]
class BackendImportExportController extends AbstractController
{
    #[Route('/_export', name: 'contao_backend_export', methods: ['POST'])]
    public function exportAction(Request $request): Response
    {
        $table = $request->request->get('table');
        $id = $request->request->get('id');

        $this->initializeContaoFramework();

        $this->container->get('contao.framework')->createInstance(DcaLoader::class, [$table])->load();

        $dataContainer = DataContainer::getDriverForTable($table);

        /** @var DataContainer $dc */
        $dc = new $dataContainer($table, []);
        $dc->id = $id;

        return $this->json([
            'contao_export' => '1.0.0',
            'data' => [
                $table => [$dc->getCurrentRecord()]
            ],
        ]);
    }

    #[Route('/_import', name: 'contao_backend_import', methods: ['POST'])]
    public function importAction(Request $request): Response
    {
        $import = json_decode($request->request->get('import'), true);
        $version = $import['contao_export'] ?? '0.0.0';
        if (version_compare($version, '1.0.0', '<') || !version_compare($version, '2.0.0', '<')) {
            throw new \InvalidArgumentException();
        }

        $table = array_key_first($import['data']);
        $row = $import['data'][$table][0];

        unset($row['id'], $row['sorting']);

        $this->container->get('contao.framework')->createInstance(DcaLoader::class, [$table])->load();
        $GLOBALS['TL_DCA'][$table]['config']['ptable'] = $row['ptable'] ?? null;
        $dataContainer = DataContainer::getDriverForTable($table);

        /** @var DataContainer $dc */
        $dc = new $dataContainer($table, []);
        try {
            $dc->create($row);
        } catch (RedirectResponseException|AjaxRedirectResponseException $exception) {
            $url = $exception->getResponse()->getTargetUrl();
        }

        return $this->json([
            'url' => $url,
        ]);
    }
}
