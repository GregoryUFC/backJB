<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $password = $request->dados->password;
    $login = $request->dados->login;
    $uuid = $request->dados->uuid;
    $dealer_id = $request->dados->dealer_id;
    $data = [
        'dealer_id' => $dealer_id,
        'name' => $request->dados->name,
        'manager_id' => $request->dados->manager_id,
        'commission_id' => $request->dados->commission_id,
        'phone' => $request->dados->phone,
        'region_id' => $request->dados->region_id,
        'limit_credit' => $request->dados->limit_credit,
    ];
    
    try {
      if($password!='' || $login!=''){
        $sqlLogin= "";
        $sqlPass= "";
        $stmte = $conn->prepare('SELECT * FROM dealers WHERE dealer_id = :dealer_id');
        $stmte->execute(array(
          ':dealer_id'   => $dealer_id,
        )); 
        $resultado = $stmte->fetch(PDO::FETCH_ASSOC);
        
        $authData = [
          'uuid'=> $uuid,
          'auth_id'=> $resultado['auth_id']
        ];
        if($password!=''){
          $sqlPass= "password= :password,";
          $authData['password'] = $password;
        }
        if($login!=''){
          $sqlLogin= "login= :login,";
          $data['login'] = $login;
          $authData['login'] = $login;
          $check = $conn->prepare('SELECT * FROM auth ORDER BY auth_id');
          $check->execute(array()); 
          while($row = $check->fetch(PDO::FETCH_ASSOC))
          {
            if ($row['login']==$login) 
            { 
              throw new Exception('Login já existe');
            }
          }
        }

        $stm = $conn->prepare("UPDATE auth SET ".$sqlLogin.''.$sqlPass."uuid= :uuid WHERE auth_id= :auth_id");
        $stm->execute($authData);
      }

      $stmt = $conn->prepare("UPDATE dealers SET name= :name,manager_id= :manager_id,".$sqlLogin."commission_id= :commission_id,phone= :phone,region_id= :region_id,limit_credit= :limit_credit WHERE dealer_id= :dealer_id");
      $stmt->execute($data);
      
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
