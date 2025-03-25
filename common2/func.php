<?

function sendJSONFromSQL($con, $sql, $toUTF = false)
{
   $q = $con->exec($sql);
   $ar = array();
   if ($toUTF) {
      while ($r = $q->fetch()) {
         foreach ($r as $k => $v)
            $r[$k] = toUTF($v);
         $ar[] = $r;
      }
   } else {
      while ($r = $q->fetch()) {
         $ar[] = $r;
      }
   }
   echo json_encode(array('success' => true, 'data' => $ar));
   die();
}

function execAPI($con, $sql, $arr = NULL)
{
   if (is_null($arr)) $arr = array();
   try {
      $q = $con->exec($sql, $arr);
   } catch (Exception $e) {
      sendJSONError($e->getMessage());
      die();
   };
   if ($q) {
      echo json_encode(array('success' => true));
   } else {
      sendJSONError($q->error());
   };
   die();
}

function sendJSONError($text)
{
   echo json_encode(array('success' => false, 'err_msg' => $text));
   die();
}
function sendJSON2Error($text)
{
   echo json_encode(array('success' => false, 'message' => $text));
   die();
}

function sendJSONOk()
{
   echo json_encode(array('success' => true));
   die();
}

function getSysTableUpdate($table, $key, $id)
{
   return "
   update $table
   set MODIFIED_USER = '" . $_SESSION['ad_login'] . "'
   where $key = $id;
   ";
}

function getNumStateDecode($field)
{
   return "decode($field,
      'In Use', 'Назначен',
      'Deleted', 'Удален',
      'Not Used', 'Свободен',
      'Reserved', 'Зарезервирован',
      'Sump', 'В отстойнике',
      'Virtual', 'Виртуальный',
      'Technological','Технологический',
      ps.name)";
}
