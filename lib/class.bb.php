<?php
class ClassBB
{
    public function __construct($smiles = array())
    {
        $this->smiles = $smiles;
    }

    public function show($text)
    {
        $text = htmlspecialchars($text);

        $text = preg_replace('/\[(\/?)(b|i|u)\s*\]/iu', "<$1$2>", $text);
        $text = preg_replace("/\[dvred\](.*?)\[\/dvred\]/iu", "<span style='color: #B00'>$1</span>", $text);

        $text = preg_replace('/\[img\s*\]([^\]\[]+)\.(jpg|gif|png|bmp|jpeg)\[\/img\]/iu', "<img src='$1.$2' alt='' class='forumimg' />", $text);
        //$text = preg_replace('/\[img\s*=\s*([\'"]?)([^\'"\]]+)\1\]/iu', "<img src='$2' alt='' class='forumimg' />", $text);
        $text = nl2br( $text );


        $text = preg_replace('/\[quote\]/', "<div>", $text);
        $text = preg_replace('/\[\/quote\]/', "</div></div>", $text);
        $text = preg_replace('/\[(\/?)quote login(\s*=\s*([\'"]?)([^\'"\[\]]+)\3\s*)? date(\s*=\s*([\'"]?)([^\'\[\]"]+)?\6\s*)\]/', "<div class='quotefield'><div class='q-header'>$4 <span>[$7]</span></div><div class='q-message'>", $text);

        $text = preg_replace('#\[url=((?:ftp|https?)://.*?)\](.*?)\[/url\]#iu', '<a href="$1" rel="nofollow">$2</a>', $text);

        $pattern = "/(?:\s|\A|\n)+(((?:htt(?:p|ps)|ftp|localhost)\:\/\/)*(www\.)*([a-zа-яё\d\-\_\.]+)(?:\.)+(ru|com|net|org|su|info|рф|am|name|pro|org|tv){1,10}[^\s\n\<\,\"\']*)/iu";
        $text = preg_replace_callback($pattern, array($this, "to_url"), $text);

        $img_path = DOMAIN."/components/forum/view/smiles/";
        foreach($this->smiles as $k=>$v)
        {
            $text = str_replace($v, "<img src='".$img_path.$k.".gif' alt='' class />", $text);
        }

        $text = preg_replace('/\[spoiler(?:\=(.*?))\](.*?)\[\/spoiler\]/s', "<div class='spoiler'><span class='spoiler_head'>$1</span><div class='spoiler_hidden'><div class='spoiler_close'>x</div>$2</div></div>", $text);

        return $text;
    }

    // Вычищает пустые бб-теги и проверяет совпадения закрывающих и открывающих
    public function save($text)
    {
        $text = preg_replace("/\[b\](\s)*\[\/b\]/iu", "", $text);
        $text = preg_replace("/\[u\](\s)*\[\/u\]/iu", "", $text);
        $text = preg_replace("/\[i\](\s)*\[\/i\]/iu", "", $text);
        $text = preg_replace("/\[img\](\s)*\[\/img\]/iu", "", $text);

        preg_match_all("/\[(b|i|u|img|url)[^\[]*\]/iu", $text, $open);
        preg_match_all("/\[\/(b|i|u|img|url)\]/iu", $text, $close);

        if(count($open[0]) != count($close[0]))
            return null;

        return $text;
    }

    public function to_url($matches)
    {

        if(!$matches[2]) {
            $r1 = "http://".$matches[1];
            $r2 = $matches[1];
            }
        else    {
            $r1 = $matches[1];
            $r2 = $matches[1];
        }

        //global $gUserid;
        //if ($gUserid == 1)
        //  print_r($matches);

        $r = ' <noindex><a rel="nofollow" target="_blank" href="'.$r1.'">'.$r2.'</a></noindex>';

        return $r;
    }
}
?>
