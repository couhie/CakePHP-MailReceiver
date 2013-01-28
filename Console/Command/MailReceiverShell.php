<?php
/**
 * MailReceiverShell.php
 * @author kohei hieda
 *
 */
class MailReceiverShell extends Shell {

	var $tasks = array('MailReceiver.Dispatch');
	var $data = array();
	var $imgDir;

	public function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);
		$this->imgDir = TMP.'img'.DS;
	}

	function startup(){
		parent::startup();
	}

	function execute() {
		$this->getMailData();
	}

	/**
	 * getMailData
	 * @param $data
	 * 今はtextメールのみ対応
	 */
	function getMailData($data = null) {
		App::import('Vendor', 'MailReceiver.QdmailReceiver', array('file'=>'QdmailReceiver'.DS.'qdmail_receiver.php'));
		if (empty($data)) {
			qd_receive_mail('stdin');
		} else {
			qd_receive_mail('direct', $data);
		}

		$this->data = array();
		$this->data['to'] = qd_receive_mail('header', array('to', 'mail'));
		$this->data['from'] = qd_receive_mail('header', array('from', 'mail'));
		$this->data['body'] = qd_receive_mail('body');
		$this->data['charset'] = mb_detect_encoding(qd_receive_mail('body'));

		$images = array();
		$imageArray = array_values(qd_receive_mail('attach'));
		foreach ($imageArray as $key=>$value) {
			if (empty($value['filename'])) {
				continue;
			}
			$filename = $value['filename'];
			while(true){
				if(!file_exists($tmp_filename = $this->imgDir.uniqid())){
					break;
				}
			}
			new File($tmp_filename, true, 0777);
			if(!file_put_contents($tmp_filename, $value['value'])){
				$this->errorLog("Failed to save imgfile to ".$tmp_filename);
				$this->data['errors'][] = "Failed to save imgfile to ".$tmp_filename;
			}
			$images[$key] = array(
				'name' => $filename,
				'type'=>$value['mimetype'],
				'tmp_name' => $tmp_filename,
				'error' => '',
				'filesize' => strlen($value['value']),
			);
		}
		$this->data['images'] = $images;
	}

	function errorLog($msg){
		$logfile = LOGS.DS.'mail_receiver_error.log';
		new File($logfile, true, 0777);
		error_log(date('Y-m-d H:i:s')."\t".$msg."\n", 3, $logfile);
	}

}