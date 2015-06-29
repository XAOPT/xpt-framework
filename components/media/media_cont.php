<?php
class ContMedia
{
	var $tpl;
	static $name = 'media';
	public $eventsList = array();

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);
	}

	function ActionModule($action, $option)
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		switch ( $action )
		{
			case 'media_block':
				return $model->MediaBlock($option);
				break;
		}
	}

	function Action($action = '', $event = '')
	{
		
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		switch ( $action )
		{
			default:
				return $model->MediaCatView();
			break;
		}
	}

}

?>
