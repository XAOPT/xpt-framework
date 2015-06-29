<?php
class AdmcontNews
{
	var $tpl;
	static $name = 'news';

	static $config = array(
		'per_page'       => 30,    ## новостей на странице
		'cover'          => true,  ## картинка к новостям
		'editable_cover' => true,  ## разрешать ли выбор области на картинке новости? только при editable_cover === true
		'multi_files'    => false, ## загрузка нескольких фотографий к новости
		'enable_cats'    => true,  ## поддержка разделения новостей на категории
		'cover_width'    => 220,
		'cover_height'   => 140,
		'coms_enabled'   => false
	);

	function __construct()
	{
		global $available_locale;

		if (!isset($available_locale) && !count($available_locale) && !defined('DEFAULT_LOCALE')) {
			echo "locale is not defined. check configuration";
			exit;
		}

		$this->tpl = new RainTPL(self::$name);	## объект шаблона

		$this->tpl->assign("LANG", $GLOBALS['lang']);
	}

	function Ajax($action = '')
	{
		if (!guser::_hasAccess(self::$name))
			return 'Ошибка доступа';

		if (!self::$config['editable_cover'] || !self::$config['cover'])
			return;

		load_model(self::$name, 'ajax');
		$model_name = ucfirst(self::$name).ucfirst('ajax');

		$model = new $model_name;

		switch ( $action )
		{
			case 'file_preview':
				return $model->UploadTempPic();
		}
	}

	function Action($action = '')
	{
		global $gUser;

		if (!guser::_hasAccess(self::$name) && !$this->checkAccess($action))
			return get_warning_html('Ошибка доступа', 'error');

		$arAction = array('save', 'delete', 'publish', 'catsave','catdel','file_preview');

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
			/*
			case 'delete_photo':
				return $model->DeletePhoto();
			case 'batch_upload':
				return $model->BatchUpload();
				*/
			case 'file_preview':
				return $model->UploadTempPic();
			case 'catsave':
				return $model->CatSave();
			case 'catdel':
				return $model->CatDelete();
			case 'publish':
				return $model->DoPublish();
			case 'delete':
				return $model->DeleteNews();
			case 'save':
				return $model->SaveNews();
			## view
			case 'catlist':
				return $model->ViewCatList();
				/*
			case 'batch':
				return $model->ViewBatchUpload();
			case 'photo_list':
				return $model->ViewPhotoList();*/
			case 'add':
			case 'edit':
				return $model->ViewEditNews();
			case 'editcat':
				return $model->ViewCatEdit();
			case 'cats':
				return $model->ViewCatsList();
			case 'list':
			default:
				return $model->ViewNewsList();
		}
	}

	private function checkAccess($action = '')
	{
		$available_methods = array();

		if (guser::_hasAccess(self::$name, 'edit_my') || guser::_hasAccess(self::$name, 'edit_all'))
			$available_methods += array('', 'list', 'edit', 'add', 'save');
		if (guser::_hasAccess(self::$name, 'publish'))
			$available_methods += array('publish');
		if (guser::_hasAccess(self::$name, 'delete'))
			$available_methods += array('delete');

		if (in_array($action, $available_methods)) return true;

		return false;
	}
}

?>
