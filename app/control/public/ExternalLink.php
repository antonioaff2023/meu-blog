<?php
class ExternalLink extends TPage
{
    public function __construct($param = null)
    {
        parent::__construct();
        $url = isset($param['url']) ? $param['url'] : 'https://prantonioaffonso.online/biblias';
        TScript::create("window.open('target', '_blank');");
    }
}