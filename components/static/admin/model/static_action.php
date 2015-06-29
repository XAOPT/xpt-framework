<?php
class StaticAction extends AdmcontStatic
{
	function __construct()
	{
		parent::__construct();
    }

    function CatSave()
    {
		global $sql, $langs;

		$cat_id    = zReq::getVar('cat_id', 'INT', 'POST');
		$cat_title = zReq::getVar('cat_title', 'SQL', 'POST');
		$desc      = zReq::getVar('desc', 'SQL', 'POST');

		## работа с языками
		$data = array();
		if (isset($langs))
		{
			foreach ($langs as $k => $v)
			{
				$data[$k] = array(
					'cat_title' => zReq::getVar("{$k}_cat_title", 'SQL', 'POST', ''),
					'desc'      => zReq::getVar("{$k}_desc", 'SQL', 'POST', '')
				);
			}
		}
		##

		if (!$cat_title)
			return get_warning_html('Не указано имя категории', 'error', DOMAIN.'/admin/'.self::$name.'/cats/');

		if ($cat_id)
		{
			$sql->SetQuery("UPDATE `static_cats` SET `cat_title`='{$cat_title}', `desc`='{$desc}' WHERE cat_id='{$cat_id}'");

			## работа с языками
			if (isset($langs))
			{
				foreach ($langs as $k => $v)
				{
					$sql->UpdateArray("{$k}_static_cats", $data[$k], "cat_id='{$cat_id}'");
				}
			}
			##

			return get_warning_html('Категория обновлена', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
		}
		else
		{
			$sql->SetQuery("INSERT INTO `static_cats` (`cat_title`, `desc`) VALUES ('{$cat_title}', '{$desc}')");

			## работа с языками
			if (isset($langs))
			{
				$id = $sql->InsertId();
				foreach ($langs as $k => $v)
				{
					$data[$k]['cat_id'] = $id;
					$sql->InsertArray("{$k}_static_cats", $data[$k]);
				}
			}
			##

			return get_warning_html('Категория добавлена', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
		}
    }

	function CatDelete()
	{
		global $sql;

		$cat_id = zReq::getVar('cat_id', 'INT', 'GET');

		if (!$cat_id)
			return get_warning_html('Ошибка удаления категории', 'error');

		$sql->SetQuery("DELETE FROM `static_cats` WHERE cat_id='{$cat_id}'");
		$sql->SetQuery("UPDATE `static_pages` SET `cat_id`='0' WHERE cat_id='{$cat_id}'");

		return get_warning_html('Категория удалена. Все страницы из категории перенесены в корзину', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
	}


	function PageSave()
	{
		global $sql, $langs, $available_locale;

        $id = zReq::getVar( 'id', 'INT', 'POST');
		$turn = isset($_POST['turn']);

		$data = array (
			'cat_id' => zReq::getVar( 'cat_id', 'INT', 'POST')
		);

		if (!$_POST['title_'.DEFAULT_LOCALE])
			return get_warning_html('Вы не указали заголовок страницы', 'error', $_SERVER['HTTP_REFERER']);

		$data['alias'] = Translit::TranslitString($_POST['title_'.DEFAULT_LOCALE]);


		if ($id)
		{
			$sql->UpdateArray('static_pages', $data, "id='{$id}'");

			## работа с языками
			foreach ($available_locale as $locale) {
				$data = array(
					'id'          => $id,
					'lang'        => $locale,
					'title'       => zReq::getVar( 'title_'.$locale, 'SQL', 'POST'),
					'html'        => zReq::getVar( 'html_'.$locale, 'SQL', 'POST'),
					'description' => zReq::getVar( 'description_'.$locale, 'SQL', 'POST'),
					'keywords'    => zReq::getVar( 'keywords_'.$locale, 'SQL', 'POST')
				);

				$sql->SetQuery("
					INSERT INTO `static_text`  (id, lang, title, html, description, keywords)
					VALUES ({$id}, '{$locale}', '{$data['title']}', '{$data['html']}', '{$data['description']}', '{$data['keywords']}')
					ON DUPLICATE KEY UPDATE
					title='{$data['title']}', html='{$data['html']}', `description`='{$data['description']}', `keywords`='{$data['keywords']}'
				");
			}
			##

			if($turn)
			{
				header("Location: ".DOMAIN.'/admin/static/edit/?id='.$id);
				exit();
			}
			else
				return get_warning_html('Страница отредактирована', 'ok', DOMAIN.'/admin/'.self::$name.'/');
		}
		else
		{
			$sql->InsertArray('static_pages', $data);

			$id = $sql->InsertId();

			## работа с языками
			foreach ($available_locale as $locale) {
				$data = array(
					'id'          => $id,
					'lang'        => $locale,
					'title'       => zReq::getVar( 'title_'.$locale, 'SQL', 'POST'),
					'html'        => zReq::getVar( 'html_'.$locale, 'SQL', 'POST'),
					'description' => zReq::getVar( 'description_'.$locale, 'SQL', 'POST'),
					'keywords'    => zReq::getVar( 'keywords_'.$locale, 'SQL', 'POST')
				);

				$sql->InsertArray('static_text', $data);
			}
			##

			return get_warning_html('Страница создана', 'ok', DOMAIN.'/admin/'.self::$name.'/');
		}
	}

	function PageDelete()
	{
		global $sql;

		$id = zReq::getVar( 'id', 'INT', 'GET');

		if (!$id)
			return get_warning_html('Ошибка удаления', 'error', DOMAIN.'/admin/'.self::$name.'/');

		$sql->SetQuery("DELETE FROM `static_pages` WHERE id='{$id}'");
		$sql->SetQuery("DELETE FROM `static_text` WHERE id='{$id}'");

		return get_warning_html('Страница удалена', 'ok', DOMAIN.'/admin/'.self::$name.'/');
	}

	function DoPublish()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'GET');

		if (!$id)
			return get_warning_html('Ошибка публикации', 'error', DOMAIN.'/admin/'.self::$name.'/');

		$sql->SetQuery("UPDATE `static_pages` SET `published`=IF(published=1,0,1) WHERE id='{$id}'");

		return get_warning_html('Выполнено', 'ok', DOMAIN.'/admin/'.self::$name."/");
	}
}

?>