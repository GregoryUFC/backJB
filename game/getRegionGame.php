<?php
    include_once('../models/headers.php');
    include_once('../models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $game_id = $request->dados->game_id;
    $region_id = $request->dados->region_id;


    $stmt = $conn->prepare('SELECT * FROM game_region WHERE game_id = :game_id AND region_id = :region_id');
    $stmt->execute(array(
    ':game_id'   => $game_id,
    ':region_id' => $region_id
    ));     
    $count = $stmt->rowCount();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
    if($count > 0){
           
        http_response_code(201);
        $data = [
          'data'=>$resultado,
          'code'=> 201
        ];
        echo json_encode($data);
    }else{
      http_response_code(404);
      $error = [
        'msg' => $e->getMessage(),
        'code'=> 404
      ];
      echo json_encode($error);
    }
  }
?>
