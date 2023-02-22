<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/cashier.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $description = $request->dados->description;
    $value = $request->dados->value;
    $type = $request->dados->type;
    $user_id = $request->dados->user_id;
    $userType = $request->dados->userType;
    $day =  date("Y-m-d H:i:s", $request->dados->day);

    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      if($user['type']=='admin'&& $userType=='dealer'){

        $stmt = $conn->prepare("INSERT INTO statement(description,value,type,dealer_id,banca_id,day,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
          $description,
          $value,
          $type,
          $user_id,
          $user['user_id'],
          $day,
          1
        ]);
        cashierDealerStatement($value,$type,$user_id,$day,$conn);

      }else if($user['type']=='admin'&& $userType=='manager'){
        $stmt = $conn->prepare("INSERT INTO statement(description,value,type,manager_id,banca_id,day,status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
          $description,
          $value,
          $type,
          $user_id,
          $user['user_id'],
          $day,
          1
        ]);
        cashierManagerStatement($value,$type,$user_id,$day,$conn);
      }
      else if($user['type']=='manager'){
  
        if($userType=='dealer'){
          $stmt = $conn->prepare("INSERT INTO statement(description,value,type,dealer_id,banca_id,day,status) VALUES (?,?,?,?,?,?,?)");
          $stmt->execute([
            $description,
            $value,
            $type,
            $user_id,
            $user['banca_id'],
            $day,
            1
          ]);
          cashierDealerStatement($value,$type,$user_id,$day,$conn);
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