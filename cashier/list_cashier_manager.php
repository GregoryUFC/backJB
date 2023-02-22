<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  $managers = []; 
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

      $managerQuery = $conn->prepare('SELECT * FROM manager WHERE banca_id = :user_id AND status=:status ORDER BY manager_id');

    }else{
      throw new Exception('Usuário não válido');
    }
    $managerQuery->execute(array(
      ':user_id'   => $user['user_id'],
      ':status' => '1'
    ));

    $index = 0;
    while($row = $managerQuery->fetch(PDO::FETCH_ASSOC))
    {
      
      $oldConsulta = $conn->prepare("SELECT SUM(balance) AS preview FROM cashier_manager WHERE manager_id = :manager_id AND end_date< :time");
      $oldConsulta->execute(array(
        ':manager_id'   => $row['manager_id'],
        ':time'   => $time,
      ));  
      $preview = $oldConsulta->fetch(PDO::FETCH_ASSOC);

      $getManager = $conn->prepare('SELECT * FROM cashier_manager WHERE manager_id = :manager_id AND :time BETWEEN starter_date AND end_date');
      $getManager->execute(array(
          ':manager_id'   => $row['manager_id'],
          ':time'   => $time,
      )); 

      $cashierManager = $getManager->fetch(PDO::FETCH_ASSOC);

      $managers[$index]['manager_id'] = $row['manager_id'];
      $managers[$index]['name'] = $row['name'];
      $managers[$index]['preview'] = $preview['preview'];
      $managers[$index]['input'] = $cashierManager['input'];
      $managers[$index]['output'] = $cashierManager['output_commission']+$cashierManager['output_prize'];
      $managers[$index]['credit'] = $cashierManager['credit'];
      $managers[$index]['debit'] =  $cashierManager['debit'];
      $managers[$index]['commission'] =  $cashierManager['commission_incoming']+($cashierManager['commission_profit']<0?0:$cashierManager['commission_profit']);
      $managers[$index]['balance'] = $preview['preview'] +$cashierManager['balance']; 
      
      $managers[$index]['preview'] = is_null($managers[$index]['preview'])?0:sprintf("%.2f", $managers[$index]['preview']);
      $managers[$index]['input'] = is_null($managers[$index]['input'])?0:$managers[$index]['input'];
      $managers[$index]['output'] = is_null($managers[$index]['output'])?0:sprintf("%.2f", $managers[$index]['output']);
      $managers[$index]['credit'] = is_null($managers[$index]['credit'])?0:$managers[$index]['credit'];
      $managers[$index]['debit'] =  is_null($managers[$index]['debit'])?0:$managers[$index]['debit'];
      $managers[$index]['commission'] =  is_null($managers[$index]['commission'])?0:sprintf("%.2f", $managers[$index]['commission']);
      $managers[$index]['balance'] = is_null($managers[$index]['balance'])?0:sprintf("%.2f", $managers[$index]['balance']);
      $index ++;
    }

    http_response_code(201);
    $data = [
      'data'=>$managers,
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
