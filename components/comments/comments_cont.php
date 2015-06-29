<?php
class ContComments
{
	var $tpl;
	static $name = 'comments';
	
	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);	
		
		$this->tpl->assign("LANG", $GLOBALS['lang']);	
	}
	
	function Ajax($action = '')
	{
		load_model(self::$name, 'ajax');
		$model_name = ucfirst(self::$name).ucfirst('ajax');	
		
		$model = new $model_name;
		
		switch ( $action ) 
		{
			case 'del':
			case 'delete':
				return $model->DeleteComment();		
			case 'add':
				return $model->AddComment();
			case 'rate':
				return $model->UpdateRating();
		}		
	}		
	
	function ActionModule($action, $option)
	{
		load_model(self::$name, 'view');
		$model_name = 'CommentsView';	

		$model = new $model_name;
		
		switch ( $action ) 
		{
			case 'comments_block':
				return $model->CommentsBlock($option);
			case 'view_last_reply':
				return $model->ViewLastReply($option);
		}
	}
     
	function Action($action = '')
	{
		$arAction = array('add', 'del', 'update');
		
		if (in_array($action, $arAction))		
			$prefix = 'action';
 		else
			$prefix = 'view';	
	
		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);	

		$model = new $model_name;
		
		switch ( $action ) 
		{
			case 'add':
				return $model->AddComment();		
			case 'del':
				return $model->DeleteComment();
			case 'update':
				return $model->UpdateComment();
		}	
	
		error_404();
	}
}

?>
