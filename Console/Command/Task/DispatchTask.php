<?php
/**
 * DispatchTask.php
 * @author kohei hieda
 *
 */
class DispatchTask extends MailReceiverShell {

	var $controller = '';
	var $action = '';

	function __construct($stdout = null, $stderr = null, $stdin = null) {
		parent::__construct($stdout, $stderr, $stdin);
	}

	function execute() {
		if (!$this->getArgs()) {
			return;
		}
		parent::execute();
		$this->dispatch();
	}

	function getArgs() {
		if (count($this->args) < 2) {
			return false;
		}
		$this->controller = $this->args[0];
		$this->action = $this->args[1];
		return true;
	}

	function dispatch() {
		$_POST = $this->data;
		App::uses('Dispatcher', 'Routing');
		$Dispatcher = new Dispatcher();
		$Dispatcher->dispatch(new CakeRequest("/{$this->controller}/{$this->action}"), new CakeResponse(array('charset' => Configure::read('App.encoding'))));
	}

}