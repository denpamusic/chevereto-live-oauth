<?php
$route = function($handler) {
	// Must be an admin
	if(!CHV\Login::getUser()['is_admin']) {
		G\redirect();
	}

	/* Get table prefix */
	$table_prefix = G\get_app_setting('db_table_prefix');

	$fields = G\DB::queryFetchAll("DESCRIBE ${table_prefix}logins");

	foreach($fields as $v) {
		if($v['Field'] == 'login_type') {
			$login_type = $v['Type'];
			continue;
		}
	}

	$login_type = explode(',', trim(str_replace('enum(', '(', $login_type), '()'));
	if( !in_array("'live'", $login_type) ) {
		$login_type[] = "'live'";
	}

	$login_type = implode(',', $login_type);

	$sql_update = [
		/* Settings values */
		'live' => "INSERT INTO `${table_prefix}settings`(`setting_id`, `setting_name`, `setting_value`, `setting_default`, `setting_typeset`) VALUES (NULL, 'live', 0, 0, 'bool');",
		'live_client_id' => "INSERT INTO `${table_prefix}settings`(`setting_id`, `setting_name`, `setting_value`, `setting_default`, `setting_typeset`) VALUES (NULL, 'live_client_id', NULL, NULL, 'string');",
		'live_client_secret' => "INSERT INTO `${table_prefix}settings`(`setting_id`, `setting_name`, `setting_value`, `setting_default`, `setting_typeset`) VALUES (NULL, 'live_client_secret', NULL, NULL, 'string');",
		/* Support new social login type */
		"ALTER TABLE `${table_prefix}logins` CHANGE `login_type` `login_type` ENUM($login_type) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;",
	];

	$settings = G\DB::queryFetchAll("SELECT `setting_name` FROM `${table_prefix}settings`");

	// Remove existing settings
	foreach($settings as $v) {
		if( isset($sql_update[$v['setting_name']]) ) {
			unset($sql_update[$v['setting_name']]);
		}
	}

	$sql_update = join("\r\n", $sql_update);
	try {
		$db = G\DB::getInstance();
		$db->query($sql_update);
		$updated = $db->exec();
		if($updated) {
			echo 'Installation successfully finished!';
		}
		exit();
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};