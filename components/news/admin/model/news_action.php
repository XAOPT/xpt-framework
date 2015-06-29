<?php
class NewsAction extends AdmcontNews
{
	function __construct()
	{
		parent::__construct();
	}

	function SaveNews()
	{
		global $sql, $gUserid, $available_locale;

		$data = array(
			'cat_id'    => zReq::getVar('cat_id', 'INT', 'POST', 0)
		);

		$id = zReq::getVar('id', 'INT', 'POST', 0);
		if ($id)
		{
			$sql->SetQuery("SELECT * FROM news WHERE id='{$id}'");
			$news = $sql->LoadRow();

			if ($news['published'] == 1 && !guser::_hasAccess(self::$name, 'edit_all'))
				return get_warning_html('Ошибка доступа', 'error', ADOMIN.'/'.self::$name.'/');
		}

		## ДАТА
		$date = zReq::getVar('date', 'SQL', 'POST', date("d.m.Y", time()));
		$time = zReq::getVar('time', 'SQL', 'POST', date("H:i", time()));

		if (!preg_match("/^\d{2}\:\d{2}$/", $time)) ## верный формат времени - чч:мм
			return get_warning_html($GLOBALS['model']['wrong_time'], 'error');

		if (!preg_match("/^(\d{2})\.(\d{2})\.(\d{4})$/", $date, $matches)) ## верный формат даты - дд:мм:гггг
			return get_warning_html($GLOBALS['model']['wrong_date'], 'error');
		else
			$data['dtime'] = "{$matches[3]}-{$matches[2]}-{$matches[1]} {$time}:00"; ## собираем в формат mysql

		## МИНИПИК
		if (self::$config['cover'] && !self::$config['editable_cover']) ## если нельзя выбирать область обложки
		{
			if (!empty($_FILES['img']['size']))
			{
				$cover = $_FILES['img'];
				if ($cover)
				{
					$data['img'] = ClassUpload::Image($cover, self::$config['cover_width'], self::$config['cover_height'], 'news', 'strict');
				}
			}
		}

		if (self::$config['cover'] && self::$config['editable_cover']) ## если МОЖНО выбирать область обложки
		{
			$file_name = zReq::getVar( 'file_name', 'SQL', 'POST', '');
			$image_source = ROOT_PATH."/uploads/news/temp/".$file_name;

			if ($file_name && file_exists($image_source))
			{
				$options = array(
					'is_local' => 1,
					'x1' => zReq::getVar( 'x1', 'INT', 'POST', 0),
					'y1' => zReq::getVar( 'y1', 'INT', 'POST', 0),
					'x2' => zReq::getVar( 'x2', 'INT', 'POST', 0),
					'y2' => zReq::getVar( 'y2', 'INT', 'POST', 0),
					'quality' => 100
				);

				if ($options['x2'] && $options['y2'])
				{
					$data['img'] = ClassUpload::Image($image_source, self::$config['cover_width'], self::$config['cover_height'], 'news', 'area_strict', $file_name, 0, $options);
				}
				else
				{
					$data['img'] = ClassUpload::Image($image_source, self::$config['cover_width'], self::$config['cover_height'], 'news', 'strict', $file_name, 0, array('is_local' => 1, 'quality' => 100));
				}
			}
		}
		####

		$turn = $_POST['turn'];

		if (!$id)
		{
			$data['alias'] = zReq::getVar( 'alias', 'SQL', 'POST', '');

			/// !!!!

			if (!$_POST['title_'.DEFAULT_LOCALE])
				return get_warning_html($GLOBALS['model']['choose_cat'], 'error', $_SERVER['HTTP_REFERER']);

			if (!$data['alias'])
				$data['alias'] = Translit::TranslitString($_POST['title_'.DEFAULT_LOCALE]);

			$data['userid'] = $gUserid;
			$sql->InsertArray('news', $data);

			//ClassModer::_logAction(self::$name, 'new', $sql->InsertId());

			$id = $sql->InsertId();

			$step    = zReq::getVar('step', 'INT', 'POST', 1);

			foreach ($available_locale as $locale) {
				$data = array(
					'id'        => $id,
					'lang'      => $locale,
					'title'     => zReq::getVar('title_'.$locale, 'SQL', 'POST'),
					'smalltext' => zReq::getVar('smalltext_'.$locale, 'SQL', 'POST'),
					'text'      => zReq::getVar('text_'.$locale, 'SQL', 'POST'),
				);

				if ($locale == DEFAULT_LOCALE && !$data['title'])
					return get_warning_html($GLOBALS['model']['choose_cat'], 'error', $_SERVER['HTTP_REFERER']);

				$sql->InsertArray('news_text', $data);
			}

			## обновляем карму
			/*require_once(ROOT_PATH."/lib/class.userstat.php");
			$stat = new ClassUserstat();
			$stat->userid = $gUserid;
			$stat->UpdateNewsRate();*/

			if ($turn == '1')
			{
				header("Location: ".DOMAIN.'/admin/news/edit/?id='.$id);
				exit();
			}
			else if ($turn == '2') {
				header("Location: ".DOMAIN.'/admin/news/edit2/?id='.$id);
				exit();
			}
			else if ($step == 1 || !$step) {
				return get_warning_html($GLOBALS['model']['news_added'], 'ok', ADOMAIN."/".self::$name."/");
			}
			else if ($step == 2) {
				return get_warning_html($GLOBALS['model']['news_goto_step_two'], 'ok', ADOMAIN."/".self::$name."/batch/?id={$id}");
			}
		}
		else
		{
			$alias = zReq::getVar( 'alias', 'SQL', 'POST', '');

			if ($alias)
				$data['alias'] = $alias;

			## удалим старый минипик по необходимости
			if (!empty($data['img']) && self::$config['cover'])
			{
				$sql->SetQuery("SELECT img FROM `news` WHERE id='{$id}'");
				$filename = $sql->LoadSingle();

				if ($filename)
				{
					unlink(ROOT_PATH."/uploads/news/".$filename);
					unlink(ROOT_PATH."/uploads/news/thumb/".$filename);

					if (self::$config['editable_cover'])
						unlink(ROOT_PATH."/uploads/news/big_cover/".$filename);
				}
			}

			$sql->UpdateArray('news', $data, "id='{$id}'");

			foreach ($available_locale as $locale) {
				$data = array(
					'title'     => zReq::getVar('title_'.$locale, 'SQL', 'POST'),
					'smalltext' => zReq::getVar('smalltext_'.$locale, 'SQL', 'POST'),
					'text'      => zReq::getVar('text_'.$locale, 'SQL', 'POST'),
				);

				if ($locale == DEFAULT_LOCALE && !$data['title'])
					return get_warning_html($GLOBALS['model']['choose_cat'], 'error', $_SERVER['HTTP_REFERER']);

				$sql->SetQuery("
					INSERT INTO `news_text`  (id, lang, title, smalltext, text)
					VALUES ({$id}, '{$locale}', '{$data['title']}', '{$data['smalltext']}', '{$data['text']}')
					ON DUPLICATE KEY UPDATE
					title='{$data['title']}', smalltext='{$data['smalltext']}', `text`='{$data['text']}'
				");
			}

			//ClassModer::_logAction(self::$name, 'edit', $id);

			if($turn == '1')
			{
				header("Location: ".ADOMAIN."/".self::$name."/edit/?id=".$id);
				exit();
			}
			else if ($turn == '2') {
				header("Location: ".ADOMAIN."/".self::$name."/edit2/?id=".$id);
				exit();
			}
			else
				return get_warning_html($GLOBALS['model']['news_edited'], 'ok', ADOMAIN."/".self::$name."/");
		}
	}


	function DeleteNews()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'GET');

		if ($id)
		{
			## удаляем обложку
			if (self::$config['cover'])
			{
				$sql->SetQuery("SELECT img FROM `news` WHERE id='{$id}'");
				$filename = $sql->LoadSingle();

				if ($filename && file_exists(ROOT_PATH."/uploads/news/".$filename))
				{
					unlink(ROOT_PATH."/uploads/news/".$filename);

					if (file_exists(ROOT_PATH."/uploads/news/thumb/".$filename))
						unlink(ROOT_PATH."/uploads/news/thumb/".$filename);
				}
			}

			$sql->SetQuery("DELETE FROM `news` WHERE `id`='{$id}'");
			$sql->SetQuery("DELETE FROM `news_text` WHERE `id`='{$id}'");

			## удаляем комменты если надо
			if (self::$config['coms_enabled'])
				$sql->SetQuery("DELETE FROM `comments` WHERE `component`='news' AND `param`='{$id}'");	 ## удаляем связанные комментарии

			### удаляем фотки
			if (self::$config['multi_files'])
			{
				require_once(ROOT_PATH."/lib/class.files.php");

				$file_cont = new ClassFiles;
				$file_cont->del_folder(ROOT_PATH."/uploads/news/full/{$id}");
				$file_cont->del_folder(ROOT_PATH."/uploads/news/thumb/{$id}");

				$sql->SetQuery("DELETE FROM `news_images` WHERE id='{$id}'");
			}

			//ClassModer::_logAction(self::$name, 'delete', $id);

			return get_warning_html($GLOBALS['model']['news_deleted'], 'ok', $_SERVER['HTTP_REFERER']);
		}
		else
			return get_warning_html($GLOBALS['model']['error'], 'error', $_SERVER['HTTP_REFERER']);
	}


	function DoPublish()
	{
		global $sql;

		$id = zReq::getVar('id', 'INT', 'GET');

		if (!$id)
			return get_warning_html($GLOBALS['model']['error'], 'error', ADOMAIN."/".self::$name."/");

		$sql->SetQuery("UPDATE `news` SET `published`=IF(published=1,0,1) WHERE id='{$id}'");

		//ClassModer::_logAction(self::$name, 'publish', $id);

		return get_warning_html($GLOBALS['model']['ok'], 'ok', $_SERVER['HTTP_REFERER']);
	}

	/*function BatchUpload()
	{
		return get_warning_html($GLOBALS['model']['images_saved'], 'ok', DOMAIN."/admin/news/");
	}

	function DeletePhoto()
	{
		global $sql;

		$image_id = zReq::getVar( 'image_id', 'INT', 'GET');

		$query = "SELECT * FROM `news_images` WHERE id='{$image_id}'";
		$sql->SetQuery($query);
		$image = $sql->LoadRow();

		$file_name = $image['image'];
		$id   = $image['id'];

		unlink(ROOT_PATH."/uploads/news/full/{$id}/".$file_name);
		unlink(ROOT_PATH."/uploads/news/thumb/{$id}/".$file_name);

		$sql->SetQuery("DELETE FROM `news_images` WHERE id='{$image_id}'");

		return get_warning_html($GLOBALS['model']['image_deleted'], 'ok', $_SERVER['HTTP_REFERER']);
	}
*/
	function CatSave()
	{
		global $sql, $langs;

		$cat_id    = zReq::getVar('cat_id', 'INT', 'POST');
		$cat_title = zReq::getVar('cat_title', 'SQL', 'POST');
		$descr     = zReq::getVar('desc', 'SQL', 'POST');

		if (!$cat_title)
			return get_warning_html($GLOBALS['model']['cat_notitle'], 'error');

		if ($cat_id) ## редактируем категорию
		{
			$sql->SetQuery("UPDATE `news_cats` SET `cat_title`='{$cat_title}', `descr`='{$descr}' WHERE cat_id='{$cat_id}'");

			return get_warning_html($GLOBALS['model']['cat_edited'], 'ok', ADOMAIN.'/'.self::$name.'/cats/');
		}
		else ## добавляем категорию
		{
			$sql->SetQuery("INSERT INTO `news_cats` (`cat_title`, `descr` ) VALUES ('{$cat_title}', '{$descr}')");

			return get_warning_html($GLOBALS['model']['cat_added'], 'ok', ADOMAIN.'/'.self::$name.'/cats/');
		}
	}

	function CatDelete()
	{
		global $sql;

		$cat_id = zReq::getVar('cat_id', 'INT', 'GET');

		if (!$cat_id)
			return get_warning_html($GLOBALS['model']['error'], 'error', ADOMAIN.'/'.self::$name.'/cats/');

		## удаляем категорию
		$sql->SetQuery("DELETE FROM `news_cats` WHERE cat_id='{$cat_id}'");

		$sql->SetQuery("UPDATE `news` SET cat_id=0 WHERE cat_id='{$cat_id}'");

		return get_warning_html($GLOBALS['model']['cat_deleted'], 'ok', ADOMAIN.'/'.self::$name.'/cats/');
	}

	function UploadTempPic()
	{
		global $gUser;

		if (!guser::_hasAccess(self::$name))
			return;

		$file = array();

		if (!empty($_FILES['img']['size']))
		{
			## минипик
			$cover = $_FILES['img'];
			if ($cover)
			{
				$img_name_arr = explode(".", $cover['name']);
				$img_type     = end($img_name_arr);
				$folder       = 'news/temp';
				$filename     = time().rand(100, 999);
				$temp         = $filename.'.'.$img_type;

				$this->CleanTemp();

				ClassUpload::Image($cover, 0, 0, $folder, 'strict', $temp);

				$imgsize = getimagesize($cover['tmp_name']);
				$file['filename'] = $filename;
				$file['img_type'] = $img_type;
				$file['x'] = $imgsize[0];
				$file['y'] = $imgsize[1];
			}

			####
		}
		else
		{
			$file['error'] = 'NOT OK';
		}

		return json_encode($file);
	}

	function CleanTemp()
	{
		$dir = ROOT_PATH."/uploads/news/temp";

		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while ($file = readdir($dh))
				{
					if(!is_dir($dir.$file))
					{
						if (filemtime($dir.DIRECTORY_SEPARATOR.$file) < strtotime('-1 days'))
							unlink($dir.DIRECTORY_SEPARATOR.$file);
					}
				}
			}
		}
		closedir($dh);
	}
}

?>
