<?
//error_reporting(E_ALL);

include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common/getLogin.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func2.php";

$action = $_REQUEST['action'];
$xaction = $_REQUEST['xaction'];

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

$get_columns_sql = "
	SELECT 
    si.COLUMN_NAME,
    si.DATA_TYPE
FROM all_tab_columns si
WHERE si.TABLE_NAME = 'KS_CRAMER_POWER'

UNION ALL

SELECT 
    'NAME' AS COLUMN_NAME,
    'VARCHAR2' AS DATA_TYPE
FROM dual
";

switch ($action) {
	case 'get_data':
		$sql = "select t.*, l.name, ls.atoll_site_name from KS_CRAMER_POWER t
			LEFT OUTER JOIN location_o l ON t.locationid = l.locationid
			LEFT OUTER JOIN sattab_locationsite_o ls ON t.locationid = ls.locationid ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "WHERE l.name LIKE '%" . $query . "%' OR ls.atoll_site_name LIKE '%" . $query . "%'";
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
		foreach ($data as $dd) {
			$rows = '';
			$columns = [];
			$primary_key_col = 'LOCATIONID';
			foreach ($cols as $key => $val) {
				if ($val['COLUMN_NAME'] == 'NAME' || $val['DATA_TYPE'] == 'DATE' || ($val['COLUMN_NAME'] == $primary_key_col && $_REQUEST['is_update'])) {
					continue;
				}
				$rows .= $val['COLUMN_NAME'] . ' = \'' . $dd->{$val['COLUMN_NAME']} . '\', ';
				$columns[] = $val['COLUMN_NAME'];
				$col_vals[] = $dd->{$val['COLUMN_NAME']};
			}
			if ($_REQUEST['is_update']) {
				$sqld .= 'UPDATE ks_cramer_power
					SET 
						' . $rows . '
						UPDATED_AT = SYSDATE
					WHERE ' . $primary_key_col . ' = ' . $dd->{$primary_key_col} . ';';
			} else {
				$sqld .= 'INSERT INTO ks_cramer_power (' . implode($columns, ', ') . ', CREATED_AT, UPDATED_AT)
						 VALUES (\'' . implode($col_vals, '\', \'') . '\', SYSDATE, SYSDATE);';
			}
		};
		$sql = "begin
			null;
			$sqld
			end;
		";
		//	  echo $sql; die();
		$q = $con->exec($sql);
		if (!$q) {
			return_error($con->error());
		} else {
			sendJSONOk();
		};
		break;
}
