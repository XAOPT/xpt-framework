<?php
/**
* Загрузка файлов на серв
* @version	0.2.13 (16:47 24.05.2012)
* @package	Zoker
* @author	Denis Petrov
*/
class ClassUpload
{
	/**
	 * Ïðîâåðÿåò ðàñøèðåíèå ôàéëà. Äëÿ ãðàôè÷åñêèõ ôàéëîâ
	 * @param string $name - èìÿ ôàéëà (âêîëþ÷àÿ ðàñøèðåíèå)
	 * @return FALSE â ñëó÷àå íåäîïóñòèìîãî ðàñøèðåíèÿ ôàéëà. Â ïðîòèâíîì ñëó÷àå - ðàñøèðåíèå ôàéëà
	 */
    static function CheckExtension($name, $file_group = 'graph')
	{
		switch($file_group)
		{
			case 'text':
				$allowed_extensions = array("doc", "txt", "rtf", "docx", "xls","csv");
				break;
			case 'graph':
			default:
				$allowed_extensions = array("gif", "jpg", "png", "jpe", "jpeg");
				break;
		}

		$name_arr  = explode(".", $name);
		$file_type = end($name_arr);

		if (!in_array(strtolower($file_type), $allowed_extensions))
			return false;
		else
			return $file_type;
	}



	/**
	 * Óñòàðåâøàÿ ôóíêöèÿ. Äëÿ ñîâìåñòèìîñòè ñ âåðñèåé 0.1.83 è ðàíåå
	 */
	static function CheckWordExtension($name)
	{
		return self::CheckExtension($name, 'text');
	}

	/**
	 * Óñòàðåâøàÿ ôóíêöèÿ. Äëÿ ñîâìåñòèìîñòè ñ âåðñèåé 0.1.83 è ðàíåå
	 */
	static function ImageFromUrl($img_link, $need_x = 0, $need_y = 0, $folder = 'default', $style = 'strict', $name = 0, $options = array() )
    {
		$options['is_remote'] = true;

		return self::Image($img_link, $need_x, $need_y, $folder, $style, $name, $options);
	}


	/**
	 * Çàãðóæàåò ïðèêðåïë¸ííîå ê ôîðìå èçîáðàæåíèå
	 * @param object $picture - ññûëêà íà èçîáðàæåíèå. Íàïðèìåð: $_FILES['img']
	 * @param integer $need_x - òðåáóåìûé ðàçìåð ïî îñè X
	 * @param integer $need_y - òðåáóåìûé ðàçìåð ïî îñè Y
	 * @param string $folder - ïàïêà, â êîòîðóþ áóäåò îñóùåñòâëåíà çàãðóçêà ôàéëà. Îáðàçîâàíèå ïîëíîãî ïóòè: ROOT_PATH."/uploads/{$folder}/";
	 * @param string $style - àëãîðèòì îáðàáîòêè ðàçìåðà èçîáðàæåíèÿ
	 * @param string $name - èìÿ ôàéëà. Åñëè íå óêàçàòü - èìÿ ôàéëà ñãåíåðèðóåòñÿ ïî timestamp
	 * @param integer $max_wieght - îãðàíè÷åíèå íà "âåñ" çàãðóæàåìîãî ôàéëà
	 * @param mixed $options - ìàññèâ äîïîëíèòåëüíûõ íàñòðîåê.
	 *   Äîïîëíèòåëüíûå íàñòðîéêè:
	 *   $options['is_local'] - åñëè true, òî ðàáîòàåì ñ ôàéëîì èç ôàéëîâîé ñèñòåìû
	 *   $options['is_remote'] - åñëè true, òî ðàáîòàåì ñ ôàéëîì ïî URL ññûëêå
	 *   $options['x1'], $options['x2'], $options['y1'], $options['y2'] - êîîðäèíàòû îáëàñòè èçîáðàæåíèÿ
	 *   $options['quantity'] - êà÷åñòâî jpeg. Ïî óìîë÷àíèþ 90
	 * @return string èìÿ ñîçäàííîãî ôàéëà
	 */
    static function Image($picture, $need_x = 0, $need_y = 0, $folder = 'default', $style = 'strict', $name = 0, $max_wieght = 0, $options = array() )
    {
		$img_type     = '';
		$imgsize      = '';

		if (!isset($options['is_local']))
			$options['is_local'] = '';

		if ($options['is_local']) //ðàáîòàåì ñ ëîêàëüíûì ôàéëîì
		{
			$imgsize      = getimagesize($picture);
			$img_name_arr = explode(".", $picture);
			$img_type     = end($img_name_arr);
		}
		else if (isset($options['is_remote']))
		{
			$temp_name = ROOT_PATH.'/cache/temp_img';

			if (file_exists($temp_name))
				unlink($temp_name);

			$file_headers = @get_headers($img_link);
			if($file_headers[0] == 'HTTP/1.0 404 Not Found')
				return '';

			file_put_contents($temp_name, file_get_contents($img_link));

			$img_name_arr = explode(".", $picture);
			$img_type     = end($img_name_arr);

			if (!self::CheckExtension($picture))
			{
				return ''; ## åñëè ðàñøèðåíèå ôàéëà íåäîïóñòèìîå
			}

			$imgsize = getimagesize($temp_name);
		}
		else // ðàáîòàåì ñ çàãðóæàåìûì ôàéëîì
		{
			if ((($picture['size']/1024) > $max_wieght) && $max_wieght !=0)
			{
				return ''; ## åñëè âåñ êàðòèíêè áîëüøå äîïóñòèìîãî (kb)
			}

			$img_name_arr = explode(".", $picture['name']);
			$img_type     = end($img_name_arr);

			if (!self::CheckExtension($picture['name']))
			{
				return ''; ## åñëè ðàñøèðåíèå ôàéëà íåäîïóñòèìîå
			}

			$imgsize = getimagesize($picture['tmp_name']);
		}

		if (!$name)
			$name = time().rand(100, 999).'.'.$img_type;

		if (!isset($options['sctrict_path']))
			$filename = ROOT_PATH."/uploads/{$folder}/{$name}";
		else
			$filename = "{$folder}/{$name}";

		if ($options['is_local'])
			copy($picture, $filename);
		else if (isset($options['is_remote']))
			copy($temp_name, $filename);
		else
			copy($picture['tmp_name'], $filename);


        ##### ÓÏÐÀÂËÅÍÈÅ ÐÀÇÌÅÐÎÌ ÊÀÐÒÈÍÊÈ #####
        $src_newX = 0; ## ñìåùåíèå òàêîå ãàäåíüêîå
        $src_newY = 0;

		$dst_x = 0;
		$dst_y = 0;

		$src_w = $imgsize[0];
		$src_h = $imgsize[1];

		$img_x = $need_x;
		$img_y = $need_y;

        if ($need_x != 0 || $need_y != 0) ## åñëè òðåáóåòñÿ êàêîå-ëèáî èçìåíåíèå
        {
			if (!$need_x || !$need_y)
			{
				$style = 'oneside';
			}

			if ($style == 'oneside') // vkontakte-style
			{
				if (!$need_x)
				{
                    $img_x = intval( $imgsize[0] * $need_y / $imgsize[1]);
				}
				else
				{
					$img_y = intval( $imgsize[1] * $need_x / $imgsize[0]);
				}
			}


			if ($style == 'anyone')
			{
				if ($imgsize[0] > $imgsize[1])
				{
                    $img_y = intval($need_x * $imgsize[1] / $imgsize[0]);
				}
				else
				{
					$img_x = intval($need_y * $imgsize[0] / $imgsize[1]);
				}
			}

			if ($style == 'anyone_lower')
			{
				if ($imgsize[0] > $need_x || $imgsize[1] > $need_y)
				{
					if ($imgsize[0] > $imgsize[1])
					{
						$img_y = intval($need_x * $imgsize[1] / $imgsize[0]);
					}
					else
					{
						$img_x = intval($need_y * $imgsize[0] / $imgsize[1]);
					}
				}
				else
				{
					$img_x = $imgsize[0];
					$img_y = $imgsize[1];
				}
			}

			if ($style == 'area_strict')
			{
				if ($options['y2'] > $imgsize[1] ||
				    $options['y1'] > $imgsize[1] ||
				    $options['x2'] > $imgsize[0] ||
				    $options['x1'] > $imgsize[0]
					)
				{
					return ''; // êàêîé-òî êîñÿê ñ êîîðäèíàòàìè
				}

				$src_newX = $options['x1'];
				$src_newY = $options['y1'];
				$img_x = $need_x;
				$img_y = $need_y;
				$src_w = $options['x2'] - $options['x1'];
				$src_h = $options['y2'] - $options['y1'];
			}

			if ($style == 'strict')
			{
				$def_x = $need_x/$imgsize[0]; ## ðàçíèöà ìåæäó äåéñòâèòåëüíûì è æåëàåìûì ïî èêñó
				$def_y = $need_y/$imgsize[1];

				if ( $def_x < 1 && $def_y < 1 )
				{
					if (abs($def_x) < abs($def_y))
					{
						$src_w    = intval($need_x*$imgsize[1]/$need_y);
						$src_newX = intval(($imgsize[0]-$src_w)/2);
					}
					else ## èçëèøåê ïî èãðåêó
					{
						$src_h    = intval($need_y*$imgsize[0]/$need_x);
						$src_newY = intval(($imgsize[1]-$src_h)/2);
					}
				}
				else if ($def_x > 1 && $def_y  > 1)
				{
					if (abs($def_x) < abs($def_y))
					{
						$src_h    = intval($imgsize[1]*$imgsize[0]/$need_x);
						$src_newY = intval(($imgsize[1]-$src_h)/2);
					}
					else if(abs($def_x) > abs($def_y))
					{
						$src_w    = intval($imgsize[0]*$imgsize[1]/$need_y);
						$src_newX = intval(($imgsize[0]-$src_w)/2);
					}
				}
				else if ($def_x > 1)
				{
					$src_h    = intval($imgsize[1]*$imgsize[0]/$need_x);
					$src_newY = intval(($imgsize[1]-$src_h)/2);
				}
				else if ($def_y > 1)
				{
					$src_w    = intval($imgsize[0]*$imgsize[1]/$need_y);
					$src_newX = intval(($imgsize[0]-$src_w)/2);
				}
			}

			if ($style == 'none')
			{
				$img_x = $imgsize[0];
				$img_y = $imgsize[1];
			}
        }
        else
		{
			$img_x = $imgsize[0];
			$img_y = $imgsize[1];
        }

        // ÊÎÍÅÖ ÓÏÐÀÂËÅÍÈß ÐÀÇÌÅÐÎÌ ÊÀÐÒÈÍÊÈ
		$thumb = imagecreatetruecolor($img_x, $img_y);

		switch($img_type)
		{
			case "gif":
				if( function_exists("imagecreatefromgif") )
				{
					$image = imagecreatefromgif($filename);
					break;
				}
			case "png":
				$image = imagecreatefrompng($filename);
				break;
			default:
				$image = imagecreatefromjpeg($filename);
				break;
		}
		imagealphablending($thumb, false);
		imagesavealpha($thumb, true);

		imagecopyresampled ($thumb, $image, $dst_x, $dst_y, $src_newX, $src_newY, $img_x, $img_y, $src_w, $src_h);

		switch($img_type)
		{
			case "gif":
				imagegif($thumb, $filename );
				break;
			case "png":
				imagepng($thumb,$filename);
				break;
			default:
				if (!isset($options['quantity']))
					$options['quantity'] = 100;

				imagejpeg($thumb, $filename, $options['quantity']);
				break;
		}

		return $name;
	}


	/**
	 * Çàãðóæàåò ïðèêðåïë¸ííûé ê ôîðìå ôàéë
	 * @param object $file - ññûëêà íà ôàéë.
	 * @param string $folder - ïàïêà, â êîòîðóþ áóäåò îñóùåñòâëåíà çàãðóçêà ôàéëà. Îáðàçîâàíèå ïîëíîãî ïóòè: ROOT_PATH."/uploads/{$folder}/";
	 * @param string $name - èìÿ ôàéëà. Åñëè íå óêàçàòü - èìÿ ôàéëà ñãåíåðèðóåòñÿ ïî timestamp
	 * @param integer $max_weight - îãðàíè÷åíèå íà "âåñ" çàãðóæàåìîãî ôàéëà
	 * @return string èìÿ ñîçäàííîãî ôàéëà
	 */
	static function Word($file, $folder = 'default', $name = 0, $max_weight = 0)
	{
		if ((($file['size']/1024) > $max_weight) && $max_weight !=0)
        {
			return ''; ## åñëè âåñ ôàéëà áîëüøå äîïóñòèìîãî (kb)
        }

		$name_arr  = explode(".", $file['name']);
		$file_type = end($name_arr);

		if (!$name)
			$name = time().rand(100, 999).'.'.$file_type;

		if (!self::CheckExtension($file['name'], 'text'))
        {
			return ''; ## åñëè ðàñøèðåíèå ôàéëà íåäîïóñòèìîå
        }

		$target_path = "{$folder}/{$name}";

		if(move_uploaded_file($file['tmp_name'], $target_path))
		{
			return $name;
		}
		else
			return false;
	}
}
?>
