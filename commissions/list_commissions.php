<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  

    $headers = apache_request_headers() ;
    try {
      $commissions = [];
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      $stmt = $conn->prepare('SELECT * FROM commissions WHERE banca_id = :user_id ORDER BY commission_id');
      if($user['type']=='admin'){
        $stmt->execute(array(
          ':user_id'   => $user['user_id'],
        ));
      }else if($user['type']=='manager'){
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
          $commissions[$index]['commission_id'] = $row['commission_id'];
          $commissions[$index]['name'] = $row['name'];
          $commissions[$index]['value'] = $row['value'];
          $index++;
        }
      }

      http_response_code(201);
      $data = [
        'data'=>$commissions,
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
