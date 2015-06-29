<?php
class CommentsAction extends ContComments
{
	function __construct()
	{
		parent::__construct();
    }
	
	function AddComment()
	{
		global $sql, $gUserid, $gLoginzaUI;
		
		$redirect_url = zReq::getVar('redir', 'SQL', 'POST');

		$data = array(
			'param'     => zReq::getVar('param', 'SQL', 'POST'),
			'parent'    => zReq::getVar('parent', 'INT', 'POST', 0),
			'component' => zReq::getVar('component', 'SQL', 'POST'),
			'user_id'   => $gUserid,
			'text'      => zReq::getVar('comment', 'NOHTML_SQL', 'POST'),
			'utime'     => TIME
		);
		
		if ($data['text'] == 'Ваш комментарий' || mb_strlen($data['text'], 'UTF-8') < 2)
			return get_warning_html('Пустое или слишком короткое сообщение', 'error');
		
		if (!$gUserid && !$gLoginzaUI)
		{
			$html .= 'Вы не авторизованы на сайте. Скопируйте текст своего сообщения и пройдите авторизацию:<br /><br />'.$data['text'];
			return get_warning_html($html, 'error');
		}
		
		if ($gLoginzaUI)
		{
			$data['user_id'] = $gLoginzaUI;
		}
		else
		{
			$data['user_id'] = $gUserid;
		}
		
		if (!$data['component'] || !$data['param'])
		{
			return get_warning_html('Ошибка добавления', 'error');
		}
			
		if (!$data['component'] || !$data['param'] || !$data['text'])
		{
			return get_warning_html('Текст комментария пустой', 'error');
		}
		
		$sql->InsertArray('comments', $data);
		
		// custom
		if ($data['component'] == 'video')		
			$sql->SetQuery("UPDATE `video` SET comments=comments+1 WHERE v_id='{$data['param']}'");
		else if ($data['component'] == 'news')
			$sql->SetQuery("UPDATE `news` SET coms=coms+1 WHERE news_id='{$data['param']}'");		
		else if ($data['component'] == 'replays')
			$sql->SetQuery("UPDATE `replays` SET coms=coms+1 WHERE r_id='{$data['param']}'");
		
		header( 'Location: '.$redirect_url ) ;
		exit;
		
		return get_warning_html('<h1>Комментарий добавлен!</h1>', 'ok', $redirect_url);
	}
	
	function UpdateComment()
	{
		global $sql, $gUserid;
		
		$redirect_url = zReq::getVar('redir', 'SQL', 'POST');
		
		$comm_id = zReq::getVar('comm_id', 'INT', 'POST');
		$text = zReq::getVar('comment', 'NOHTML_SQL', 'POST');
		
		$comment = $sql->SetQuery("SELECT * FROM `comments` WHERE comm_id='{$comm_id}'", 'LoadRow');
		
		if (empty($comment))
			return get_warning_html("Ошибка получения данных", 'error', $redirect_url);
		
		if (!guser::_isAdmin() && ($gUserid != $comment['user_id'] || (TIME - $comment['utime'])>210))
			return get_warning_html("С момента публикации комментария прошло более 3х минут. Исправления невозможны :(", 'error', $redirect_url);		
		
		$sql->SetQuery("UPDATE `comments` SET text='{$text}' WHERE comm_id='{$comm_id}'");
		
		return get_warning_html('<h1>Комментарий отредактирован!</h1>', 'ok', $redirect_url);
	}
	
	function DeleteComment()
	{
		global $sql;
		
		if (!guser::_isAdmin())
			return get_warning_html("Ошибка доступа", 'error', $redirect_url);
			
		$comm_id = zReq::getVar('id', 'INT', 'GET');
		$text = 'Комментарий удалён модератором';
		
		$childes = $sql->SetQuery("SELECT COUNT(*) FROM `comments` WHERE parent='{$comm_id}'", 'LoadSingle');

		if ($childes)
			$sql->SetQuery("UPDATE `comments` SET text='{$text}' WHERE comm_id='{$comm_id}'");
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
		}
		
		return get_warning_html('<h1>Комментарий удалён!</h1>', 'ok');
	}	
}

?>
