<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $list = $request->dados;

    try {
      $token = $headers['Auth'];
      $user=[];
  
      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      if($user['type']=='admin'){
  
        $stmt = $conn->prepare("UPDATE game_region SET quotation= :quotation, limit_bet= :limit_bet WHERE game_region_id= :game_region_id");
        foreach ($list as $gameRegion) {
          $data = [
            'quotation' => $gameRegion->quotation,
            'limit_bet' => $gameRegion->limit_bet,
            'game_region_id' => $gameRegion->game_region_id
          ];
          $stmt->execute($data);
        }
  
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
