<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Doctrine\DBAL\Platforms\MySQLPlatform;

$GLOBALS['TL_DCA']['tl_module']['fields']['oauthClients'] = [
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_oauth_client.title',
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => ['type' => 'blob', 'length' => MySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false],
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_module']['palettes']['oauth_connect'] = '{title_legend},name,headline,type;{config_legend},oauthClients,autologin;{account_legend},reg_groups,reg_assignDir;{redirect_legend},jumpTo,redirectBack;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';
