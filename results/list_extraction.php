<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  function numberChange($n)
  {
      return intval($n);
  }
  
    $extractions = [];

    $stmt = $conn->prepare('SELECT * FROM extraction ORDER BY extraction_id');
    $stmt->execute(array()); 
    $count = $stmt->rowCount();

    if($count > 0){
        $index = 0;
        while($row = $stmt->fetch(PDO::FETCH_ASSOC))
        { 
            $extractions[$index]['extraction_id'] = $row['extraction_id'];
            $extractions[$index]['name'] = $row['name'];
            $extractions[$index]['hour'] = $row['hour'];
            $days = explode(";",$row['days']);
            $extractions[$index]['days'] = array_map('numberChange', $days);
            $index++;
        }
           
        http_response_code(201);
        $data = [
          'data'=>$extractions,
          'code'=> 201
        ];
        echo json_encode($data);
    }else{
      $error = [
        'msg' => $e->getMessage(),
        'code'=> 404
      ];
      echo json_encode($error);
    }
  
?>
