<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $commission_id = $_GET['commission_id'];
   

    try {
        $stmt = $conn->prepare('SELECT * FROM commissions  WHERE commission_id = :commission_id');
        $stmt->execute(array(
            ':commission_id'  => $commission_id,
        )); 
        $count = $stmt->rowCount();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
        if($count < 0 OR !isset($resultado)){
            throw new Exception('Comissão não encontrada');
        }
        http_response_code(201);
        $data = [
            'data'=>$resultado,
            'code'=> 201
        ];
        echo json_encode($data);
    } catch (Exception $e) {
        http_response_code(404);
        $error = [
            'msg' => $e->getMessage(),
            'code'=> 404
          ];
        echo json_encode($error);
    }
        

    
  
?>
