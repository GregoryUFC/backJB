<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  $dados = file_get_contents("php://input");
  
  $dealers = []; 

  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $starter_date =  $request->dados->starter_date ;
    $end_date = $request->dados->end_date;
    try {
      $token = $headers['Auth'];
      $user=[];

      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      if($user['type']=='admin'){

          $dealerQuery = $conn->prepare('SELECT D.*,M.comm_incoming,C.value AS commission_value FROM dealers AS D
          LEFT JOIN manager as M 
          on D.manager_id = M.manager_id
          LEFT JOIN commissions as C 
          on D.commission_id = C.commission_id
          WHERE D.banca_id = :user_id AND D.status=:status ORDER BY manager_id');

      }else if($user['type']=='manager'){

        $dealerQuery = $conn->prepare('SELECT D.*,M.comm_incoming,C.value AS commission_value FROM dealers AS D
        LEFT JOIN manager as M 
        on D.manager_id = M.manager_id
        LEFT JOIN commissions as C 
        on D.commission_id = C.commission_id
        WHERE D.manager_id = :user_id AND D.status=:status ORDER BY manager_id');

      }else if($user['type']=='dealer'){

        $dealerQuery = $conn->prepare('SELECT D.*,M.comm_incoming,C.value AS commission_value FROM dealers AS D
        LEFT JOIN manager as M 
        on D.manager_id = M.manager_id
        LEFT JOIN commissions as C 
        on D.commission_id = C.commission_id
        WHERE D.dealer_id = :user_id AND D.status=:status ORDER BY manager_id');

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
        $dealers[$index]['name'] = $row['name'];
        $dealers[$index]['input'] = 0;
        $dealers[$index]['output_commission'] = 0;
        $dealers[$index]['output_prize'] = 0;
        $dealers[$index]['commission_profit'] = 0;
        $dealers[$index]['profit'] = 0; 

        $bets = $conn->prepare('SELECT bet_id,value FROM bets WHERE dealer_id = :dealer_id AND status ="perm" AND time BETWEEN :starter_date AND :end_date');
        $bets->execute(array(
            ':dealer_id'   => $row['dealer_id'],
            ':starter_date'   => $starter_date,
            ':end_date'   => $end_date,
        )); 
        
        $bids = $conn->prepare('SELECT reward FROM bids WHERE bet_id = :bet_id AND win =1');
        while($col = $bets->fetch(PDO::FETCH_ASSOC))
        {
          $output_commission = $col['value']*$row['commission_value']/100;
          $commission_profit = ($col['value']-$output_commission)*$row['comm_incoming']/100;
          $dealers[$index]['input'] += $col['value'];
          $dealers[$index]['output_commission'] += $output_commission;
          $dealers[$index]['commission_profit'] += $commission_profit;
          $dealers[$index]['profit'] += ($col['value']-$output_commission-$commission_profit);
          $bids->execute(array(
            ':bet_id'   => $col['bet_id']
          ));  
          while($colRow = $bids->fetch(PDO::FETCH_ASSOC))
          {
            $dealers[$index]['output_prize'] += $colRow['reward'];
            $dealers[$index]['profit'] -= $colRow['reward'];
          }
        }


        $dealers[$index]['input'] = is_null($dealers[$index]['input'])?0:sprintf("%.2f", $dealers[$index]['input']);
        $dealers[$index]['output_commission'] = is_null($dealers[$index]['output_commission'])?0:sprintf("%.2f", $dealers[$index]['output_commission']);
        $dealers[$index]['output_prize'] = is_null($dealers[$index]['output_prize'])?0:sprintf("%.2f", $dealers[$index]['output_prize']);
        $dealers[$index]['commission_profit'] = is_null($dealers[$index]['commission_profit'])?0:sprintf("%.2f", $dealers[$index]['commission_profit']);
        $dealers[$index]['profit'] =  is_null($dealers[$index]['profit'])?0:sprintf("%.2f", $dealers[$index]['profit']);
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
  }
  
?>
