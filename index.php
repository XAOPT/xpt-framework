<?php
$msc = microtime();

if ($_SERVER['APPLICATION_ENV'] == "test")
    require_once("./include/inc_config_test.php");
else if ($_SERVER['APPLICATION_ENV'] == "production" || !isset($_SERVER['APPLICATION_ENV']) || empty($_SERVER['APPLICATION_ENV'])) {
    require_once("./include/inc_config_production.php");
}
else
    require_once("./include/inc_config_development.php");

require_once(ROOT_PATH."/include/inc_libs.php");

global $sql;
$sql->debug_mode = true;

$rewrite  = new ZokerRewrite();

if ($rewrite->component == 'logout') /* Пользователь пытается выйти из панели управления */
{
    /// TODO: вынести код в сессии
    global $gUserid, $sql;

    $sql->setQuery("DELETE FROM `##session` WHERE `userid`='{$gUserid}'");

    setcookie('sessid', "",  -10, "/");
    setcookie('userid', "", -10, "/");
    header('Location: '.ADOMAIN."/");
    exit;
}

global $gUser;

class ClassIndex
{
    var $tpl;

    function __construct()
    {
        global $sql, $rewrite, $gUser;

        $_pjax = (bool)zReq::GetVar("_pjax", "STRING", "GET", false);

        if ($_pjax) {
            echo $this->GetComponent();
            exit;
        }

        $this->tpl = new RainTPL('', 'core');

        if ($rewrite->module)
        {
            $this->tpl->assign( "component", master_module($rewrite->module) );
        }
        else if (!$rewrite->component) {
            $rewrite->component = "static";
        }

        if ($rewrite->component)
        {
            $this->tpl->assign( "component", $this->GetComponent() );

            if ($rewrite->component == 'static')
            {
                $do = zReq::GetVar('do', 'SQL', 'GET', '');

                $this->tpl->assign( "static_name", $do );
            }
        }

        $this->tpl->assign( "component_name", $rewrite->component );

        $this->tpl->assign( "gCurrentPage", "http://" . $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] );
        $this->tpl->assign( "gTitle", ClassPage::GetTitle() );
        $this->tpl->assign( "gUser", $gUser );
        $this->tpl->assign( "keywords", ClassPage::GetKeywords() );
        $this->tpl->assign( "description", ClassPage::GetDescription() );
    }

    function GetComponent()
    {
        global $sql, $rewrite, $gUser;

        load_controller($rewrite->component); ## подгружает файл класса

        $prefix = ($rewrite->admin_mod)?'Admcont':'Cont';

        /*if ($prefix == 'Admcont')
        {
            global $gUser;

            $sql->SetQuery("SELECT * FROM `components` WHERE sysname='{$rewrite->component}'");
            $comp = $sql->LoadRow();

            $access_array = explode(',', $comp['old_access']);

            if (empty($gUser) || !in_array($gUser['usergroupid'], $access_array))
                return get_warning_html("Ошибка доступа. Отказать", 'error');
        }*/

        $controller_name = $prefix.ucfirst($rewrite->component);
        $controller      = new $controller_name;

        return $controller->Action($rewrite->action);
    }
}

/* загружаем язык по умолчанию */
$gLocale = $rewrite->locale;
$gLang = include ROOT_PATH."/include/lang/".DEFAULT_LOCALE.".txt";

if ($gLocale != DEFAULT_LOCALE) {
    language_extend(include ROOT_PATH."/include/lang/".$gLocale.".txt");
}

$index = new ClassIndex();

if($rewrite->admin_mod)
{
/*  if (!guser::_isModer())
    {
        //header('Location: '.DOMAIN.'/login/admin/');
        echo "<b>Ты кто такой? Давай, до свидания!</b>";
        exit;
    }*/

    $index->tpl->assign( "gTitle", ClassPage::GetTitle() );
    $menu_array = array('cards','goods','lobby','users');
    $html = admin_menu($menu_array);

    $index->tpl->assign( "menu", $html);
}

/* выводим сгенерированный хтмл на страницу */
ob_start();
global $debug_info;
$msc = microtime()-$msc;
$debug_info .= "<br />Pagegen: {$msc} msc";
$index->tpl->assign( "sql_debug", $debug_info );
$index->tpl->draw( "index", false );
ob_end_flush();

?>