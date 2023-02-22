<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  $headers = apache_request_headers() ;

  try {
    $dealers = [];
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }
    if($user['type']=='admin'){
    $stmt = $conn->prepare('SELECT D.*,M.name AS manager_name,C.name AS commision_name 
    FROM dealers AS D 
    LEFT JOIN manager as M 
    on D.manager_id = M.manager_id 
    LEFT JOIN commissions as C 
    on D.commission_id = C.commission_id 
    WHERE D.banca_id = :user_id
    ORDER BY dealer_id');
    $stmt->execute(array(
      ':user_id'   => $user['user_id'],
    ));
    }else if($user['type']=='manager'){
      $stmt = $conn->prepare('SELECT D.*,M.name AS manager_name ,C.name AS commision_name 
      FROM dealers AS D 
      LEFT JOIN manager as M 
      on D.manager_id = M.manager_id 
      LEFT JOIN commissions as C 
      on D.commission_id = C.commission_id 
      WHERE D.manager_id = :manager_id
      ORDER BY dealer_id');
      $stmt->execute(array(
        ':manager_id'   => $user['user_id'],
      ));
    }else{
      throw new Exception('Usuário não válido');
    }
    $count = $stmt->rowCount();

    if($count > 0){
      $index = 0;
      while($row = $stmt->fetch(PDO::FETCH_ASSOC))
      { 
        $dealers[$index]['dealer_id'] = $row['dealer_id'];
        $dealers[$index]['name'] = $row['name'];
        $dealers[$index]['manager_name'] = $row['manager_name'];
        $dealers[$index]['manager_id'] = $row['manager_id'];
        $dealers[$index]['commision_name'] = $row['commision_name'];
        $dealers[$index]['login'] = $row['login'];
        $dealers[$index]['status'] = $row['status'];
        $dealers[$index]['phone'] = $row['phone'];
        $index++;
      }
    }

    http_response_code(201);
    $data = [
      'data'=>$dealers,
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
