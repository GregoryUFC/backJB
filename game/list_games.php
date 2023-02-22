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

    if($user['type']=='admin'){
      $stmt = $conn->prepare('SELECT G.*,GS.status FROM games AS G
      LEFT JOIN game_status as GS 
      on GS.game_id = G.game_id 
      WHERE GS.banca_id = :user_id AND G.type !="addition"
      ORDER BY game_id');

      $stmt->execute(array(
        ':user_id'   => $user['user_id'],
      )); 

    }else if($user['type']=='dealer'){
      $stmt = $conn->prepare('SELECT G.*,GS.status FROM games AS G
      LEFT JOIN game_status as GS 
      on GS.game_id = G.game_id 
      WHERE GS.banca_id = :user_id AND GS.status =1
      ORDER BY game_id');

      $stmt->execute(array(
        ':user_id'   => $user['banca_id'],
      )); 

    }else{
      throw new Exception('Usuário não válido');
    }

    $count = $stmt->rowCount();

    if($count > 0){
      $index = 0;
      while($row = $stmt->fetch(PDO::FETCH_ASSOC))
      {
        $games[$index]['game_id'] = $row['game_id'];
        $games[$index]['name'] = $row['name'];
        $games[$index]['status'] = $row['status'];
        $games[$index]['acronym'] = $row['acronym'];
        $games[$index]['type'] = $row['type'];
        $games[$index]['type_bet'] = $row['type_bet'];
        $games[$index]['size_bet'] = $row['size_bet'];
        $games[$index]['min'] = $row['min'];
        $games[$index]['max'] = $row['max'];
        $games[$index]['prizes'] = $row['prizes'];
        $games[$index]['fixed_prize'] = $row['fixed_prize'];
        $index++;
        
      }
  
    }
    http_response_code(201);
    $data = [
      'data'=>$games,
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
