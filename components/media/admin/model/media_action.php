<?php
class MediaAction extends AdmcontMedia
{
	function __construct()
	{
		parent::__construct();
	}

	function CatSave()
	{
		global $sql, $available_locale;

		$id = zReq::getVar('id', 'INT', 'POST', 0);

		if (!$id) {
			$id = $sql->SetQuery("SELECT MAX(id) FROM `media_cats`")->LoadSingle();

			$id = !$id?1:$id+1;
		}

		if ($id) {
			foreach ($available_locale as $locale) {
				$data = array(
					'cat_title' => zReq::getVar('cat_title_'.$locale, 'SQL', 'POST', ''),
					'descr'      => zReq::getVar('descr_'.$locale, 'SQL', 'POST', '')
				);

				if (!$data['cat_title'])
					return get_warning_html('Не задано имя категории', 'error', $_SERVER['HTTP_REFERER']);

				$sql->SetQuery("
					INSERT INTO `media_cats`  (id, lang, cat_title, descr)
					VALUES ({$id}, '{$locale}', '{$data['cat_title']}', '{$data['descr']}')
					ON DUPLICATE KEY UPDATE
					cat_title='{$data['cat_title']}', descr='{$data['descr']}'
				");
			}
		}

		return get_warning_html('Изменения сохранены', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
	}

	function CatDelete()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'GET', 0);

		if (!$id)
			return get_warning_html('Ошибка удаления категории', 'error', $_SERVER['HTTP_REFERER']);

		$sql->SetQuery("DELETE FROM `media_cats` WHERE id='{$id}'");
		$sql->SetQuery("DELETE FROM `media_items` WHERE `cat_id`='{$id}' AND `type`='youtube'");

		return get_warning_html('Категория удалена. Все страницы из категории перенесены в корзину', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
	}

	function Save()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'POST', 0);

		$data = array(
			'type' => zReq::getVar('type', 'SQL', 'POST', 'youtube'),
			'cat_id' => zReq::getVar('cat_id', 'INT', 'POST', 0)
		);

		if (!$data['cat_id'])
			return get_warning_html('Не выбрана категория', 'error', $_SERVER['HTTP_REFERER']);

		if ($data['type'] == 'youtube') {
			$data['source'] = zReq::getVar('source', 'SQL', 'POST', '');

			require_once(ROOT_PATH."/lib/class.parse_video_link.php");
			$parserClass = new ParseVideoLink();

			$parserClass->link = $data['source'];
			$video_params = $parserClass->GetParams();

			if (!isset($video_params['source_id']))
				return get_warning_html('Не удалось распознать ссылку на ютуб', 'error', $_SERVER['HTTP_REFERER']);

			$data['source'] = $video_params['source_id'];
		}
		else if ($data['type'] == 'image') {
			if (!empty($_FILES['img']['size']))
			{
				$data['source'] = ClassUpload::Image($_FILES['img'], self::$config['thumb_width'], self::$config['thumb_height'], 'media/thumbs', 'strict');
				ClassUpload::Image($_FILES['img'], 0, 0, 'media', 'strict', $data['source']);
			}
		}

		if ($id) {
			if ($data['type'] == 'image' && isset($data['source'])) {
				$old_source = $sql->SetQuery("SELECT source FROM `media_items` WHERE id={$id}")->LoadSingle();

				if (file_exists(ROOT_PATH."/uploads/media/".$old_source))
					unlink(ROOT_PATH."/uploads/media/".$old_source);
				if (file_exists(ROOT_PATH."/uploads/media/thumbs/".$old_source))
					unlink(ROOT_PATH."/uploads/media/thumbs/".$old_source);
			}

			$sql->UpdateArray('media_items', $data, "id={$id}");
		}
		else {
			$sql->InsertArray('media_items', $data);
		}

		return get_warning_html('Документ сохранён', 'ok', DOMAIN.'/admin/'.self::$name.'/cats/');
	}

	function DeleteItem()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'GET', 0);

		if (!$id)
			return get_warning_html('Ошибка', 'error', DOMAIN.'/admin/'.self::$name.'/cats/');

		$item = $sql->SetQuery("SELECT * FROM `media_items` WHERE id='{$id}'")->LoadRow();

		if ($item['type'] == 'image') {
			if (file_exists(ROOT_PATH."/uploads/media/".$item['source']))
				unlink(ROOT_PATH."/uploads/media/".$item['source']);
			if (file_exists(ROOT_PATH."/uploads/media/thumbs/".$item['source']))
				unlink(ROOT_PATH."/uploads/media/thumbs/".$item['source']);
		}

		$sql->SetQuery("DELETE FROM `media_items` WHERE id='{$id}'");

		return get_warning_html('Документ удалён', 'ok', DOMAIN.'/admin/'.self::$name.'/list/?id='.$item['cat_id']);
	}
}

?>