<?php
class AdmcontStatic
{
	var $tpl;
	static $name = 'static';

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);
	}

	function Action($action = '')
	{
		if (!guser::_isAdmin())
			return get_warning_html('Ошибка доступа', 'error');

		$arAction = array('catsave','pagesave','delete','catdel','publish');

		if (in_array($action, $arAction))
			$prefix = 'action';
		else
			$prefix = 'view';

		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);

		$model = new $model_name;

		switch ( $action )
		{
			## action
			case 'publish':
				return $model->DoPublish();
				break;
			case 'catdel':
				return $model->CatDelete();
				break;
			case 'catsave':
				return $model->CatSave();
				break;
			case 'pagesave':
				return $model->PageSave();
				break;
			case 'delete':
				return $model->PageDelete();
				break;
			## view
			case 'trashbin':
				return $model->ViewPagesList(0);
				break;
			case 'edit':
				return $model->PageEdit();
				break;
			case 'editcat':
				return $model->ViewCatEdit();
				break;
			case 'cats':
				return $model->ViewCatsList();
			case 'pages':
			default:
				return $model->ViewPagesList();
				break;
		}
	}
}

?>
