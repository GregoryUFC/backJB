<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  $dealers = []; 
  $dt = new DateTime;
  $dt->sub(new DateInterval('PT3H'));
  $time = $dt->format('Y-m-d H:i:s');

  try {
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='admin'){

      $dealerQuery = $conn->prepare('SELECT * FROM dealers WHERE banca_id = :user_id AND status=:status ORDER BY manager_id');

    }else if($user['type']=='manager'){

      $dealerQuery = $conn->prepare('SELECT * FROM dealers WHERE manager_id = :user_id AND status=:status ORDER BY manager_id');

    }else if($user['type']=='dealer'){

      $dealerQuery = $conn->prepare('SELECT * FROM dealers WHERE dealer_id = :user_id AND status=:status ORDER BY manager_id');

    }else{
      throw new Exception('Usuário não válido');
    }
    $dealerQuery->execute(array(
      ':user_id'   => $user['user_id'],
      ':status' => '1'
    ));

    $index = 0;
    while($row = $dealerQuery->fetch(PDO::FETCH_ASSOC))
    {
      
      $oldConsulta = $conn->prepare("SELECT SUM(balance) AS preview FROM cashier_dealer WHERE dealer_id = :dealer_id AND end_date< :time");
      $oldConsulta->execute(array(
        ':dealer_id'   => $row['dealer_id'],
        ':time'   => $time,
      ));  
      $preview = $oldConsulta->fetch(PDO::FETCH_ASSOC);

      $getDealer = $conn->prepare('SELECT * FROM cashier_dealer WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date');
      $getDealer->execute(array(
          ':dealer_id'   => $row['dealer_id'],
          ':time'   => $time,
      )); 

      $cashierDealer = $getDealer->fetch(PDO::FETCH_ASSOC);

      $dealers[$index]['dealer_id'] = $row['dealer_id'];
      $dealers[$index]['name'] = $row['name'];
      $dealers[$index]['preview'] = $preview['preview'];
      $dealers[$index]['input'] = $cashierDealer['input'];
      $dealers[$index]['output'] = $cashierDealer['output_commission']+$cashierDealer['output_prize'];
      $dealers[$index]['output_commission'] = $cashierDealer['output_commission'];
      $dealers[$index]['output_prize'] = $cashierDealer['output_prize'];
      $dealers[$index]['credit'] = $cashierDealer['credit'];
      $dealers[$index]['debit'] =  $cashierDealer['debit'];
      $dealers[$index]['balance'] = $preview['preview']+$cashierDealer['balance']; 

      $dealers[$index]['preview'] = is_null($dealers[$index]['preview'])?0:sprintf("%.2f", $dealers[$index]['preview']);
      $dealers[$index]['input'] = is_null($dealers[$index]['input'])?0:$dealers[$index]['input'];
      $dealers[$index]['output'] = is_null($dealers[$index]['output'])?0:sprintf("%.2f", $dealers[$index]['output']);
      $dealers[$index]['output_commission'] = is_null($dealers[$index]['output_commission'])?0:sprintf("%.2f", $dealers[$index]['output_commission']);
      $dealers[$index]['output_prize'] = is_null($dealers[$index]['output_prize'])?0:sprintf("%.2f", $dealers[$index]['output_prize']);
      $dealers[$index]['credit'] = is_null($dealers[$index]['credit'])?0: $dealers[$index]['credit'];
      $dealers[$index]['debit'] =  is_null($dealers[$index]['debit'])?0:$dealers[$index]['debit'];
      $dealers[$index]['balance'] = is_null($dealers[$index]['balance'])?0:sprintf("%.2f", $dealers[$index]['balance']);
      $index ++;
    }

    http_response_code(201);
    $data = [
      'data'=>$dealers,
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
