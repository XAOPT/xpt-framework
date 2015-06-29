<?php
class ContStatic
{
	var $tpl;
	static $name = 'static';

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);	## объект шаблона
    }

	function ActionModule($action, $option)
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		switch ( $action )
		{
			case 'static_block':
				return $model->StaticBlock($option);
				break;
        }
	}

    function Action($action = '')
    {
		load_model(self::$name, 'view'); ## здесь всегда подключаю одну и ту же модель. можно сделать несколько разных в зависимости от action
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		if ((int)$action > 0)
		{
			return $model->ViewItem($action);
		}
		else
		{
			global $sql;

			$sql->SetQuery("SELECT id FROM `static_pages` WHERE alias='{$action}'");
			$id = $sql->LoadSingle();

			if ($id)
				return $model->ViewItem($id);
		}
    }
}

?>