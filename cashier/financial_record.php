<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  
  $financial = []; 
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

    if($user['type']=='manager'){

      $oldQuery = $conn->prepare('SELECT SUM(balance) AS sumBalance FROM cashier_manager WHERE manager_id = :user_id AND end_date< :time');

      $financialQuery = $conn->prepare('SELECT * FROM cashier_manager WHERE manager_id = :user_id AND :time BETWEEN starter_date AND end_date');

    }else{
      throw new Exception('Usuário não válido');
    }
    $oldQuery->execute(array(
        ':user_id'   => $user['user_id'],
        ':time'   => $time,
    ));

    $financialQuery->execute(array(
      ':user_id'   => $user['user_id'],
      ':time'   => $time,
    ));
    $preview = $oldQuery->fetch(PDO::FETCH_ASSOC);

    
    if($user['type']=='manager'){
      $financial = $financialQuery->fetch(PDO::FETCH_ASSOC);
      $financial['preview'] = $preview['sumBalance'];
      $financial['output'] = $financial['output_commission'] + $financial['output_prize'] ;
      $financial['result'] = $financial['input']- $financial['output'] - $financial['commission_incoming'];
      $financial['balance'] = $preview['sumBalance']+$financial['balance'];
    }

    $financial['preview'] = is_null($financial['preview'])?0: sprintf("%.2f", $financial['preview']);
    $financial['input'] = is_null($financial['input'])?0: sprintf("%.2f", $financial['input']);
    $financial['output'] = is_null($financial['output'])?0: sprintf("%.2f", $financial['output']);
    $financial['output_commission'] = is_null($financial['output_commission'])?0:sprintf("%.2f", $financial['output_commission']);
    $financial['output_prize'] = is_null($financial['output_prize'])?0:sprintf("%.2f", $financial['output_prize']);
    $financial['commission_incoming'] = is_null($financial['commission_incoming'])?0:sprintf("%.2f", $financial['commission_incoming']);
    $financial['commission_profit'] =$financial['commission_profit']<0?0:$financial['commission_profit'];
    $financial['commission_profit'] = is_null($financial['commission_profit'])?0:sprintf("%.2f", $financial['commission_profit']);
    $financial['credit'] = is_null($financial['credit'])?0:sprintf("%.2f", $financial['credit']);
    $financial['debit'] =  is_null($financial['debit'])?0:sprintf("%.2f", $financial['debit']);
    $financial['result'] = is_null($financial['result'])?0: sprintf("%.2f", $financial['result']);
    $financial['balance'] = is_null($financial['balance'])?0:sprintf("%.2f", $financial['balance']);
   

    http_response_code(201);
    $data = [
      'data'=>$financial,
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
