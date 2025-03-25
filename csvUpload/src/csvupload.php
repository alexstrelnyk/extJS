<?
//error_reporting(E_ALL);

include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func.php";


$action = $_REQUEST['action'];
$xaction = $_REQUEST['xaction'];
if ($_REQUEST['data']) {
	$data = json_decode(preg_replace("/'/", '', $_REQUEST['data']));
}


function get($param)
{
	$result = false;

	if (isset($_REQUEST[$param])) {
		$result = $_REQUEST[$param];
	}

	return $result;
}

switch ($action) {
	case 'get_tables':
		$sql = "SELECT table_name as ID, table_name as NAME
			FROM user_tables
			WHERE table_name in ('TMP4_PRIOR', 'TMP5', 'TEST_CSV')
		";

		sendJSONFromSQL(ksdb_connect('cramer_admin'), $sql);
		break;
	case 'get_table_columns':
		if ($name = get('table_name')) {
			$sql = "select t.COLUMN_NAME,t.DATA_TYPE,t.DATA_LENGTH from all_tab_columns t where t.TABLE_NAME='$name'
			";

			sendJSONFromSQL(ksdb_connect('cramer_admin'), $sql);
		}
		//	echo __FILE__.' '.__LINE__.'<pre>';print_r('sdd').'</pre>';die;
		break;
}
