<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  
  function is_base64($s){

    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) return false;


    $decoded = base64_decode($s, true);
    if(false === $decoded) return false;


    if(base64_encode($decoded) != $s) return false;

    return true;
  }

  $headers = apache_request_headers() ;
   


    try {
      $token = $headers['Auth'];
      $decode='';
      if(is_base64($token)){
        $decode =base64_decode($token);
      }else{
        throw new Exception('Token não valido');
      }

      $pieces = explode("/", $decode);

      if($pieces[1]!='dealer')
      {
          throw new Exception('Token não autorizado');
      }
  
      $stmt = $conn->prepare('SELECT * FROM auth WHERE uuid = :uuid AND type = :type');
      $stmt->execute(array(
      ':uuid'   => $pieces[0],
      ':type'   => $pieces[1]
      ));     
      $count = $stmt->rowCount();
      $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 

      if($count < 0 OR !$resultado)
      {
            throw new Exception('Token não autorizado');
      }

      http_response_code(201);
      $data = [
        'data' => null,
        'code'=> 201
      ];
      echo json_encode( $data);
    } catch (Exception $e) {
      http_response_code(404);
      $error = [
        'msg' => $e->getMessage(),
        'code'=> 404
      ];
      echo json_encode($error);
    }
  
?>
