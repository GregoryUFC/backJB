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
    $statement_id = $request->dados->statement_id;
    $value = $request->dados->value;
    $type = $request->dados->type;
    $user_id = $request->dados->user_id;
    $userType = $request->dados->userType;
    $dayState = $request->dados->day;
    $day =  date("Y-m-d H:i:s", (($dayState/1000)-(3*60*60)));

    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }
      $updateManager = $conn->prepare("UPDATE statement SET status = 0 WHERE statement_id = :statement_id");

      if($user['type']=='admin'&& $userType=='dealer'){
        $updateManager->execute([
          ':statement_id'   => $statement_id,
        ]);
        cashierDealerCancelStatement($value,$type,$user_id,$day,$conn);

      }else if($user['type']=='admin'&& $userType=='manager'){
        $updateManager->execute([
          ':statement_id'   => $statement_id,
        ]);
        cashierManagerCancelStatement($value,$type,$user_id,$day,$conn);
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