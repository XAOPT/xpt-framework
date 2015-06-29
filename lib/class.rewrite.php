<?php
/*
* Разбор адресной строки
*/

class ZokerRewrite
{
	var $component;
	var $action;
	var $admin_mod = false;
	var $module    = false;
	var $ajax      = false;

	var $params    = array();
	var $template  = 'default';

	function __construct($url = '')
	{
		global $available_locale;

		if (!$url) $url = $_SERVER['REQUEST_URI'];

		$path = parse_url($url);
		$path = explode('/', $path['path']);

		$action = zReq::getVar('do', 'STRING', 'REQUEST', '');
		$ajax   = zReq::getVar('ajax', 'BOOL', 'REQUEST', false);
		$module = zReq::getVar('module', 'BOOL', 'REQUEST', false);
		$comp   = zReq::getVar('comp', 'SQL', 'REQUEST', '');
		$admin  = zReq::getVar('admin', 'BOOL', 'REQUEST', false);

		if (in_array($path[1], $available_locale)) {
			$this->locale = $path[1];
			array_splice($path, 1, 1);
		}
		else
			$this->locale = 'ru';

		/* AJAX */
		if ($ajax || (isset($path[2]) && $path[2] == 'ajax') || $path[1] == 'ajax')
		{
			$this->ajax = true;
		}

		/* АДМИНКА */
		if ($admin || (ADMIN_URL && (isset($path[1]) && $path[1] == ADMIN_URL) || ($this->ajax && isset($path[2]) && $path[2] == ADMIN_URL)) )
		{
			$this->admin_mod = true;
		}

		/* МОДУЛЬ */
		if ($module || $path[1] == 'module' || $path[1] == 'mod')
		{
			if (isset($path[2]))
				$this->module    = $path[2];

		}

		if ($this->admin_mod && $this->ajax)
		{
			if (isset($path[3]))
				$this->component = $path[3];

			if (isset($path[4]))
				$this->action = $path[4];
		}
		else if ($this->admin_mod || $this->ajax)
		{
			if (isset($path[2]))
				$this->component = $path[2];

			if (isset($path[3]))
				$this->action = $path[3];
		}
		else
		{
			if (isset($path[1]))
				$this->component = $path[1];

			if (isset($path[2]))
				$this->action = $path[2];
		}

		/* Статическая страница */
		if (isset($path[1]) && preg_match("/(.*?)\.html/", $path[1], $matches))
		{
			$this->component = 'static';
			$this->action = $matches[1];
		}

		if ($action)
			$this->action = $action;

		if ($comp)
			$this->component = $comp;

		/* Сваливаем остальную часть в params */
		$length = count($path);
		if ($length > 3)
		{
			for ($i=3; $i < $length; $i++)
			{
				if (isset($path[$i]))
					$this->params[] = $path[$i];
			}
		}

		$this->template = $this->GetTemplate();
	}

	function GetTemplate()
	{
		if ($this->admin_mod)
			return 'admin';

		/*if ($this->component)
		{
			global $sql;
			$template = $sql->SetQuery("SELECT template FROM components WHERE sysname='{$this->component}'", 'LoadSingle');
			$this->template = ($template)?$template:'default';
		}*/

		return 'default';
	}
}

?>