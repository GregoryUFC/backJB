<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  $dados = file_get_contents("php://input");
  $headers = apache_request_headers() ;
  $auth_id;
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $name = $request->dados->name;
    $comm_incoming = $request->dados->comm_incoming;
    $comm_profit = $request->dados->comm_profit;
    $region_id = $request->dados->region_id;
    $limit_credit = $request->dados->limit_credit;
    $login = $request->dados->login;
    $password = $request->dados->password;
    $email = $request->dados->email;
    $uuid = $request->dados->uuid;

    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      $check = $conn->prepare('SELECT * FROM auth WHERE login = :login ORDER BY auth_id');
      $check->execute(array(
        ':login'   => $login
      ));
      $count = $check->rowCount(); 
      if ($count>0) 
      { 
        throw new Exception('Login já existe');
      }
      
      $stm = $conn->prepare("INSERT INTO auth(uuid,login,password,type) VALUES (?,?,?,?)");
      $stm->execute([
        $uuid,
        $login,
        $password,
        'manager'
      ]);
      $auth_id = $conn->lastInsertId();

      $stmt = $conn->prepare("INSERT INTO manager(name,comm_incoming,comm_profit,region_id,limit_credit,email,status,auth_id,banca_id) VALUES (?,?,?,?,?,?,?,?,?)");
      if($user['type']=='admin'){
        $stmt->execute([
          $name,
          $comm_incoming,
          $comm_profit,
          $region_id,
          $limit_credit,
          $email,
          1,
          $auth_id,
          $user['user_id'],
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
      if (isset($auth_id)) {
        $del = $conn->prepare('DELETE FROM auth WHERE auth_id = :auth_id');
        $del->execute(array(
            ':auth_id'   => $auth_id
        ));
      }
      http_response_code(404);
      $error = [
        'msg' => $e->getMessage(),
        'code'=> 404
      ];
      echo json_encode($error);
    }
  }
?>
