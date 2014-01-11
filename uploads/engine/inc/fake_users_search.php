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

if (!defined('DATALIFEENGINE') OR !defined('LOGGED_IN')) {
	die("Hacking attempt!");
}
if($member_id['user_group'] !='1') {
	msg("error", $lang['index_denied'], $lang['index_denied']);
}

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

/**
 * Основная функция модуля
 * @return string - результат работы модуля.
 */
function searchFakeUser() {
	global $config, $dle_api, $cfg, $db, $user_group, $member_id, $_TIME, $_IP;
	// Формируем разные переменные из реквеста....
	// if(($_REQUEST['user_hash'] == "" || $_REQUEST['user_hash'] != $dle_login_hash) ) {
	// 	die( "Hacking attempt! User not found" );
	// }
	$fromregdate = (!empty($_REQUEST['fromregdate'])) ? intval($_REQUEST['fromregdate']) : false;
	$fromlastdate = (!empty($_REQUEST['fromlastdate'])) ? intval($_REQUEST['fromlastdate']) : false;
	if ($fromregdate && ($fromlastdate > $fromregdate * 30)) {
		$fromlastdate = $fromregdate * 30 - 1;
	}
	$lastdate_interval = (!empty($_REQUEST['lastdate_interval'])) ? intval($_REQUEST['lastdate_interval']) : false;
	$start_from = (intval($_REQUEST['start_from']) > 0) ? intval($_REQUEST['start_from']) : 0;
	$sort = $db->safesql(trim(htmlspecialchars(strip_tags($_REQUEST['sort']))));
	$sort = ($sort) ? $sort : 'reg_date';
	$sort_dir = $db->safesql(trim(htmlspecialchars(strip_tags($_REQUEST['sort_dir']))));
	$sort_dir = ($sort_dir == 'DESC') ? 'DESC' : 'ASC';
	$comm_num = (intval($_REQUEST['comm_num']) > 1) ? intval($_REQUEST['comm_num']) : 1;
	$news_num = (intval($_REQUEST['news_num']) > 1) ? intval($_REQUEST['news_num']) : 1;
	$user_per_page = (!empty($_REQUEST['user_per_page'])) ? intval($_REQUEST['user_per_page']) : 50;

	$show_user_id = ($_REQUEST['show_user_id'] == 'yes') ? true : false;
	$show_email = ($_REQUEST['show_email'] == 'yes') ? true : false;
	$show_news_num = ($_REQUEST['show_news_num'] == 'yes') ? true : false;
	$show_comm_num = ($_REQUEST['show_comm_num'] == 'yes') ? true : false;
	$show_user_group = ($_REQUEST['show_user_group'] == 'yes') ? true : false;
	$show_lastdate = ($_REQUEST['show_lastdate'] == 'yes') ? true : false;
	$show_reg_date = ($_REQUEST['show_reg_date'] == 'yes') ? true : false;
	$show_banned = ($_REQUEST['show_banned'] == 'yes') ? true : false;
	$show_info = ($_REQUEST['show_info'] == 'yes') ? true : false;
	$show_signature = ($_REQUEST['show_signature'] == 'yes') ? true : false;
	$show_foto = ($_REQUEST['show_foto'] == 'yes') ? true : false;
	$show_fullname = ($_REQUEST['show_fullname'] == 'yes') ? true : false;
	$show_land = ($_REQUEST['show_land'] == 'yes') ? true : false;
	$show_icq = ($_REQUEST['show_icq'] == 'yes') ? true : false;
	$show_pm = ($_REQUEST['show_pm'] == 'yes') ? true : false;
	$show_logged_ip = ($_REQUEST['show_logged_ip'] == 'yes') ? true : false;

	if ($_REQUEST['odnodnevka'] == "yes") {
		$odnodnevka_checked = 'checked';
		$comm_num = 1;
		$news_num = 1;
	}
	if ($_REQUEST['ignore_comm_num'] == 'yes') {
		$ignore_comm_num_checked = 'checked';
		$comm_num_disabled = 'disabled';
	}
	if ($_REQUEST['ignore_news_num'] == 'yes') {
		$ignore_news_num_checked = 'checked';
		$news_num_disabled = 'disabled';
	}

	$not_null_info_checked = ($_REQUEST['not_null_info'] == 'yes') ? 'checked' : false;
	$not_null_info_link_checked = ($_REQUEST['not_null_info_link'] == 'yes') ? 'checked' : false;
	$not_null_signature_checked = ($_REQUEST['not_null_signature'] == 'yes') ? 'checked' : false;
	$not_null_signature_link_checked = ($_REQUEST['not_null_signature_link'] == 'yes') ? 'checked' : false;

	$show_user_id_checked = ($show_user_id) ? 'checked' : false;
	$show_email_checked = ($show_email) ? 'checked' : false;
	$show_news_num_checked = ($show_news_num) ? 'checked' : false;
	$show_comm_num_checked = ($show_comm_num) ? 'checked' : false;
	$show_user_group_checked = ($show_user_group) ? 'checked' : false;
	$show_lastdate_checked = ($show_lastdate) ? 'checked' : false;
	$show_reg_date_checked = ($show_reg_date) ? 'checked' : false;
	$show_banned_checked = ($show_banned) ? 'checked' : false;
	$show_info_checked = ($show_info) ? 'checked' : false;
	$show_signature_checked = ($show_signature) ? 'checked' : false;
	$show_foto_checked = ($show_foto) ? 'checked' : false;
	$show_fullname_checked = ($show_fullname) ? 'checked' : false;
	$show_land_checked = ($show_land) ? 'checked' : false;
	$show_icq_checked = ($show_icq) ? 'checked' : false;
	$show_pm_checked = ($show_pm) ? 'checked' : false;
	$show_logged_ip_checked = ($show_logged_ip) ? 'checked' : false;

	if (!$_REQUEST['find_users']) {
		$show_user_id_checked = 'checked';
		$show_lastdate_checked = 'checked';
		$show_reg_date_checked = 'checked';
		$show_foto_checked = 'checked';
	}
	// Формируем разные переменные из реквеста

	$output = <<<HTML
	<form id="searchusers" method="POST" action="{$_SERVER["PHP_SELF"]}?mod={$cfg['moduleName']}">  
		<input type="hidden" name="find_users" value="1">          
		<input type="hidden" name="start_from" id="start_from" value="{$start_from}">    
		<input type="hidden" name="sort" id="sort" value="{$sort}">    
		<input type="hidden" name="sort_dir" id="sort_dir" value="{$sort_dir}"> 
		<input type="hidden" name="user_hash" value="{$dle_login_hash}">   

		<div class="descr">
			<div class="form-field clearfix">
				<div class="lebel">&nbsp;</div>
				<div class="control">
					<input type="checkbox" value="yes" name="odnodnevka" id="odnodnevka" class="checkbox" {$odnodnevka_checked}><label for="odnodnevka"><span></span> Искать однодневок</label> <span class="ttp mini" title="Пользователи, дата регистрации которых совпадает с датой последнего посещения, при этом они не оставили ни одного комментария или новости. В 99,99% это боты, можно смело удалять таких.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">"Стаж" пользователя</div>
				<div class="control">
					<input type="text" name="fromregdate" value="{$fromregdate}" style="width: 50px;" class="not-oneday"> <small>(мес)</small> <span class="ttp mini" title="Указывается количество месяцев, прошедших с момента регистрации пользователя. Если оставить поле пустым - будут отобраны все пользователи с момента создания сайта.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Не искать моложе</div>
				<div class="control">
					<input type="text" name="fromlastdate" value="{$fromlastdate}" style="width: 50px;" class="not-oneday"> <small>(дни)</small> <span class="ttp mini" title="Указывается количество дней. Настройка нужна, чтобы исключить из поиска недавно зарегистрированных пользователей.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Интервал между регистрацией и последним посещением</div>
				<div class="control">
					<input type="text" name="lastdate_interval" value="{$lastdate_interval}" style="width: 50px;" class="not-oneday"> <small>(дни)</small> <span class="ttp mini" title="Указывается временной интервал между регистрацией и последним посещением (в днях). Иногда боты ещё полдня висят на сайте, или заходят на сайт через некоторое время после регистрации.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Комментариев меньше чем</div>
				<div class="control">
					<input type="text" name="comm_num" id="comm_num_inp" value="{$comm_num}" style="width: 50px;" class="not-oneday" {$comm_num_disabled}> 
					<input type="checkbox" value="yes" name="ignore_comm_num" id="ignore_comm_num" class="checkbox inp-text-checkbox not-oneday" data-inp-text="#comm_num_inp" {$ignore_comm_num_checked}><label for="ignore_comm_num"><span></span> Не учитывать комментарии</label> <span class="ttp mini" title="Указывается максимальное количество новостей пользователя, но на один меньше. Т.е. установка числа 10 отберёт всех пользователей, имеющих не более 9 новостей. Так же можно не учитывать этот параметр выставив галочку.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Новостей меньше чем</div>
				<div class="control">
					<input type="text" name="news_num" id="news_num_inp" value="{$news_num}" style="width: 50px;" class="not-oneday" {$news_num_disabled}> 
					<input type="checkbox" value="yes" name="ignore_news_num" id="ignore_news_num" class="checkbox inp-text-checkbox not-oneday" data-inp-text="#news_num_inp" {$ignore_news_num_checked}><label for="ignore_news_num"><span></span> Не учитывать новости</label> <span class="ttp mini" title="Указывается максимальное количество комментариев пользователя, но на один меньше. Т.е. установка числа 10 отберёт всех пользователей, имеющих не более 9 комментариев. Так же можно не учитывать этот параметр выставив галочку.">?</span>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Не пустая информация "О себе"</div>
				<div class="control">
					<input type="checkbox" value="yes" name="not_null_info" id="not_null_info" class="checkbox parent-checkbox not-oneday" data-child-checkbox="#not_null_info_link" {$not_null_info_checked}><label for="not_null_info"><span></span> Да</label> <input type="checkbox" value="yes" name="not_null_info_link" id="not_null_info_link" class="checkbox child-checkbox not-oneday" data-parent-checkbox="#not_null_info" {$not_null_info_link_checked}><label for="not_null_info_link"><span></span> Только если есть ссылки</label>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Не пустая подпись</div>
				<div class="control">
					<input type="checkbox" value="yes" name="not_null_signature" id="not_null_signature" class="checkbox parent-checkbox not-oneday" data-child-checkbox="#not_null_signature_link" {$not_null_signature_checked}><label for="not_null_signature"><span></span> Да</label> <input type="checkbox" value="yes" name="not_null_signature_link" id="not_null_signature_link" class="checkbox child-checkbox not-oneday" data-parent-checkbox="#not_null_signature" {$not_null_signature_link_checked}><label for="not_null_signature_link"><span></span> Только если есть ссылки</label>
				</div>
			</div>
			<div class="form-field clearfix">
				<div class="lebel">Пользователей на страницу</div>
				<div class="control">
					<input type="text" name="user_per_page" value="{$user_per_page}" style="width: 50px;"> <span class="ttp mini" title="Указывается количество пользователей, выводимых а одной странице.">?</span>
				</div>
			</div>

			<div class="form-field clearfix">
				<div class="lebel">
					Показывать поля <br>
					<input type="checkbox" id="show_all_rows" class="checkbox main-checkbox" data-checkboxes=".show-rows"><label title="Отметить всё" for="show_all_rows"><span></span>Выбрать всё</label>
				</div>
				<div class="control">
					<div class="fleft w33p">
						<input type="checkbox" name="show_user_id" id="show_user_id" value="yes" class="checkbox show-rows" {$show_user_id_checked}>
						<label for="show_user_id"><span></span>ID пользователя</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_foto" id="show_foto" value="yes" class="checkbox show-rows" {$show_foto_checked}>
						<label for="show_foto"><span></span>Фото</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_email" id="show_email" value="yes" class="checkbox show-rows" {$show_email_checked}>
						<label for="show_email"><span></span>Email</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_fullname" id="show_fullname" value="yes" class="checkbox show-rows" {$show_fullname_checked}>
						<label for="show_fullname"><span></span>Полное имя</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_reg_date" id="show_reg_date" value="yes" class="checkbox show-rows" {$show_reg_date_checked}>
						<label for="show_reg_date"><span></span>Дата регистрации</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_lastdate" id="show_lastdate" value="yes" class="checkbox show-rows" {$show_lastdate_checked}>
						<label for="show_lastdate"><span></span>Дата последнего посещения</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_logged_ip" id="show_logged_ip" value="yes" class="checkbox show-rows" {$show_logged_ip_checked}>
						<label for="show_logged_ip"><span></span>IP адрес</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_banned" id="show_banned" value="yes" class="checkbox show-rows" {$show_banned_checked}>
						<label for="show_banned"><span></span>Показывать бан</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_news_num" id="show_news_num" value="yes" class="checkbox show-rows" {$show_news_num_checked}>
						<label for="show_news_num"><span></span>Кол-во новостей</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_comm_num" id="show_comm_num" value="yes" class="checkbox show-rows" {$show_comm_num_checked}>
						<label for="show_comm_num"><span></span>Кол-во комментариев</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_info" id="show_info" value="yes" class="checkbox show-rows" {$show_info_checked}>
						<label for="show_info"><span></span>Информация "Обо мне"</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_signature" id="show_signature" value="yes" class="checkbox show-rows" {$show_signature_checked}>
						<label for="show_signature"><span></span>Подпись</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_land" id="show_land" value="yes" class="checkbox show-rows" {$show_land_checked}>
						<label for="show_land"><span></span>Место жительства</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_icq" id="show_icq" value="yes" class="checkbox show-rows" {$show_icq_checked}>
						<label for="show_icq"><span></span>номер ICQ</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_pm" id="show_pm" value="yes" class="checkbox show-rows" {$show_pm_checked}>
						<label for="show_pm"><span></span>Кол-во ПМ</label>
					</div>
					<div class="fleft w33p">
						<input type="checkbox" name="show_user_group" id="show_user_group" value="yes" class="checkbox show-rows" {$show_user_group_checked}>
						<label for="show_user_group"><span></span>Группа</label>
					</div>
				</div>
			</div>
			<hr>
			<div class="form-field clearfix">
				<div class="lebel">&nbsp;</div>
				<div class="control">
						<input class="btn" type="submit" value="Найти пользователей">
				</div>
			</div>
		</div> <!-- .descr -->
	</form>

HTML;
	if ($_REQUEST['do_mass'] == 'yes') {
		$selected_users = $_REQUEST['selected_users'];
		$done = 0;
		$errText = array();
		$err = false;
		foreach ($selected_users as $id) {
			$id = intval($id);
			if ($id > 1) {
				$row = $db->super_query("SELECT user_id, user_group, name, foto FROM " . USERPREFIX . "_users WHERE user_id='$id'");
			}
			$rowId = ($row['user_id']) ? $row['user_id'] : false;
			if (!$rowId) {
				$errText [] = '<p class="red">Указанного пользователя (ID = ' . $id . ') не существует!</p>';
				$err = true;
			}

			if ($rowId == 1) {
				$errText [] = '<p class="red">Действия над админом недопустимы!</p>';
				$err = true;
			}

			if ($member_id['user_group'] != 1 AND $row['user_group'] == 1) {
				die('Не хватает полномочий!');
			}
			if (!$err) {
				if ($_REQUEST['do_mass_delete'] == 'yes') {
					if ($config['version_id'] > 9.3) {
						$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '41', '{$row['name']}')");
					}

					$db->query("DELETE FROM " . USERPREFIX . "_pm WHERE user_from = '{$row['name']}' AND folder = 'outbox'");
					$db->query("DELETE FROM " . USERPREFIX . "_users WHERE user_id='$rowId'");
					$db->query("DELETE FROM " . USERPREFIX . "_banned WHERE users_id='$rowId'");
					$db->query("DELETE FROM " . USERPREFIX . "_pm WHERE user='$rowId'");

					@unlink(ROOT_DIR . "/uploads/fotos/" . $row['foto']);
				}
				elseif ($_REQUEST['do_mass_clear_signature'] == 'yes') {

					$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '64', '{$row['name']}')");

					$db->query("UPDATE " . USERPREFIX . "_users SET signature = '' WHERE user_id='$rowId'");
				}
				elseif ($_REQUEST['do_mass_clear_info'] == 'yes') {

					$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '64', '{$row['name']}')");

					$db->query("UPDATE " . USERPREFIX . "_users SET info = '' WHERE user_id='$rowId'");
				}

				$done++;
			}
		}
		$errText = implode('', $errText);

		clear_cache();
		@unlink(ENGINE_DIR . '/cache/system/banned.php');
		$selected_users_count = count($selected_users);

		if (($_REQUEST['do_mass_delete'] = 'yes')) {
			$_text3 = 'удален';
			$_text4 = 'удаления';
		}
		if (($_REQUEST['do_mass_clear_signature'] = 'yes') || ($_REQUEST['do_mass_clear_info'] = 'yes')) {
			$_text3 = 'обработан';
			$_text4 = 'обработки';
		}

		if ($done > 0 && ($selected_users_count == $done)) {
			$doneText = '<h2>Все выбранные Вами пользователи успешно ' . $_text3 . 'ы!</h2>';
		}
		elseif ($done < 1) {
			$doneText = '<h2 class="red">Пользователи не были ' . $_text3 . 'ы!</h2>';
			$doneText .= '<p>В процессе ' . $_text4 . ' возникли следующие ошибки:</p>' . $errText;
		}
		else {
			$doneText = '<h2 class="red">' . wordSpan($done, $_text3 . '||о|о') . ' <b>' . $selected_users_count . '</b> из ' . $done . ' ' . wordSpan($countSelected, 'пользовател|ь|я|ей') . '</h2>';
			$doneText .= '<p>В процессе ' . $_text4 . ' возникли следующие ошибки:</p>' . $errText;
		}

		$output = <<<HTML
		<div class="descr">
			{$doneText}
			<a class="btn" href="{$config['admin_path']}?mod={$cfg['moduleName']}">Вернуться назад</a>
		</div>
HTML;

	}
	if (!empty($_POST['do_action'])) {

		$selected = $_REQUEST['selected'];
		$countSelected = count($selected);

		if ($countSelected > 0) {
			$hiddnField = false;
			foreach ($selected as $userid) {
				$userid = intval($userid);
				$hiddnField .= '<input type="hidden" name="selected_users[]" value="' . $userid . '"> ';
			}
			if ($_POST['do_action'] == 'del') {
				$_text1 = wordSpan($countSelected, 'удален||о|о');
				$_text2 = 'Удалить';
				$_do_mass = '<input type="hidden" name="do_mass_delete" value="yes">';
			}

			if ($_POST['do_action'] == 'clear_signature') {
				$_text1 = wordSpan($countSelected, 'обработан||о|о');
				$_text2 = 'Обработать';
				$_do_mass = '<input type="hidden" name="do_mass_clear_signature" value="yes">';
			}
			if ($_POST['do_action'] == 'clear_info') {
				$_text1 = wordSpan($countSelected, 'обработан||о|о');
				$_text2 = 'Обработать';
				$_do_mass = '<input type="hidden" name="do_mass_clear_info" value="yes">';
			}

			$resultHeaderText = '<p class="green">Будет ' . $_text1 . ' <b>' . $countSelected . '</b> ' . wordSpan($countSelected, 'пользовател|ь|я|ей') . '.</p>';
			$userCountText = wordSpan($countSelected, 'пользовател|я|ей|ей');
			$resultHeaderText .= <<<HTML
			<form id="del_users" class="d-inline" method="POST" action="{$_SERVER["PHP_SELF"]}?mod={$cfg['moduleName']}">
				<input type="hidden" name="do_mass" value="yes">
				{$_do_mass}
				<input type="hidden" name="user_hash" value="{$dle_login_hash}"> 
				{$hiddnField}
				<input class="btn active" type="submit" value="{$_text2} {$userCountText}">
			</form>
HTML;
		}
		else {
			$resultHeaderText = '<p class="red">Не выбрано ни одного пользователя</p>';
		}
		$output = <<<HTML
		<div class="descr">
			<div class="form-field clearfix">
				<div class="lebel">&nbsp;</div>
				<div class="control">
					{$resultHeaderText}
					<a class="btn" href="{$config['admin_path']}?mod={$cfg['moduleName']}">Вернуться назад</a>
				</div>
			</div>
		</div>
HTML;

	}
	elseif (!empty($_POST['find_users'])) {
		$where = array();
		$order_by = array();
		if ($sort && $sort_dir) {
			$order_by[] = $sort . ' ' . $sort_dir;
		}
		// Формируем переменные для запроса и формирования таблицы пользователей
		$fields = array('user_id', 'name', 'reg_date', 'lastdate', 'comm_num', 'news_num');

		if ($show_user_id) {
			$show_user_id_th = '<th id="user_id" class="none">ID</th>';
			$show_user_id_td = '<td>' . $fUser['user_id'] . '</td>';
		}
		if ($show_foto) {
			$fields[] = 'foto';
			$show_foto_th = '<th id="foto" class="none">Фото</th>';
		}
		if ($show_email) {
			$fields[] = 'email';
			$show_email_th = '<th id="email" class="none">Email</th>';
		}
		if ($show_news_num) {
			$show_news_num_th = '<th id="news_num" class="none">Кол-во новостей</th>';
		}
		if ($show_comm_num) {
			$show_comm_num_th = '<th id="comm_num" class="none">Кол-во комментариев</th>';
		}
		if ($show_user_group) {
			$fields[] = 'user_group';
			$show_user_group_th = '<th id="user_group" class="none">Группа</th>';
		}
		if ($show_lastdate) {
			$show_lastdate_th = '<th id="lastdate" class="none">Дата посл. посещения</th>';
		}
		if ($show_reg_date) {
			$show_reg_date_th = '<th id="reg_date" class="none">Дата регистрации</th>';
		}
		if ($show_banned) {
			$fields[] = 'banned';
			$show_banned_th = '<th id="banned" class="none">Забанен</th>';
		}
		if ($show_info) {
			$fields[] = 'info';
			$show_info_th = '<th id="info" class="none">Информация о себе</th>';
		}
		if ($show_signature) {
			$fields[] = 'signature';
			$show_signature_th = '<th id="signature" class="none">Подпись</th>';
		}
		if ($show_fullname) {
			$fields[] = 'fullname';
			$show_fullname_th = '<th id="fullname" class="none">Полное имя</th>';
		}
		if ($show_land) {
			$fields[] = 'land';
			$show_land_th = '<th id="land" class="none">Место жительства</th>';
		}
		if ($show_icq) {
			$fields[] = 'icq';
			$show_icq_th = '<th id="icq" class="none">ICQ</th>';
		}
		if ($show_pm) {
			$fields[] = 'pm_all';
			$fields[] = 'pm_unread';
			$show_pm_th = '<th id="pm_all" class="none">ПМ</th>';
		}
		if ($show_logged_ip) {
			$fields[] = 'logged_ip';
			$show_logged_ip_th = '<th id="logged_ip" class="none">IP адрес</th>';
		}
		// Формируем переменные для запроса и формирования таблицы пользователей


		if ($fromregdate > 0) {
			$fromregdate = time() - 60 * 60 * 24 * 30 * $fromregdate;
			$where[] = 'reg_date < \'' . $fromregdate . '\'';
		}
		if (!empty($_POST['odnodnevka'])) {
			$where[] = 'reg_date=lastdate AND comm_num < 1 AND news_num < 1';

		}
		elseif (empty($_POST['odnodnevka'])) {
			if ($fromlastdate > 0) {
				$fromlastdate = time() - 60 * 60 * 24 * $fromlastdate;
				$where[] = 'reg_date > \'' . $fromlastdate . '\'';
			}

			if ($lastdate_interval > 0) {
				$where[] = 'lastdate < reg_date + \'' . $lastdate_interval . '\'';
			}
			if ($comm_num > 0 && !$ignore_comm_num_checked) {
				$where[] = 'comm_num < ' . $comm_num;
			}
			if ($news_num > 0 && !$ignore_news_num_checked) {
				$where[] = 'news_num < ' . $news_num;
			}

			if ($not_null_info_checked) {
				$where[] = 'info <> \'\'';

				if ($not_null_info_link_checked) {
					$where[] = 'info Regexp \'(http|https|ftp)\'';
				}
			}
			if ($not_null_signature_checked) {
				$where[] = 'signature <> \'\'';

				if ($not_null_signature_link_checked) {
					$where[] = 'signature REGEXP \'http\'';
				}
			}
		}

		$where = implode(' AND ', $where);
		if (!$where) $where = '1';

		$order_by = implode(', ', $order_by);
		if (!$order_by) $order_by = 'reg_date ASC';

		$fields = implode(', ', $fields);

		$query = "SELECT " . $fields . " FROM " . USERPREFIX . "_users WHERE " . $where . " AND user_id != '1' ORDER BY " . $order_by . " LIMIT " . $start_from . ", " . $user_per_page;
		$query_count = "SELECT COUNT(*) as count FROM " . USERPREFIX . "_users WHERE " . $where . " AND user_id != '1'";
		$result_count = $db->super_query($query_count);

		$all_count_user = $result_count['count'];

		$fUsers = $db->super_query($query, true);
		if ($all_count_user > 0) {
			$output .= '<h2 id="result-header">По заданным критериям ' . wordSpan($all_count_user, 'найден||о|о') . ' <b>' . $all_count_user . '</b> ' . wordSpan($all_count_user, 'пользовател|ь|я|ей') . '</h2><hr />';
		}
		else {
			$output .= '<h2 id="result-header" class="red">По заданным критериям не найдено ни одного пользователя. Попробуйте составить запрос иначе.</h2><hr />';
		}
		$output .= <<<HTML
		<form id="editusers" method="POST" action="{$_SERVER["PHP_SELF"]}?mod={$cfg['moduleName']}">  
		<input type="hidden" name="user_hash" value="{$dle_login_hash}"> 
HTML;
// Формируем таблицу из переменных
		$output .= '<table><thead><tr>';
		$output .= $show_user_id_th . $show_foto_th . $show_email_th . '<th id="name" class="none">Имя</th>' . $show_fullname_th . $show_reg_date_th . $show_lastdate_th . $show_logged_ip_th . $show_banned_th . $show_news_num_th . $show_comm_num_th . $show_info_th . $show_signature_th . $show_land_th . $show_icq_th . $show_pm_th . $show_user_group_th;
		$output .= '<th><input type="checkbox" id="select_all_checkbox" class="checkbox main-checkbox" data-checkboxes=".connected-checkbox"><label title="Отметить всё" for="select_all_checkbox"><span></span></label></th></tr></thead>';

		$i = $start_from;
		foreach ($fUsers as $fUser) {
			$i++;
			// Формируем ячейки таблицы
			if ($show_user_id) {
				$show_user_id_td = '<td>' . $fUser['user_id'] . '</td>';
			}
			if ($show_foto) {
				// Определяем аватарку
				if (count(explode("@", $fUser['foto'])) == 2) {
					// Если граватар
					$fUser['foto'] = 'http://www.gravatar.com/avatar/' . md5(trim($fUser['foto'])) . '?s=' . intval($user_group[$fUser['user_group']]['max_foto']);
				}
				else {
					// Если у нас
					if ($fUser['foto'] and (file_exists(ROOT_DIR . "/uploads/fotos/" . $fUser['foto'])))
						$fUser['foto'] = $config['http_home_url'] . 'uploads/fotos/' . $fUser['foto'];
					else {
						$fUser['foto'] = 'engine/skins/images/noavatar.png';
					}
				}
				$show_foto_td = '<td><img class="user-avatar" src="' . $fUser['foto'] . '" alt="Фото ' . $fUser['name'] . '" ></td>';
			}
			if ($show_email) {
				$show_email_td = '<td>' . $fUser['email'] . '</td>';
			}
			if ($show_news_num) {
				$show_news_num_td = '<td>' . $fUser['news_num'] . '</td>';
			}
			if ($show_comm_num) {
				$show_comm_num_td = '<td>' . $fUser['comm_num'] . '</td>';
			}
			if ($show_user_group) {
				$fUser['user_group'] = $user_group[$fUser['user_group']]['group_prefix'] . $user_group[$fUser['user_group']]['group_name'] . $user_group[$fUser['user_group']]['group_suffix'];
				$show_user_group_td = '<td>' . $fUser['user_group'] . '</td>';
			}
			if ($show_lastdate) {
				$fUser['lastdate'] = langdate("j.m.Y H:i", $fUser['lastdate']);
				$show_lastdate_td = '<td>' . $fUser['lastdate'] . '</td>';
			}
			if ($show_reg_date) {
				$fUser['reg_date'] = langdate("j.m.Y H:i", $fUser['reg_date']);
				$show_reg_date_td = '<td>' . $fUser['reg_date'] . '</td>';
			}
			if ($show_banned) {
				$fUser['banned'] = ($fUser['banned'] == 'yes') ? 'Да' : false;
				$show_banned_td = '<td>' . $fUser['banned'] . '</td>';
			}
			if ($show_info) {
				$show_info_td = '<td>' . $fUser['info'] . '</td>';
			}
			if ($show_signature) {
				$show_signature_td = '<td>' . $fUser['signature'] . '</td>';
			}

			if ($show_fullname) {
				$show_fullname_td = '<td>' . $fUser['fullname'] . '</td>';
			}
			if ($show_land) {
				$show_land_td = '<td>' . $fUser['land'] . '</td>';
			}
			if ($show_icq) {
				$show_icq_th = '<th>ICQ</th>';
				$show_icq_td = '<td>' . $fUser['icq'] . '</td>';
			}
			if ($show_pm) {
				$fUser['pm_unread'] = ($fUser['pm_unread'] > 0) ? '| <b title="Не прочитанные сообщения">' . $fUser['pm_unread'] . '</b>' : false;
				$show_pm_td = '<td>' . $fUser['pm_all'] . $fUser['pm_unread'] . '</td>';
			}
			if ($show_logged_ip) {
				$show_logged_ip_td = '<td>' . $fUser['logged_ip'] . '</td>';
			}
			// Формируем ячейки таблицы
			$output .= '<tr>';
			$output .= $show_user_id_td . $show_foto_td . $show_email_td . '<td><b><a href="' . $config['http_home_url'] . '/index.php?subaction=userinfo&user=' . urlencode($fUser['name']) . '" target="_blank">' . $fUser['name'] . '</a></b></td>' . $show_fullname_td . $show_reg_date_td . $show_lastdate_td . $show_logged_ip_td . $show_banned_td . $show_news_num_td . $show_comm_num_td . $show_info_td . $show_signature_td . $show_land_td . $show_icq_td . $show_pm_td . $show_user_group_td;
			$output .= '<td><input type="checkbox" value="' . $fUser['user_id'] . '" name="selected[]" id="cuid_' . $fUser['user_id'] . '" class="checkbox connected-checkbox"><label for="cuid_' . $fUser['user_id'] . '"><span></span></label></td></tr>';
		}
		$output .= '</table>';
		$output .= <<<HTML
				<div class="ta-right">
					<select name="do_action" id="do_action">
						<option>-- выберите действие --</option>
						<option value="del">Удалить выбранных</option>
						<option value="clear_signature">Очистить подпись</option>
						<option value="clear_info">Очистить инфо о пользователе</option>
					</select>
					<input class="btn" type="submit" value="Выполнить действие">
				</div>
		</form>
HTML;
// Формируем таблицу из переменных

	// pagination	

		$npp_nav = "<div class=\"news_navigation\" >";
		if ($start_from > 0) {
			$previous = $start_from - $user_per_page;
			$npp_nav .= "<a class='btn btn-small' onClick=\"javascript:list_submit($previous); return(false)\" href=#> &lt;&lt; </a>&nbsp;";
		}
		if ($all_count_user > $user_per_page) {
			$enpages_count = @ceil($all_count_user / $user_per_page);
			$enpages_start_from = 0;
			$enpages = "";
			if ($enpages_count <= 10) {
				for ($j = 1; $j <= $enpages_count; $j++) {
					if ($enpages_start_from != $start_from) {
						$enpages .= "<a class='btn btn-small' onclick=\"javascript:list_submit($enpages_start_from); return(false);\" href=\"#\">$j</a> ";
					}
					else {
						$enpages .= "<span class='btn btn-small active'>$j</span> ";
					}
					$enpages_start_from += $user_per_page;
				}
				$npp_nav .= $enpages;
			}
			else {
				$start = 1;
				$end = 10;
				if ($start_from > 0) {
					if (($start_from / $user_per_page) > 4) {
						$start = @ceil($start_from / $user_per_page) - 3;
						$end = $start + 9;

						if ($end > $enpages_count) {
							$start = $enpages_count - 10;
							$end = $enpages_count - 1;
						}
						$enpages_start_from = ($start - 1) * $user_per_page;
					}
				}

				if ($start > 2) {
					$enpages .= "<a class='btn btn-small' onclick=\"javascript:list_submit(0); return(false);\" href=\"#\">1</a> ... ";
				}

				for ($j = $start; $j <= $end; $j++) {
					if ($enpages_start_from != $start_from) {
						$enpages .= "<a class='btn btn-small' onclick=\"javascript:list_submit($enpages_start_from); return(false);\" href=\"#\">$j</a> ";
					}
					else {
						$enpages .= "<span class='btn btn-small active'>$j</span> ";
					}
					$enpages_start_from += $user_per_page;
				}

				$enpages_start_from = ($enpages_count - 1) * $user_per_page;
				$enpages .= "... <a class='btn btn-small' onclick=\"javascript:list_submit($enpages_start_from); return(false);\" href=\"#\">$enpages_count</a> ";

				$npp_nav .= $enpages;
			}
		}


		if ($all_count_user > $i) {
			$how_next = $all_count_user - $i;
			if ($how_next > $user_per_page) {
				$how_next = $user_per_page;
			}
			$npp_nav .= "<a class='btn btn-small' onclick=\"javascript:list_submit($i); return(false)\" href=#> &gt;&gt; </a> ";
		}
		$npp_nav .= "</div>";
		$output .= $npp_nav;

	// pagination
	}


	// Функция возвращает то, что должно быть выведено
	return $output;
}

/**
 * Подсчитываем общее кол-во юзеров в БД
 * @param  boolean $nomOnly Возвратить только кол-во.
 *
 * @return string - блок с кол-вом юзеров
 */
function allUsersCount($nomOnly = false) {
	global $config, $db, $cfg;
	$_aUC = $db->super_query("SELECT COUNT(*) as count FROM " . USERPREFIX . "_users");
	$uCountNum = $_aUC['count'];
	$uCount = ($nomOnly) ? $uCountNum : '<div class="ta-right">Всего в БД <b>' . $uCountNum . '</b> ' . wordSpan($uCountNum, 'пользовател|ь|я|ей') . '</div>';
	return $uCount;
}

/**
 * Функция для установки правильного окончания слов
 * @param int    $n     - число, для которого будет расчитано окончание
 * @param string $words - варианты окончаний для (1 комментарий, 2 комментария, 100 комментариев)
 *
 * @return string - слово с правильным окончанием
 */
function wordSpan($n = 0, $words) {
	$words = explode('|', $words);
	$n = intval($n);
	return $n % 10 == 1 && $n % 100 != 11 ? $words[0] . $words[1] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $words[0] . $words[2] : $words[0] . $words[3]);
}


?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="<?=$config['charset']?>">
	<title><?=$cfg['moduleTitle']?> - Управление модулем</title>
	<meta name="viewport" content="width=device-width">
	<link href="http://fonts.googleapis.com/css?family=Ubuntu+Condensed&subset=latin,cyrillic" rel="stylesheet">
	<style>
		/*Общие стили*/
		html{background: #bdc3c7 url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAMAAAAp4XiDAAAAUVBMVEWFhYWDg4N3d3dtbW17e3t1dXWBgYGHh4d5eXlzc3OLi4ubm5uVlZWPj4+NjY19fX2JiYl/f39ra2uRkZGZmZlpaWmXl5dvb29xcXGTk5NnZ2c8TV1mAAAAG3RSTlNAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEAvEOwtAAAFVklEQVR4XpWWB67c2BUFb3g557T/hRo9/WUMZHlgr4Bg8Z4qQgQJlHI4A8SzFVrapvmTF9O7dmYRFZ60YiBhJRCgh1FYhiLAmdvX0CzTOpNE77ME0Zty/nWWzchDtiqrmQDeuv3powQ5ta2eN0FY0InkqDD73lT9c9lEzwUNqgFHs9VQce3TVClFCQrSTfOiYkVJQBmpbq2L6iZavPnAPcoU0dSw0SUTqz/GtrGuXfbyyBniKykOWQWGqwwMA7QiYAxi+IlPdqo+hYHnUt5ZPfnsHJyNiDtnpJyayNBkF6cWoYGAMY92U2hXHF/C1M8uP/ZtYdiuj26UdAdQQSXQErwSOMzt/XWRWAz5GuSBIkwG1H3FabJ2OsUOUhGC6tK4EMtJO0ttC6IBD3kM0ve0tJwMdSfjZo+EEISaeTr9P3wYrGjXqyC1krcKdhMpxEnt5JetoulscpyzhXN5FRpuPHvbeQaKxFAEB6EN+cYN6xD7RYGpXpNndMmZgM5Dcs3YSNFDHUo2LGfZuukSWyUYirJAdYbF3MfqEKmjM+I2EfhA94iG3L7uKrR+GdWD73ydlIB+6hgref1QTlmgmbM3/LeX5GI1Ux1RWpgxpLuZ2+I+IjzZ8wqE4nilvQdkUdfhzI5QDWy+kw5Wgg2pGpeEVeCCA7b85BO3F9DzxB3cdqvBzWcmzbyMiqhzuYqtHRVG2y4x+KOlnyqla8AoWWpuBoYRxzXrfKuILl6SfiWCbjxoZJUaCBj1CjH7GIaDbc9kqBY3W/Rgjda1iqQcOJu2WW+76pZC9QG7M00dffe9hNnseupFL53r8F7YHSwJWUKP2q+k7RdsxyOB11n0xtOvnW4irMMFNV4H0uqwS5ExsmP9AxbDTc9JwgneAT5vTiUSm1E7BSflSt3bfa1tv8Di3R8n3Af7MNWzs49hmauE2wP+ttrq+AsWpFG2awvsuOqbipWHgtuvuaAE+A1Z/7gC9hesnr+7wqCwG8c5yAg3AL1fm8T9AZtp/bbJGwl1pNrE7RuOX7PeMRUERVaPpEs+yqeoSmuOlokqw49pgomjLeh7icHNlG19yjs6XXOMedYm5xH2YxpV2tc0Ro2jJfxC50ApuxGob7lMsxfTbeUv07TyYxpeLucEH1gNd4IKH2LAg5TdVhlCafZvpskfncCfx8pOhJzd76bJWeYFnFciwcYfubRc12Ip/ppIhA1/mSZ/RxjFDrJC5xifFjJpY2Xl5zXdguFqYyTR1zSp1Y9p+tktDYYSNflcxI0iyO4TPBdlRcpeqjK/piF5bklq77VSEaA+z8qmJTFzIWiitbnzR794USKBUaT0NTEsVjZqLaFVqJoPN9ODG70IPbfBHKK+/q/AWR0tJzYHRULOa4MP+W/HfGadZUbfw177G7j/OGbIs8TahLyynl4X4RinF793Oz+BU0saXtUHrVBFT/DnA3ctNPoGbs4hRIjTok8i+algT1lTHi4SxFvONKNrgQFAq2/gFnWMXgwffgYMJpiKYkmW3tTg3ZQ9Jq+f8XN+A5eeUKHWvJWJ2sgJ1Sop+wwhqFVijqWaJhwtD8MNlSBeWNNWTa5Z5kPZw5+LbVT99wqTdx29lMUH4OIG/D86ruKEauBjvH5xy6um/Sfj7ei6UUVk4AIl3MyD4MSSTOFgSwsH/QJWaQ5as7ZcmgBZkzjjU1UrQ74ci1gWBCSGHtuV1H2mhSnO3Wp/3fEV5a+4wz//6qy8JxjZsmxxy5+4w9CDNJY09T072iKG0EnOS0arEYgXqYnXcYHwjTtUNAcMelOd4xpkoqiTYICWFq0JSiPfPDQdnt+4/wuqcXY47QILbgAAAABJRU5ErkJggg==') repeat;}
		body{min-width: 800px; max-width: 1200px; padding: 20px;margin: 20px auto;font:normal 14px/18px Arial, Helvetica, sans-serif;background: #f1f1f1;box-shadow: 0 0 15px 0 rgba(0, 0, 0, 0.1);color: #34495e;}
		::-moz-selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		::selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		hr{margin: 18px 0;border: 0;border-top: 1px solid #f5f5f5;border-bottom: 1px solid #bdc3c7;}
		.preview  {display: block;margin: 20px auto 40px;max-width: 100%;}
		.descr  {font: normal 18px/24px "Trebuchet MS", Arial, Helvetica, sans-serif;color: #34495e;margin: 20px -20px;padding: 20px;background: #ecf0f1;-webkit-box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);text-shadow: 0 1px 0 #fff;}
		b{color: #2980b9;}
		.descr hr  {margin: 18px -20px;}
		.ta-center  {text-align: center;}
		.ta-left {text-align: left;}
		.ta-right {text-align: right;}
		.logo{margin: 0 auto;display: block;}
		a{color: #2980b9;}
		a:hover{text-decoration: none;color: #c0392b;}
		.btn{line-height: 32px;font-size: 100%;margin: 0;vertical-align: baseline;*vertical-align: middle;cursor: pointer;*overflow: visible;background: #3498db;color: #ecf0f1;text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);border: 0;border-radius: 3px;padding: 0 15px;display: inline-block; text-decoration: none; border-bottom: solid 3px #2980b9; transition: all ease .6s;}
		.btn:hover, .btn.active{background: #e74c3c; border-bottom-color: #c0392b; color: #ecf0f1;}
		.btn-small {line-height: 26px;}
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
		input[type="text"], select {padding: 4px 10px;width: 250px;vertical-align: middle;height: 24px;line-height: 24px;border: solid 1px #95a5a6;display: inline-block;border-radius: 3px;}
		input[type="text"]:focus, select:focus {border-color: #3498db;color:#2c3e50;outline: none;-webkit-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);-moz-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);}
		select {height: 32px; width: auto;}
		form {margin-bottom: 10px;}
		.checkbox { display:none; }
		.checkbox + label { cursor: pointer; margin-top: 4px; display: inline-block; }
		.checkbox + label span { display:inline-block; width:18px; height:18px; margin:-1px 4px 0 0; vertical-align:middle; background: #fff; cursor:pointer; border-radius: 4px; border: solid 2px #3498db; }
		.checkbox:checked + label span { background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAICAYAAADN5B7xAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAIJJREFUeNpi+f//PwMhIL6wjQVITQDi10xEKBYEUtuAOBuIGVmgAnkgyZfxVY1oilWB1BYgVgPiRqB8A8iGfCBuAGGggnokxS5A6iSyYpA4I8gPQEkQB6YYxH4FxJOAmAVZMVwD1ERkTTCAohgE4J6GSjTiU4xiA5LbG5AMwAAAAQYAgOM4GiRnHpIAAAAASUVORK5CYII=') no-repeat 50% 50%; border-color: #16a085; }
		.checkbox:disabled + label span, input[type="text"]:disabled {background: #e9e9e9; border-color: #ccc;}
		label + .checkbox + label, input[type="text"] + .checkbox + label {margin-left: 10px;}
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
		.fleft {float: left;}
		.fright {float: right;}
		.w33p {width: 33.333%;}
		.d-inline {display: inline;}
		/*Стили для подсказок*/
		.ttp { position: relative; cursor: help; border-bottom: 1px dotted; }
		.ttp.mini { display: inline-block; font-size: 14px; width: 20px; text-align: center; margin-left: 10px; color: #2980b9; border: 1px solid #2980b9; background: #cddee9; border-radius: 4px; }
		.ttp div { display: none; position: absolute; bottom: -1px; left: -1px; z-index: 1000; width: 320px; padding: 10px 20px; text-align: left; box-shadow: 0 3px 0 rgba(41, 128, 185, 0.54); border: 1px solid #2980b9; background: #cddee9; border-radius: 4px; text-shadow: none; color: #34495e; }
		/*таблицы*/
		table { max-width: 100%; background-color: #f1f1f1; border-collapse: collapse; border-spacing: 0; width: 100%; margin-bottom: 18px; }
		tr:first-child th, tr:first-child td { border-top: 0; }
		table tbody tr:hover td { background-color: #e9e9e9; }
		table th, table td { padding: 8px; line-height: 18px; text-align: left; vertical-align: middle; border-top: 1px solid #ddd; }
		table th {font-size: 12px; vertical-align: bottom; background: #ddd;}
		table th.none {cursor: pointer;}
		table th.ASC, table th.DESC { background: #3498db; color: #fff; box-shadow: inset 0 -4px 0 0 #2980b9; vertical-align: middle; text-align: center; cursor: pointer;}
		table th.DESC {box-shadow: inset 0 4px 0 0 #2980b9;}
		/*прочие стили*/
		.user-avatar {max-height: 40px; max-width: 40px;}
	</style>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/autosize.js/1.18.1/jquery.autosize.min.js"></script>
	<script>
		/*! https://github.com/jmosbech/StickyTableHeaders */
		(function(e,t){"use strict";function i(i,s){var n=this;n.$el=e(i),n.el=i,n.id=a++,n.$el.bind("destroyed",e.proxy(n.teardown,n)),n.$clonedHeader=null,n.$originalHeader=null,n.isSticky=!1,n.hasBeenSticky=!1,n.leftOffset=null,n.topOffset=null,n.init=function(){n.options=e.extend({},o,s),n.$el.each(function(){var t=e(this);t.css("padding",0),n.$scrollableArea=e(n.options.scrollableArea),n.$originalHeader=e("thead:first",this),n.$clonedHeader=n.$originalHeader.clone(),t.trigger("clonedHeader."+l,[n.$clonedHeader]),n.$clonedHeader.addClass("tableFloatingHeader"),n.$clonedHeader.css("display","none"),n.$originalHeader.addClass("tableFloatingHeaderOriginal"),n.$originalHeader.after(n.$clonedHeader),n.$printStyle=e('<style type="text/css" media="print">.tableFloatingHeader{display:none !important;}.tableFloatingHeaderOriginal{position:static !important;}</style>'),e("head").append(n.$printStyle)}),n.updateWidth(),n.toggleHeaders(),n.bind()},n.destroy=function(){n.$el.unbind("destroyed",n.teardown),n.teardown()},n.teardown=function(){n.isSticky&&n.$originalHeader.css("position","static"),e.removeData(n.el,"plugin_"+l),n.unbind(),n.$clonedHeader.remove(),n.$originalHeader.removeClass("tableFloatingHeaderOriginal"),n.$originalHeader.css("visibility","visible"),n.$printStyle.remove(),n.el=null,n.$el=null},n.bind=function(){n.$scrollableArea.on("scroll."+l,n.toggleHeaders),n.$scrollableArea[0]!==t&&(e(t).on("scroll."+l+n.id,n.setPositionValues),e(t).on("resize."+l+n.id,n.toggleHeaders)),n.$scrollableArea.on("resize."+l,n.toggleHeaders),n.$scrollableArea.on("resize."+l,n.updateWidth)},n.unbind=function(){n.$scrollableArea.off("."+l,n.toggleHeaders),n.$scrollableArea[0]!==t&&(e(t).off("."+l+n.id,n.setPositionValues),e(t).off("."+l+n.id,n.toggleHeaders)),n.$scrollableArea.off("."+l,n.updateWidth),n.$el.off("."+l),n.$el.find("*").off("."+l)},n.toggleHeaders=function(){n.$el&&n.$el.each(function(){var i,l=e(this),a=n.$scrollableArea[0]===t?isNaN(n.options.fixedOffset)?n.options.fixedOffset.height():n.options.fixedOffset:n.$scrollableArea.offset().top+(isNaN(n.options.fixedOffset)?0:n.options.fixedOffset),o=l.offset(),s=n.$scrollableArea.scrollTop()+a,r=n.$scrollableArea.scrollLeft(),d=n.$scrollableArea[0]===t?s>o.top:a>o.top,c=(n.$scrollableArea[0]===t?s:0)<o.top+l.height()-n.$clonedHeader.height()-(n.$scrollableArea[0]===t?0:a);d&&c?(i=o.left-r+n.options.leftOffset,n.setPositionValues(),n.$originalHeader.css({position:"fixed","margin-top":0,left:i,"z-index":1}),n.isSticky=!0,n.leftOffset=i,n.topOffset=a,n.$clonedHeader.css("display",""),n.updateWidth()):n.isSticky&&(n.$originalHeader.css("position","static"),n.$clonedHeader.css("display","none"),n.isSticky=!1,n.resetWidth(e("td,th",n.$clonedHeader),e("td,th",n.$originalHeader)))})},n.setPositionValues=function(){var i=e(t).scrollTop(),l=e(t).scrollLeft();!n.isSticky||0>i||i+e(t).height()>e(document).height()||0>l||l+e(t).width()>e(document).width()||n.$originalHeader.css({top:n.topOffset-(n.$scrollableArea[0]===t?0:i),left:n.leftOffset-(n.$scrollableArea[0]===t?0:l)})},n.updateWidth=function(){if(n.isSticky){var t=e("th,td",n.$originalHeader),i=e("th,td",n.$clonedHeader);n.cellWidths=[],n.getWidth(i),n.setWidth(i,t),n.$originalHeader.css("width",n.$clonedHeader.width())}},n.getWidth=function(t){t.each(function(t){var i,l=e(this);i="border-box"===l.css("box-sizing")?l.outerWidth():l.width(),n.cellWidths[t]=i})},n.setWidth=function(e,t){e.each(function(e){var i=n.cellWidths[e];t.eq(e).css({"min-width":i,"max-width":i})})},n.resetWidth=function(t,i){t.each(function(t){var l=e(this);i.eq(t).css({"min-width":l.css("min-width"),"max-width":l.css("max-width")})})},n.updateOptions=function(t){n.options=e.extend({},o,t),n.updateWidth(),n.toggleHeaders()},n.init()}var l="stickyTableHeaders",a=0,o={fixedOffset:0,leftOffset:0,scrollableArea:t};e.fn[l]=function(t){return this.each(function(){var a=e.data(this,"plugin_"+l);a?"string"==typeof t?a[t].apply(a):a.updateOptions(t):"destroy"!==t&&e.data(this,"plugin_"+l,new i(this,t))})}})(jQuery,window);

		function list_submit(num) {
			$('#searchusers').find('#start_from').val(num);
			$('#searchusers').submit();
			return false;
		}

		jQuery(document).ready(function ($) {
			$.fn.scrollView = function () {
				return this.each(function () {
					$('html, body').animate({
						scrollTop: $(this).offset().top - 20
					}, 1000);
				});
			}
			$('#result-header').scrollView();
			$('table').stickyTableHeaders();
			$('.main-checkbox').on('change', function () {
				var itemData = $(this).data('checkboxes'),
					item = $(itemData + ':enabled');
				if ($(this).prop('checked')) {
					item.prop('checked', true).trigger('change');
				}
				else {
					item.prop('checked', false).trigger('change');
				}
			});
			var odnodnevka = $('#odnodnevka'),
				not1day = $('.not-oneday');
				sow_sort = $('#sort'),
				sow_sort_dir = $('#sort_dir');

			if (odnodnevka.is(':checked')) {
				not1day.prop('disabled', true);
			};
			odnodnevka.on('change', function() {
				if ($(this).prop('checked')) {
					not1day.prop('disabled', true);
				}
				else {
					not1day.prop('disabled', false);
					$('.inp-text-checkbox').prop('checked', false).trigger('change');
				}
			});
			$('#'+sow_sort.val()).prop('class', sow_sort_dir.val());
			$('table').on('click', 'th', function() {
				if ($(this).prop('id')) {					
					var sort = $(this).attr('id'),
						sort_dir = $(this).attr('class'),
						searchusers = $('#searchusers');
					searchusers.find('#sort').val(sort);
					if (sort_dir == 'DESC' || sort_dir == 'none') {
						searchusers.find('#sort_dir').val('ASC');
						$(this).prop('class','ASC').siblings().prop('class', 'none');
					};
					if (sort_dir == 'ASC') {
						searchusers.find('#sort_dir').val('DESC');
						$(this).prop('class','DESC').siblings().prop('class', 'none');
					};
					searchusers.submit();
					return false;
				};
			});

			$('.child-checkbox').on('change', function() {
				var thisData = $(this).data('parentCheckbox'),
					item = $(thisData + ':enabled');
				if ($(this).prop('checked')) {
					item.prop('checked', true).trigger('change');
				}
			});

			$('.parent-checkbox').on('change', function() {
				var thisData = $(this).data('childCheckbox'),
					item = $(thisData + ':enabled');
				if (!$(this).prop('checked')) {
					item.prop('checked', false).trigger('change');
				} 
			});

			$('.inp-text-checkbox').on('change', function() {
				var thisData = $(this).data('inpText'),
					item = $(thisData);
				if ($(this).prop('checked')) {
					item.prop('disabled', true);
				} else {
					item.prop('disabled', false);
				}
			});
			
			
			/*! http://dimox.name/beautiful-tooltips-with-jquery/ */
			$('span.ttp').each(function(){var el=$(this);var title=el.attr('title');if(title&&title!=''){el.attr('title','').append('<div>'+title+'</div>');var width=el.find('div').width();var height=el.find('div').height();el.hover(function(){el.find('div').clearQueue().delay(200).animate({width:width+20,height:height+20},200).show(200).animate({width:width,height:height},200);},function(){el.find('div').animate({width:width+20,height:height+20},150).animate({width:'hide',height:'hide'},150);}).mouseleave(function(){if(el.children().is(':hidden'))el.find('div').clearQueue();});}}); 

			$('textarea').autosize();
			$('textarea').click(function () {
				$(this).select();
			});
		});
	</script>
</head>
<body>
	<header>
		<div class="clearfix">
			<div class="fleft">
				<a href="<?=$PHP_SELF?>?mod=main" class="btn btn-small"><?=$lang['skin_main']?></a>
				<a class="btn btn-small" href="<?=$PHP_SELF?>?mod=options&amp;action=options" title="Список всех разделов">Список всех разделов</a>
				<a href="<?=$config['http_home_url']?>" target="_blank" class="btn btn-small"><?=$lang['skin_view']?></a>
			</div>
			<div class="fright">
				<?=$lang['skin_name'] . ' ' . $member_id['name'] . ' <small>(' . $user_group[$member_id['user_group']]['group_name'] .')</small> '?>
				<a href="<?=$PHP_SELF?>?action=logout" class="btn btn-small"><?=$lang['skin_logout']?></a>
			</div>
		</div>
		<hr>
		<h1 class="ta-center"><big class="red"><?=$cfg['moduleTitle']?></big> v.<?=$cfg['moduleVersion']?> от <?=$cfg['moduleDate']?></h1>
	</header>
	<section>  

		<h2 class="gray ta-center"><?=$cfg['moduleDescr']?></h2>
		<hr>
		<?php 
			$allUsersCount = allUsersCount(); 
			echo $allUsersCount;
		?>
		
		<?php 
			$output = searchFakeUser();
			echo $output;
		?>
	<hr>
	<div>Автор модуля: <a href="http://pafnuty.name/" target="_blank">ПафНутиЙ</a> <br> ICQ: 817233 <br> <a href="mailto:pafnuty10@gmail.com">pafnuty10@gmail.com</a></div>
	</section> 	
</body>
</html>
