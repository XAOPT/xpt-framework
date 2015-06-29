<?php
class MediaView extends ContMedia
{
	function __construct()
	{
		parent::__construct();
	}

	function MediaBlock($options = array())
	{
		global $sql;

		$limit = 3;

		$limit = (isset($options['count']))?$options['count']:3;

		$locale = DEFAULT_LOCALE;

		if (!isset($options['cat_id']))
			return $this->tpl->draw( "media_block" );

		$options['cat_id'] = intval($options['cat_id']);

		$cat = $sql->SetQuery("SELECT * FROM `media_cats` WHERE id={$options['cat_id']}")->LoadRow();

		$items = $sql->SetQuery("SELECT * FROM `media_items` WHERE cat_id={$options['cat_id']} ORDER BY created DESC LIMIT 0, {$limit}")->LoadAllRows();

		if (empty($items))
			return $this->tpl->draw( "media_block" );

		require_once(ROOT_PATH."/lib/class.parse_video_link.php");
		$parserClass = new ParseVideoLink();

		foreach ($items as &$i) {
			if ($i['type'] == 'youtube') {
				$i['img'] = $parserClass->GetThumbLink($i['type'], $i['source']);
			}
		}

		$this->tpl->assign( "cat", $cat );
		$this->tpl->assign( "items", $items );

		return $this->tpl->draw( "media_block" );
	}


	function MediaCatView()
	{
		global $sql, $rewrite;

		$alias = zReq::GetVar('action', 'GET', 'SQL', $rewrite->action);

		$cat = $sql->SetQuery("SELECT * FROM `media_cats` WHERE `alias`='{$alias}'")->LoadRow();
		$items = $sql->SetQuery("SELECT * FROM `media_items` WHERE `cat_id`='{$cat['id']}' ORDER BY created DESC")->LoadAllRows();

		require_once(ROOT_PATH."/lib/class.parse_video_link.php");
		$parserClass = new ParseVideoLink();

		foreach ($items as &$i) {
			if ($i['type'] == 'youtube') {
				$i['img'] = $parserClass->GetThumbLink($i['type'], $i['source']);
			}
		}

		$this->tpl->assign('cat', $cat);
		$this->tpl->assign('items', $items);

		return $this->tpl->draw("media_cat");
	}
}

?>
