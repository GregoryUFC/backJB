<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');


    $region_id = $_GET['region_id'];
   
    if(isset($region_id) && !empty($region_id)){
   
        try {
          $stmte = $conn->prepare('DELETE FROM region WHERE region_id = :region_id');
          $stmte->execute(array(
              ':region_id'   => $region_id
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
