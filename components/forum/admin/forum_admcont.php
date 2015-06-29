<?php
class AdmcontForum
{
	var $tpl;
	static $name = 'forum';
	
	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);	
    }
     
    function Action($action = '')
    {
		$arAction = array('catsave','catdel');
		
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
			case 'catdel':
				return $model->CatDelete();
				break;			
			case 'catsave':
				return $model->CatSave();
				break;			
			## view	
			case 'editcat':
				return $model->ViewCatEdit();
				break;						
			default:			
				return $model->ViewCatsList();
				break;					
        }
    }
}

?>