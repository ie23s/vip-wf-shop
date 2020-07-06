<?PHP
ini_set('display_errors', 0);
define('DBHost', '127.0.0.1');
define('DBPort', 3306);
define('DBName', 'i1485576_shop');
define('DBUser', 'i1485576_shop');
define('DBPassword', '8aa0af6884c0a434af4b542b672594de'); 
require(__DIR__ . "/DB/PDO.class.php");

$kewords = "WarFace, vip-ускоритель, пин-коды, пин коды варфейс, варфейс, кредиты WarFace, пин коды варфейс бесплатно, купить пин код WarFace";

function load($title, $content, $keywords, $mdesc, $meta) {
	$file = file_get_contents('./themes/index.html', FILE_USE_INCLUDE_PATH);
	$file = str_ireplace('{title}', $title, $file);
	$file = str_ireplace('{content}', $content, $file);
	$file = str_ireplace('{keywords}', $keywords, $file);
	$file = str_ireplace('{meta}', $meta, $file);
	$file = str_ireplace('{mdesc}', $mdesc, $file);
	return $file;
}
function cat($id, $name, $description, $url, $cost) {
	$file = file_get_contents('./themes/catalog.html', FILE_USE_INCLUDE_PATH);
	$file = str_ireplace('{id}', $id, $file);
	$file = str_ireplace('{name}', $name, $file);
	$file = str_ireplace('{description}', $description, $file);
	$file = str_ireplace('{url}', $url, $file);
	$file = str_ireplace('{cost}', $cost, $file);
	return $file;
}
function pr($id, $name, $description, $url, $cost) {
	$file = file_get_contents('./themes/product.html', FILE_USE_INCLUDE_PATH);
	$file = str_ireplace('{id}', $id, $file);
	$file = str_ireplace('{name}', $name, $file);
	$file = str_ireplace('{description}', $description, $file);
	$file = str_ireplace('{url}', $url, $file);
	$file = str_ireplace('{cost}', $cost, $file);
	return $file;
}
$DB = new Db(DBHost, DBPort, DBName, DBUser, DBPassword);
$page = "";
$show = "";
$meta = '';
if(!isset($_GET['do']))
	$path = "";
else
	$path = $_GET['do'];
$path = explode('/', $path);
$mdesc = 'Vip-WF - купить пин-код для WarFace, постоянные конкурсы, получи бесплатно пин-код WarFace. Надежный сервис покупки пин-кодов для WarFace. Самые выгодные цены на пин-коды WarFace.';
//HEADERS
$headers = array();
//MAIN
if(!isset($path[1])) {
	$title = 'Vip-WF - Пин коды для игры WarFace';
	$prods = $DB->query("SELECT * FROM `products` ORDER BY `priority` DESC");
	$content = '<div id="products"><div class="thumbnails">';
	foreach($prods as $prod) {
		$content .= "<div class\"hjs\"><img width=\"200\" alt=\"{$prod['shortname']}\" src=\"/icons/{$prod['id']}.jpg\" title=\"{$prod['shortname']}\"></img><a href=\"https://vip-wf.ru/{$prod['id']}/{$prod['url']}/\">{$prod['shortname']}</a></div>";
	}
	$content .= '</div></div>';
	$meta = '<link rel="canonical" href="https://vip-wf.ru/" />';
	$show = load($title,$content,$kewords, $mdesc, $meta); 
} else {
	//CATALOG
	if($path[1] == 'catalog') {
		$title = 'Vip-WF - Каталог товаров';
		$prods = $DB->query("SELECT * FROM `products` ORDER BY `priority` DESC");
		$content = '<div id="catalog">';
		foreach($prods as $prod) {
			$kewords .= ", " . $prod['shortname'];
			$content .= cat($prod['id'], $prod['shortname'], $prod['description'], $prod['url'], $prod['price']);
		}
		$content .= '</div>';
		$meta = '<link rel="canonical" href="https://vip-wf.ru/catalog/" />';
		$show = load($title,$content,$kewords,$mdesc,$meta); 
	} else {
		//PRODUCT
		@$prod = $DB->query("SELECT * FROM `products` WHERE `id` = :id", array('id' => $path[1]));
		@$prod_url = $DB->query("SELECT * FROM `products` WHERE `url` = :url OR `url` = :id", array('id' => $path[1], 'url' => $path[2]));
		if(count($prod) == 0 && count($prod_url) == 0) {
			$headers[] = 'HTTP/1.0 404 Not Found';
			$content = '<h1 style="margin-top: 40px;">Error 404</h1>';
		} else {
			if(count($prod) != 0 && $path[2] != $prod[0]['url']) {
				$headers[] = "HTTP/1.1 301 Moved Permanently"; 
				$headers[] = "Location: https://vip-wf.ru/{$prod[0]['id']}/{$prod[0]['url']}"; 
			} else if(count($prod_url) != 0 && $path[1] != $prod_url[0]['id']) {
				$headers[] = "HTTP/1.1 301 Moved Permanently"; 
				$headers[] = "Location: https://vip-wf.ru/{$prod_url[0]['id']}/{$prod_url[0]['url']}"; 
			} else {
				$prod = $prod[0];
				$title = 'Vip-WF - '.$prod['shortname'];
				$kewords .= ", " . $prod['shortname'];
				$mdesc = $prod['description'];
				$meta = "<link rel=\"canonical\" href=\"https://vip-wf.ru/{$prod['id']}/{$prod['url']}/\" />";
				$content = pr($prod['id'], $prod['shortname'], $prod['description'], $prod['url'], $prod['price']);
				$show = load($title,$content,$kewords,$mdesc,$meta);
			}
		}
	}
}
foreach($headers as $header)
	header($header);
echo $show;