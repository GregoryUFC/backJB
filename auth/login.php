<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  $dados = file_get_contents("php://input");
  
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $login = $request->dados->login;
    $password = $request->dados->password;

    try {
      $stmt = $conn->prepare('SELECT * FROM auth WHERE login = :login AND password = :password');
      $stmt->execute(array(
      ':login'   => $login,
      ':password' => $password
      ));     
      $count = $stmt->rowCount();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
      if($count <= 0 AND !$resultado){
        throw new Exception('Login e/ou senha estão errados');
      }

      if($resultado['type']==='dealer'){
        throw new Exception('Usuario não autorizado');
      }

      $enconde = base64_encode($resultado['uuid'].'/'.$resultado['type']);

      http_response_code(201);
      $data = [
        'data'=>$enconde,
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
