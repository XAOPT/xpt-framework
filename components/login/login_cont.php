<?php
class ContLogin
{
	var $tpl;
	static $name = 'login';

	function __construct()
	{
		$this->tpl = new RainTPL(self::$name);

		require_once(ROOT_PATH."/lib/class.mail.php");

		$this->eventsList['nomail'] = "Данная почта не зарегестрирована на сайте!<br />Пожалуйста, укажите Ваш e-mail, введённый при регистрации.";
		$this->eventsList['ok']     = "Для восстановления пароля следуйте инструкциям, отправленным на Вашу почту.";
		$this->eventsList[0]        = "Письмо не было отправлено, попробуйте снова или обратитесь к администрации портала.";
		$this->eventsList[1]        = "E-mail адресата письма недействительный, обратитесь к администрации портала.";
		$this->eventsList[2]        = "E-mail отправителя письма недействительный, обратитесь к администрации портала.";
		$this->eventsList[3]        = "Письмо не было отправлено, попробуйте снова или обратитесь к администрации портала.";
    }

	function ActionModule($action, &$options = array())
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');

		$model = new $model_name;

		switch ( $action )
		{
			case 'login_button':
				return $model->ViewLoginButton();
				break;
        }
	}

    function Action($action = '')
    {
		$arAction = array('reg', 'out', 'sendmail','steamauth','slogout');

		if (in_array($action, $arAction))
			$prefix = 'action';
        else
			$prefix = 'view';

		load_model(self::$name, $prefix);
		$model_name = ucfirst(self::$name).ucfirst($prefix);

		$model = new $model_name;

		switch ( $action )
		{
			case 'steamauth':
				return $model->SteamAuth();
			case 'slogout':
				return $model->SteamLogout();
			case 'out':
				return $model->LogOut();
			case 'reg':
				return $model->RegUser();
			case 'sendmail':
				return $model->SendMail();
			//case 'admin':
			//	return $model->ViewAdminLogin();
			//	break;
			case 'vauth':
				return $model->ViewAuthForm();
			case 'rememberpass':
				return $model->ViewRemember();
			case 'registration':
				return $model->ViewRegForm();
			default:
				return $model->ViewAuthForm();
        }

		error_404();
    }
}

?>
