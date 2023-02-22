<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $password = $request->dados->password;
    $login = $request->dados->login;
    $uuid = $request->dados->uuid;
    $manager_id = $request->dados->manager_id;
    $data = [
        'manager_id' => $manager_id,
        'name' => $request->dados->name,
        'comm_incoming' => $request->dados->comm_incoming,
        'comm_profit' => $request->dados->comm_profit,
        'region_id' => $request->dados->region_id,
        'limit_credit' => $request->dados->limit_credit,
        'email' => $request->dados->email,
    ];
    
    try {
      if($password!='' || $login!=''){
        $sqlLogin= "";
        $sqlPass= "";
        $stmte = $conn->prepare('SELECT * FROM manager WHERE manager_id = :manager_id');
        $stmte->execute(array(
          ':manager_id'   => $manager_id,
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

      $stmt = $conn->prepare("UPDATE manager SET name= :name,comm_incoming= :comm_incoming,comm_profit= :comm_profit,region_id= :region_id,limit_credit= :limit_credit,email= :email WHERE manager_id= :manager_id");
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
