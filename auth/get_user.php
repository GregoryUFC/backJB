<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

  $dados = file_get_contents("php://input");

  try {
    if(isset($dados) && !empty($dados)){
      $request = json_decode($dados);
      $token = $request->token;
      $decode = base64_decode($token);

      $pieces = explode("/", $decode);
      
      $stm = $conn->prepare('SELECT * FROM auth WHERE uuid = :uuid AND type = :type');
      $stm->execute(array(
      ':uuid' => $pieces[0],
      ':type' => $pieces[1]
      ));     
      $auth = $stm->fetch(PDO::FETCH_ASSOC); 
      
      switch ($pieces[1]) {
        case 'admin':
            $stmt = $conn->prepare('SELECT name,footer FROM banca WHERE auth_id = :auth_id');
            break;
        case 'manager':
            $stmt = $conn->prepare('SELECT name,region_id,status FROM manager WHERE auth_id = :auth_id');
            break;
        case 'dealer':
            $stmt = $conn->prepare('SELECT dealer_id,name,region_id,status,phone FROM dealers WHERE auth_id = :auth_id');
            break;
      }
    
      $stmt->execute(array(
      ':auth_id'   => $auth['auth_id']
      ));     
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
      $resultado['type'] = $pieces[1];
      http_response_code(201);
      $data = [
        'data'=>$resultado,
        'code'=> 201
      ];
      echo json_encode($data);
    }
  }catch (Exception $e) {
    http_response_code(404);
    $error = [
      'msg' => $e->getMessage(),
      'code'=> 404
    ];
    echo json_encode($error);
  }
?>
