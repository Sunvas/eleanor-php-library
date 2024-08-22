<?php
$ent=ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE | ENT_DISALLOWED;

return[
	/** Ошибка при подключении к БД */
	'connect'=>function($error,$errno,$db)use($ent){
		$error=$errno ? htmlspecialchars($error,$ent,Eleanor\CHARSET,false) : '';

		if($error)
			$error=<<<HTML
: <b>{$error}</b> (error #<b>{$errno}</b>)
HTML;
		else
			$error='.';

		return<<<HTML
Невозможно подключиться к базе данных {$db}{$error}
HTML;
	},

	/** Ошибка в запросе */
	'query'=>function($error,$errno)use($ent){
		$error=htmlspecialchars($error,$ent,Eleanor\CHARSET,false);

		return<<<HTML
SQL запрос выполнился неудачно: <b>{$error}</b> (error #{$errno})
HTML;
	},
];