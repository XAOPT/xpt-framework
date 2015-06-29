<?php

class ContSearch
{
	var $tpl;
	static $name = 'search';

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);

		$this->tpl->assign("LANG", $GLOBALS['lang']);
	}

	function Action($action = '')
	{
		$arAction = array('');

		if (in_array($action, $arAction) || !$action)
			$prefix = 'action';
		else
			$prefix = 'view';

		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);

		$model = new $model_name;

		switch ( $action )
		{
			default:
				return $model->SearchResult();
		}
	}
}

?>