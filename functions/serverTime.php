<?php
    include_once('../models/headers.php');

    $currentTimeinSeconds = time(); 
    $currentTimeinSeconds = $currentTimeinSeconds*1000;
    if(isset($currentTimeinSeconds)){
        http_response_code(201);
        $data = [
            'data'=>$currentTimeinSeconds,
            'code'=> 201
        ];
        echo json_encode($data);
    }else{
        http_response_code(404);
        $error = [
            'msg' => 'Não pode obter tempo',
            'code'=> 404
        ];
        echo json_encode($error);
    }
    
  
?>
