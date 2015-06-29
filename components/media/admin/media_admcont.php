<?php
class AdmcontMedia
{
	var $tpl;
	static $name = 'media';

	static $config = array(
		'thumb_width'    => 240,
		'thumb_height'   => 180
	);

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);	## объект шаблона
	}

	function Action($action = '')
	{
		$arAction = array('save','del','catsave','catdel');

		if (in_array($action, $arAction))
			$prefix = 'action';
		else
			$prefix = 'view';

		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);

		$model = new $model_name;

		switch ( $action )
		{
			/* action */
			case 'save':
				return $model->Save();
			case 'del':
				return $model->DeleteItem();
			case 'catdel':
				return $model->CatDelete();
			case 'catsave':
				return $model->CatSave();
			/* view */
			case 'edit':
				return $model->Edit();
			case 'catedit':
				return $model->CatEdit();
			case 'list':
				return $model->listItems();
			case 'cats':
			default:
				return $model->CatsList();
		}
	}
}

?>
