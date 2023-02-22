<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $data = [
        'manager_id' => $request->dados->manager_id,
        'status' => $request->dados->status
    ];
    try {

        $stmt = $conn->prepare("UPDATE manager SET status= :status WHERE manager_id= :manager_id");
        $stmt->execute($data);
        
        http_response_code(201);
        $data = [
          'data'=>null,
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

  }
?>
