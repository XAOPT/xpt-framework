<?php
class AdmcontUsers
{
	var $tpl;
	static $name = 'users';

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);
	}

	function Action($action = '')
	{
		if (!$this->checkAccess($action))
			return get_warning_html('Ошибка доступа', 'error');

		$arAction = array('save', 'delete','save_group','delete_group');

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
			case 'save_group':
				return $model->SaveUserGroup();
			case 'delete_group':
				return $model->DeleteUserGroup();
			case 'save':
				return $model->SaveUser();
			case 'delete':
				return $model->DeleteUser();
			/* view */
			case 'add_group':
			case 'edit_group':
				return $model->EditGroup();
			case 'groups_list':
				return $model->UserGroupsList();
			case 'add':
			case 'edit':
				return $model->ViewUserEdit();
			case 'list':
			default:
				return $model->ViewUsersList();
		}
	}

	private function checkAccess($action = '')
	{
		if (guser::_isAdmin())
			return true;

		if (guser::_hasAccess(self::$name, 'edit'))
			$available_methods = array_merge($available_methods, array('','list','save', 'edit', 'add'));
		if (guser::_hasAccess(self::$name, 'delete'))
			$available_methods = array_merge($available_methods, array('delete'));

		if (in_array($action, $available_methods)) return true;

		return false;
	}
}

?>