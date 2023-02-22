<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;

  try {
    $games = [];

    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='dealer'){
      $stmt = $conn->prepare('SELECT * FROM version LIMIT 1');

      $stmt->execute(); 

    }else{
      throw new Exception('Usuário não válido');
    }

    $version = $stmt->fetch(PDO::FETCH_ASSOC);

    http_response_code(201);
    $data = [
      'data'=>$version,
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
