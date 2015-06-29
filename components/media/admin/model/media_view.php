<?php
class MediaView extends AdmcontMedia
{
	function __construct()
	{
		parent::__construct();
	}

	private function _getCatsArray()
	{
		global $sql, $available_locale;

		$all_cats = $sql->SetQuery("SELECT * FROM `media_cats` ORDER BY cat_title DESC")->LoadAllRows();
		$cats = array();

		$locale = (defined(DEFAULT_LOCALE))?DEFAULT_LOCALE:$available_locale[0];

		// сгруппируем категории по айдишнику с предпочтением к выбранному языку интерфейса
		if (!empty($all_cats))
		foreach ($all_cats as $c)
		{
			if (!isset($cats[$c['id']]) || $cats[$c['id']]['lang'] != DEFAULT_LOCALE)
				$cats[$c['id']] = $c;
		}

		return $cats;
	}

	public function CatsList()
	{
		$cats = $this->_getCatsArray();

		$this->tpl->assign( "cats", $cats );

		return $this->tpl->draw( "cats" );
	}

	public function CatEdit()
	{
		global $sql, $available_locale;

		$id = zReq::getVar('id', 'INT', 'GET');

		if ($id)
		{
			$translations = $sql->SetQuery("SELECT * FROM `media_cats` WHERE id={$id}")->LoadByUniq('lang');

			$this->tpl->assign( "translations", $translations );
			$this->tpl->assign( "cat_id", $id );
		}

		$this->tpl->assign( "available_locale", $available_locale );

		return $this->tpl->draw( "cat_edit" );
	}

	public function Edit()
	{
		global $sql, $available_locale;

		$id = zReq::getVar('id', 'INT', 'GET');

		if ($id)
		{
			$object = $sql->SetQuery("SELECT * FROM `media_items` WHERE id={$id}")->LoadRow();

			if ($object['type'] == 'youtube') {
				require_once(ROOT_PATH."/lib/class.parse_video_link.php");
				$parserClass = new ParseVideoLink();

				$object['source'] = $parserClass->RepareUrl($object['type'], $object['source']);
			}

			$this->tpl->assign( "object", $object );
		}

		$cats = $this->_getCatsArray();

		if (empty($cats))
			return "Пожалуйста, создайте сначала хотя бы одну категорию медиа-файлов";

		$this->tpl->assign( "cats", $cats );

		return $this->tpl->draw( "edit" );
	}

	public function listItems()
	{
		global $sql;

		$cat_id = zReq::getVar('id', 'INT', 'GET', 0);

		$items = $sql->SetQuery("SELECT * FROM `media_items` WHERE cat_id={$cat_id}")->LoadAllRows();

		$this->tpl->assign( "items", $items );

		return $this->tpl->draw( "list" );
	}
}
?>