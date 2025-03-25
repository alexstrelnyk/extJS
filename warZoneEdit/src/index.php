<?
//error_reporting(E_ALL);

include_once "../../../../../common/gconnect.php";
include_once "../../../../../common2/tv_json.php";
include_once "func.php";
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
} elseif ($action == 'getSatus') {
  sendJSONFromSQL(ksdb_connect('cramer_admin'), "select s.STATUSID as id,s.NAME as value from status s
join statustype st on s.STATUS2STATUSTYPE=st.STATUSTYPEID
where st.STATUSTYPE2DIMENSIONOBJECT=6");
} elseif ($action == 'getParent') {
  sendJSONFromSQL(ksdb_connect('cramer_admin'), "select l.locationid as id,l.name as value from location_o l
where l.location2locationtype=1800000011");
} elseif ($action == 'getValue') {
  if (!empty($_REQUEST['sattab'])) {
    sendJSONFromSQL(ksdb_connect('cramer_admin'), "select sat.*,l.* from " . $_REQUEST['sattab'] . " sat
     join location_o l on l.locationid=sat.locationid
     where sat.locationid='" . $_REQUEST['locid'] . "'");
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
  foreach ($data as $fk => $fv) {
    $fv = str_replace("'", "''", $fv);
    if (preg_match("[0-9]+\-[0-9]+\-[0-9]+T[0-9]+:[0-9]+:[0-9]", $fv)) {
      $sql .= "PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '" . str_replace($_REQUEST['cut'], '', $fk) . "', o_result);
if (o_result<>to_date(replace('$fv','T',' '),'YYYY-MM-DD HH24:MI:SS')) then 
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '" . str_replace($_REQUEST['cut'], '', $fk) . "', to_date(replace('$fv','T',' '),'YYYY-MM-DD HH24:MI:SS'));
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('" . $_SESSION['ad_login'] . "','crn_loc_attribute','update','" . $_REQUEST['tablename'] . "'," . $_REQUEST['locid'] . ",'Set " . str_replace($_REQUEST['cut'], '', $fk) . " = $fv');
end if;
";
    } elseif ($fk == $_REQUEST['cut'] . 'LOCATION2PARENTLOCATION') {
      $sql .= "PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '" . str_replace($_REQUEST['cut'], '', $fk) . "', o_result);
if (o_result<>'$fv' or (o_result is null and '$fv' is not null)) then 
pkglocation.setparentlocation(o_ErrorCode, o_ErrorText, " . $_REQUEST['locid'] . ", '$fv');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('" . $_SESSION['ad_login'] . "','crn_loc_attribute','update','" . $_REQUEST['tablename'] . "'," . $_REQUEST['locid'] . ",'Set " . str_replace($_REQUEST['cut'], '', $fk) . " = $fv');
end if;
";
    } elseif ($fk == 'MANUAL_PRIORITY') {
      $sql .= "PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '$fk', o_result);
if (o_result<>'$fv' or (o_result is null and '$fv' is not null)) then 
pkgkssync_nodes_obj_location.set_site_manual_priority(" . $_REQUEST['locid'] . ", '$fv');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
PKGKS_GENERAL.AUDIT('" . $_SESSION['ad_login'] . "','crn_loc_attribute','update','" . $_REQUEST['tablename'] . "'," . $_REQUEST['locid'] . ",'Set " . str_replace($_REQUEST['cut'], '', $fk) . " = $fv');
end if;
";
    } else {
      $sql .= "PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '" . str_replace($_REQUEST['cut'], '', $fk) . "', o_result);
if (nvl(o_result,'~')<>nvl('$fv','~')) then 
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", '" . str_replace($_REQUEST['cut'], '', $fk) . "', '" . $fv . "');
if (o_ErrorCode<>0) then raise_application_error (-20101,o_ErrorText); end if;
";

      if ($fk == $_REQUEST['cut'] . 'PFJ_TYPE') {
        $sql .= "select e.value into tmp from enumeration e where e.tablename='SATTAB_LOCATIONPFJ' and e.fieldname='PFJ_TYPE' and e.sequence='$fv';
PKGGeneral.GetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", 'NAME', o_result);
PKGGeneral.SetObjectAttribute(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", 'NAME', regexp_replace(o_result,'\/.*$')||'/'||tmp);
";
      };
      $sql .= "PKGKS_GENERAL.AUDIT('" . $_SESSION['ad_login'] . "','crn_loc_attribute','update','" . $_REQUEST['tablename'] . "'," . $_REQUEST['locid'] . ",'Set " . str_replace($_REQUEST['cut'], '', $fk) . " = $fv');
end if;
";
    }
  };
  $sql .= "
      begin
        select dimuserid into userid from dimuser where upper(name) like upper('" . $_SESSION['ad_login'] . "');
      exception
        when no_data_found then userid:=1;
      end;
        PKGGeneral.SetLastModifiedBy(o_ErrorCode, o_ErrorText, 6, " . $_REQUEST['locid'] . ", userid);
      if (o_ErrorCode<>0) then raise_application_error (-20102,o_ErrorText); end if;
end;";
  //  echo $sql; die();
  execAPI(ksdb_connect('cramer_admin'), $sql);
}
