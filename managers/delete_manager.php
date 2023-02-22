<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

    $manager_id = $_GET['manager_id'];
   
    if(isset($manager_id) && !empty($manager_id)){
   
        try {
            $stmte = $conn->prepare('DELETE FROM manager WHERE manager_id = :manager_id');
            $stmte->execute(array(
                ':manager_id'   => $manager_id
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
    }
  
?>
