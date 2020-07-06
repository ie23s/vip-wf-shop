<?PHP
ini_set('display_errors', 1);
if (@$_GET['pass'] != 'MaxPidor' || @$_GET['secret'] != 'etochistootlox0vzas4itablya') {
    if (@$_GET['secret'] != 'etochistootlox0vzas4itablya') {
        die('<form><input name="pass" /> <input type="submit" />');
    } else {
        die('<form><input name="pass" /><input type="hidden" name="secret" value="etochistootlox0vzas4itablya" /> <input type="submit" />');
    }
    
}

define('DBHost', '127.0.0.1');
define('DBPort', 3306);
define('DBName', 'i1485576_shop');
define('DBUser', 'i1485576_shop');
define('DBPassword', '8aa0af6884c0a434af4b542b672594de');
require(__DIR__ . "/DB/PDO.class.php");
function translit($s)
{
    $s = (string) $s; // преобразуем в строковое значение
    $s = strip_tags($s); // убираем HTML-теги
    $s = str_replace(array(
        "\n",
        "\r"
    ), " ", $s); // убираем перевод каретки
    $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
    $s = trim($s); // убираем пробелы в начале и конце строки
    $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
    $s = strtr($s, array(
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'j',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'c',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ы' => 'y',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya',
        'ъ' => '',
        'ь' => ''
    ));
    $s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
    $s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
    return $s; // возвращаем результат
}
$DB = new Db(DBHost, DBPort, DBName, DBUser, DBPassword);



//Код выполения
if (isset($_POST['remove'])) {
    $rem = array();
    foreach ($_POST as $key => $val) {
        if (strpos($key, 'id-') === 0) {
            $rem[] = str_replace('id-', '', $key);
        }
        
    }
    $remo = '(';
    foreach ($rem as $val) {
        $remo .= $val . ',';
    }
    $remo .= '-1)';
    echo "<h2>Удалено!</h2>";
    
    $DB->query("DELETE FROM `products` WHERE `id` IN {$remo}");
    
    $DB->query("UPDATE `all_product` SET `deleted`=1 WHERE `product_id` IN {$remo}");
    
}
if (isset($_POST['create'])) {
	$url = translit($_POST['name']);
    $DB->query("INSERT INTO `products`(`shortname`, `price`, `count`, `description`, `url`) VALUES ('{$_POST['name']}','{$_POST['cost']}',0,'{$_POST['desc']}', '{$url}')");
    
    $uploaddir  = './icons/';
    $uploadfile = $uploaddir . $DB->lastInsertId() . ".jpg";
    copy($_FILES['uploadfile']['tmp_name'], $uploadfile);
    echo "<h2>Создано!</h2>";
    
}
if (isset($_POST['add'])) {
    $data = str_replace("\r", "", $_POST['tov']);
    $data = explode(PHP_EOL, $data);
    foreach ($data as $val)
	{
        $v = trim($val);
		if(mb_strlen($v,'UTF-8') != 0)
			$DB->query("INSERT INTO `all_product`(`product_id`, `value`) VALUES ('{$_POST['prod']}','{$v}')");
    }
    
    $DB->query("UPDATE `products` SET `count`=`count`+ " . count($data) . " WHERE `id`= {$_POST['prod']}");
    echo "<h2>Добавлено!</h2>";
    
}
if (isset($_POST['remk'])) {
    $data = str_replace("\r", "", $_POST['tov']);
    $data = explode(PHP_EOL, $data);
    foreach ($data as $val) {
        $v = trim($val);
		if(mb_strlen($v,'UTF-8') > 4)
			$DB->query("UPDATE `all_product` SET `deleted`=1 WHERE `value` LIKE '%{$v}%'");
    }
    
	$result = $DB->query("SELECT * FROM `products`");
    foreach ($result as $val) {
		
		$c = $DB->query("SELECT COUNT(*) FROM `all_product` WHERE `deleted` = 0 AND `product_id` = {$val['id']}");
        $DB->query("UPDATE `products` SET `count`={$c[0]['COUNT(*)']} WHERE `id`= {$val['id']}");
	}
    echo "<h2>Удалено!</h2>";
    
}
if (isset($_POST['prio'])) {
    $DB->query("UPDATE `products` SET `priority`='{$_POST['pr']}' WHERE `id`= {$_POST['prod']}");
    echo "<h2>Вроде как, поставил приоритет!</h2>";
    
}
$prods = $DB->query("SELECT * FROM `products` ORDER BY `priority` DESC");

?>
<table>
    <tr>
        <td>
                <h3>
                    Удаление
                </h3>
                    <form method="post" href="">
					
						<div style="width: 400px;height: 300px;overflow: auto;">
							<table>
                        <?PHP
foreach ($prods as $val)
    echo <<<HTML
                                   <tr>
                                            <th style="width: 20px"><input type="checkbox" name="id-{$val['id']}"></th>
                                            <td>{$val['shortname']} (P:{$val['priority']}) (К:{$val['count']})</td>
                                    </tr>
HTML;
?>
						   </table>
						</div>
                        <input type="submit" name="remove" value="Удалить"/>
                    </form>
        </td>
        <td>
            <div style="height: 300px; margin:0 40px">
                <h3>
                    Создать
                </h3>
                <form method="post" href="" enctype=multipart/form-data>
                    <input name="name" placeholder="Имя"/><br />
                    <input name="cost"  placeholder="Цена" /><br />
                    Описание<br />
                    <textarea name="desc"></textarea><br />
                <input name="uploadfile" type="file" /><br />
                    <input type="submit" name="create" value="Создать"/>
                </form>
            </div>
        </td>
        <td>
            <div style="height: 300px; margin:0 40px">
                <h3>
                    Добавить
                </h3>
                <form method="post"  href="">
                    <select name="prod">
                        <option disabled selected>Выбери товар</option>
                        <?PHP
foreach ($prods as $val)
    echo "<option value=\"{$val['id']}\">{$val['shortname']}</option>";
?>
                   </select>
                    <br/>
                    Введи ключи через перенос строки<br />
                    <textarea name="tov"></textarea>
                    <br/>
                    <input type="submit" name="add" value="Добавить"/>
                </form>
            </div>
        </td>
    </tr>
	<tr>
        <td>
            <div style="height: 300px; margin:0 40px">
                <h3>
                    Удалить ключи!!!!
                </h3>
                <form method="post"  href="">
                    Введи ключи через перенос строки<br />
                    <textarea name="tov"></textarea>
                    <br/>
                    <input type="submit" name="remk" value="Удалить ключи"/>
                </form>
            </div>
        </td>
        <td>
            <div style="height: 300px; margin:0 40px">
                <h3>
                    Приоритет
                </h3>
                <form method="post"  href="">
                    <select name="prod">
                        <option disabled selected>Выбери товар</option>
                        <?PHP
foreach ($prods as $val)
    echo "<option value=\"{$val['id']}\">{$val['shortname']}</option>";
?>
                   </select>
                    <br/>
                    <input name="pr" placeholder="Приоритет" /><br />
                    <br/>
                    <input type="submit" name="prio" value="Приоритет"/>
                </form>
            </div>
        </td>
    </tr>
</table>
                <h3>
                    Последние покупки
                </h3>
                    <form method="post" href="">
					
						<div style="min-width: 90vw;height: 300px;overflow: auto;">
							<table>
<?PHP
$cp = $DB->query("SELECT * FROM `checkpay` ORDER BY `id` DESC LIMIT 50");

foreach ($cp as $val) {
	$d = date('[m-d-Y H:i]', $val['time']);
	if($val['valid'] == 1) {
		$valid = '<text style="color:orange">VALID</text>';
	} else {
		if($val['paid'] == 1) {
			$valid = '<text style="color:green">PAID</text>';
		} else {
			$valid = '<text style="color:red">REMOVED</text>';
		}
	}
	
    echo <<<HTML
                                   <tr>
                                            <th style="width: 20px"><input type="checkbox" name="id-{$val['id']}"></th>
                                            <td>{$d}</td>
                                            <td>{$val['email']}</td>
                                            <td>{$val['amount']}</td>
											<td>Сп. тов. в буд.</td>
                                            <td>{$valid}</td>
                                            <td><a href="http://vip-wf.ru/download.php?id={$val['url_id']}">Скачать</a></td>
                                    </tr>
HTML;
}
?>
						   </table>
						</div>
                        <input type="submit" name="removech" value="Удалить"/>
                    </form>