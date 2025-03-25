<?
error_reporting(E_ALL);

include_once "../../../../../common/gconnect.php";
include_once "../../../../../common2/tv_json.php";
include_once "../../../../../common2/export.php";
include_once "func.php";
include_once "func2.php";
include_once "../../../../../common/oss_api.php";

set_time_limit(1800);
ini_set('max_execution_time', 1800);
ini_set('default_socket_timeout', 1800);

$action = $_REQUEST['action'];

if ($action == 'getAttr') {
	sendJSONFromSQL(ksdb_connect('cramer_admin'), "select col.TABLE_NAME,
   col.COLUMN_NAME,
   decode(col.DATA_TYPE,'DATE','datefield',decode(count(e.enumerationid),0,'textfield','combobox')) type
    from all_tab_columns col
    left join enumeration e on e.tablename||'_O'=col.TABLE_NAME and e.fieldname=col.COLUMN_NAME
    where col.TABLE_NAME=(select t.tablename||'_O' from LOCATIONTYPE_M t
    join location_o l on t.locationtypeid=l.location2locationtype
    where l.locationid=" . $_REQUEST['locid'] . ") and col.COLUMN_NAME not in ('LOCATIONID')
    group by col.TABLE_NAME,col.COLUMN_NAME,col.DATA_TYPE");
} elseif ($action == 'getStore') {
	sendJSONFromSQL(ksdb_connect('cramer_admin'), "select e.sequence id,e.value from ENUMERATION e
   where (upper(e.tablename)||'_O'=upper('" . $_REQUEST['tablename'] . "') or upper(e.tablename)=upper('" . $_REQUEST['tablename'] . "')) and upper(e.fieldname)=upper('" . $_REQUEST['fieldname'] . "')");
} elseif ($action == 'getCommutators') {
	$result = false;

	$c_sql = "select st.ip as ID,n.name||'/'||st.ip as VALUE from node_o n
join (select nodeid,ip from sattab_gt_nodes2_o union
      select nodeid,ip_address as ip from sattab_os6250_24m_node_o) st on st.nodeid=n.nodeid
where n.name like 'MDU%'";
	if (isset($_REQUEST['query']) && $_REQUEST['query']) {
		$c_sql .= " AND (n.name like upper('%" . $_REQUEST['query'] . "%') or st.ip like '%" . $_REQUEST['query'] . "%')";
	} else {
		$c_sql .= " AND rownum <=10";
	}

	sendJSONFromSQL(ksdb_connect('cramer_admin'), $c_sql);
} elseif ($action == 'getParent') {
	sendJSONFromSQL(ksdb_connect('cramer_admin'), "select l.locationid as id,l.name as value from location_o l
where l.location2locationtype=1800000011");
} elseif ($action == 'getDataFromCommutators') {

	$con = ksdb_connect('cramer_admin');
	$sql = "select p.PORTID,p.NAME,bb.kbpsvalue/1000 as BANDWIDTH
from (select nodeid,ip from sattab_gt_nodes2_o
      union
      select nodeid,ip_address as ip from sattab_os6250_24m_node_o) st
join port_vw p on p.PORT2NODE=st.nodeid
left join bandwidth_m bb on bb.bandwidthid=p.PORT2BANDWIDTH
where p.PARENTPORT2PORT is null
and p.PORT2PORTTYPE in (1900000030,1900000025,1900000011,1900000060)
and st.ip='" . $_REQUEST['commutator'] . "'
-- and rownum<=3
order by p.PORTNUMBER";

	$q = $con->exec($sql);


	if (!$q) throw new Exception($q->error());
	$ar = array();
	$rs_id = time();

	session_start();

	$luser = '';

	if (isset($_SESSION['ad_login'])) {
		$luser = $_SESSION['ad_login'];
	}

	while ($r = $q->fetch($q)) {

		$sres1 = wsget(array('ServiceName' => 'OSS_API.Configurator.getPortAllInfo'), array('PORTID' => $r['PORTID'], 'LEVEL' => '2'));
		$sres = wsget(array('ServiceName' => 'OSS_API.HelpDesk.getAbonentCableTest'), array('PORTID' => $r['PORTID']));


		if (property_exists($sres, 'Context')) {
			if ($sres->Context->Code == 1) {
				$r['STATUS'] = '';
				$r['PARAM'] = '';
				if ($sres1->Context->Code == 1) {
					foreach ($sres1->Data->PARAMS as $level2) {
						if ($level2->NAME == 'Опер статус порта') {
							$r['STATUS'] = $level2->VALUE[1];
						}
						if ($level2->NAME == 'Параметры порта') {
							$r['PARAM'] = str_replace('Speed:', '', $level2->VALUE[4]);
						}
					}
				}

				$r['RES'] = $sres->Data;
				//			    $r['RES2'] = $sres1->Data;

				//	echo __FILE__.' '.__LINE__.'<pre>';print_r($r).'</pre>';die;

				$r['P1L'] = '';
				$r['P1S'] = '';
				$r['P2L'] = '';
				$r['P2S'] = '';
				$r['P3L'] = '';
				$r['P3S'] = '';
				$r['P4L'] = '';
				$r['P4S'] = '';

				if (property_exists($r['RES'], 'CableLength')) {
					$CableLength = $r['RES']->CableLength;

					if (is_array($CableLength)) {
						$result = [];
						foreach ($CableLength as $ckey => $value) {
							if ($value->TestName == 'Статус пар') {
								$state = $value->TestValue;
								$lines = explode("\n", $state);

								foreach ($lines as $line) {
									$parts = explode(":", $line);
									$key = trim($parts[0]);
									$value = trim($parts[1]);

									$result['state'][$key] = $value;
								}
							}
							if ($value->TestName == 'Длины пар') {
								$length = $value->TestValue;
								$lines = explode("\n", $length);


								foreach ($lines as $line) {
									$parts = explode(":", $line);
									$key = trim($parts[0]);
									$value = trim($parts[1]);

									$result['length'][$key] = intval($value);
								}
							}
						}

						$r['P1L'] = $result['length'][1];
						$r['P1S'] = $result['state'][1];

						$r['P2L'] = $result['length'][2];
						$r['P2S'] = $result['state'][2];

						$r['P3L'] = $result['length'][3];
						$r['P3S'] = $result['state'][3];

						$r['P4L'] = $result['length'][4];
						$r['P4S'] = $result['state'][4];
					} else {
						if (property_exists($r['RES']->CableLength, 'TestValue')) {

							$string = $r['RES']->CableLength->TestValue;
							$rstatus = $r['RES']->CableLength->TestStatus;

							if ($rstatus) {
								$r['P1S'] = $rstatus;
							} else {
								if (substr($string, 0, 5) == 'Port:') {
									$string = $r['RES']->CableLength->TestValue;


									$pairSubstring = strstr($string, 'Pair A length');
									$pairInfo = explode('Pair ', $pairSubstring);
									$result = [];
									foreach ($pairInfo as $pair) {
										if ($pair) {
											if (preg_match('/^([A-Z])\s+([a-zA-Z]+)\(m\):\s+(\d+)/', $pair, $matches)) {
												$pairName = $matches[1];
												$attribute = $matches[2];
												$value = $matches[3];

												$result[$pairName][$attribute] = $value;
											} else {
												if (preg_match('/^([A-Z]) (state): (.+)$/', $pair, $matches)) {
													$pairName = $matches[1];
													$attribute = $matches[2];
													$value = trim($matches[3]);
													$value = str_replace($_REQUEST['com_name'], '', $value);
													$value = str_replace('<br>', '', $value);

													$result[$pairName][$attribute] = $value;
												}
											}
										}
									}

									$r['P1L'] = $result['A']['length'];
									$r['P1S'] = $result['A']['state'];

									$r['P2L'] = $result['B']['length'];
									$r['P2S'] = $result['B']['state'];

									if (isset($result['C'])) {
										$r['P3L'] = $result['C']['length'];
										$r['P3S'] = $result['C']['state'];
									}
									if (isset($result['D'])) {
										$r['P4L'] = $result['D']['length'];
										$r['P4S'] = $result['D']['state'];
									}
								} else {

									if (substr($string, 0, 6) == 'length') {

										$parts = explode("state:", $string);

										$lengths = explode("/", $parts[0]);
										$states = explode("/", $parts[1]);

										$result = [];

										$firstLength = explode(":", $lengths[0])[1]; // Extract the length value from "length:44"
										$result[] = array(
											'length' => $firstLength,
											'state' => trim($states[0])
										);

										for ($i = 1; $i < count($lengths); $i++) {
											$result[] = array(
												'length' => $lengths[$i],
												'state' => trim($states[$i])
											);
										}

										$r['P1L'] = $result[0]['length'];
										$r['P1S'] = $result[0]['state'];

										$r['P2L'] = $result[1]['length'];
										$r['P2S'] = $result[1]['state'];

										$r['P3L'] = $result[2]['length'];
										$r['P3S'] = $result[2]['state'];

										$r['P4L'] = $result[3]['length'];
										$r['P4S'] = $result[3]['state'];
									} else {

										$lines = explode("\n", $string);

										$result = [];

										foreach ($lines as $line) {
											preg_match('/Pair ([A-Z]) (\w+): (.+)/', $line, $matches);
											if (!empty($matches)) {
												$pair = $matches[1];
												$attribute = $matches[2];
												$value = $matches[3];

												if ($attribute == 'length') {
													$value = intval($value);
												}
												if ($attribute == 'state') {
													$value = substr($value, 0, 50);
												}
												$result[$pair][$attribute] = $value;
											}
										}

										$r['P1L'] = $result['A']['length'];
										$r['P1S'] = $result['A']['state'];

										$r['P2L'] = $result['B']['length'];
										$r['P2S'] = $result['B']['state'];

										if (isset($result['C'])) {
											$r['P3L'] = $result['C']['length'];
											$r['P3S'] = $result['C']['state'];
										}
										if (isset($result['D'])) {
											$r['P4L'] = $result['D']['length'];
											$r['P4S'] = $result['D']['state'];
										}


										if (!$r['P1L'] && property_exists($r['RES']->CableLength, 'TestName')) {
											$test_name = $r['RES']->CableLength->TestName;

											if ($test_name == 'Длина кабеля') {
												$r['P1S'] = $r['RES']->CableLength->TestValue;
											}
										}
									}
								}
							}
						}
					}
				}
				//		echo __FILE__.' '.__LINE__.'<pre>';print_r($r).'</pre>';die;

				$sql = "INSERT INTO
				CRAMER.KS_FTTB_LKD
				(ID, ip, portid, name, BANDWIDTH, P1L, P1S, P2L, P2S, P3L, P3S, P4L, P4S, LDATE, RS_ID, LUSER, STATUS, PARAM)
				VALUES
				(cramer.KS_FTTB_LKD_ID.NEXTVAL, 
				'" . $_REQUEST['commutator'] . "', 
				'" . $r['PORTID'] . "', 
				'" . $r['NAME'] . "', 
				'" . $r['BANDWIDTH'] . "', 					
				'" . intval($r['P1L']) . "', 
				'" . $r['P1S'] . "', 
				'" . intval($r['P2L']) . "', 
				'" . $r['P2S'] . "', 
				'" . intval($r['P3L']) . "', 
				'" . $r['P3S'] . "',
				'" . intval($r['P4L']) . "', 
				'" . $r['P4S'] . "', 
				sysdate,
				" . $rs_id . ",
				'" . $luser . "',
				'" . $r['STATUS'] . "',
				'" . $r['PARAM'] . "'
				)";

				$con->exec($sql);
			} else {
				echo json_encode(array('success' => false, 'message' => $sres->Context->Error));
				die;
			}
		}
		$ar[] = $r;
	}

	echo json_encode(array('success' => true, 'data' => $ar));
} elseif ($action == 'getLastData') {
	$con = ksdb_connect('cramer_admin');
	$sql = "
	SELECT t.*
	FROM CRAMER.KS_FTTB_LKD t
	WHERE IP = '" . $_REQUEST['commutator'] . "'
	AND RS_ID IS NOT NULL
	AND RS_ID = (
		SELECT MAX(RS_ID)
		FROM CRAMER.KS_FTTB_LKD
		WHERE IP = t.IP
	)
	ORDER BY PORTID ASC
	";

	$q = $con->exec($sql);
	if (!$q) throw new Exception($q->error());
	$ar = array();
	while ($r = $q->fetch($q)) {
		$ar[] = $r;
	}

	echo json_encode(array('success' => true, 'data' => $ar));
} elseif ($action == 'export') {
	$con = ksdb_connect('cramer_admin');
	$sql = "
	SELECT ID, ip, portid, name, BANDWIDTH, P1L, P1S, P2L, P2S, P3L, P3S, P4L, P4S, LDATE
	FROM CRAMER.KS_FTTB_LKD t
	WHERE IP = '" . $_REQUEST['commutator'] . "'
	AND RS_ID IS NOT NULL
	AND RS_ID = (
		SELECT MAX(RS_ID)
		FROM CRAMER.KS_FTTB_LKD
		WHERE IP = t.IP
	)
	ORDER BY PORTID ASC
	";
	ExportToExcel($con, $sql, $_REQUEST['commutator_name']);




	//	echo json_encode(array('success'=>true,'data'=>$ar));
	// echo __FILE__.' '.__LINE__.'<pre>';print_r($ar).'</pre>';die;

} elseif ($action == 'getValue') {
	if (!empty($_REQUEST['sattab'])) {
		//sendJSONFromSQL(ksdb_connect('cramer_admin'),"select sat.*,l.* from ".$_REQUEST['sattab']." sat
		//join location_o l on l.locationid=sat.locationid
		//where sat.locationid='".$_REQUEST['locid']."'");

		$result = false;
		$sres = wsget(array('ServiceName' => 'OSS_API.HelpDesk.getAbonentCableTest'), array('PORTID' => '74904738'));
		if (property_exists($sres, 'Context')) {
			if ($sres->Context->Code == 1) {
				$result = $sres->Data->CableLength;
			}
		}
		if ($result) {
			//sendJSONData($result);
			echo json_encode(array('success' => true, 'data' => $result));
			die();
		}
	} else {
		sendJSONFromSQL(ksdb_connect('cramer_admin'), "select l.* from location_o l
     where l.locationid='" . $_REQUEST['locid'] . "'");
	};
} elseif ($action == 'saveattr') {
	$data = json_decode($_REQUEST['data']);
	$sql = "declare
  o_ErrorCode NUMBER;
  o_ErrorText VARCHAR2(200);
  o_result VARCHAR2(200);
  tmp varchar2(200);
  userid NUMBER;
  begin
      CRAMER.getsession();
";


	//echo __FILE__.' '.__LINE__.'<pre>';print_r($data->WLocAttr_CableName).'</pre>';die;
	/*
  foreach ($data as $fk=>$fv) {
    $fv=str_replace("'","''",$fv);
    if (preg_match("[0-9]+\-[0-9]+\-[0-9]+T[0-9]+:[0-9]+:[0-9]",$fv)) {
      $sql.="PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '".str_replace($_REQUEST['cut'],'',$fk)."', o_result);
if (o_result<>to_date(replace('$fv','T',' '),'YYYY-MM-DD HH24:MI:SS')) then 
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '".str_replace($_REQUEST['cut'],'',$fk)."', to_date(replace('$fv','T',' '),'YYYY-MM-DD HH24:MI:SS'));
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('".$_SESSION['ad_login']."','crn_loc_attribute','update','".$_REQUEST['tablename']."',".$_REQUEST['locid'].",'Set ".str_replace($_REQUEST['cut'],'',$fk)." = $fv');
end if;
";
    } elseif ($fk==$_REQUEST['cut'].'LOCATION2PARENTLOCATION') {
    $sql.="PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '".str_replace($_REQUEST['cut'],'',$fk)."', o_result);
if (o_result<>'$fv' or (o_result is null and '$fv' is not null)) then 
pkglocation.setparentlocation(o_ErrorCode, o_ErrorText, ".$_REQUEST['locid'].", '$fv');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('".$_SESSION['ad_login']."','crn_loc_attribute','update','".$_REQUEST['tablename']."',".$_REQUEST['locid'].",'Set ".str_replace($_REQUEST['cut'],'',$fk)." = $fv');
end if;
";
    } elseif ($fk=='MANUAL_PRIORITY') {
    $sql.="PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '$fk', o_result);
if (o_result<>'$fv' or (o_result is null and '$fv' is not null)) then 
pkgkssync_nodes_obj_location.set_site_manual_priority(".$_REQUEST['locid'].", '$fv');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('".$_SESSION['ad_login']."','crn_loc_attribute','update','".$_REQUEST['tablename']."',".$_REQUEST['locid'].",'Set ".str_replace($_REQUEST['cut'],'',$fk)." = $fv');
end if;
";
    } else {
    $sql.="PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '".str_replace($_REQUEST['cut'],'',$fk)."', o_result);
if (nvl(o_result,'~')<>nvl('$fv','~')) then 
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", '".str_replace($_REQUEST['cut'],'',$fk)."', '".$fv."');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
";

if ($fk==$_REQUEST['cut'].'PFJ_TYPE') {
$sql.="select e.value into tmp from enumeration e where e.tablename='SATTAB_LOCATIONPFJ' and e.fieldname='PFJ_TYPE' and e.sequence='$fv';
PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", 'NAME', o_result);
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", 'NAME', regexp_replace(o_result,'\/.*$')||'/'||tmp);
";
};
$sql.="PKGKS_GENERAL.AUDIT('".$_SESSION['ad_login']."','crn_loc_attribute','update','".$_REQUEST['tablename']."',".$_REQUEST['locid'].",'Set ".str_replace($_REQUEST['cut'],'',$fk)." = $fv');
end if;
";  }
  };
  $sql.="
      begin
        select dimuserid into userid from dimuser where upper(name) like upper('".$_SESSION['ad_login']."');
      exception
        when no_data_found then userid:=1;
      end;
        PKGGeneral.SetLastModifiedBy(o_ErrorCode, o_ErrorText, 6, ".$_REQUEST['locid'].", userid);
      if (o_ErrorCode<>0) then raise_application_error (-20102,o_ErrorText); end if;
end;";
//  echo $sql; die();
*/

	$sql = "INSERT INTO
CRAMER.KS_FTTB_LKD
(ID, NAME, STATUS, VALUE, CREATED_AT)
VALUES
(cramer.KS_FTTB_LKD_ID.NEXTVAL, '$data->WLocAttr_CableName', '$data->WLocAttr_CableStatus', '$data->WLocAttr_CableValue', sysdate)
";
	//echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;
	execAPI(ksdb_connect('cramer_admin'), $sql);
}
