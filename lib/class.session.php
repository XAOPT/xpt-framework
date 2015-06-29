<?php
## version: 0.2.3
## previous: 0.2.2
##
$gUser       = array();
$gUserid     = 1;

class ClassSession {

	function __construct()
	{
		global $sql, $gUser, $gUserid, $gAlogin;

		$username = zReq::GetVar('login', 'SQL');
		$password = zReq::GetVar('pwd', 'SQL');

		if (!empty($username) && !empty($password))
		{
			$md5_pass = md5($password);

			$q = "
			SELECT *
			FROM `##users` as u
			WHERE `email`='{$username}' AND `password`='{$md5_pass}'
			";

			$gUser = $sql->setQuery($q)->LoadRow();

			if ($gUser['userid'])
			{

				$gUserid = $gUser['userid'];

				$this->StartSession();
			}
			else
			{
				header('Location: '.ADOMAIN.'/');
			}
		}
		else
		{
			$userid = $this->CheckSession();

			if ($userid)
			{
				$q = "
				SELECT *
				FROM `##users`
				WHERE `userid`='{$userid}'
				";
				$sql->setQuery($q);
				$gUser = $sql->LoadRow();

				if (!$sql->NumRows())
					return;

				$gUserid = $userid;
			}
		}

		if (!empty($gUser))
		{
			guser::_constructAccess();
		}
	}

	function StartSession()
	{
		global $gUserid, $sql;

		if(!$gUserid) return false;

		$sessid = md5(microtime());

		$browser = $_SERVER['HTTP_USER_AGENT'];
		$user_IP = $_SERVER['REMOTE_ADDR'];

		$start_time = time();
		$last_update = time();

		$query = "SELECT * FROM `##session` WHERE `userid`='{$gUserid}'";
		$sql->setQuery($query);

		if ($sql->NumRows())
	    	$sql->setQuery("UPDATE `##session` SET `id`='$sessid' ,`browser`='$browser' ,`ip`='$user_IP' ,`start_time`='$start_time' ,`lastupd`='$last_update' WHERE `userid`='{$gUserid}';");
		else
	    	$sql->setQuery("INSERT INTO `##session`(`id`,`userid`,`browser`,`ip`,`start_time`,`lastupd`) VALUE ('$sessid','{$gUserid}','$browser','$user_IP','$start_time','$last_update');");

		setcookie('sessid', "$sessid",  time()+86400000, "/");
		setcookie('userid', "$gUserid", time()+86400000, "/");
	}

	function CheckSession()
	{
		global $sql;

		if (isset($_COOKIE['sessid']) && isset($_COOKIE['userid']))
		{
			$sessid = $_COOKIE['sessid'];
			$userid = (int)$_COOKIE['userid'];
		}
		else
			return false;

		if (!$sessid || !$userid)
			return false;

		$browser = $_SERVER['HTTP_USER_AGENT'];
		$user_IP = $_SERVER['REMOTE_ADDR'];
		$last_update = time();

		if (!preg_match('/^[a-f0-9]{32,32}$/', $sessid))return false; //Неверный формат sessid
		if ($userid <= 0)                       return false; //Неверный формат userid

		$sql->SetQuery("SELECT * FROM `##session` WHERE `userid`='$userid' ORDER BY `lastupd` LIMIT 0,1;");

		if (!$sql->NumRows())                   return false; //Данный пользователь не прошёл авторизацию?

		$row = $sql->LoadRow();
		if ($row['id'] != $sessid)               return false; //Уже существует активная сессия для данного пользователя?


		if ($row['browser'] != $browser)               return false; //Попытка использования сессии из другого браузера
		if ($row['lastupd'] < ($last_update-(60*60*24*100))) return false; //Сессия уже закончилась? (120 мин)

		$sql->SetQuery("UPDATE `##session` SET `lastupd`='".time()."' WHERE `userid`='$userid'");

		return $row['userid'];
	}
}

$gUserGroups = array();

class guser
{
	static function _initUserGroups()
	{
		global $sql, $gUserGroups;

		$sql->SetQuery("SELECT * FROM `##users_groups`");
		$gUserGroups = $sql->LoadByUniq('ugid');

		return $gUserGroups;
	}

	static function _constructAccess()
	{
		global $gUser, $sql;

		if (empty($gUser['groups'])) return;

		$in_search_block = implode(',', json_decode($gUser['groups'], true));

		$gUser['groups'] = json_decode($gUser['groups'], true);

		$groups = $sql->SetQuery("SELECT * FROM `##users_groups` WHERE ugid IN ({$in_search_block})", 'LoadAllRows');

		if (empty($groups)) return array();

		$access   = array();
		$projects = array();
		foreach ($groups as $g)
		{
			if ($g['ugid'] == 1)
			{
				$access['admin'] = true;
				continue;
			}

			if (!$g['access_comp']) {
				continue;
			}

			$json = get_object_vars(json_decode($g['access_comp']));

			// перебираем все компоненты, к которым есть доступ у этой группы
			if (!empty($json))
			foreach ($json as $key=>$value)
			{
				// проходимся по всем уровням доступа к компоненту, например view, forum
				foreach ($value as $v)
				{
					// если данный уровень доступа подразумевает список каких-то объектов. Например для модера форума - перечень айдишников веток
					if (is_object($v))
					{
						$v = get_object_vars($v);
						$obj_key = key($v);

						// возможно пересечение доступов от разных групп пользователей. если раннее такого доступа небыло - создаём пустой массив
						if (!isset($access[$key])) $access[$key] = array();

						if (!isset($access[$key][$obj_key])) $access[$key][$obj_key] = array();

						$params = explode(',', $v[$obj_key]);

						// проверяем пересечения с другими группами пользователей
						foreach($params as $p)
						{
							if (!in_array($p, $access[$key][$obj_key]))
								$access[$key][$obj_key][] = $p;
						}
					}
					else
					{
						if (!isset($access[$key])) $access[$key] = array();

						if (!isset($access[$key][$v]))
						{
							$access[$key][$v] = true;
						}
					}
				}
			}

			## доступ к проектам
			if (!empty($g['access_proj'])) {
				$access_proj = json_decode($g['access_proj'], true);
				$projects = array_merge($projects, $access_proj);
			}
		}

		$gUser['access'] = $access;
		$gUser['project_access'] = $projects;


		return;
	}

	static function _isModer()
	{
		global $gUser;

		if (empty($gUser) || empty($gUser['groups'])) return false;

		$moders_ids = array();

		foreach ($moders_ids as $id)
		{
			if (in_array($id, $gUser['groups']))
				return true;
		}

		return false;
	}

	static function _isAdmin()
	{
		global $gUser;

		if (!empty($gUser) && isset($gUser['access']['admin']) && $gUser['access']['admin'])
			return true;
		else
			return false;
	}

	static function _hasAccess($component = '', $acc_level = '', $param = '')
	{
		global $gUser;

		if (empty($gUser['groups'])) return false;

		if (self::_isAdmin()) return true;

		if (empty($component)) return false;

		if (empty($acc_level) && isset($gUser['access'][$component])) return true;

		$acc_level = (array)$acc_level;

		foreach ($acc_level as $level) {
			if (isset($gUser['access'][$component]) && isset($gUser['access'][$component][$level]))
			{
				if (is_array($gUser['access'][$component][$level]))
				{
					if (empty($param))	return true;

					if (in_array($param, $gUser['access'][$component][$level]))
						return true;
					else
						return false;
				}
				else
					return true;
			}
		}

		return false;
	}

	static function _hasProjectAccess($project_id = -1)
	{
		global $gUser;

		if (guser::_isAdmin())
			return true;

		if ($project_id < 0)
			$project_id = $gUser['sku'];

		if (in_array($project_id, $gUser['project_access']) && $project_id >= 0)
			return true;
		else
			return false;
	}

	static function id()
	{
		global $gUser;

		if (empty($gUser)) return false;

		return $gUser['userid'];
	}

	static function avatar($avatar = 'myava')
	{
		if ($avatar == 'myava')
		{
			global $gUser;
			$avatar = $gUser['avatar'];
		}

		if (!empty($avatar))
			echo "<img src='".DOMAIN."/uploads/avatar/{$avatar}' />";
		else
			echo "<img src='".DOMAIN."/templates/default/images/topmenu-ava.png' />";
	}

	public function _groupText($groups = '')
	{
		global $gUser, $gUserGroups;

		if (empty($gUserGroups)) $gUserGroups = guser::_initUserGroups();

		if (!$groups)
		{
			//$groups = $gUser['groups'];
			echo "Пользователь";
			return;
		}


		if (!is_array($groups)) $groups = explode(' ', $groups);

		if (!isset($groups[0]) || !isset($gUserGroups[$groups[0]])) return false;

		echo $gUserGroups[$groups[0]]['title'];

		return;
	}

	public function getValue($key = '')
	{
		global $gUser;
		return isset($gUser[$key])?$gUser[$key]:null;
	}
}

$autologin = new ClassSession();

?>