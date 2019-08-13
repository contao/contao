<?php

namespace PHPSTORM_META {

	registerArgumentsSet('contao_backend_templates',
		'be_ace',
		'be_alerts',
		'be_confirm',
		'be_csv_import',
		'be_diff',
		'be_help',
		'be_login',
		'be_login_two_factor',
		'be_main',
		'be_maintenance',
		'be_maintenance_mode',
		'be_navigation',
		'be_pagination',
		'be_picker',
		'be_popup',
		'be_preview',
		'be_purge_data',
		'be_rebuild_index',
		'be_switch',
		'be_tinyMCE',
		'be_two_factor',
		'be_welcome',
		'be_widget',
		'be_widget_chk',
		'be_widget_pw',
		'be_widget_rdo',
		'be_wildcard',
    );

	expectedArguments(\Contao\BackendTemplate::__construct(), 0, argumentsSet('contao_backend_templates'));
	expectedArguments(\Contao\BackendTemplate::setName(), 0, argumentsSet('contao_backend_templates'));
	expectedReturnValues(\Contao\BackendTemplate::getName(), argumentsSet('contao_backend_templates'));

}
