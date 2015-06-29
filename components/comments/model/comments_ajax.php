<?php
class CommentsAjax extends ContComments
{
	function __construct()
	{
		parent::__construct();
	}
	
	function DeleteComment()
	{
		global $sql;
		
		if (!guser::_isAdmin())
			return 'Ошибка доступа';
			
		$comm_id = zReq::getVar('id', 'INT', 'POST', 0);
		$text = 'Комментарий удалён модератором';
		
		$childes = $sql->SetQuery("SELECT COUNT(*) FROM `comments` WHERE parent='{$comm_id}'", 'LoadSingle');

		if ($childes)
		{
			$sql->SetQuery("UPDATE `comments` SET text='{$text}' WHERE comm_id='{$comm_id}'");
			return 1;
		}
		else
		{
			$params = $sql->SetQuery("SELECT component, param FROM `comments` WHERE comm_id='{$comm_id}'", 'LoadRow');
			
			switch ($params['component'])
			{
				case 'video':
					$sql->SetQuery("UPDATE `video` SET comments=comments-1 WHERE v_id='{$params['param']}'", 'LoadRow');
					break;
				case 'news':
					$sql->SetQuery("UPDATE `news` SET coms=coms-1 WHERE news_id='{$params['param']}'", 'LoadRow');
					break;
			}

			$sql->SetQuery("DELETE FROM `comments` WHERE comm_id='{$comm_id}'");	
			$sql->SetQuery("DELETE FROM `comments_rate` WHERE c_id='{$comm_id}'");	
			
			return 2;
		}
	}	
	
	function AddComment()
	{
		global $sql, $gUserid, $gUser;
		
		$data = array(
			'param'     => zReq::getVar('param', 'SQL', 'POST'),
			'parent'    => zReq::getVar('parent', 'INT', 'POST', 0),
			'component' => zReq::getVar('component', 'SQL', 'POST'),
			'user_id'   => $gUserid,
			'text'      => zReq::getVar('comment', 'NOHTML_SQL', 'POST'),
			'utime'     => TIME
		);
		
		if ($data['text'] == 'Ваш комментарий' || mb_strlen($data['text'], 'UTF-8') < 2)
			return 1;
		
		if (!$gUserid)
			return 2;
		
		if (!$data['component'] || !$data['param'])
			return 3;
			
		$sql->InsertArray('comments', $data);
		
		$comm_id = $sql->InsertId();
		
		// custom
		if ($data['component'] == 'video')		
			$sql->SetQuery("UPDATE `video` SET comments=comments+1 WHERE v_id='{$data['param']}'");
		else if ($data['component'] == 'news')
			$sql->SetQuery("UPDATE `news` SET coms=coms+1 WHERE news_id='{$data['param']}'");		
		else if ($data['component'] == 'replays')
			$sql->SetQuery("UPDATE `replays` SET coms=coms+1 WHERE r_id='{$data['param']}'");
		
		$reply = array(
			'karma'  => $gUser['karma'],
			'avatar' => $gUser['avatar'],
			'login'  => $gUser['login'],
			'text'   => $data['text'],
			'comm_id' => $comm_id
		);
		
		$reply = json_encode($reply);
		
		return $reply;
	}
     
	function UpdateRating()
	{
		global $sql, $gUserid, $gUser;
		
		$c_id = zReq::getVar('c_id', 'INT', 'POST', 0);
		$dir  = zReq::getVar('dir', 'SQL', 'POST', '');
		
		if (empty($gUserid))
			return 'nologin';
		
		## если карма меньше нужного
		if ($gUser['karma'] < 0)
			return 'karmaerror';
		
		## если голосует за сам себя
		$sql->SetQuery("SELECT user_id FROM `comments` WHERE comm_id='{$c_id}'");
		$сom_user = $sql->LoadSingle();
		if ($сom_user == $gUserid)		
			return 'error1';
		
		## если уже голосовал
		$sql->SetQuery("SELECT * FROM `comments_rate` WHERE c_id='{$c_id}' AND userid='{$gUserid}'");		
		if (!$c_id || $sql->NumRows())
		{
			return 'error';
		}		
		
		$inc = '+0';
		
		if ($dir == 'up')
			$inc = '+1';
		else
			$inc = '-1';
			
		$sql->SetQuery("UPDATE `users_stat` SET venom=venom{$inc} WHERE userid='{$gUserid}'"); ## Следим за злобностью пользователя
		
		$sql->SetQuery("UPDATE `comments` SET rating=rating{$inc} WHERE comm_id='{$c_id}'");
		
		$time = TIME;
		$sql->SetQuery("INSERT INTO `comments_rate` (c_id,userid,utime) VALUES ('{$c_id}','{$gUserid}','{$time}')");
		
		require_once(ROOT_PATH."/lib/class.userstat.php");
		## обновляем карму
		$stat = new ClassUserstat();
		$stat->userid = $сom_user;
		if ($dir == 'up')
			$stat->UpdateCommentRate(1);
		else
			$stat->UpdateCommentRate(0);		
		
		
		return "ok";
	}
}

?>
