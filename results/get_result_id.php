<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  function numberChange($n)
  {
      return intval($n);
  }


    $result_id = $_GET['result_id'];
   
    if(isset($result_id) && !empty($result_id)){
  
        $stmt = $conn->prepare('SELECT * FROM results  WHERE result_id = :result_id');
        $stmt->execute(array(
            ':result_id'   => $result_id,
        )); 
        $count = $stmt->rowCount();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
        $resultado['day'] = (strtotime($resultado['day'])+ 60*60*3 )*1000;
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
