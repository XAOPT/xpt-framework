<?php
class LoginAction extends ContLogin
{
	function __construct()
	{
		parent::__construct();
    }
	function SteamAuth(){
		global $api,$sql;
		setcookie('sessid', "",  -10, "/");
    setcookie('userid', "", -10, "/");
		if(isset($_SERVER['HTTP_REFERER']) and !isset($_SESSION["referal_auth"]))
      $_SESSION["referal_auth"] = $_SERVER['HTTP_REFERER'];
    $OpenId = new LightOpenId(DOMAIN."/sauth/");    
		if(!$OpenId->mode){
		 	$OpenId->identity = "http://steamcommunity.com/openid";		
      header("Location: {$OpenId->authUrl()}");
    }
    elseif($OpenId->mode == "cancel"){
    	header("Location:".DOMAIN);
    }
    else{
    	if(!isset($_SESSION["SteamAuthq"])){
        $_SESSION["SteamAuthq"] = $OpenId->validate() ? $OpenId->identity : null;
        $_SESSION["SteamAuthID64"] = str_replace("http://steamcommunity.com/openid/id/", "", $_SESSION["SteamAuthq"]);
    	}
    	if($_SESSION["SteamAuthq"] !== null){
	      $Steam64 = str_replace("http://steamcommunity.com/openid/id/", "", $_SESSION["SteamAuthq"]);
	      $profile = json_decode(file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$api}&steamids={$Steam64}")) ;
	      $sql->setQuery("INSERT INTO `users` (`steamid`,`login`,`avatar`,`joined`) 
	      								VALUES ('{$profile->response->players[0]->steamid}' , '{$profile->response->players[0]->personaname}' , '{$profile->response->players[0]->avatarfull}' , NOW()) 
	      								ON DUPLICATE KEY UPDATE `login` = '{$profile->response->players[0]->personaname}', `avatar` = '{$profile->response->players[0]->avatarfull}' ");
	      $userid = $sql->InsertId();
	      $sql->SetQuery("INSERT IGNORE INTO `users_stat` (`userid`) VALUES ({$userid})");
	      mysql_errno();
	      mysql_error();
	      if(isset($_SESSION["referal_auth"])){

	      	$ref = $_SESSION["referal_auth"];
	      	unset($_SESSION["referal_auth"]);
	      	header("Location:".$ref);
	      }
	      else{
	      	header("Location:".DOMAIN);
	      }
      }
    }
	}
	function SteamLogout(){
		unset($_SESSION["SteamAuthq"]);
		unset($_SESSION["SteamAuthID64"]);
		    setcookie('sessid', "",  -10, "/");
        setcookie('userid', "", -10, "/");
		
		header('Location: '.DOMAIN);
		exit;
	}
	function RegUser()
	{
		global $sql;
		
		$data = array(
			'username'  => zReq::getVar('new_username', 'NOHTML_SQL', 'POST'),
			'email'     => zReq::getVar('uem', 'SQL', 'POST'),
			'password'  => zReq::getVar('new_uzxcpd', 'SQL', 'POST'),
			'password2' => zReq::getVar('new_uzxcpd2', 'SQL', 'POST')
		);
		
		session_start();
		if (isset($_SESSION["captcha"]) && $_SESSION["captcha"]===$_POST["captcha"])
		{}
		else
		{
            $data['error'] = 'Код введён не верно';
			return $this->RegError($data);
		}
		unset($_SESSION["captcha"]);
		
        if ($usernameError = $this->checkUsername($data['username']))
        {
			$data['error'] = "$usernameError";
			return $this->RegError($data);
        }
		
        if($data['password'] != $data['password2'])
		{
            $data['error'] = 'Пароли не совпадают';
			return $this->RegError($data);
		}

        if (strlen($data['password'])<6)
		{
			$data['error'] = 'Пароль должен состоять минимум из 6 символов';
			return $this->RegError($data);
		}

        $data['password'] = md5($data['password']);
     
        
        $data['email'] = $this->check_email($data['email']);
        if (!$data['email'])
		{
			$data['error'] = 'Введен несуществующий e-mail адрес';
			return $this->RegError($data);
		}

		$sql->SetQuery("SELECT `userid` FROM `users` WHERE `email`='{$data['email']}' OR LOWER(login)='".strtolower($data['username'])."'");        
        $user_exist = $sql->LoadSingle();        

        if ($user_exist)
        {
            $data['error'] = 'Указаные имя пользователя или e-mail уже использовались для регистрации';	
			return $this->RegError($data);
        }
		
        return $this->InsertHuman($data);
	}
	
	function RegError(&$data)
	{
		load_model(self::$name, 'view');
		$model_name = ucfirst(self::$name).ucfirst('view');
		$model = new $model_name;
		return $model->ViewRegForm($data);
	}
	
    function InsertHuman(&$data)
	{		
		global $sql;       
        
		$query = "INSERT INTO `users` (`login`,`password`,`email`, `joined`)
                  VALUES ('{$data['username']}','{$data['password']}','{$data['email']}', NOW()) ";
        $sql->SetQuery($query);
		
		## USER PROFILE STAT
		$userid = $sql->InsertId();
		
		
		$sql->InsertArray('users_stat', $data);

		return $this->tpl->draw( "reg_ok" );
    }	
	
	function checkUsername($username = '')
	{
		$forbidden_names = array('administrator','guest');
		
		//if ( preg_match('!^[^a-zA-Z0-9]+$!', $username) == 1 )
		//	return "Имя пользователя должно состоять из английских букв и цифр";   		
		
		if ( preg_match('/[^A-Za-z0-9\_\-\.]/', $username) == 1 )
			return "Имя пользователя должно состоять из английских букв и цифр";                

		if (empty($username))    
			return "Не введено имя пользователя";
		
		if (mb_strlen($username, 'UTF-8') < 3)    
			return "Имя пользователя должно содержать минимум 3 символа";
		
		if (mb_strlen($username, 'UTF-8') > 20)    
			return "Длина имени пользователя ограничена 20-ю символами";    
		
		if (in_array(strtolower($username),$forbidden_names))
			return "Выбранное имя пользователя запрещено к использованию";
	}

	function check_email($email = "")
	{
		$email = trim($email);
		$email = str_replace( " ", "", $email );
		$email = preg_replace( "#[\;\#\n\r\*\'\"<>&\%\!\(\)\{\}\[\]\?\\/\s]#", "", $email );

		if ( preg_match( "/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,4})(\]?)$/", $email) )
			return $email;
		else
			return FALSE;
	}
	
	function Logout()
	{
		global $gUserid, $gAlogin, $sql;
		
        setcookie('sessid', "",  -10, "/");
        setcookie('userid', "", -10, "/");
		
		header('Location: '.DOMAIN);
		exit;
	}
	
	function SendMail()
	{
		global $sql, $rewrite;
		
		$component = $rewrite->component;
		
		$events = array();
		
		$to    = zReq::getVar('email', 'STRING', 'POST');
		$email = zReq::getVar('email', 'SQL', 'POST');
		
		
		$login = $sql->SetQuery("SELECT login FROM users WHERE email = '{$email}'", 'LoadSingle');
		
		if(!$login || empty($email))
		{
			header("Location: ".DOMAIN."/".$component."/rememberpass/?events=nomail");
			exit();
		}
		else
		{
			$newpass    = $this->GeneratePass(8);
			
			$newpassmd5 = md5($newpass);
			
			$subject    = "Восстановление пароля на ".DOMAIN;
			$message    = '<b>Здравствуйте, '.$login.'!</b><br><br>
						   Для вас был сгенерирован новый пароль: <b>'.$newpass.'</b><br><br>
						   Вы можете изменить его после авторизации в своем профиле на сайте <a href="'.DOMAIN.'">'.DOMAIN.'</a>';
						
			$mail       = new Mail();
			$mail->send($message, $to, $subject, MAIL);
			
			$events = $mail->events;
			
			if(!$events)
			{
				$sql->SetQuery("UPDATE users SET password = '{$newpassmd5}' WHERE email = '{$email}'");
				
				header("Location: ".DOMAIN."/auth/?p=mail");
				exit();
			}
			else
			{
				$ev = '';
						
				$cn = count($events);
				
				for($i = 0; $i < $cn; $i++)
				{
					if($i == $cn - 1 )
						$r = "";
					else
						$r = ",";
						
					$ev .= $this->events[$i].$r;
					
				}
				
				header("Location: ".DOMAIN."/".$component."/rememberpass/?events=".$ev);
				exit();
			
			}
			
		}
		
	}
	
	function GeneratePass($number)
	{
		$symbols = array('a','b','c','d','e','f',
						 'g','h','i','j','k','l',
						 'm','n','o','p','r','s',
						 't','u','v','x','y','z',
						 'A','B','C','D','E','F',
						 'G','H','I','J','K','L',
						 'M','N','O','P','R','S',
						 'T','U','V','X','Y','Z',
						 '1','2','3','4','5','6',
						 '7','8','9','0','-','_'
						 );
   
		// Генерируем пароль
		$pass = "";
		for($i = 0; $i < $number; $i++)
		{
		     // Вычисляем случайный индекс массива
		     $index = rand(0, count($symbols) - 1);
		     $pass .= $symbols[$index];
		}
		
		return $pass;
	}
}

?>
