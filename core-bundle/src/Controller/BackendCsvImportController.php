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

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\FileUpload;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendCsvImportController
{
    public const SEPARATOR_COMMA = 'comma';
    public const SEPARATOR_LINEBREAK = 'linebreak';
    public const SEPARATOR_SEMICOLON = 'semicolon';
    public const SEPARATOR_TABULATOR = 'tabulator';

    private ContaoFramework $framework;
    private Connection $connection;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private string $projectDir;

    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework, Connection $connection, RequestStack $requestStack, TranslatorInterface $translator, string $projectDir)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->projectDir = $projectDir;
    }

    public function importListWizardAction(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            static fn (array $data, array $row): array => array_merge($data, $row),
            $dc->table,
            'listitems',
            (int) $dc->id,
            $this->translator->trans('MSC.lw_import.0', [], 'contao_default'),
            true
        );
    }

    public function importTableWizardAction(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            static function (array $data, array $row): array {
                $data[] = $row;

                return $data;
            },
            $dc->table,
            'tableitems',
            (int) $dc->id,
            $this->translator->trans('MSC.tw_import.0', [], 'contao_default')
        );
    }

    public function importOptionWizardAction(DataContainer $dc): Response
    {
        return $this->importFromTemplate(
            static function (array $data, array $row): array {
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

    private function importFromTemplate(callable $callback, string $table, string $field, int $id, string $submitLabel = null, bool $allowLinebreak = false): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new InternalServerErrorException('No request object given.');
        }

        $this->framework->initialize();

        $uploader = $this->framework->createInstance(FileUpload::class);
        $template = $this->prepareTemplate($request, $uploader, $allowLinebreak);

        if (null !== $submitLabel) {
            $template->submitLabel = $submitLabel;
        }

        if ($request->request->get('FORM_SUBMIT') === $this->getFormId($request)) {
            try {
                $data = $this->fetchData($uploader, (string) $request->request->get('separator', ''), $callback);
            } catch (\RuntimeException $e) {
                $message = $this->framework->getAdapter(Message::class);
                $message->addError($e->getMessage());

                return new RedirectResponse($request->getUri());
            }

            $this->connection->update($table, [$field => serialize($data)], ['id' => $id]);

            return new RedirectResponse($this->getBackUrl($request));
        }

        return new Response($template->parse());
    }

    private function prepareTemplate(Request $request, FileUpload $uploader, bool $allowLinebreak = false): BackendTemplate
    {
        $template = new BackendTemplate('be_csv_import');
        $config = $this->framework->getAdapter(Config::class);

        $template->formId = $this->getFormId($request);
        $template->backUrl = $this->getBackUrl($request);
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
     * Returns an array of data from the imported CSV files.
     *
     * @return array<string>
     */
    private function fetchData(FileUpload $uploader, string $separator, callable $callback): array
    {
        $data = [];
        $files = $this->getFiles($uploader);
        $delimiter = $this->getDelimiter($separator);

        foreach ($files as $file) {
            $fp = fopen($file, 'r');

            while (false !== ($row = fgetcsv($fp, 0, $delimiter))) {
                $data = $callback($data, $row);
            }
        }

        return $data;
    }

    private function getFormId(Request $request): string
    {
        return 'tl_csv_import_'.$request->query->get('key');
    }

    private function getBackUrl(Request $request): string
    {
        return str_replace('&key='.$request->query->get('key'), '', $request->getRequestUri());
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function getSeparators(bool $allowLinebreak = false): array
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

    private function getDelimiter(string $separator): string
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
     * @return array<string>
     */
    private function getFiles(FileUpload $uploader): array
    {
        $files = $uploader->uploadTo('system/tmp');

        if (\count($files) < 1) {
            throw new \RuntimeException($this->translator->trans('ERR.all_fields', [], 'contao_default'));
        }

        foreach ($files as &$file) {
            $extension = Path::getExtension($file, true);

            if ('csv' !== $extension) {
                throw new \RuntimeException(sprintf($this->translator->trans('ERR.filetype', [], 'contao_default'), $extension));
            }

            $file = Path::join($this->projectDir, $file);
        }

        return $files;
    }
}
