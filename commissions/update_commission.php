<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $vector = [
        'commission_id' => $request->dados->commission_id,
        'name' => $request->dados->name,
        'value' => $request->dados->value,
    ];
    
    try {

        $stmt = $conn->prepare("UPDATE commissions SET name= :name,value= :value WHERE commission_id= :commission_id");
        $stmt->execute($vector);
        
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
