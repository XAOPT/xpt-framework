<?php
class ForumAction extends AdmcontForum
{
	function __construct()
	{
		parent::__construct();
	}

	function CatSave()
	{
		global $sql, $langs;

		$cat_id = zReq::getVar('cat_id', 'INT', 'POST');


		$data = array(
			'parent'    => zReq::getVar('parent', 'INT', 'POST', 0),
			'cat_title' => zReq::getVar('cat_title', 'SQL', 'POST'),
			'descr'     => zReq::getVar('descr', 'SQL', 'POST', ''),
			'access'     => zReq::getVar('access', 'SQL', 'POST', 0)
		);

		if (!$data['cat_title'])
			return get_warning_html('Не указано имя категории', 'error', DOMAIN.'/admin/'.self::$name.'/cats/');

		if ($cat_id)
		{
			$sql->UpdateArray('forum_cats', $data, "cat_id='{$cat_id}'");

			return get_warning_html('Категория обновлена', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
		}
		else
		{
			$sql->InsertArray('forum_cats', $data);

			return get_warning_html('Категория добавлена', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
		}
	}

	function CatDelete()
	{
		global $sql;

		$cat_id = zReq::getVar('id', 'INT', 'GET');

		if (!$cat_id)
			return get_warning_html('Ошибка удаления категории', 'error');

		$sql->SetQuery("DELETE FROM `forum_cats` WHERE cat_id='{$cat_id}'");

		###!!! что делать с топиками?
		$sql->SetQuery("UPDATE `static_pages` SET `cat_id`='0' WHERE cat_id='{$cat_id}'");

		return get_warning_html('Категория удалена', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
	}
}

?>