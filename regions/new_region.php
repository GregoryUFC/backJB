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

    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }
      $stmt = $conn->prepare("INSERT INTO region(banca_id,name) VALUES (?,?)");
      if($user['type']=='admin'){
        $stmt->execute([
          $user['user_id'],
          $name,
        ]);
      }else{
        throw new Exception('Usuário não válido');
      }

      $id = $conn->lastInsertId();
      
      $stm = $conn->prepare('SELECT * FROM games ORDER BY game_id');
      $stm->execute(array()); 

      $st = $conn->prepare("INSERT INTO game_region(game_id,region_id,quotation,limit_bet) VALUES (?,?,?,?)");
      while($row = $stm->fetch(PDO::FETCH_ASSOC))
      {
          if($row['type'] !='addition'){
              $row = [$row['game_id'], $id, 0, 0];
              $st->execute($row);
          }
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
