<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use AppBundle\AppBundle;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreUnknownClasses([
        AppBundle::class,
        'Gmagick',
        'Swift_Attachment',
        'Swift_EmbeddedFile',
        'Swift_Mailer',
        'Swift_Message',
    ])
    ->ignoreErrorsOnPackage('contao-components/ace', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/chosen', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/colorbox', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/colorpicker', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/contao', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/datepicker', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/dropzone', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/handorgel', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/jquery', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/jquery-ui', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/mediabox', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/mootools', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/simplemodal', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/swipe', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/swiper', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/tablesort', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/tablesorter', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/tinymce4', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('contao-components/tristen-tablesort', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/deprecation-contracts', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/html-sanitizer', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/polyfill-intl-idn', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/polyfill-mbstring', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/rate-limiter', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/stopwatch', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('twig/string-extra', [ErrorType::UNUSED_DEPENDENCY])
;
