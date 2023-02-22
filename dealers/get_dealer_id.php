<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $dealer_id = $_GET['dealer_id'];
   
    if(isset($dealer_id) && !empty($dealer_id)){
  
        $stmt = $conn->prepare('SELECT dealer_id,name,manager_id,commission_id,phone,limit_credit,region_id FROM dealers WHERE dealer_id = :dealer_id');
        $stmt->execute(array(
            ':dealer_id'   => $dealer_id,
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
            http_response_code(406);
            $error = [
                'msg' => $e->getMessage(),
                'code'=> 404
              ];
            echo json_encode($error);
        }
    }
  
?>
