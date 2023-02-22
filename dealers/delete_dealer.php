<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $dealer_id = $_GET['dealer_id'];
   
    if(isset($dealer_id) && !empty($dealer_id)){
   
        try {
            $stmte = $conn->prepare('DELETE FROM dealers WHERE dealer_id = :dealer_id');
            $stmte->execute(array(
                ':dealer_id' => $dealer_id
            ));
            http_response_code(201);
            $data = [
                'data'=>null,
                'code'=> 201
            ];
            echo json_encode($data);
        } catch (Exception $e) {
            http_response_code(406);
            $error = [
                'msg' => $e->getMessage(),
                'code'=> 404
              ];
            echo json_encode($error);
        }
    }
  
?>
