<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\DataContainer;
use Contao\FileUpload;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class BackendCsvImportController
{
    public const SEPARATOR_COMMA = 'comma';
    public const SEPARATOR_LINEBREAK = 'linebreak';
    public const SEPARATOR_SEMICOLON = 'semicolon';
    public const SEPARATOR_TABULATOR = 'tabulator';

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param ContaoFrameworkInterface $framework
     * @param Connection               $connection
     * @param RequestStack             $requestStack
     * @param TranslatorInterface      $translator
     * @param string                   $projectDir
     */
    public function __construct(ContaoFrameworkInterface $framework, Connection $connection, RequestStack $requestStack, TranslatorInterface $translator, string $projectDir)
    {
        $this->framework    = $framework;
        $this->connection   = $connection;
        $this->requestStack = $requestStack;
        $this->translator   = $translator;
        $this->projectDir   = $projectDir;
    }

    /**
     * Imports CSV data in the list wizard.
     *
     * @param DataContainer $dc
     *
     * @return Response
     */
    public function importListWizard(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            function (array $data, array $row): array {
                return array_merge($data, $row);
            },
            $dc->table,
            'listitems',
            (int) $dc->id,
            $this->translator->trans('MSC.lw_import.0', [], 'contao_default'),
            true
        );
    }

    /**
     * Imports CSV data in the table wizard.
     *
     * @param DataContainer $dc
     *
     * @return Response
     */
    public function importTableWizard(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            function (array $data, array $row): array {
                $data[] = $row;

                return $data;
            },
            $dc->table,
            'tableitems',
            (int) $dc->id,
            $this->translator->trans('MSC.tw_import.0', [], 'contao_default')
        );
    }

    /**
     * Imports CSV data in the options wizard.
     *
     * @param DataContainer $dc
     *
     * @return Response
     */
    public function importOptionWizard(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            function (array $data, array $row): array {
                $data[] = [
                    'value' => $row[0],
                    'label' => $row[1],
                    'default' => !empty($row[2]) ? 1 : '',
                    'group' => !empty($row[3]) ? 1 : '',
                ];

                return $data;
            },
            $dc->table,
            'options',
            (int) $dc->id,
            $this->translator->trans('MSC.ow_import.0', [], 'contao_default')
        );
    }

    /**
     * Runs the default import routine with a Contao template.
     *
     * @param callable    $callback
     * @param string      $table
     * @param string      $field
     * @param int         $id
     * @param string|null $submitLabel
     * @param bool        $allowLinebreak
     *
     * @throws InternalServerErrorException
     *
     * @return Response
     */
    private function importFromTemplate(callable $callback, string $table, string $field, int $id, string $submitLabel = null, $allowLinebreak = false): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new InternalServerErrorException('No request object given.');
        }

        $this->framework->initialize();

        $uploader = new FileUpload();
        $template = $this->prepareTemplate($request, $uploader, $allowLinebreak);

        if (null !== $submitLabel) {
            $template->submitLabel = $submitLabel;
        }

        if ($request->request->get('FORM_SUBMIT') === $this->getFormId($request)) {
            try {
                $data = $this->fetchData($uploader, $request->request->get('separator'), $callback);
            } catch (\RuntimeException $e) {
                Message::addError($e->getMessage());

                return new RedirectResponse($request->getUri(), 303);
            }

            $this->connection->update(
                $table,
                [$field => serialize($data)],
                ['id' => $id]
            );

            $response = new RedirectResponse($this->getBackUrl($request));
            $response->headers->setCookie(new Cookie('BE_PAGE_OFFSET', 0, 0, $request->getBasePath(), null, false, false));

            return $response;
        }

        return new Response($template->parse());
    }

    /**
     * Creates the CSV import template.
     *
     * @param Request    $request
     * @param FileUpload $uploader
     * @param bool       $allowLinebreak
     *
     * @return BackendTemplate|object
     */
    private function prepareTemplate(Request $request, FileUpload $uploader, bool $allowLinebreak = false): BackendTemplate
    {
        /** @var BackendTemplate|object $template */
        $template = new BackendTemplate('be_csv_import');

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $template->formId = $this->getFormId($request);
        $template->backUrl = $this->getBackUrl($request);
        $template->action = $request->getRequestUri();
        $template->fileMaxSize = $config->get('maxFileSize');
        $template->uploader = $uploader->generateMarkup();
        $template->separators = $this->getSeparators($allowLinebreak);
        $template->submitLabel = $this->translator->trans('MSC.apply', [], 'contao_default');
        $template->backBT = $this->translator->trans('MSC.backBT', [], 'contao_default');
        $template->backBTTitle = $this->translator->trans('MSC.backBTTitle', [], 'contao_default');
        $template->separatorLabel = $this->translator->trans('MSC.separator.0', [], 'contao_default');
        $template->separatorHelp = $this->translator->trans('MSC.separator.1', [], 'contao_default');
        $template->sourceLabel = $this->translator->trans('MSC.source.0', [], 'contao_default');
        $template->sourceLabelHelp = $this->translator->trans('MSC.source.1', [], 'contao_default');

        return $template;
    }

    /**
     * Returns an array of data from imported CSV files.
     *
     * @param FileUpload $uploader
     * @param string     $separator
     * @param callable   $callback
     *
     * @return array
     */
    private function fetchData(FileUpload $uploader, $separator, callable $callback): array
    {
        $data = [];
        $files = $this->getFiles($uploader);
        $delimiter = $this->getDelimiter($separator);

        foreach ($files as $file) {
            $fp = fopen($file, 'rb');

            while (false !== ($row = fgetcsv($fp, 0, $delimiter))) {
                $data = $callback($data, $row);
            }
        }

        return $data;
    }

    /**
     * Returns the form ID for the template.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getFormId(Request $request): string
    {
        return 'tl_csv_import_'.$request->query->get('key');
    }

    /**
     * Returns the back button and redirect URL.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getBackUrl(Request $request): string
    {
        return str_replace('&key='.$request->query->get('key'), '', $request->getRequestUri());
    }

    /**
     * Returns an array of separators for the template.
     *
     * @param bool $allowLinebreak
     *
     * @return array<string,array>
     */
    private function getSeparators($allowLinebreak = false): array
    {
        $separators = [
            self::SEPARATOR_COMMA => [
                'delimiter' => ',',
                'value' => self::SEPARATOR_COMMA,
                'label' => $this->translator->trans('MSC.comma', [], 'contao_default'),
            ],
            self::SEPARATOR_SEMICOLON => [
                'delimiter' => ';',
                'value' => self::SEPARATOR_SEMICOLON,
                'label' => $this->translator->trans('MSC.semicolon', [], 'contao_default'),
            ],
            self::SEPARATOR_TABULATOR => [
                'delimiter' => "\t",
                'value' => self::SEPARATOR_TABULATOR,
                'label' => $this->translator->trans('MSC.tabulator', [], 'contao_default'),
            ],
        ];

        if ($allowLinebreak) {
            $separators[self::SEPARATOR_LINEBREAK] = [
                'delimiter' => "\n",
                'value' => self::SEPARATOR_LINEBREAK,
                'label' => $this->translator->trans('MSC.linebreak', [], 'contao_default'),
            ];
        }

        return $separators;
    }

    /**
     * Converts a separator name/constant into a delimiter character.
     *
     * @param string $separator
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    private function getDelimiter($separator): string
    {
        $separators = $this->getSeparators(true);

        if (!isset($separators[$separator])) {
            throw new \RuntimeException($this->translator->trans('MSC.separator.1', [], 'contao_default'));
        }

        return $separators[$separator]['delimiter'];
    }

    /**
     * Returns the uploaded files from a FileUpload instance.
     *
     * @param FileUpload $uploader
     *
     * @throws \RuntimeException
     *
     * @return array
     */
    private function getFiles(FileUpload $uploader): array
    {
        $files = $uploader->uploadTo('system/tmp');

        if (\count($files) < 1) {
            throw new \RuntimeException($this->translator->trans('ERR.all_fields', [], 'contao_default'));
        }

        foreach ($files as &$file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ('csv' !== $extension) {
                throw new \RuntimeException(
                    sprintf($this->translator->trans('ERR.filetype', [], 'contao_default'), $extension)
                );
            }

            $file = $this->projectDir.'/'.$file;
        }

        return $files;
    }
}
