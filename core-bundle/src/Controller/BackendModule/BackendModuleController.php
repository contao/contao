<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\BackendModule;

use Contao\Ajax;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Backend\BackendState;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Module;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Renders the backend module, delegates to callback or the DataContainer action if necessary.
 */
class BackendModuleController extends AbstractBackendModuleController
{
    public function __invoke(Request $request, PickerBuilderInterface $pickerBuilder): Response
    {
        $user = $this->get('contao.framework')->createInstance(BackendUser::class);

        $blnAccess = (isset($this->options['disablePermissionChecks']) && true === $this->options['disablePermissionChecks']) || $user->hasAccess($this->getName(), 'modules');

        // Check whether the current user has access to the current module
        if (!$blnAccess) {
            throw new AccessDeniedException(sprintf('Back end module "%s" is not allowed for user "%s".', $this->getName(), $user->username));
        }

        $arrTables = (array) ($this->options['tables'] ?? []);
        $strTable = $request->query->get('table') ?: ($arrTables[0] ?? null);
        $id = !$request->query->has('act') && $request->query->has('id')
            ? $request->query->get('id')
            : $this->get('session')->get('CURRENT_ID');

        // Store the current ID in the current session
        if ($id !== $this->get('session')->get('CURRENT_ID')) {
            $this->get('session')->set('CURRENT_ID', $id);
        }

        \define('CURRENT_ID', ($request->query->get('table') ? $id : $request->query->get('id')));

        $headline = $this->trans('MOD.'.$this->getName().'.0');

        // Add the module style sheet
        if (isset($this->options['stylesheet'])) {
            foreach ((array) $this->options['stylesheet'] as $stylesheet) {
                $GLOBALS['TL_CSS'][] = $stylesheet;
            }
        }

        // Add module javascript
        if (isset($this->options['javascript'])) {
            foreach ((array) $this->options['javascript'] as $javascript) {
                $GLOBALS['TL_JAVASCRIPT'][] = $javascript;
            }
        }

        $dc = null;

        // Create the data container object
        if ($strTable) {
            if (!\in_array($strTable, $arrTables, true)) {
                throw new AccessDeniedException(sprintf('Table "%s" is not allowed in module "%s".', $strTable, $this->getName()));
            }

            // Load the language and DCA file
            System::loadLanguageFile($strTable);
            Controller::loadDataContainer($strTable);

            // Include all excluded fields which are allowed for the current user
            if (\is_array($GLOBALS['TL_DCA'][$strTable]['fields'] ?? null)) {
                foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $k => $v) {
                    if (($v['exclude'] ?? null) && $user->hasAccess($strTable.'::'.$k, 'alexf')) {
                        if ('tl_user_group' === $strTable) {
                            $GLOBALS['TL_DCA'][$strTable]['fields'][$k]['orig_exclude'] = $GLOBALS['TL_DCA'][$strTable]['fields'][$k]['exclude'];
                        }

                        $GLOBALS['TL_DCA'][$strTable]['fields'][$k]['exclude'] = false;
                    }
                }
            }

            // Fabricate a new data container object
            if (!isset($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])) {
                System::log(sprintf('Missing data container for table "%s"', $strTable), __METHOD__, TL_ERROR);
                trigger_error('Could not create a data container object', E_USER_ERROR);
            }

            $dataContainer = 'DC_'.$GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];

            /** @var DataContainer $dc */
            $dc = new $dataContainer($strTable, $this->options);

            if (
                $request->query->has('picker')
                && null !== ($picker = $pickerBuilder->createFromData($request->query->get('picker')))
                && $dc instanceof DataContainer
            ) {
                $dc->initPicker($picker);
            }
        }

        // Wrap the existing headline
        $headline = sprintf('<span>%s</span>', $headline);

        // AJAX request
        if (Environment::get('isAjaxRequest') && $request->request->has('action')) {
            $objAjax = new Ajax($request->request->get('action'));

            // Call executePreActions again because we need its state
            try {
                $objAjax->executePreActions();
            } catch (ResponseException $e) {
            }

            $objAjax->executePostActions($dc);
        }

        // Trigger the module callback
        if (isset($this->options['callback']) && class_exists($this->options['callback'])) {
            trigger_deprecation('contao/core-bundle', '4.12', 'Using "callback" in BE_MOD is deprecated and will no longer work in Contao 5.0. Create a new controller with the "contao.backend_module" tag instead.');

            /** @var Module $objCallback */
            $objCallback = new $this->options['callback']($dc);

            return new Response($objCallback->generate());
        }

        // Custom action (if key is not defined in config.php the default action will be called)
        if (($key = $request->query->get('key')) && isset($this->options[$key])) {
            $objCallback = System::importStatic($this->options[$key][0]);
            $response = $objCallback->{$this->options[$key][1]}($dc);

            if ($response instanceof RedirectResponse) {
                throw new ResponseException($response);
            }

            if (!$response instanceof Response) {
                $response = new Response($response);
            }

            // Add the name of the parent element
            if ($request->query->has('table') && !empty($GLOBALS['TL_DCA'][$strTable]['config']['ptable']) && \in_array($request->query->get('table'), $arrTables, true) && $request->query->get('table') !== $arrTables[0]) {
                $objRow = $this->get('database_connection')->executeQuery(
                    sprintf(
                        'SELECT * FROM %s WHERE id=(SELECT pid FROM %s WHERE id=:id)',
                        $GLOBALS['TL_DCA'][$strTable]['config']['ptable'],
                        $strTable
                    ), ['id' => $request->query->get('id')]
                )->fetch(FetchMode::STANDARD_OBJECT)
                ;

                if ($objRow->title) {
                    $headline .= sprintf(' › <span>%s</span>', $objRow->title);
                } elseif ($objRow->name) {
                    $headline .= sprintf(' › <span>%s</span>', $objRow->name);
                }
            }

            // Add the name of the submodule
            $headline .= ' › <span>'.sprintf($GLOBALS['TL_LANG'][$strTable][$request->query->get('key')][1], $request->query->get('id')).'</span>';

            $this->get(BackendState::class)->setHeadline($headline);

            return $response;
        }

        // Default action
        if (\is_object($dc)) {
            $act = $request->query->get('act');

            if (!$act || 'paste' === $act || 'select' === $act) {
                $act = $dc instanceof \listable ? 'showAll' : 'edit';
            }

            switch ($act) {
                case 'delete':
                case 'show':
                case 'showAll':
                case 'undo':
                    if (!$dc instanceof \listable) {
                        System::log(sprintf('Data container %s is not listable', $strTable), __METHOD__, TL_ERROR);
                        trigger_error('The current data container is not listable', E_USER_ERROR);
                    }
                    break;

                case 'create':
                case 'cut':
                case 'cutAll':
                case 'copy':
                case 'copyAll':
                case 'move':
                case 'edit':
                    if (!$dc instanceof \editable) {
                        System::log(sprintf('Data container %s is not editable', $strTable), __METHOD__, TL_ERROR);
                        trigger_error('The current data container is not editable', E_USER_ERROR);
                    }
                    break;
            }

            // Add the name of the parent elements
            if ($strTable && \in_array($strTable, $arrTables, true) && $strTable !== $arrTables[0]) {
                $trail = [];

                $pid = $dc->id;
                $table = $strTable;
                $ptable = 'edit' !== $act ? ($GLOBALS['TL_DCA'][$strTable]['config']['ptable'] ?? null) : $strTable;

                while ($ptable && !\in_array($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? null, [5, 6], true)) {
                    $objRow = $this->get('database_connection')->executeQuery("SELECT * FROM {$ptable} WHERE id=:pid", ['pid' => $pid])->fetch(FetchMode::STANDARD_OBJECT);

                    // Add only parent tables to the trail
                    if ($table !== $ptable) {
                        // Add table name
                        if (isset($GLOBALS['TL_LANG']['MOD'][$table])) {
                            $trail[] = ' › <span>'.$GLOBALS['TL_LANG']['MOD'][$table].'</span>';
                        }

                        // Add object title or name
                        if ($objRow->title) {
                            $trail[] = ' › <span>'.$objRow->title.'</span>';
                        } elseif ($objRow->name) {
                            $trail[] = ' › <span>'.$objRow->name.'</span>';
                        } elseif ($objRow->headline) {
                            $trail[] = ' › <span>'.$objRow->headline.'</span>';
                        }
                    }

                    System::loadLanguageFile($ptable);
                    Controller::loadDataContainer($ptable);

                    // Next parent table
                    $pid = $objRow->pid;
                    $table = $ptable;
                    $ptable = $GLOBALS['TL_DCA'][$ptable]['config']['dynamicPtable'] ?? null ? $objRow->ptable : ($GLOBALS['TL_DCA'][$ptable]['config']['ptable'] ?? null);
                }

                // Add the last parent table
                if (isset($GLOBALS['TL_LANG']['MOD'][$table])) {
                    $trail[] = ' › <span>'.$GLOBALS['TL_LANG']['MOD'][$table].'</span>';
                }

                // Add the breadcrumb trail in reverse order
                foreach (array_reverse($trail) as $breadcrumb) {
                    $headline .= $breadcrumb;
                }
            }

            $do = $request->query->get('do');

            // Add the current action
            if ('editAll' === $act) {
                if (isset($GLOBALS['TL_LANG']['MSC']['all'][0])) {
                    $headline .= sprintf(' › <span>%s</span>', $GLOBALS['TL_LANG']['MSC']['all'][0]);
                }
            } elseif ('overrideAll' === $act) {
                if (isset($GLOBALS['TL_LANG']['MSC']['all_override'][0])) {
                    $headline .= sprintf(' › <span>%s</span>', $GLOBALS['TL_LANG']['MSC']['all_override'][0]);
                }
            } elseif ($request->query->get('id')) {
                if ('files' === $do || 'tpl_editor' === $do) {
                    // Handle new folders (see #7980)
                    if (false !== strpos($request->query->get('id'), '__new__')) {
                        $headline .= sprintf(' › <span>%s</span> › <span>%s</span>', \dirname($request->query->get('id')), $GLOBALS['TL_LANG'][$strTable]['new'][1]);
                    } else {
                        $headline .= sprintf(' › <span>%s</span>', $request->query->get('id'));
                    }
                } elseif (isset($GLOBALS['TL_LANG'][$strTable][$act])) {
                    if (\is_array($GLOBALS['TL_LANG'][$strTable][$act])) {
                        $headline .= ' › <span>'.sprintf($GLOBALS['TL_LANG'][$strTable][$act][1], $request->query->get('id')).'</span>';
                    } else {
                        $headline .= ' › <span>'.sprintf($GLOBALS['TL_LANG'][$strTable][$act], $request->query->get('id')).'</span>';
                    }
                }
            } elseif ($request->query->get('pid')) {
                if ('files' === $do || 'tpl_editor' === $do) {
                    if ('move' === $act) {
                        $headline .= sprintf(' › <span>%s</span> › <span>%s</span>', $request->query->get('pid'), $GLOBALS['TL_LANG'][$strTable]['move'][1]);
                    } else {
                        $headline .= sprintf(' › <span>%s</span>', $request->query->get('pid'));
                    }
                } elseif (isset($GLOBALS['TL_LANG'][$strTable][$act])) {
                    if (\is_array($GLOBALS['TL_LANG'][$strTable][$act])) {
                        $headline .= ' › <span>'.sprintf($GLOBALS['TL_LANG'][$strTable][$act][1], $request->query->get('pid')).'</span>';
                    } else {
                        $headline .= ' › <span>'.sprintf($GLOBALS['TL_LANG'][$strTable][$act], $request->query->get('pid')).'</span>';
                    }
                }
            }

            $this->get(BackendState::class)->setHeadline($headline);

            return new Response($dc->$act());
        }

        return new Response();
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;
        $services['database_connection'] = Connection::class;
        $services[BackendState::class] = BackendState::class;

        return $services;
    }

    private function getName(): string
    {
        return $this->options['type'];
    }

    private function trans(string $key, array $parameters = [])
    {
        return $this->get('translator')->trans($key, [], 'contao_default');
    }
}
