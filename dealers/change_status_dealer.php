<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $data = [
        'dealer_id' => $request->dados->dealer_id,
        'status' => $request->dados->status
    ];
    try {

        $stmt = $conn->prepare("UPDATE dealers SET status= :status WHERE dealer_id= :dealer_id");
        $stmt->execute($data);
        
        http_response_code(201);
        $data = [
          'data'=>null,
          'code'=> 201
        ];
        echo json_encode($data);
      } catch (Exception $e) {
        http_response_code(406);
        $error = [
          'msg' => $e->getMessage(),
          'code'=> 404
        ];
        echo json_encode($error);
      }

  }
?>
