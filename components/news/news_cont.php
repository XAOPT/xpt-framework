<?php
class ContNews
{
	var $tpl;
	static $name = 'news';

	static $config = array(
		'coms_enabled' => false, // подключать ли комментарии к новостям
		'per_page'     => 20    // новостей на странице
	);

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
			case 'news_block':
				return $model->NewsBlock($option);
				break;
		}
	}

	function Action($action = '')
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		if (!$action)
		{
			return $model->ViewNewsList();
		}
		else {
			switch ( $action )
			{
				case 'view':
					return $model->ViewFullNews();
				case 'list':
					return $model->ViewNewsList();
				default:
					return $model->FullView($action);
			}
		}
	}
}

?>
