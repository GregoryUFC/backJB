<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  $regions = [];

  try {
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='admin'){
      
      $stmt = $conn->prepare('SELECT GR.*, G.name AS game_name 
      FROM game_region AS GR 
      LEFT JOIN region AS R 
      on GR.region_id = R.region_id 
      LEFT JOIN games AS G 
      on GR.game_id = G.game_id 
      WHERE R.banca_id = :user_id
      ORDER BY region_id');

    }else{
      throw new Exception('Usuário não válido');
    }

    $stmt->execute(array(
      ':user_id'   => $user['user_id'],
    )); 
    $count = $stmt->rowCount();

    if($count > 0){
      $index = 0;
      while($row = $stmt->fetch(PDO::FETCH_ASSOC))
      { 
          $regions[$index]['game_region_id']    = $row['game_region_id'];
          $regions[$index]['game_id'] = $row['game_id'];
          $regions[$index]['region_id'] = $row['region_id'];
          $regions[$index]['quotation'] = $row['quotation'];
          $regions[$index]['limit_bet'] = $row['limit_bet'];
          $regions[$index]['game_name'] = $row['game_name'];
          $index++;
      
      }
    }
    http_response_code(201);
    $data = [
      'data'=>$regions,
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
