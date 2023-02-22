<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  $statements = []; 

  try {
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='admin'){

      $statementQuery = $conn->prepare('SELECT S.*,M.name AS manager_name, D.name AS dealer_name FROM statement AS S
      LEFT JOIN manager as M 
      on S.manager_id = M.manager_id
      LEFT JOIN dealers as D 
      on S.dealer_id = D.dealer_id 
      WHERE S.banca_id = :user_id ORDER BY day DESC');

    }else if($user['type']=='manager'){

      $statementQuery = $conn->prepare('SELECT S.*, D.name AS dealer_name FROM statement AS S
      LEFT JOIN dealers as D 
      on S.dealer_id = D.dealer_id 
      WHERE D.manager_id = :user_id ORDER BY day DESC');
      

    }else{
      throw new Exception('Usuário não válido');
    }
    $statementQuery->execute(array(
      ':user_id'   => $user['user_id'],
    ));

    $index = 0;
    while($row = $statementQuery->fetch(PDO::FETCH_ASSOC))
    {
      $statements[$index]['statement_id'] = $row['statement_id'];
      $statements[$index]['description'] = $row['description'];
      $statements[$index]['value'] = $row['value'];
      $statements[$index]['name'] = $row['manager_name']?$row['manager_name']:$row['dealer_name'];
      $statements[$index]['user_id'] = $row['dealer_id']?$row['dealer_id']:$row['manager_id'];
      $statements[$index]['userType'] = $row['dealer_id']?'dealer':'manager';
      $statements[$index]['day'] = (strtotime($row['day'])+3*60*60)*1000;
      $statements[$index]['type'] = $row['type'];
      $statements[$index]['status'] = $row['status'];
      $index ++;
    }

    http_response_code(201);
    $data = [
      'data'=>$statements,
      'code'=> 201
    ];
    echo json_encode($data);
  } catch (Exception $e) {
    http_response_code(406);
    $error = [
      'msg' => $e->getMessage(),
      'code'=> 404
    ];
    echo json_encode($error);
  }
  
?>
