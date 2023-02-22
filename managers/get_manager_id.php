<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $manager_id = $_GET['manager_id'];
   
    if(isset($manager_id) && !empty($manager_id)){
  
        $stmt = $conn->prepare('SELECT * FROM manager  WHERE manager_id = :manager_id');
        $stmt->execute(array(
            ':manager_id'   => $manager_id,
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
