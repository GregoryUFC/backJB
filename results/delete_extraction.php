<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

    $extraction_id = $_GET['extraction_id'];
   
    if(isset($extraction_id) && !empty($extraction_id)){
   
        try {
            $token = $headers['Auth'];
            $user=[];
      
            if(is_base64($token)){
              $user = getUserId($token,$conn);
            }else{
              throw new Exception('Token não válido');
            }

            $stmte = $conn->prepare('DELETE FROM extraction WHERE extraction_id = :extraction_id');
            if($user['type']=='admin'){
                $stmte->execute(array(
                  ':extraction_id'   => $extraction_id
                ));
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
