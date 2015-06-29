<?php
class UsersAction extends AdmcontUsers
{
	function __construct()
	{
		parent::__construct();
    }

	function SaveUser()
	{
		global $sql;

		$data = array(
			'email'    => zReq::getVar('email', 'SQL', 'POST'),
			'realname' => zReq::getVar('realname', 'SQL', 'POST')
		);

		$userid = zReq::getVar('userid', 'INT', 'POST');

		$groups = $sql->SetQuery("SELECT * FROM `##users_groups`")->LoadAllRows();

		$data['groups'] = array();

		if (!empty($groups))
		foreach ($groups as $g)
		{
			if (isset($_POST['groups'][$g['ugid']]))
				$data['groups'][] = $g['ugid'];

		}

		$data['groups'] = json_encode($data['groups']);

		$password = zReq::getVar('password', 'SQL', 'POST');
		if (!empty($password))
			$data['password'] = md5($password);

		if (!$userid)
		{
			$sql->InsertArray('##users', $data);
		}
		else
		{
			if(in_array('1', $data['groups'])){
				return get_warning_html("Данный пользователь является администратором", "error", $_SERVER['HTTP_REFERER']);
			}

			if (!guser::_hasAccess(self::$name, 'edit'))
				return get_warning_html('Ошибка доступа', 'error');

			$sql->UpdateArray('##users', $data, "userid='{$userid}'");
		}

		return get_warning_html('Изменения сохранены', 'ok', ADOMAIN.'/'.self::$name.'/list/');
	}

	function DeleteUser()
	{
		global $sql;

		$userid = zReq::getVar('id', 'INT', 'GET');

		if ($userid)
		{
			$sql->SetQuery("DELETE FROM `##users` WHERE userid='{$userid}'");
			return get_warning_html('Пользователь удалён', 'ok', ADOMAIN.'/'.self::$name.'/list/');
		}
		else
			return get_warning_html('Ошибка удаления', 'error', ADOMAIN.'/'.self::$name.'/list/');
	}


	function SaveUserGroup()
	{
		global $sql;

		$ugid = zReq::GetVar('ugid', 'INT', 'POST', 0);
		$data = array(
			'title' => zReq::GetVar('title', 'SQL', 'POST', '')
		);

		if (!$data['title'])
			return get_warning_html('Не заполнено название группы', 'error', $_SERVER['HTTP_REFERER']);

		if ($ugid == 1)
			return get_warning_html('Группа администраторы не может быть отредактирована', 'error', ADOMAIN.'/'.self::$name.'/groups_list/');

		/* сохраняем доступ к компонентам */
		$components = $sql->SetQuery("SELECT sysname FROM `##components` WHERE access <> ''")->LoadSingleArray();

		$access_comp = array();
		foreach($components as $c)
		{
			if (isset($_POST['access_'.$c]) && !empty($_POST['access_'.$c]))
			{
				foreach($_POST['access_'.$c] as $key => $value)
				{
					if (isset($temp))
						unset($temp);

					if (isset($_POST['params_'.$c.'_'.$key])) {
						$temp[$key] = mysql_real_escape_string($_POST['params_'.$c.'_'.$key]);
					}
					else
						$temp = mysql_real_escape_string($key);

					$access_comp[$c][] = $temp;
				}
			}
		}
		$data['access_comp'] = json_encode($access_comp);

		if (!$ugid)
		{
			$sql->InsertArray('##users_groups', $data);
			return get_warning_html('Группа пользователей добавлена', 'ok', ADOMAIN.'/'.self::$name.'/groups_list/');
		}
		else
		{
			$sql->UpdateArray('##users_groups', $data, "ugid='{$ugid}'");
			return get_warning_html('Группа пользователей отредактирована', 'ok', ADOMAIN.'/'.self::$name.'/groups_list/');
		}
	}

	function DeleteUserGroup()
	{
		global $sql;

		$ugid = zReq::GetVar('ugid', 'INT', 'GET', 0);

		if (!$ugid)
			return get_warning_html('Ошибка удаления группы', 'error', $_SERVER['HTTP_REFERER']);

		if ($ugid == 1)
			return get_warning_html('Группа администраторы не может быть удалена', 'error', $_SERVER['HTTP_REFERER']);

		//$sql->SetQuery("UPDATE `##users` SET groups='2' WHERE groups='{$ugid}'");
		$sql->SetQuery("DELETE FROM `##users_groups` WHERE ugid='{$ugid}'");

		return get_warning_html('Группа пользователей удалена. Все пользователи этой группы стали гостями', 'ok', $_SERVER['HTTP_REFERER']);
	}
}

?>