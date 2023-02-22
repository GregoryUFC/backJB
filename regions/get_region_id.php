<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $region_id = $_GET['region_id'];
   
    if(isset($region_id) && !empty($region_id)){
  
        $stmt = $conn->prepare('SELECT * FROM region  WHERE region_id = :region_id');
        $stmt->execute(array(
            ':region_id'   => $region_id,
        )); 
        $count = $stmt->rowCount();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
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
