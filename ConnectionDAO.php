<?php
class ConnectionDAO{
	
	private $pdo;
	public function __construct () {
		try {
			$this->pdo = new PDO("sqlsrv:server=localhost;Database=TUWR_admin",  "usr_tuwr_admin", "pass_tuwr_admin");
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e) {
			echo "Houston, tenemos un problema: " . $e->getMessage();
			die();
		}
	}
	protected function getPDO() {
		return $this->pdo;
	}
}