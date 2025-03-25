<?
function sendJSONOk()
{
   echo json_encode(array('success' => true));
   die();
}

function sendJSONData($data)
{
   echo json_encode(array('success' => true, 'data' => $data));
   die();
}

function sendJSONError($text, $data = array())
{
   echo json_encode(array('success' => false, 'message' => toUTF($text), 'data' => $data));
   die();
}

function sendJSONFromSQL($con, $sql, $toUTF = false)
{
   $q = $con->exec($sql);
   if (!$q) throw new Exception($q->error());
   $ar = array();
   if ($toUTF) {
      while ($r = $q->fetch()) {
         foreach ($r as $k => $v)
            $r[$k] = toUTF($v);
         $ar[] = $r;
      }
   } else {
      while ($r = $q->fetch($q)) {
         $ar[] = $r;
      }
   }
   echo json_encode(array('success' => true, 'data' => $ar));
   die();
}

function execAPI($p)
{
   if (!isset($p['params'])) $p['params'] = array();
   foreach ($p['params'] as $k => $v) {
      $p['sql'] = preg_replace('/\?/', "'$v'", $p['sql'], 1);
   };
   try {
      $q = $p['con']->exec($p['sql']);
      if (!$q) echo json_encode(array('success' => false, 'message' => $q->error()));
      //	throw new Exception($q->error());
      if (isset($p['id_sql'])) {
         $id_q = $p['con']->exec($p['id_sql']);
         if (!$id_q) echo json_encode(array('success' => false, 'message' => $q->error()));
         //     throw new Exception($id_q->error());
         $id_r = $id_q->fetch();
         if (!$id_r) echo json_encode(array('success' => false, 'message' => $q->error()));
         //     throw new Exception($id_q->error());
         return $id_r['ID'];
      } else {
         if (isset($p['send_ok'])) {
            sendJSONOk();
         } else
            return true;
      }
   } catch (Exception $e) {
      echo json_encode(array('success' => false, 'message' => $e->getMessage()));
   };
}
