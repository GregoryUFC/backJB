<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  $dados = file_get_contents("php://input");
  $headers = apache_request_headers() ;
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $name = $request->dados->name;
    $value = $request->dados->value;

    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      $stmt = $conn->prepare("INSERT INTO commissions(banca_id,name,value) VALUES (?,?,?)");
      if($user['type']=='admin'){
        $stmt->execute([
          $user['user_id'],
          $name,
          $value,
        ]);
      }else{
        throw new Exception('Usuário não válido');
      }

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