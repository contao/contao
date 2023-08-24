<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Controller\Page;

use Contao\Config;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Contao\UnusedArgumentsException;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractContentCompositionController extends AbstractController
{
    protected function renderPage(PageModel $pageModel, ResponseContext $responseContext = null): Response
    {
        /** @var PageModel $objPage */
        global $objPage;

        $objPage = $pageModel;
        $objPage->loadDetails();

        // Set the admin e-mail address
        if ($objPage->adminEmail) {
            list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail($objPage->adminEmail);
        } else {
            list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail(Config::get('adminEmail'));
        }

        // Backup some globals (see #7659)
        $arrBackup = array(
            $GLOBALS['TL_HEAD'] ?? array(),
            $GLOBALS['TL_BODY'] ?? array(),
            $GLOBALS['TL_MOOTOOLS'] ?? array(),
            $GLOBALS['TL_JQUERY'] ?? array(),
            $GLOBALS['TL_USER_CSS'] ?? array(),
            $GLOBALS['TL_FRAMEWORK_CSS'] ?? array(),
        );

        try {
            return (new PageRegular($responseContext))->getResponse($objPage, true);
        } // Render the error page (see #5570)
        catch (UnusedArgumentsException $e) {
            // Restore the globals (see #7659)
            list(
                $GLOBALS['TL_HEAD'],
                $GLOBALS['TL_BODY'],
                $GLOBALS['TL_MOOTOOLS'],
                $GLOBALS['TL_JQUERY'],
                $GLOBALS['TL_USER_CSS'],
                $GLOBALS['TL_FRAMEWORK_CSS']
                ) = $arrBackup;

            throw $e;
        }
    }
}
