<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $commission_id = $_GET['commission_id'];
   
    try {
        $stmte = $conn->prepare('DELETE FROM commissions WHERE commission_id = :commission_id');
        $stmte->execute(array(
            ':commission_id'   => $commission_id
        ));
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
    
  
?>
