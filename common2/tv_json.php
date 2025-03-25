<?
if (!function_exists('json_encode')) {
	include_once('JSON.php');
	$GLOBALS['JSON_OBJECT'] = new Services_JSON();
	function json_encode($value)
	{
		return $GLOBALS['JSON_OBJECT']->encode($value);
	}

	function json_decode($value)
	{
		if ($value[0] != '[')
			$value = '[' . $value . ']';
		$value = str_replace('\"', '"', $value);
		return $GLOBALS['JSON_OBJECT']->decode($value);
	}
	function tv_json_decode($value)
	{
		if ($value[0] != '[')
			$value = '[' . $value . ']';
		$value = str_replace('\"', '"', $value);
		return $GLOBALS['JSON_OBJECT']->decode($value);
	}
} else {
	function tv_json_decode($value)
	{
		if ($value[0] != '[')
			$value = '[' . $value . ']';
		return json_decode($value);
	}
}
if (!function_exists('json_exec')) {
	function json_exec($con, $sql, $msg = '')
	{
		$qr = $con->exec($sql);
		if (!$qr) {
			$dbmsg = $qr->error();
			$dbmsg = $sql . '<br>' . $msg . '<br>' . $dbmsg;
			echo json_encode(array('success' => false, 'message' => $dbmsg));
			exit;
		}
		return $qr;
	}
}

function toUTF($v)
{
	//	return iconv("windows-1251", "UTF-8", $v);
	return $v;
}

function fromUTF($v)
{
	//	return iconv( "UTF-8", "windows-1251",$v);
	return $v;
}

#Call web service
# input var 1 '$arr_context_params' - array of context  (array) such as: full ServiceName,Login,Password
# input var 1 'arr_data_params' - array of params  (array) with  full web service name (string)

#function wsget($arr_context_params,$arr_data_params, $server = 'oss-api.kyivstar.ua')
#{
#   $client = new SoapClient(NULL,
#        array(
#          //"location" => "http://oss-api.tech.local/cgi-bin/soap-server.cgi",
#          //"location" => "http://10.44.12.143/cgi-bin/soap-server.cgi",
#          //"location" => "http://10.4.0.12:3011/cgi-bin/soap-server.cgi",
#		  "location" => "http://$server/cgi-bin/soap-server.cgi",
#          "uri"      => "urn:ServicePack",
#          "style"    => SOAP_RPC,
#          "use"      => SOAP_ENCODED,
#                  "connection_timeout"  => 600,
#                  'exceptions' => 0
#
#        ));
#        #'encoding'=>'ISO-8859-1'   ,
#
#	$t = $client->__call("WebService",array(array('Context'=>$arr_context_params,'Data'=>$arr_data_params)));
#
#	if (is_soap_fault($t))
#	{
#		//return_error ("SOAP Fault: (faultcode: {$result->faultcode}, faultstring: {$t->faultstring})", E_USER_ERROR);
#	}
#
#
#  return object_to_array($t);
#}

#Convet object to Array
function object_to_array($obj)
{
	$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
	foreach ($_arr as $key => $val) {
		$val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
		$arr[$key] = $val;
	}
	return $arr;
}
#Return array with error massage in json from PHP to Javascript
function return_error($msg)
{
	$returnData = array();
	$returnData['success'] = false;
	$returnData['error_msg'] = $msg;
	echo json_encode($returnData);
	exit;
}
