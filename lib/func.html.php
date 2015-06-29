<?php

/**
 *  Выводит сообщение об ошибке
 *  @param string $message - текст сообщения
 *  @param string $type    - 'error' или 'ok'
 *  @param string $redirect - ссылка, куда будет осуществлён редирект после показа сообщения
 *  @return string
 */
function get_warning_html($message = '', $type = 'error', $redirect = '', $options=array())
{
    setcookie('error_type', "$type",  time()+60, "/");
    setcookie('error_text', "$message",  time()+60, "/");

    if (isset($options['highlight']))
        setcookie('sys_highlight', $options['highlight'],  time()+60, "/");

    if (!$redirect)
    {
        global $rewrite;

        $redirect = ($rewrite->admin_mod)?DOMAIN.'/'.ADMIN_URL.'/':DOMAIN;
    }

    header("X-Pjax-Url: ".$redirect);
    exit;
}

/**
 *  Генерирует элемент формы типа SELECT
 *  @param string $select_name - параметр name элемента SELECT
 *  @param string $data        - массив хэшей, на основе которого будет построен селект
 *  @param string $val_name    - ключ элемента хэш-массива, значение которого будет подставлено в параметр value для option
 *  @param string $text_name   - ключ элемента хэш-массива, значение которого будет подставлено в option: <option>text_name</option>
 *  @param string $selected    - value строки, которая должна быть выбрана по умолчанию
 *  @return string
 */
function generate_select ($select_name, $data, $val_name, $text_name, $selected = '', $js = array())
{
    $output = "<select class='form-control input-sm' name='".$select_name."'";
    if (!empty($js))
    foreach ($js as $k => $v)
    {
        $output .= " {$k}='{$v}' ";
    }

   $output .= ">";
   if (!empty($data))
   foreach ($data as $option)
   {
       $output .= "<option value='".$option[$val_name]."'";
       if ($option[$val_name] == $selected) {$output .= " SELECTED ";}
       $output .= ">".$option[$text_name]."</option>";
   }
   $output .= "</select>";
   return $output;
}

function generate_array_select ($select_name, $data, $selected = '')
{
   $output= "<select class='form-control input-sm' name='".$select_name."'>";
   if (!empty($data))
   foreach ($data as $key => $value)
   {
       $output .= "<option value='".$key."'";
       if ($key == $selected) {$output .= " SELECTED ";}
       $output .= ">".$value."</option>";
   }
   $output .= "</select>";
   return $output;
}

/**
 *  Генерирует  три элемента типа SELECT для выбора даты рождения
 *  @param string $mysql_date - дата в формате YYYY-MM-DD, которая будет выбрана по умолчанию
 *  @param string $prefix     - префикс для имен селектов
 *  @return string
 */
function generate_date_picker($mysql_date = '', $prefix = '')
{
    if (!empty($mysql_date))
    {
        $date = explode('-', $mysql_date);
    }
    $output = "<table cellpadding='0' cellspacing='0' border='0'><tr>";

    $output .= "<td><select class='form-control input-sm' name='{$prefix}month'>";
    for ($i=1; $i <= 12; $i++)
    {
        $j = sprintf('%1$02d', $i);
        $output .= "<option value='{$j}' ";
        if (isset($date[1]) && $date[1]==$j) $output .= "SELECTED";
        $output .= ">{$i}</option>";
    }
    $output .= "</select></td>";

    $output .= "<td><select class='form-control input-sm' name='{$prefix}day'>";
    for ($i=1; $i<=31;$i++)
    {
        $j = sprintf('%1$02d', $i);
        $output .= "<option value='{$j}' ";
        if (isset($date[2]) && $date[2]==$j) $output .= "SELECTED";
        $output .= ">{$i}</option>";
    }
    $output .= "</select></td>";

    $output .= "<td><select name='{$prefix}year'>";

    for ($i=date('Y'); $i >= 1980; $i--)
    {
        $output .= "<option value='{$i}' ";
        if (isset($date[0]) && $date[0]==$i) $output .= "SELECTED";
        $output .= ">{$i}</option>";
    }
    $output .= "</select></td>";


    $output .= "</tr></table>";

    return $output;
}

/**
 *  Генерирует кнопку, отображающую состояния флага
 *  @param string $published - состояние флага. 0 или N - Выключено; 1 или Y - включено
 *  @return string
 */
function get_publish_style($published = 0)
{
    if ($published == 1 || $published == 'Y')
        return "fa-eye";
    else
        return "fa-eye-slash";
}

/**
 *  Возвращает код инициализации визуального редактора текста
 *  @return string
 */
function init_editor($editor = '')
{
    if (!$editor)
        $editor = EDITOR;

    if (file_exists(ROOT_PATH."/lib/editors/".$editor."/init.html"))
        return file_get_contents(ROOT_PATH."/lib/editors/".$editor."/init.html");
    else
        return '';
}


/**
 *  Возвращает время в относительном виде. Напр.: 1 день назад, месяц назад и т.п. Используется для отображения времени написания комментариев и т.п.
 *  @param integer $time - Nвремя с начала эпохи
 *  @return string
 */
function relative_time($time)
{
    if ($time+60 > TIME) //меньше Минуты
    {
        return "только что";
    }
    else if ($time+3600 > TIME) //меньше часа
    {
        $minutes = ceil((TIME-$time)/60);
        return "{$minutes} мин назад";
    }
    else if ($time+86400 > TIME) // меньше суток
    {
        $hours = floor((TIME-$time)/3600);
        if ($hours == 1)
            return "1 час назад";
        else
            return "{$hours} часов назад";
    }
    else if ($time+604800 > TIME) // меньше недели
    {
        $days = floor((TIME-$time)/86400);
        if ($days == 1)
            return "1 день назад";
        else
            return "{$days} дней назад";
    }
    else if ($time+2592000 > TIME) // меньше месяца
    {
        $weeks = floor((TIME-$time)/604800);
        if ($weeks == 1)
            return "1 неделю назад";
        else
            return "{$weeks} недель назад";
    }
    else if ($time+31104000 > TIME) // меньше года
    {
        $month = floor((TIME-$time)/2592000);
        if ($month == 1)
            return "1 месяц назад";
        else
            return "{$month} месяцев назад";
    }
    else
    {
        return "более года назад";
    }

    return '';
}
?>