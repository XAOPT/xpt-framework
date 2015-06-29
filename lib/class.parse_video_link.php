<?php

class ParseVideoLink
{
	var $params = array();

	function GetParams()
	{
		if (!$this->link)
			return;

		if (preg_match("/youtu/", $this->link))
		{
			$this->params['source'] = 'youtube';

			if (preg_match("/youtu\.be\//",$this->link))
			{
				$link_name_arr  = explode("/", $this->link);
				$this->params['source_id'] = end($link_name_arr);
			}
			else if (preg_match("/v=([^&]+)/", $this->link, $matches))
			{
				$this->params['source_id'] = $matches[1];
			}
		}

		if (isset($this->params['source_id']))
			$this->params['source_id'] = mysql_real_escape_string(trim($this->params['source_id']));

		return $this->params;
	}

	function GetThumbLink($source = '', $source_id = '')
	{
		if (!$source)
			$source = $this->params['source'];

		if (!$source_id)
			$source_id = $this->params['source_id'];

		switch ($source)
		{
			case 'youtube':
				return "http://img.youtube.com/vi/".$source_id."/0.jpg";
			default:
				return;
		}
	}

	static function RepareUrl($source = '', $id = '', $channel = '')
	{
		if (!$source || !$id)
			return false;

		switch ($source)
		{
			case 'youtube':
				return 'http://youtu.be/'.$id;
			default:
				return $id;
		}
	}

	function GetVideoDescription()
	{
		$this->GetVodParams();

		if ($this->params['source'] == 'youtube')
		{
			return $this->parseYoutubeDescription($this->params['source_id']);
		}

		return false;
	}

	function parseYoutubeDescription($source_id = '')
	{
		if (!$source_id) return false;

		$feedURL = 'http://gdata.youtube.com/feeds/api/videos/' . $this->params['source_id'];

		$entry = file_get_contents($feedURL);

		$entry = simplexml_load_file($feedURL);
		$obj = new stdClass;

		// get nodes in media: namespace for media information
		$media = $entry->children('http://search.yahoo.com/mrss/');
		$obj->title       = $media->group->title;
		$obj->description = $media->group->description;
		$obj->author      = $entry->author->name;

		// get video player URL
		$attrs         = $media->group->player->attributes();
		$obj->watchURL = $attrs['url'];

		// get video thumbnail
		$attrs             = $media->group->thumbnail[0]->attributes();
		$obj->thumbnailURL = $attrs['url'];

		// get <yt:duration> node for video length
		$yt          = $media->children('http://gdata.youtube.com/schemas/2007');
		$attrs       = $yt->duration->attributes();
		$obj->length = $attrs['seconds'];

		// get <yt:stats> node for viewer statistics
		$yt             = $entry->children('http://gdata.youtube.com/schemas/2007');
		$attrs          = $yt->statistics->attributes();
		$obj->viewCount = $attrs['viewCount'];

		// get <gd:rating> node for video ratings
		$gd = $entry->children('http://schemas.google.com/g/2005');
		if ($gd->rating) {
			$attrs = $gd->rating->attributes();
			$obj->rating = $attrs['average'];
		} else {
			$obj->rating = 0;
		}

		// get <gd:comments> node for video comments
		$gd = $entry->children('http://schemas.google.com/g/2005');
		if ($gd->comments->feedLink) {
			$attrs              = $gd->comments->feedLink->attributes();
			$obj->commentsURL   = $attrs['href'];
			$obj->commentsCount = $attrs['countHint'];
		}

		// get feed URL for video responses
		$entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
		$nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/2007#video.responses']");
		if (count($nodeset) > 0) {
			$obj->responsesURL = $nodeset[0]['href'];
		}

		// get feed URL for related videos
		$entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
		$nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/2007#video.related']");
		if (count($nodeset) > 0) {
			$obj->relatedURL = $nodeset[0]['href'];
		}

		return $obj;
	}
}

?>
