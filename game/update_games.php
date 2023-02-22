<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  $dados = file_get_contents("php://input");
  $headers = apache_request_headers() ;
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $game_id = $request->dados->game_id;
    $status = $request->dados->status;

    try {
      $token = $headers['Auth'];
      $user=[];
  
      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }
  
      if($user['type']=='admin'){
  
        $stmt = $conn->prepare("UPDATE game_status SET status= :status WHERE game_id= :game_id AND banca_id = :user_id");
        $stmt->execute([
          ':status' => $status=='1'?0:1,
          ':game_id' => $game_id,
          ':user_id'   => $user['user_id'],
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
