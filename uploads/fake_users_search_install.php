<?php
/*
=============================================================================
Fake Users Search - Модуль поиска и удаления фэйковых пользователей
=============================================================================
Автор:  ПафНутиЙ 
URL:    http://pafnuty.name/
ICQ:    817233 
email:  pafnuty10@gmail.com
=============================================================================
*/

// Первым делом подключаем DLE_API как это ни странно, но в данном случаи это упрощает жизнь разработчика.
include('engine/api/api.class.php');

/**
 * Массив с конфигурацией установщика, ведь удобно иметь одинаковый код для разных установщиков разных модулей.
 * @var array
 */
$cfg = array(
	// Идентификатор модуля (для внедения в админпанель и назначение имени иконки с расширением .png)
	'moduleName'    => 'fake_users_search',

	// Название модуля - показывается как в установщике, так и в админке.
	'moduleTitle'   => 'Fake Users Search',

	// Описание модуля, для установщика и админки.
	'moduleDescr'   => 'Модуль поиска и удаления фэйковых пользователей',

	// Версия модуля, для установщика
	'moduleVersion' => '1.0',

	// Дата выпуска модуля, для установщика
	'moduleDate'    => '09.01.2014',

	// Версии DLE, поддержваемые модулем, для установщика
	'dleVersion'    => '9.x - 10.x',

	// ID групп, для которых доступно управление модулем в админке.
	'allowGroups'   => '1',

	// Массив с запросами, которые будут выполняться при установке
	'queries'       => array( 
		// "SELECT * FROM " . PREFIX . "_post WHERE id = '1'",
		// "SELECT * FROM " . PREFIX . "_usergroups"
	),

	// Устанавливать админку (true/false). Включает показ кнопки установки и удаления админки.
	'installAdmin'  => true,

	// Отображать шаги утановки модуля
	'steps'         => false

);

// Шаги установки модуля
$steps = <<<HTML
<div class="descr">
	<h2>Редактирование файлов</h2>
	<ol>
		<li>Выполнить установку админчасти модуля (кнопка ниже).</li>
	</ol>
</div>
HTML;


function installer() {
	global $config, $dle_api, $cfg, $steps;

	$output = '';

	$queries = (count($cfg['queries'])) ? true : false;

	if ($queries) {
		foreach ($cfg['queries'] as $qq) {
			$queriesTxt .= '<textarea readonly>' . $qq . '</textarea>';
		}
	}

	if ($cfg['installAdmin']) {
		$aq = $dle_api->db->super_query("SELECT name FROM " . PREFIX . "_admin_sections WHERE name = '{$cfg['moduleName']}'");

		$adminInstalled = ($aq['name'] == $cfg['moduleName']) ? true : false;

	}

	// Если через $_POST передаётся параметр install, производим инсталляцию, согласно параметрам
	if (!empty($_POST['install'])) {
		// Выводим результаты  установки модуля
		$output .= '<div class="descr"><ul>';

		if ($queries) {
			// Выполняем запросы из массива.
			foreach ($cfg['queries'] as $q) {
				$query[] = $dle_api->db->query($q);
			}

			$output .= '<li><b>Запросы выполнены!</b></li>';
		}

		// Установка админки (http://dle-news.ru/extras/online/include_admin.html)
		if ($cfg['installAdmin']) {

			$install_admin = $dle_api->install_admin_module($cfg['moduleName'], $cfg['moduleTitle'], $cfg['moduleDescr'], $cfg['moduleName'] . '.png', $cfg['allowGroups']);

			if ($install_admin) {
				$output .= '<li><b>Админчасть модуля установлена</b></li>';
			}
		}

		$output .= '<li><b>Установка завершена!</b></li></ul></div>';
		$output .= '<div class="alert">Не забудьте удалить файл установщика!</div>';
		if ($cfg['installAdmin'] && $install_admin) {
			$output .= '<p><a class="btn" href="/' . $config['admin_path'] . '?mod=' . $cfg['moduleName'] . '" target="_blank" title="Перейти к управлению модулем">Настройка модуля</a></p> <hr>';
		}

	}

	// Если через $_POST передаётся параметр remove, производим удаление админчасти модуля
	elseif (!empty($_POST['remove'])) {
		$remove_admin = $dle_api->uninstall_admin_module($cfg['moduleName']);
		$output .= '<div class="descr"><p><b>Админчасть модуля удалена</b></p></div>';
		$output .= '<div class="alert">Не забудьте удалить файл установщика!</div>';
	}

	// Если через $_POST ничего не передаётся, выводим форму для установки модуля
	else {
		// Выводим кнопку удаления  модуля
		if ($cfg['installAdmin'] && $adminInstalled) {
			$uninstallForm = <<<HTML
			<hr>
			<div class="form-field clearfix">
				<div class="lebel red">Удаление админчасти модуля</div>
				<div class="control">
					<form method="POST">            
						<input type="hidden" name="remove" value="1">
						<button class="btn active" type="submit">Удалить админчасть модуля</button>
					</form>
				</div>
			</div>
HTML;
		}
		// Выводим кнопку установки модуля с допзпросами
		if ($queries) {
			$installForm = <<<HTML
			<div class="form-field clearfix">
				<div class="lebel">Установка модуля</div>
				<div class="control">
					<form method="POST">            
						<input type="hidden" name="install" value="1">
						<button class="btn" type="submit">Установить модуль</button>
						<span id="wtq" class="btn">Какие запросы будут выполнены?</span>
					</form>
				</div>
			</div>
			<div class="queries clearfix hide">
				$queriesTxt
			</div>
HTML;
		}
		// Выводим кнопку установки админчасти модуля
		else {
			if (!$adminInstalled) {
				$installForm = <<<HTML
				<div class="form-field clearfix">
					<div class="lebel">Установка админчасти</div>
					<div class="control">
						<form method="POST">            
							<input type="hidden" name="install" value="1">
							<button class="btn" type="submit">Установить админчасть модуля</button>
						</form>
					</div>
				</div>
HTML;
			}
		}

		// Вывод
		if ($cfg['steps']) {
			$output .= $steps;
		}
		$output .= <<<HTML
			<p class="alert">Перед установкой модуля обязательно <a href="/{$config['admin_path']}?mod=dboption" target="_blank" title="Открыть инструменты работы с БД DLE в новом окне">сделайте бэкап БД</a>!</p>
			<div class="descr">
				<h2>Выполнение запросов в БД</h2>

				$installForm
				$uninstallForm
			</div>
HTML;


	}

	// Функция возвращает то, что должно быть выведено
	return $output;
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="<?=$config['charset']?>">
	<title><?=$cfg['moduleTitle']?></title>
	<meta name="viewport" content="width=device-width">
	<link href="http://fonts.googleapis.com/css?family=Ubuntu+Condensed&subset=latin,cyrillic" rel="stylesheet">
	<style>
		/*Общие стили*/
		html{background: #bdc3c7 url('data:image/png;base64,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') repeat;}
		body{width: 960px;padding: 20px;margin: 20px auto;font:normal 14px/18px Arial, Helvetica, sans-serif;background: #f1f1f1;box-shadow: 0 0 15px 0 rgba(0, 0, 0, 0.1);color: #34495e;}
		::-moz-selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		::selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		hr{margin: 18px 0;border: 0;border-top: 1px solid #f5f5f5;border-bottom: 1px solid #bdc3c7;}
		.preview  {display: block;margin: 20px auto 40px;max-width: 100%;}
		.descr  {font: normal 18px/24px "Trebuchet MS", Arial, Helvetica, sans-serif;color: #34495e;margin: 20px -20px;padding: 20px;background: #ecf0f1;-webkit-box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);text-shadow: 0 1px 0 #fff;}
		b{color: #2980b9;}
		.descr hr  {margin: 18px -20px;}
		.ta-center  {text-align: center;}
		.logo{margin: 0 auto;display: block;}
		a{color: #2980b9;}
		a:hover{text-decoration: none;color: #c0392b;}
		.btn, a.btn{line-height: 32px;font-size: 100%;margin: 0;vertical-align: baseline;*vertical-align: middle;cursor: pointer;*overflow: visible;background: #3498db;color: #ecf0f1;text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);border: 0;border-radius: 3px;padding: 0 15px;display: inline-block; text-decoration: none; border-bottom: solid 3px #2980b9;}
		.btn:hover, a.btn:hover, .btn.active{background: #e74c3c; border-bottom-color: #c0392b}
		article,
		.gray{color: #95a5a6;}
		.green{color: #16a085;}
		.red{color: #c0392b;}
		.blue{color: #3498db;}
		h1, h2, h3, h4, h1 b, h2 b, h3 b, h4 b{font-family: 'Ubuntu Condensed', sans-serif;font-weight: normal;}
		h3{margin: 0;}
		h1{line-height: 20px;line-height: 28px;}
		.clr{clear: both;height: 0;overflow: hidden;}
		li{margin-bottom: 20px;color: #2980b9;}
		li li{margin-bottom: 4px;margin-top: 4px;}
		li.div, li li, li h3{color: #34495e;}
		textarea{width: 100%;margin-bottom: 10px;vertical-align: top;-webkit-transition: height 0.2s;-moz-transition: height 0.2s;transition: height 0.2s;outline: none;display: block;color:#f39c12;padding: 5px 10px;font: normal 14px/20px Consolas,'Courier New',monospace;background-color: #2c3e50;white-space: pre;white-space: pre-wrap;word-break: break-all;word-wrap: break-word;text-shadow: none;border: none; border-left: solid 3px #f39c12; box-sizing: border-box; }
		textarea:focus{background: #bdc3c7;border-color: #2980b9; color:#2c3e50;}
		input[type="text"] {padding: 4px 10px;width: 250px;vertical-align: middle;height: 24px;line-height: 24px;border: solid 1px #95a5a6;display: inline-block;border-radius: 3px;}
		input[type="text"]:focus {border-color: #3498db;color:#2c3e50;outline: none;-webkit-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);-moz-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);}
		form {margin-bottom: 10px;}
		.checkbox { display:none; }
		.checkbox + label { cursor: pointer; margin-top: 4px; display: inline-block; }
		.checkbox + label span { display:inline-block; width:18px; height:18px; margin:-1px 4px 0 0; vertical-align:middle; background: #fff; cursor:pointer; border-radius: 4px; border: solid 2px #3498db; }
		.checkbox:checked + label span { background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAICAYAAADN5B7xAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAIJJREFUeNpi+f//PwMhIL6wjQVITQDi10xEKBYEUtuAOBuIGVmgAnkgyZfxVY1oilWB1BYgVgPiRqB8A8iGfCBuAGGggnokxS5A6iSyYpA4I8gPQEkQB6YYxH4FxJOAmAVZMVwD1ERkTTCAohgE4J6GSjTiU4xiA5LbG5AMwAAAAQYAgOM4GiRnHpIAAAAASUVORK5CYII=') no-repeat 50% 50%; border-color: #16a085; }
		.form-field {margin-bottom: 20px; margin-left: 20px;}
		.lebel {float: left;width: 300px;padding-right: 10px;line-height: 32px; text-align: right;}
		.control {margin-left: 320px;}
		.control input[type="text"] { width: 300px; margin-bottom: 2px; }
		.queries {padding: 10px 0;}
		.form-field-large .lebel {width: 100px;}
		.form-field-large .control {width: 622px;}
		.form-field-large .control input[type="text"] { width: 600px; margin-bottom: 2px; }
		.alert {background: #ebada7; color: #c0392b; text-shadow: none; padding: 20px; margin: 0 -20px; font-weight: bold; text-align: center;}
		.alert+.descr{margin-top: 0;}
		.clearfix:before, .clearfix:after {content: ""; display: table;}
		.clearfix:after {clear: both;}
		.clearfix {*zoom: 1;} 
		.hide {display: none;}
	</style>
</head>
<body>
	<header>
		<h1 class="ta-center"><big class="red"><?=$cfg['moduleTitle']?></big> v.<?=$cfg['moduleVersion']?> от <?=$cfg['moduleDate']?></h1>
		<hr>
	</header>
	<section>  

		<h2 class="gray ta-center">Мастер установки модуля <?=$cfg['moduleTitle']?> для DLE <?=$cfg['dleVersion']?></h2>

		<?php
			$output = installer();
			echo $output;
		?>

	</section> 	
	<div>Автор модуля: <a href="http://pafnuty.name/" target="_blank">ПафНутиЙ</a> <br> ICQ: 817233 <br> <a href="mailto:pafnuty10@gmail.com">pafnuty10@gmail.com</a></div>

	<!-- scripts -->
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/autosize.js/1.18.1/jquery.autosize.min.js"></script>
	<script>
		jQuery(document).ready(function ($) {
			$('textarea').autosize();
			$('textarea').click(function () {
				$(this).select();
			});
		});
		$(document).on('click', '#wtq', function () {
			$('.queries').slideToggle(400);
			$(this).toggleClass('active');
		})
	</script>
	<!-- scripts -->
	<!-- scripts -->
</body>
</html>
