<?php
class UsersView extends AdmcontUsers
{
	function __construct()
	{
		parent::__construct();
    }

	function ViewUsersList()
	{
		global $sql;

		$pagination = new classPagination();
		$pagination->current_page = zReq::getVar('p', 'INT', 'GET', 1);
		$pagination->per_page = 70;

		$limit_start = ($pagination->current_page - 1)*$pagination->per_page;

		$users = $sql->SetQuery("SELECT * FROM `##users` ORDER BY email LIMIT {$limit_start}, {$pagination->per_page}")->LoadAllRows();

		$this->tpl->assign( "users", $users );

		## странички
		$query = "
		SELECT COUNT(*)
		FROM `##users`
		";
		$pagination->total = $sql->SetQuery($query)->LoadSingle();

		$groups = $sql->SetQuery("SELECT * FROM `##users_groups`")->LoadAllRows();
		$this->tpl->assign( "groups", $groups );

		$pagination->url_start = ADOMAIN."/".self::$name."/?p=";

		foreach ($users as &$user)
		{
			if ($user['groups']) {
				$temp = json_decode($user['groups'], true);

				if(is_array($temp) && count($temp)){
					$user['groups'] = implode(',', $temp);

					$group = $sql->SetQuery("SELECT GROUP_CONCAT(title SEPARATOR ', ') FROM `##users_groups` WHERE ugid IN({$user['groups']})")->LoadSingle();
					$user['group'] = $group;
				} else {
					$user['group'] = '';
				}
			}
			else {
				$user['group'] = '';
			}
		}

		$this->tpl->assign( "users", $users );

		return $this->tpl->draw( "users_list" );
	}

	function ViewUserEdit()
	{
		global $sql;

		$userid = zReq::GetVar('id', 'INT', 'GET', 0);

		if ($userid)
		{
			if (!guser::_hasAccess(self::$name, 'edit'))
				return get_warning_html('Ошибка доступа', 'error');

			$user = $sql->SetQuery("SELECT * FROM `##users` WHERE userid='{$userid}'")->LoadRow();

			$user['groups'] = json_decode($user['groups'], true);
			$this->tpl->assign( "user", $user );
		}

		$groups = $sql->SetQuery("SELECT * FROM `##users_groups` ORDER BY ugid")->LoadAllRows();
		$this->tpl->assign( "groups", $groups );

		return $this->tpl->draw( "edit_user" );
	}


	function UserGroupsList()
	{
		global $sql;

		$groups = $sql->SetQuery("SELECT * FROM `##users_groups` ORDER BY ugid")->LoadAllRows();

		$this->tpl->assign( "groups", $groups );

		return $this->tpl->draw( "groups_list" );
	}

	function EditGroup()
	{
		global $sql;

		$ugid = zReq::GetVar('ugid', 'INT', 'GET', 0);

		if ($ugid == 1)
			return get_warning_html('Группа администраторы не может быть отредактирована', 'error', ADOMAIN.'/'.self::$name.'/ugs/');

		/* доступ к компонентам */
		$sql->SetQuery("SELECT * FROM `##components` WHERE access <> ''");
		$components = $sql->LoadByUniq('sysname');

		if (!empty($components))
		foreach ($components as &$c)
		{
			$c['access'] = json_decode($c['access'], true);
		}
		$this->tpl->assign( "components", $components );

		if ($ugid)
		{
			$group = $sql->SetQuery("SELECT * FROM `##users_groups` WHERE ugid='{$ugid}'")->LoadRow();
			$json = json_decode($group['access_comp'], true);

			$access = array();
			foreach ($json as $key=>$value)
			{
				foreach ($value as $v)
				{
					if (!isset($access[$key])) $access[$key] = array();

					if (!isset($access[$key][$v]))
					{
						$access[$key][$v] = true;
					}
				}
			}
			$group['access_comp'] = $access;

			$group['access_proj'] = json_decode($group['access_proj'], true);

			$this->tpl->assign( "group", $group );
		}

		return $this->tpl->draw( "edit_group" );
	}
}

?>