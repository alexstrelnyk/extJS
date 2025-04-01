<?
//error_reporting(E_ALL);

include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common/getLogin.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func2.php";

$action = $_REQUEST['action'];
$xaction = $_REQUEST['xaction'];
$primary_key_col = 'LOCID';
$static_fields = [
	$primary_key_col,
	'NAME',
	'ATOLL_SITE_NAME',
];

getLogin();
checkAccess();
$role = (isset($_SESSION['roles']['Cramer navigator']['Standart user']) ? 1 : 0);

$con = ksdb_connect('cramer_admin');

function getSQLData($con, $sql)
{
	$q = $con->exec($sql);
	if (!$q) throw new Exception($q->error());
	$ar = array();
	while ($r = $q->fetch($q)) {
		$ar[] = $r;
	}

	return $ar;
}
$static_cols = '';
foreach ($static_fields as $col) {
	$static_cols .= "SELECT 
    '$col' AS COLUMN_NAME,
    'VARCHAR2' AS DATA_TYPE
FROM dual

UNION ALL ";
}
$get_columns_sql = $static_cols . "
	SELECT 
    si.COLUMN_NAME,
    si.DATA_TYPE
FROM all_tab_columns si
WHERE si.TABLE_NAME = 'KS_CRAMER_POWER'
";


switch ($action) {
	case 'get_data':
		$sql = "select l.locationid as $primary_key_col, l.name, ls.atoll_site_name, t.* from CRAMER.location_o l
LEFT OUTER JOIN CRAMER.KS_CRAMER_POWER t ON t.locationid = l.locationid
LEFT OUTER JOIN CRAMER.sattab_locationsite_o ls ON l.locationid = ls.locationid
WHERE l.location2locationtype=1900000001
AND rownum < 10 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND l.name = '" . $query . "' OR ls.atoll_site_name = '" . $query . "'";
		}
		if (isset($_REQUEST['locd']) && $locd = $_REQUEST['locd']) {
			$sql .= "AND t.locationid = " . $locd;
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_enumerations':
		$sql = "select e.* from enumeration e 
			WHERE e.TABLENAME LIKE 'KS_CRAMER_POWER' ";
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_columns':
		sendJSONFromSQL($con, $get_columns_sql, false);
		break;
	case 'save_data':
		$cols = getSQLData($con, $get_columns_sql);

		$data = tv_json_decode($_REQUEST['data']);
		if (isset($_SESSION['ad_login'])) {
			$user = $_SESSION['ad_login'];
		} else {
			return_error('Not authorized');
			die();
		};
		$sqld = '';
		$sql = '';
		array_push($static_fields, 'LOCATIONID');
		array_push($static_fields, 'CREATED_AT');
		array_push($static_fields, 'UPDATED_AT');

		foreach ($data as $dd) {
			$rows = '';
			$columns = [];
			$col_vals = [];
			foreach ($cols as $key => $val) {
				$col_name = $val['COLUMN_NAME'];
				$col_type = $val['DATA_TYPE'];
				$col_val = $dd->{$col_name};
				if (in_array($col_name, $static_fields)) {
					continue;
				}
				$columns[] = $col_name;
				switch ($col_type) {
					case 'NUMBER':
						$parsed_val = $col_vals[] = $col_val ? $col_val : 'NULL';
						break;
					case 'DATE':
						$parsed_val = $col_vals[] = $col_val ? 'TO_DATE(\'' . date('Y-m-d H:i:s', strtotime($col_val)) . '\', \'YYYY-MM-DD HH24:MI:SS\')' : 'NULL';
						break;
					default:
						$parsed_val = $col_vals[] = $col_val ? '\'' . $col_val . '\'' : 'NULL';
				}
				$rows .= $col_name . ' = ' . $parsed_val . ', ';
			}
			if ($dd->{'LOCATIONID'}) {
				$sqld .= '
UPDATE ks_cramer_power
	SET 
' . $rows . '
UPDATED_AT = SYSDATE
WHERE LOCATIONID = ' . $dd->{$primary_key_col} . ';';
			} else {
				$sqld .= '
INSERT INTO ks_cramer_power (LOCATIONID, ' . implode($columns, ', ') . ', CREATED_AT, UPDATED_AT)
VALUES (' . $dd->{$primary_key_col} . ', ' . implode($col_vals, ', ') . ', SYSDATE, SYSDATE);';
			}
		};
		$sql = "begin null; 
$sqld end;";
		//	echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;
		$q = $con->exec($sql);
		if (!$q) {
			return_error($con->error());
		} else {
			sendJSONOk();
		};
		break;
}
