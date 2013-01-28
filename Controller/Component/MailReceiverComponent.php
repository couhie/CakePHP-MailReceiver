<?php
/**
 * MailReceiverComponent.php
 * @author kohei hieda
 *
 */
class MailReceiverComponent extends Component {

	var $settings = null;

	/**
	 * initialize
	 * @param &$controller
	 * @param $settings
	 */
	public function initialize(&$controller, $settings = array()) {
		$default = array(
			'host'=>'localhost',
			'port'=>'110',
			'user'=>'',
			'pass'=>'');

		$this->settings = Set::merge($default, $settings);
	}

	/**
	 * getMailData
	 * @return array
	 */
	public function getMailData() {
		$mailArray = $this->receiveMail($this->settings['host'], $this->settings['port'], $this->settings['user'], $this->settings['pass']);

		App::uses('Shell', 'Console');
		App::uses('MailReceiverShell', 'MailReceiver.Console/Command');
		$mailReceive = new MailReceiverShell();

		$ret = array();
		if (!empty($mailArray)) {
			foreach ($mailArray as $mail) {
				$mailReceive->getMailData($mail);
				$ret[] = $mailReceive->data;
			}
		}
		return $ret;
	}

	/**
	 * receiveMail
	 * @param $host
	 * @param $port
	 * @param $user
	 * @param $pass
	 * @return array
	 */
	private function receiveMail($host, $port, $user, $pass) {
		$fp = fsockopen($host, $port);

		// ログイン
		$line = fgets($fp, 512);
		fputs($fp, "USER $user\r\n");
		$line = fgets($fp, 512);
		fputs($fp, "PASS $pass\r\n");
		$line = fgets($fp, 512);
		if (!eregi("OK", $line)) {
			fclose($fp);
			return false;
		}

		// メールボックス内のデータを取得
		fputs($fp, "STAT\r\n");
		$line = fgets($fp, 512);
		list($stat, $num, $size) = explode(' ', $line);
		if (0 + $num == 0) {
			fclose($fp);
			return false;
		}

		// それぞれ受信して、配列に納める
		for ($id = 1; $id <= $num; $id++) {
			fputs($fp, "RETR $id\r\n");
			$line = fgets($fp);

			$msg[$id] = "";
			while (preg_match("/^\.\r?\n$/i", $line) === 0) {
				$line = fgets($fp, 512);
				$msg[$id] .= $line;
			}

			fputs($fp, "DELE $id\r\n");
			$line = fgets($fp, 512);
		}

		fputs($fp, "QUIT\r\n");
		fclose($fp);

		return $msg;
	}

}