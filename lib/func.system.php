<?php

####
## load component's controller
####
function load_controller($component_name)
{
    global $rewrite;

    $component_name = strtolower($component_name);

    if (!defined('LANGUAGE'))
        $lang = 'ru';
    else
        $lang = LANGUAGE;

    if ($rewrite->admin_mod)
    {
        $lang_path = ROOT_PATH."/components/".$component_name."/admin/lang/".$lang.".php";
        $path = ROOT_PATH."/components/".$component_name."/admin/".$component_name."_admcont.php";
    }
    else
    {
        $lang_path = ROOT_PATH."/components/".$component_name."/lang/".$lang.".php";
        $path = ROOT_PATH."/components/".$component_name."/".$component_name."_cont.php";
    }

    if (file_exists($path))
    {
        require_once($path);
    }
    else
        error_404();

    if (file_exists($lang_path))
    {
        require_once($lang_path);
    }
}

####
## load component's model
## models file name: model/{component_name}_{model_prefix}.php
####
function load_model($component_name, $model_prefix = false)
{
    global $rewrite, $gLocale;

    $component_name = strtolower($component_name);

    if ($model_prefix)
        $file_name = $component_name."_".$model_prefix.".php";
    else
        $file_name = $component_name.".php";

    if (file_exists(ROOT_PATH."/components/".$component_name."/lang/".DEFAULT_LOCALE.".txt"))
        language_extend(include ROOT_PATH."/components/".$component_name."/lang/".DEFAULT_LOCALE.".txt");

    if (file_exists(ROOT_PATH."/components/".$component_name."/admin/lang/".DEFAULT_LOCALE.".txt"))
        language_extend(include ROOT_PATH."/components/".$component_name."/admin/lang/".DEFAULT_LOCALE.".txt");

    if ($gLocale != DEFAULT_LOCALE && file_exists(ROOT_PATH."/components/".$component_name."/lang/".$gLocale.".txt"))
        language_extend(include ROOT_PATH."/components/".$component_name."/lang/".$gLocale.".txt");

    if ($rewrite->admin_mod)
        require_once(ROOT_PATH."/components/".$component_name."/admin/model/".$file_name);
    else
        require_once(ROOT_PATH."/components/".$component_name."/model/".$file_name);
}

####
## returns templates location for component
####
function comp_tpl_path($component_name)
{
    global $rewrite;

    if ($rewrite->admin_mod)
    {
        return "components/".$component_name."/admin/view/";
    }
    else
    {
        if (file_exists("templates/default/components/".$component_name."/"))
        {
            return "templates/default/components/".$component_name."/";
        }
        else
        {
            return "components/".$component_name."/view/";
        }
    }
}

function show_module($component, $action, $options = array())
{
    load_controller($component);

    $controller_name = 'Cont'.ucfirst($component);
    $controller = new $controller_name;

    return $controller->ActionModule($action, $options);
}

function master_module($module_name = '', $action = '', $options = '')
{
    $module_name = strtolower($module_name);
    $path = ROOT_PATH."/modules/".$module_name."/".$module_name.".php";

    if (file_exists($path))
    {
        require_once($path);

        if (!defined('LANGUAGE'))
            $lang = 'ru';
        else
            $lang = LANGUAGE;

        $lang_path = ROOT_PATH."/modules/".$module_name."/lang/".$lang.".php";

        if (file_exists($lang_path))
            require_once($lang_path);
    }
    else
        return 'Модуль не найден';

    $controller = new $module_name;

    return $controller->SwitchAction($action, $options);
}

function get_plugins_outputs($component = '', $action = '', $options = array())
{
    if (empty($component)) return;

    global $sql;

    $plugins_outputs = array();

    $query = "SELECT `plugin`, `method`, `action`, `param_name` FROM `plugins` WHERE component='{$component}'";
    $sql->SetQuery($query);
    $plugins = $sql->LoadAllRows();

    foreach ($plugins as $p)
    {
        $funcs = explode(',',$p['action']);

        if (in_array($action, $funcs))
        {
            $path = ROOT_PATH."/plugins/".$p['plugin']."/".$p['plugin'].".php";

            if (file_exists($path))
                require_once($path);
            else
                $plugins[] = 'Модуль не найден';

            $controller = new $p['plugin'];

            $param = zReq::getVar($p['param_name'], 'SQL');

            $plugins_outputs[] = $controller->$p['method']($component, $param);
        }
    }

    return $plugins_outputs;
}


function get_std_config($table = '', $condition = array())
{
    global $sql;

    $config = array();

    if ($table)
    {
        $query = "SELECT `key`, `value` FROM `{$table}` WHERE 1=1";
        foreach ($condition as $k => &$v)
        {
            $query .= " AND `{$k}`='{$v}'";
        }
        $sql->SetQuery($query);
        $options = $sql->LoadAllRows();

        foreach ($options as &$option)
        {
            $config[$option['key']] = $option['value'];
        }
    }

    return $config;
}

function save_std_config($table = '', $var_keys = array())
{
    global $sql;

    if ($table)
    {
        foreach ($var_keys as $k=>&$v)
        {
            $val = zReq::getVar($k, $v);

            $query = "SELECT count(*) FROM `{$table}` WHERE `key`='{$k}'";
            $sql->SetQuery($query);
            $num_rows = $sql->LoadSingle();

            if  ($num_rows)
            {
                $query = "UPDATE `{$table}` SET `value`='{$val}' WHERE `key`='{$k}';";
                $sql->SetQuery($query);
            }
            else
            {
                $query = "INSERT INTO `{$table}` (`key`, `value`) VALUES ('{$k}', '{$val}')";
                $sql->SetQuery($query);
            }
        }
    }
}

function ru_json_encode($data)
{
    return preg_replace_callback(
        '/\\\u([0-9a-fA-F]{4})/',
        create_function('$match', 'return mb_convert_encoding("&#" . intval($match[1], 16) . ";", "UTF-8", "HTML-ENTITIES");'),
        json_encode($data)
    );
}

function console_log($data) {
    file_put_contents(ROOT_PATH.'/console.txt', print_r($data, true), FILE_APPEND);
}


function language_extend($extension = array())
{
    global $gLang;

    if (is_array($extension) && !empty($extension))
    foreach ($extension as $key => $value)
    {
        $gLang[$key] = $value;
    }

    return true;
}

function curl_get($host, $referer = 'http://google.com/'){
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.9.168 Version/11.51");
    curl_setopt($ch, CURLOPT_URL, $host);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSLVERSION,3);
    $html = curl_exec($ch);
    echo curl_error($ch);
    curl_close($ch);
    return $html;
}

####################
function error_404()
{
    header("HTTP/1.0 404 Not Found");
    header("HTTP/1.1 404 Not Found");
    header("Status: 404 Not Found");
    $_SERVER['REDIRECT_STATUS'] = 404;
    include('404page.html');
    exit;
}
function error_410()
{
    header("HTTP/1.0 410 Gone");
    header("HTTP/1.1 410 Gone");
    header("Status: 410 Gone");
    $_SERVER['REDIRECT_STATUS'] = 410;
    include('404page.html');
    exit;
}
function error_301($url)
{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: {$url}");
    exit;
}
function error_under_conc(){
    include('under_construction.html');
    exit;
}
?>