<?PHP
define('DBHost', '127.0.0.1');
define('DBPort', 3306);
define('DBName', 'i1485576_shop');
define('DBUser', 'i1485576_shop');
define('DBPassword', '8aa0af6884c0a434af4b542b672594de');
require(__DIR__ . "/DB/PDO.class.php");

class Functions {
	public $DB;
	public $autosubmit = '<script type="text/javascript"> window.onload=function(){ document.forms["form"].submit(); } </script>';
	public function __construct() {
		$this->DB = new Db(DBHost, DBPort, DBName, DBUser, DBPassword);
	}
	public function getProducts() {
		$result = $this->DB->query("SELECT * FROM `products` WHERE `count` > 0 ORDER BY `priority` DESC");
		for($i = 0; $i < count($result); $i++)
			if($result[$i]['count'] > 3)
				$result[$i]['count'] = 3;
		return json_encode($result);
	}
	public function getProduct($id) {
		$result = $this->DB->query("SELECT * FROM `products` WHERE `id` = {$id}");
		return $result[0];
	}
	public function validate($json) {
		if (!isset($json->email) || !isset($json->paysystem) || !isset($json->card[0][1]) || !isset($json->card[0][0]))
			return 20;
		foreach ($json->card as $card) {
			$result = $this->DB->query("SELECT * FROM `products` WHERE `id` = :id", array(
				'id' => $card[0]
			));
			if (count($result) == 0) {
				return 21;
			}
			
			$count = (int) $card[1];
			
			if ($result[0]['count'] < $count) {
				return 25;
			}
		}
		return 11; //Success
	}
	public function getPrice($id) {
		$result = $this->DB->query("SELECT * FROM `products` WHERE `id` = :id", array(
			'id' => $id
		));
		if (count($result) == 0) {
			return 0;
		}
		return $result[0]['price'];
	}
	public function cron() {
		$result = $this->DB->query("SELECT * FROM `checkpay` WHERE `time` < :time AND `valid` = 1", array(
			'time' => (time() - 900)
		));
		foreach ($result as $val) {
			foreach (json_decode($val['products']) as $card) {
				$this->DB->query("UPDATE `products` SET `count`=`count`+:count WHERE `id`=:id", array(
					'id' => $card[0],
					'count' => $card[1]
				));
			}
		}
		$this->DB->query("UPDATE `checkpay` SET `valid`=0  WHERE `time` < :time AND `valid` = 1", array(
			'time' => (time() - 900)
		));
	}
	
	public function sendEmail($order_id, $email, $url_id, $err, $v) {
		if($v)
		{
			$status = " выполнен успешно!";
			$text = "<p>Ваш заказ успешно выполнен и оплачен, вы можете получить свои коды по ссылке ниже.</p><a href=\"http://vip-wf.ru/download.php?id={$url_id}\">http://vip-wf.ru/download.php?id={$url_id}</a>";
		}
		else
		{
			$status = " не выполнен в следствии ошибки №{$err}!";
			$text = "<p>Ваш заказ не выполнен из-за ошибки №{$err}. Просим обратиться Вас по адресу contact@vip-wf.ru, в случае потери денег. Мы ответим Вам в ближайшее время.</p>";
		}
		
		$to  = "Клиент Vip-WF.RU <{$email}>" ;

		$subject = 'Заказ №'.$order_id.$status;
		$message = '
		<html> 
			<head> 
				<title>Заказ №'.$order_id.$status.'</title> 
			</head> 
			<body>
				'.$text.'
				<p>Спасибо за сотрудничество, с уважением, команда Vip-WF.RU.</p>
			</body> 
		</html>'; 

		$headers  = "Content-type: text/html; charset=utf-8 \r\n"; 
		$headers .= "From: VipWF.RU Team <team@vip-wf.ru>\r\n";

		mail($to, $subject, $message, $headers); 
	}
	
	public function pay($json) {
		$check = $this->DB->query("SELECT * FROM `checkpay` WHERE `url_id`=:id", array(
			'id' => $json->id
		));
		if (count($check) == 0) {
			return 'Error ' . 22;
		}
		if ($check[0]['valid'] == 0) {
			return 'Error ' . 23;
		}
		$order_id = $check[0]['id'];
		$sum      = $check[0]['amount'];
		$url_id   = $check[0]['url_id'];
		switch ($json->paysystem) {
			case 1: //YandexMoney
				$form = "<form name=\"form\" method=\"POST\" action=\"https://money.yandex.ru/quickpay/confirm.xml\">
                            <input type=\"hidden\" name=\"receiver\" value=\"410017861915452\">
                            <input type=\"hidden\" name=\"quickpay-form\" value=\"shop\">
                            <input type=\"hidden\" name=\"targets\" value=\"Оплата заказа №{$order_id}\">
                            <input type=\"hidden\" name=\"paymentType\" value=\"PC\">
                            <input type=\"hidden\" name=\"sum\" value=\"{$sum}\" data-type=\"number\">
                            <input type=\"hidden\" name=\"label\" value=\"{$order_id}\">
                            <input type=\"hidden\" name=\"successURL\" value=\"http://vip-wf.ru/download.php?id={$url_id}\">
                            <input type=\"submit\" value=\"Перевести\">
                        </form>" . $this->autosubmit;
				break;
			case 2: //VISA/Mastercars
				$form = "<form name=\"form\" method=\"POST\" action=\"https://money.yandex.ru/quickpay/confirm.xml\">
                            <input type=\"hidden\" name=\"receiver\" value=\"410017861915452\">
                            <input type=\"hidden\" name=\"quickpay-form\" value=\"shop\">
                            <input type=\"hidden\" name=\"targets\" value=\"Оплата заказа №{$order_id}\">
                            <input type=\"hidden\" name=\"paymentType\" value=\"AC\">
                            <input type=\"hidden\" name=\"sum\" value=\"{$sum}\" data-type=\"number\">
                            <input type=\"hidden\" name=\"label\" value=\"{$order_id}\">
                            <input type=\"hidden\" name=\"successURL\" value=\"http://vip-wf.ru/download.php?id={$url_id}\">
                            <input type=\"submit\" value=\"Перевести\">
                        </form>" . $this->autosubmit;
				break;
			case 3: //WebMoney
				$form = "<form name=\"form\" method=\"POST\" action=\"https://merchant.webmoney.ru/lmi/payment_utf.asp\">
                          <input type=\"hidden\" name=\"LMI_PAYEE_PURSE\" value=\"R237193955339\">
                          <input type=\"hidden\" name=\"LMI_PAYMENT_AMOUNT\" value=\"{$sum}\">
                          <input type=\"hidden\" name=\"LMI_PAYMENT_NO\" value=\"{$order_id}\">
                          <input type=\"hidden\" name=\"LMI_PAYMENT_DESC\" value=\"Оплата заказа №{$order_id}\">
                          <input type=\"hidden\" name=\"LMI_SUCCESS_URL\" value=\"http://vip-wf.ru/download.php?id={$url_id}\">
                          <input type=\"submit\" value=\"Перевести\">
                        </form>" . $this->autosubmit;
				break;
			case 4: //QIWI
				$form = "<form name=\"form\" method=\"GET\" action=\"https://oplata.qiwi.com/create\">
                          <input type=\"hidden\" name=\"billId\" value=\"{$url_id}\">
                          <input type=\"hidden\" name=\"amount\" value=\"{$sum}\">
                          <input type=\"hidden\" name=\"publicKey\" value=\"2S7mpWSvB93qSAr7uYNu2Vvnd2pTVzxEviw6chKKbG9xyy9pcxcvrmne6c6m7cUabcbN8Gnkjk77SEeN2YVBvr9TQaKHJqrtxQ5hnGeLF1D7jv4wxihiqQMiHgE2Us7AZTEP1jC3kT71H8FnbKzBBZfvsbuDJBnVs2cxSnhPJdyaZ8CP6qP7WnwtdE1vRdBmar3JAY3SXAmwB5z2ktCEMYanvkNCCRK1GD\">
                          <input type=\"hidden\" name=\"successUrl\" value=\"http://vip-wf.ru/download.php?id={$url_id}\">
                          <input type=\"submit\" value=\"Перевести\">
                        </form>" . $this->autosubmit;
				break;
		}
		return $form;
		
	}
	public function generatePayLink($json) {
		$valid = $this->validate($json);
		if ($valid != 11) {
			return json_encode(array(
				"code" => $valid
			));
		}
		$sum = 0;
		foreach ($json->card as $card) {
			$sum += $this->getPrice($card[0]) * $card[1];
		}
		$url_id = md5(uniqid(rand(), true));
		$qiwi = 0;
		if($json->paysystem == 4)
			$qiwi = 1;
		$result = $this->DB->query("INSERT INTO `checkpay`(`products`, `email`, `time`, `valid`, `amount`, `url_id`, `isqiwi`)
                                    VALUES (:prods, :email, :time, 1, :amount, :url_id, :q)", array(
			'prods' => json_encode($json->card),
			'email' => $json->email,
			'time' => time(),
			'amount' => $sum,
			'url_id' => $url_id,
			'q' => $qiwi
		));
		
		foreach ($json->card as $card) {
			$this->DB->query("UPDATE `products` SET `count`=`count`-:count WHERE `id`=:id", array(
				'id' => $card[0],
				'count' => $card[1]
			));
		}
		
		$j = array(
			'path' => 31,
			'paysystem' => $json->paysystem,
			'id' => $url_id
		);
		return json_encode(array(
			"code" => $valid,
			'url_id' => $url_id,
			"url" => "http://vip-wf.ru/processor.php?json=" . urlencode(json_encode($j))
		));
	}
	
	public function checkYandexPay() {
		$operation_id        = $_POST['operation_id'];
		$amount              = $_POST['amount'];
		$sender              = $_POST['sender'];
		$notification_secret = '2Jp/CtAJA0H6z4sNR2OrzYrX';
		$label               = $_POST['label'];
		$label               = 1;
		$integration         = sha1($_POST['notification_type'] . '&' . $operation_id . '&' . $amount . '&' . $_POST['currency'] . '&' . $_POST['datetime'] . '&' . $sender . '&' . $_POST['codepro'] . '&' . $notification_secret . '&' . $label);
		if (strcasecmp($integration, $_POST['sha1_hash']) != 0) {
			$this->DB->query("UPDATE `checkpay` SET `valid` = 0 WHERE `id`=:label", array(
				'label' => $label
			));
			//TODO Send email!
			return 21; //Error Code!
		}
		$check = $this->DB->query("SELECT * FROM `checkpay` WHERE `id`=:label", array(
			'label' => $label
		));
		if (count($check) == 0) {
			return 22;
		}
		if ($check[0]['valid'] == 0) {
			return 23;
		}
		if ($check[0]['amount'] != $_POST['withdraw_amount']) {
			return 24;
		}
		//TODO Success
		$this->DB->query("UPDATE `checkpay` SET `valid` = 0, `operation_id`=:opid, `sender`=:sender, `paid` = 1 WHERE `id`=:label", array(
			'label' => $label,
			'opid' => $operation_id,
			'sender' => $sender
		));
		return 10; //Success Operation
		
	}
	
	public function checkWMPay() {
		$sign  = 'R237193955339' . $_POST['LMI_PAYMENT_AMOUNT'] . $_POST['LMI_PAYMENT_NO'] . $_POST['LMI_MODE'] . $_POST['LMI_SYS_INVS_NO'] . $_POST['LMI_SYS_TRANS_NO'] . $_POST['LMI_SYS_TRANS_DATE'] . '6f1ddae537bb5031131fb21ef9344125' . $_POST['LMI_PAYER_PURSE'] . $_POST['LMI_PAYER_WM'];
		$hash  = hash('sha256', $sign);
		$label = $_POST['LMI_PAYMENT_NO'];
		if (strcasecmp($hash, $_POST['LMI_HASH']) != 0){
			$nv = $this->DB->query("SELECT * FROM `checkpay` WHERE `id` = :label AND `valid` = 1", array(
				'label' => $label
			));
			foreach (json_decode($nv[0]['products']) as $card) {
				$this->DB->query("UPDATE `products` SET `count`=`count`+:count WHERE `id`=:id", array(
					'id' => $card[0],
					'count' => $card[1]
				));
			}
			$this->DB->query("UPDATE `checkpay` SET `valid` = 0 WHERE `id`=:label", array(
				'label' => $label
			));
			$this->sendEmail($label, $nv[0]['email'], 0, 21, false);
			return 21; //Error Code!
		}
		$check = $this->DB->query("SELECT * FROM `checkpay` WHERE `id`=:label", array(
			'label' => $label
		));
		if (count($check) == 0) {
			return 22;
		}
		if ($check[0]['valid'] == 0) {
			$this->sendEmail($label, $nv[0]['email'], 0, 23, false);
			return 23;
		}
		if ($check[0]['amount'] != $_POST['LMI_PAYMENT_AMOUNT']) {
			$this->sendEmail($label, $nv[0]['email'], 0, 24, false);
			return 24;
		}
		//TODO Success
		$this->DB->query("UPDATE `checkpay` SET `valid` = 0, `operation_id`=:opid, `sender`=:sender, `paid` = 1 WHERE `id`=:label", array(
			'label' => $label,
			'opid' => $_POST['LMI_SYS_TRANS_NO'],
			'sender' => $_POST['LMI_PAYER_WM']
		));
		$this->sendEmail($label, $nv[0]['email'], $nv[0]['url_id'], 21, true);
		return 10; //Success Operation
		
	}
	
	public function isPaid($id) {
		$check = $this->DB->query("SELECT * FROM `checkpay` WHERE `url_id`=:id", array(
			'id' => $id
		));
		if (count($check) == 0) {
			return json_encode(array(
				"code" => 22
			));
		}
		if ($check[0]['valid'] == 0 && $check[0]['paid'] == 0) {
			return json_encode(array(
				"code" => 23
			));
		}
		return json_encode(array(
			'code' => 12,
			'paid' => $check[0]['paid']
		)); //Success Operation
		
	}
	
	public function getTextOfO($id) {
		
		if (!$this->isPaid($id))
			return json_encode(array(
				"code" => 23
			));
		$check = $this->DB->query("SELECT * FROM `checkpay` WHERE `url_id`=:id", array(
			'id' => $id
		));
		$text  = "";
		if (empty($check[0]['text'])) {
			$toremove = array();
			
			foreach (json_decode($check[0]['products']) as $val) {
				$name = $this->getProduct($val[0])['shortname'];
				$len = strlen($name);
				if ($len % 2 != 0) {
					$len++;
					$name .= "-";
				}
				$def = "";
				for ($i = 0; $i < (8 - ($len / 2)); $i++)
					$def .= '-';
				$text .= $def . $name . $def . "\n";
				
				$prdsssss = $this->DB->query("SELECT * FROM `all_product` WHERE `product_id` = :id AND `deleted` = 0 LIMIT {$val[1]}", array(
					'id' => $val[0]
				));
				foreach ($prdsssss as $val1) {
					$toremove[] = $val1['id'];
					$text .= $val1['value'] . "\n";
					
				}
				$prdsssss = $this->DB->query("SELECT * FROM `all_product` WHERE `product_id` = :id AND `deleted` = 0 LIMIT {$val[1]}", array(
					'id' => $val[0]
				));
				$text .= "\n";
			}
			$remo = '(';
			foreach ($toremove as $val) {
				$remo .= $val . ',';
			}
			$remo .= '-1)';
			$this->DB->query("UPDATE `all_product` SET `deleted`=1 WHERE `id` IN {$remo}");
			$this->DB->query("UPDATE `checkpay` SET `text`=:text WHERE `url_id` = :id ", array(
				'id' => $id,
				'text' => $text
			));
			return $text;
		}
		return $check[0]['text'];
		
	}
	
	public function checkQIWI() {
		$result = $this->DB->query("SELECT * FROM `checkpay` WHERE `isqiwi` = 1 AND `valid` = 1");
		foreach ($result as $val) {
			$check = $this->checkQIWIBill($val['url_id']);
			if ($check == 10) {
				$this->DB->query("UPDATE `checkpay` SET `valid`= 0, `paid` = 1 WHERE `url_id`=:id", array(
					'id' => $val['url_id']
				));
			
			}
			if((int)($check / 10) != 1) {
				$this->DB->query("UPDATE `checkpay` SET `valid`= 0 WHERE `url_id`=:id", array(
					'id' => $val['url_id']
				));
			}
		}
	}
	public function checkQIWIBill($id) {
		$SECRET_KEY = 'eyJ2ZXJzaW9uIjoiUDJQIiwiZGF0YSI6eyJwYXlpbl9tZXJjaGFudF9zaXRlX3VpZCI6ImNmYmI3ZmFiLWM0OWQtNGQ5ZS1hNjU2LWY3MDNiODgzNTNlYSIsInVzZXJfaWQiOiI3OTEzMTUwMDA5MCIsInNlY3JldCI6Ijk3NjIyOTg0ODQ2Y2VhYTBlOWU4YTRiMmJiNjQxYmQwMWE4M2MyZTZhZGE3ODJiYWQ0NDkzZWYzMzBjYzY4MjgifX0=';
		
		$url = "https://api.qiwi.com/partner/bill/v1/bills/" . $id;
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, Array(
			"Authorization: Bearer {$SECRET_KEY}"
		));
		$result = curl_exec($ch);
		curl_close($ch);
		$r = json_decode($result);
		$checking = $this->DB->query("SELECT * FROM `checkpay` WHERE `url_id`=:id", array(
				'id' => $id
			));

		if (count($checking) == 0) {
			return 22;
		}
		if ($checking[0]['valid'] == 0) {
			$this->sendEmail($checking[0]['id'], $checking[0]['email'], 0, 23, false);
			return 23;
		}
		if (floatval($r->amount->value) != $checking[0]['amount']) {
			$this->sendEmail($checking[0]['id'], $checking[0]['email'], 0, 24, false);
			return 24;
		}
		if ($r->status->value == "PAID") {
			$this->sendEmail($checking[0]['id'], $checking[0]['email'], $checking[0]['url_id'], 0, true);
			return 10;
		}
		return 13;
	}
}
