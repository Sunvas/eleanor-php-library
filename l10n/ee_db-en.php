<?php
$ent=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

return[
	'lack_of_data'=>'There is not enough data to connect to the database.',

	/** Ошибка при подключении к БД */
	'connect'=>function($errno,$error,$db)use($ent){
		$error=$errno ? htmlspecialchars($error,$ent,Eleanor\CHARSET,false) : '';

		if($error)
			$error=<<<HTML
: <b>{$error}</b> (error #<b>{$errno}</b>)
HTML;
		else
			$error='.';

		return<<<HTML
Can't connect to database {$db}{$error}
HTML;
	},

	/** Ошибка в запросе */
	'query'=>function($errno,$error)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
Execution of SQL query failed: <b>{$error}</b> (error #{$errno})
HTML;
	},
];