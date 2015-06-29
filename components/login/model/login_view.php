<?php
class LoginView extends ContLogin
{
	function __construct()
	{
		parent::__construct();
    }
	
	function ViewAdminLogin()
	{
		$this->tpl->draw( "admin_login", false );
		exit;
	}
     
    function ViewLoginButton()
    {	
		global $gUser, $gAsuserid;

		$this->tpl->assign( "gUser",       $gUser );
		$this->tpl->assign( "gAsuserid",   $gAsuserid );
		
		$this->tpl->draw( "login_button" ); 
    }

	function ViewRegForm($data = '')
	{
		global $gUser;
		
		$this->tpl->assign( "gUser", $gUser );
		$this->tpl->assign( "data", $data );
		return $this->tpl->draw( "reg_form" );
	}	
	
	function ViewAuthForm()
	{
		global $gUser;

		$p   = zReq::getVar('p', 'SQL', 'GET', '');
		$ref = zReq::getVar('ref', 'SQL', 'POST', '');
		
		if (!$ref && isset($_SERVER['HTTP_REFERER']))
			$ref = $_SERVER['HTTP_REFERER'];
		else
			$ref = DOMAIN;
			
		if (preg_match("/sauth/", $ref))
			$ref = DOMAIN;
			
		if ($p == 'error')
			$this->tpl->assign( "wrong_pass", true );
		else if ($p == 'mail')
			$this->tpl->assign( "mail", true );		
		
		$this->tpl->assign( "gUser", $gUser );
		$this->tpl->assign( "ref", $ref );
		return $this->tpl->draw( "auth_form" );
	}
	
	function ViewRemember()
	{
		$GetEvents        = zReq::getVar('events', 'STRING', 'GET');
		$GetEvents        = preg_replace("/\,$/", '', $GetEvents);
		
		$ArrayEvents = $ArrayEventsNum = array();
				
		if($GetEvents)
		{
			$ArrayEventsNum = explode(",", $GetEvents);
						
			foreach($ArrayEventsNum as $v)
			{
				if($this->eventsList[$v])
					$ArrayEvents[] = $this->eventsList[$v];
			}
												
		}
		
		$this->tpl->assign( "events", $ArrayEvents );
	
		return $this->tpl->draw( "remember_pass" );
	}
}

?>
