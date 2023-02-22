<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  function numberChange($n)
  {
      return intval($n);
  }


    $extraction_id = $_GET['extraction_id'];
   
    if(isset($extraction_id) && !empty($extraction_id)){
  
        $stmt = $conn->prepare('SELECT * FROM extraction  WHERE extraction_id = :extraction_id');
        $stmt->execute(array(
            ':extraction_id'   => $extraction_id,
        )); 
        $count = $stmt->rowCount();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
        $days = explode(";",$resultado['days']);
        $resultado['days'] = array_map('numberChange', $days);
        if($count > 0 AND isset($resultado)){
          http_response_code(201);
          $data = [
              'data'=>$resultado,
              'code'=> 201
            ];
          echo json_encode($data);
        }else{
          http_response_code(404);
          $error = [
              'msg' => $e->getMessage(),
              'code'=> 404
            ];
            echo json_encode($error);
        }
    }
  
?>
