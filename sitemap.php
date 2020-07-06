<?php
set_time_limit(0);
if(@$_GET['p'] != 'qojmbfdskoi377ydsgaynki' && @$argv[1] != 'qojmbfdskoi377ydsgaynki')
    die();

include("./sitemap.class.php");
$sitemap = new sitemap();

//игнорировать ссылки с расширениями:
$sitemap->set_ignore(array("javascript:", ".css", ".js", ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif"));

//ссылка Вашего сайта:
$sitemap->get_links("https://vip-wf.ru/");
$sitemap->get_links("https://vip-wf.ru/catalog/");

//если нужно вернуть просто массив с данными:
//$arr = $sitemap->get_array();
//echo "<pre>";
//print_r($arr);
//echo "</pre>";

$map = $sitemap->generate_sitemap();

$file = 'sitemap.xml';

$f=fopen($file,'w');
fwrite($f,$map);
fclose($f);
?>