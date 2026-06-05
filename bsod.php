<?php
# Eleanor PHP Library © 2025 --> https://eleanor-cms.com/library
namespace Eleanor\Classes;
$l10n=new L10n('bsod');

return[
	/** CLI version
	 * @param string $error Error title
	 * @param int|string $code The error code by which the error is easily identified programmatically
	 * @param ?string $file The path to the file where the error occurred
	 * @param ?int $line Line number where the error occurred
	 * @param ?string $hint Hint to fix the error
	 * @param ?array $input Data that led to the failure */
	'cli'=>function($error,$code,$file,$line,$hint,$input)use($l10n){
		if($hint)
			$hint=$l10n['hint'].': '.$hint.PHP_EOL;

		$CLI=new CLI()
			->RED($l10n['error_occurred'])->reset(\PHP_EOL,\PHP_EOL)
			->YELLOW($error)->reset(\PHP_EOL,\PHP_EOL)
			->concat($l10n['file'].': ')->cyan($file)->reset("[")->purple($line)->reset("]",\PHP_EOL);

		if($input)
			$CLI->concat($l10n['input'].': ')->bold(\print_r($input,true))->reset(\PHP_EOL);

		if($hint)
			$CLI->concat($l10n['hint'].': ')->green($hint)->reset(PHP_EOL);

		return $CLI;
	},

	/** Text version
	 * @param string $error Error title
	 * @param int|string $code The error code by which the error is easily identified programmatically
	 * @param ?string $file The path to the file where the error occurred
	 * @param ?int $line Line number where the error occurred
	 * @param ?string $hint Hint to fix the error
	 * @param ?array $input Data that led to the failure */
	'text'=>function($error,$code,$file,$line,$hint,$input)use($l10n){
		if($line)
			$line="[{$line}]";

		if($file)
			$file=PHP_EOL.$l10n['file'].': '.$file.$line;

		if($hint)
			$hint=$l10n['hint'].': '.$hint;

		if($input)
			$input=str_repeat(PHP_EOL,2)
				.$l10n['input'].': '.print_r($input,true);

		return<<<TEXT
{$l10n['error_occurred']}
=======
{$error}{$file}
=======
{$hint}{$input}
TEXT;
	},

	/** JSON version
	 * @param string $error Error title
	 * @param int|string $code The error code by which the error is easily identified programmatically
	 * @param ?string $file The path to the file where the error occurred
	 * @param ?int $line Line number where the error occurred
	 * @param ?string $hint Hint to fix the error
	 * @param ?array $input Data that led to the failure */
	'json'=>function($error,$code,$file,$line,$hint,$input){
		$data=['ok'=>false]+\compact('error','code','file','line','hint','input');

		return json_encode($data,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	},

	/** HTML version
	 * @param string $error Error title
	 * @param int|string $code The error code by which the error is easily identified programmatically
	 * @param ?string $file The path to the file where the error occurred
	 * @param ?int $line Line number where the error occurred
	 * @param ?string $hint Hint to fix the error
	 * @param ?array $input Data that led to the failure */
	'html'=>function($error,$code,$file,$line,$hint,$input)use($l10n)
	{
		if($line)
			$line=<<<HTML
<span style="color:red" title="{$l10n['line']}">[{$line}]</span>
HTML;

		if($file)
			$file=<<<HTML
<code><span style="color:darkblue" title="{$l10n['file']}">{$file}</span>{$line}</code>
HTML;

		if($hint)
			$hint="<i title='{$l10n['hint']}'>{$hint}</i>";

		if($input)
		{
			$input=print_r($input,true);
			$input=htmlspecialchars($input,\ENT_QUOTES | \ENT_HTML5 | \ENT_SUBSTITUTE | \ENT_DISALLOWED, \Eleanor\CHARSET, false);
			$input=<<<HTML
<pre title="{$l10n['input']}"><code>{$input}</code></pre>
HTML;
		}

		$charset=\Eleanor\CHARSET;
		$title=strip_tags($error);
		$base=\Eleanor\SITEDIR;
		$year=idate('Y');
		$lang=L10n::$code;

		return<<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="{$charset}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="none">
<base href="{$base}">
<title>{$l10n['error_occurred']} :: {$title}</title>
<style>
body{color:black;background-color:whitesmoke;font-family:sans-serif;margin:0;padding:.5rem;}
header{font-size:1.6rem;padding:.5rem 0 .4rem}
main{margin:.5rem 0;padding:.75rem 0;border:darkgray dashed;border-width:2px 0 2px;font-size:1.1rem;overflow-y:auto}
code+code{display:block; margin-top:.5rem}
footer{position:fixed;bottom:10px;right:10px;font-size:.8rem;}
a{text-decoration:none}
</style>
</head>
<body>
<!-- {$code} -->
<header>{$l10n['error_occurred']}</header>
<main><code>{$error}</code>{$file}</main>
{$hint}{$input}
<footer>Powered by <a href="https://eleanor-cms.com/library" target="_blank">Eleanor PHP Library</a> &copy; {$year}</footer>
</body>
</html>
HTML;
	}
];