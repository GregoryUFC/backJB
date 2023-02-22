<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  function searchForTime($time, $array) {
    foreach ($array as $key => $val) {
        if ($val['day'] === $time) {
            return $key;
            break;
        }
    }
    return null;
  }

  function searchForExtra($id, $array) {
    foreach ($array as $key => $val) {
        if ($val['extraction_id'] === $id) {
            return $key;
            break;
        }
    }
    return null;
  }

  $month = $_GET['m'];
  $year = $_GET['y'];
  $headers = apache_request_headers() ;
  
  $results = [];
  $extractions = [];
  $nextMonth = $month+1;
  $a_date = $year."-".$month."-10";
  $b_date = $year."-".$nextMonth."-10";
  $starter_date = date("Y-m-t 23:59:59", strtotime($a_date));
  $end_date = date("Y-m-t 23:59:59", strtotime($b_date));


  try {
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    $stmt = $conn->prepare('SELECT * FROM results AS R 
    LEFT JOIN extraction as E ON R.extraction_id=E.extraction_id 
    WHERE R.banca_id =:user_id AND day BETWEEN :starter_date AND :end_date
    ORDER BY day DESC');
    if($user['type']=='admin'){
      $stmt->execute(array(
        ':starter_date'   => $starter_date,
        ':end_date'   => $end_date,
        ':user_id'   => $user['user_id'],
      )); 
    }else if($user['type']=='manager'){
      $stmt->execute(array(
        ':starter_date'   => $starter_date,
        ':end_date'   => $end_date,
        ':user_id'   => $user['banca_id'],
      )); 
    }else if($user['type']=='dealer'){
      $stmt->execute(array(
        ':starter_date'   => $starter_date,
        ':end_date'   => $end_date,
        ':user_id'   => $user['banca_id'],
      )); 
    }else{
      throw new Exception('Usuário não válido');
    }
    
    $count = $stmt->rowCount();
  
    $stm = $conn->prepare('SELECT * FROM extraction ORDER BY extraction_id');
    $stm->execute(array()); 

    $index = 0;
    while($col = $stm->fetch(PDO::FETCH_ASSOC))
    { 
      $extractions[$index]['extraction_id'] = $col['extraction_id'];
      $extractions[$index]['name'] = $col['name'];
      $extractions[$index]['hour'] = $col['hour'];
      $extractions[$index]['empty'] = true;
      $index++;
    } 

    $t = $year.'-'.($nextMonth<10?'0'.$nextMonth:$nextMonth);
    $i = intval(date("t", strtotime($b_date)));
    while ($i >0)
    {
      $time = (strtotime($year.'-'.$nextMonth.'-'.$i.' 00:00:00')+3*60*60)*1000;
      if(date("Y-m")!=$t){
        array_push($results, ['day' => $time,'results'=> $extractions]);
      }else if(date("Y-m")== $t && $i <= intval(date("d"))){
        array_push($results, ['day' => $time,'results'=> $extractions]);
      }

      $i--;
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC))
    { 
      $time = (strtotime($row['day'])+ 60*60*3 )*1000;
      $key = searchForTime($time,$results);
      $extraKey = searchForExtra($row['extraction_id'],$extractions);
      
      $tempResults=[];
      $tempResults['result_id'] = $row['result_id'];
      $tempResults['extraction_id'] = $row['extraction_id'];
      $tempResults['name'] = $row['name'];
      $tempResults['hour'] = $row['hour'];
      $tempResults['prize1'] = $row['prize1'];
      $tempResults['prize2'] = $row['prize2'];
      $tempResults['prize3'] = $row['prize3'];
      $tempResults['prize4'] = $row['prize4'];
      $tempResults['prize5'] = $row['prize5'];
      $tempResults['prize6'] = $row['prize6'];
      $tempResults['prize7'] = $row['prize7'];
      $tempResults['empty'] = false;

      $results[$key]['results'][$extraKey] =$tempResults;
      
    } 
    http_response_code(201);
    $data = [
      'data'=>$results,
      'code'=> 201
    ];
    echo json_encode($data);
  }catch (Exception $e) {
    http_response_code(406);
    $error = [
      'msg' => $e->getMessage(),
      'code'=> 404
    ];
    echo json_encode($error);
  }
?>
