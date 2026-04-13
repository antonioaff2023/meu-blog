<?php
require_once 'init.php';
require_once 'app/lib/app/MeuRenderer.php';
$sermoes = SermoesViews::render(1); // 1 = Sermões
$estudos = SermoesViews::render(2); // 2 = Estudos Bíblicos
$devocional = ''; // preenchido dentro do bloco público abaixo
$ini = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : []);

// AdiantiCoreApplication::setRouter(array('AdiantiRouteTranslator', 'translate'));



new TSession;
ApplicationAuthenticationService::checkMultiSession();
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

$caminho_menu = 'app/templates/adminbs5/menu_superior.html';

if (file_exists($caminho_menu)) {
    $menu_html = file_get_contents($caminho_menu);
} else {
    $menu_html = '<p>Erro: Menu não encontrado.</p>';
}



if ($class == 'LoginForm'  || $class == 'SermoesPublicosView')
{
    $ini['general']['public_view'] = '0';
}


if ( TSession::getValue('logged') )
{
    if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
    {
        $content = file_get_contents("app/templates/{$theme}/iframe.html");
    }
    else
    {
        $content = file_get_contents("app/templates/{$theme}/layout.html");
        $content = str_replace('{MENU}', AdiantiMenuBuilder::parse('menu.xml', $theme), $content);
        $content = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top.xml', $theme), $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom.xml', $theme), $content);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        $content = file_get_contents("app/templates/{$theme}/public.html");
        $menu    = AdiantiMenuBuilder::parse('menu-public.xml', $theme);
        $content = str_replace('{MENU}', $menu, $content);

        // $menu = new MenuRenderer('menu-top-public.xml', 'MENUTOP');
        // $content = $menu->render($content);

        $content = str_replace('{MENUTOP}', $menu_html, $content);
        $content = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);


        // Gera o devocional do dia aqui, onde $ini já está definido e o contexto é público
        // InicioPublicoView é carregado pelo autoload do Adianti — não precisa de require_once
        $devocional = InicioPublicoView::renderDevocionalHero();
        $content = str_replace('{DEVOCIONAL}', $devocional, $content);
        // $content = str_replace('{SERMOES}', $sermoes, $content);
        // $content = str_replace('{ESTUDOS}', $estudos, $content);
    }
    else if ($class == 'SermoesPublicosView')
    {
        $content = file_get_contents("app/templates/{$theme}/layout-basic.html");
        // Teste
        
        
    }
    else if ($class == 'LoginForm')
    {
        $content = file_get_contents("app/templates/{$theme}/login.html");
    }
}

$content = ApplicationTranslator::translateTemplate($content);
$content = AdiantiTemplateParser::parse($content);

echo $content;

if (TSession::getValue('logged') OR $public)
{
    if ($class)
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    }
}
else
{
    if (isset($ini['general']['public_view']) && $ini['general']['public_view'] == '1')
    {
        if (!empty($ini['general']['public_entry']))
        {
            AdiantiCoreApplication::loadPage($ini['general']['public_entry'], '', $_REQUEST);
            
        }
    }
    
    else
    {
        AdiantiCoreApplication::loadPage('LoginForm', '', $_REQUEST);
    }
}