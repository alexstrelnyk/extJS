<?php

ini_set('max_execution_time', '3600');
ini_set('memory_limit', '512M');
ini_set('upload_max_filesize', '2000M');
ini_set('post_max_size', '500M');


//	var_dump($_FILES);
//	exit(json_encode(var_dump($_FILES))); 

error_reporting(E_ALL);
include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func.php";
//include_once "../../../../../../common2/func2.php";
//include '../../../../../../common/jfunc.php';

function sendJSONData($data)
{
	$res = json_encode(array('success' => true, 'data' => $data));
	$len = strlen($res);
	header("Content-length: $len");
	echo $res;
	die();
}

$con = ksdb_connect('cramer_admin');

foreach ($_FILES as $v) {
	if ($v['size'] > 0) {
		//			sendJSONData(['asdasd']); 
		$fname = $v['tmp_name'];
		//			sendJSONData(['asdasd']); 

		$separator = $_REQUEST['separator'];
		$tname = $_REQUEST['tablesCombo'];

		$file = fopen($fname, 'r');

		$parsed = [];
		if ($file !== false) {
			$step = 0;

			if ($_REQUEST['clear_table']) {
				$sql = "DELETE FROM CRAMER.$tname";
				$con->exec($sql);
			}
			while (($data = fgetcsv($file, 0, $separator)) !== false) {
				$parsed[] = $data;

				if ($cols_length = $_REQUEST['cols_length']) {
					$sql_cols = '';
					$st = 0;
					$skip_cols = [];
					for ($i = 0; $i < $cols_length; $i++) {
						if ($_REQUEST['col_' . $i]) {
							if ($st > 0) {
								$sql_cols .= ', ';
							}
							$sql_cols .= $_REQUEST['col_' . $i];
							$st++;
						} else {
							$skip_cols[] = $i;
						}
					}
					$sql_header = "INSERT INTO CRAMER.$tname " . '(' . $sql_cols . ') VALUES (';

					$cl = 0;
					foreach ($data as $key => $value) {
						if (in_array($key, $skip_cols)) {
							continue;
						}
						if ($cl > 0) {
							$sql_header .= ', ';
						}
						$sql_header .= "'$value'";
						$cl++;
					}
					$sql_header .= ')';

					//			sendJSONData($sql_header); 
					try {
						$q = $con->exec($sql_header);
					} catch (Exception $e) {
						sendJSONError($e->getMessage());
						die();
					};
					if (!$q) {
						sendJSONError($q->error());
					};
				}
				if (!isset($_REQUEST['make_import']) && $step >= 5) {
					sendJSONData($parsed);
				}
				$step++;
			}

			if (!isset($_REQUEST['make_import'])) {
				sendJSONData($parsed);
			}

			fclose($file);
		}
		sendJSONData(true);
	}
}
