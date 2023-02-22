<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  try {
    $managers = [];
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='admin'){
      $stmt = $conn->prepare('SELECT M.*,R.name AS region_name FROM manager AS M LEFT JOIN region as R on M.region_id = R.region_id WHERE M.banca_id = :user_id ORDER BY manager_id');
    }else if($user['type']=='manager'){
      $stmt = $conn->prepare('SELECT manager_id, name FROM manager WHERE manager_id = :user_id');
    }else{
      throw new Exception('Usuário não válido');
    }
   
    $stmt->execute(array(
      ':user_id'   => $user['user_id'],
    ));
    $count = $stmt->rowCount();

    if($count > 0 && $user['type']=='admin'){
      $index = 0;
      while($row = $stmt->fetch(PDO::FETCH_ASSOC))
      { 
          $managers[$index]['manager_id'] = $row['manager_id'];
          $managers[$index]['name'] = $row['name'];
          $managers[$index]['region_name'] = $row['region_name'];
          $managers[$index]['region_id'] = $row['region_id'];
          $managers[$index]['limit_credit'] = $row['limit_credit'];
          $managers[$index]['status'] = $row['status'];
          $index++;
        }  
    }else if($user['type']=='manager'){
      $managers = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    http_response_code(201);
    $data = [
      'data'=>$managers,
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
