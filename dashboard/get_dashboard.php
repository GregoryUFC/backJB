<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  $headers = apache_request_headers() ;

  try {
    $now = time();
    /*
    $dt = new DateTime;
    $dt->sub(new DateInterval('PT3H'));
    $dt->sub(new DateInterval('P30D'));
    $starter_date = $dt->format('Y-m-d H:i:s');
    */

    $dt = new DateTime;
    $dt->sub(new DateInterval('PT3H'));
    $week = $dt->format('w')==0?7:$dt->format('w');
    $dt->sub(new DateInterval('P'.$week.'D'));
    $dt->add(new DateInterval('P1D'));
    $dt->setTime(0, 0);
    $starter_week = $dt->format('Y-m-d H:i:s');

    $dtm = new DateTime;
    $dtm->sub(new DateInterval('PT3H'));
    $dtm->sub(new DateInterval('P5M'));
    $starter_month = $dtm->format('Y-m-00 00:00:00');
    $starter_month_small = $dtm->format('m');
    
    $dashboard=[];

    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }

    if($user['type']=='admin'){
      $queryBet = $conn->prepare('SELECT COUNT(bet_id) AS quant, SUM(value) AS total FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.banca_id =:user_id AND time>:starter_week');
      /*
      $queryLastMonthBet = $conn->prepare('SELECT COUNT(bet_id) AS quant, SUM(value) AS total FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.banca_id =:user_id AND time>:starter_date');

      $queryFirstBet = $conn->prepare('SELECT time FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.banca_id =:user_id ORDER BY bet_id ASC LIMIT 1');
      */
      $queryBetByMonth = $conn->prepare('SELECT time,value FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.banca_id =:user_id AND time>:starter_month');

      /*********MANAGER COUNT*********/
      $queryManager = $conn->prepare('SELECT COUNT(manager_id) AS quant FROM manager WHERE banca_id =:user_id');
      $queryManager->execute(array(
        ':user_id'   => $user['user_id'],
      )); 
      $result_manager = $queryManager->fetch(PDO::FETCH_ASSOC); 
      $dashboard['manager_count'] = $result_manager['quant'];

      $queryDealer = $conn->prepare('SELECT COUNT(dealer_id) AS quant FROM dealers WHERE banca_id =:user_id');
    }else if($user['type']=='manager'){
      $queryBet = $conn->prepare('SELECT COUNT(bet_id) AS quant, SUM(value) AS total FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.manager_id =:user_id AND time>:starter_week');
      /*
      $queryLastMonthBet = $conn->prepare('SELECT COUNT(bet_id) AS quant, SUM(value) AS total FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.manager_id =:user_id AND time>:starter_date');

      $queryFirstBet = $conn->prepare('SELECT time FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.manager_id =:user_id ORDER BY bet_id ASC LIMIT 1');
      */
      $queryBetByMonth = $conn->prepare('SELECT time,value FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status ="perm" AND D.manager_id =:user_id AND time>:starter_month');

      $queryDealer = $conn->prepare('SELECT COUNT(dealer_id) AS quant FROM dealers WHERE manager_id =:user_id');
    }else{
      throw new Exception('Usuário não válido');
    }
    /*********BET COUNT*********/
    $queryBet->execute(array(
      ':user_id'   => $user['user_id'],
      ':starter_week'   => $starter_week
    )); 
    $result_bet = $queryBet->fetch(PDO::FETCH_ASSOC); 
    $dashboard['bet_count'] = $result_bet['quant'];
    $dashboard['bet_sum'] = $result_bet['total'];

    /*********LAST MONTH BET LIST*********
    $queryLastMonthBet->execute(array(
        ':user_id'   => $user['user_id'],
        ':starter_date'   => $starter_date,
    )); 
    $result_last_month_bet = $queryLastMonthBet->fetch(PDO::FETCH_ASSOC); 
    $avgCountMonth = $result_last_month_bet['quant']/30;
    $avgValueMonth = $result_last_month_bet['total']/30;
    /
    /*********FIRST BET*********
    $queryFirstBet->execute(array(
        ':user_id'   => $user['user_id']
    )); 
    $first_bet= $queryFirstBet->fetch(PDO::FETCH_ASSOC); 
    if(strtotime($first_bet['time'])){
      $your_date = strtotime($first_bet['time']);
      /
      /*********AVERAGE BET CALC*********
      $datediff = $now - $your_date;
      $daynumber = round($datediff / (60 * 60 * 24));
      $avgCount = $dashboard['bet_count']/$daynumber;
      $avgValue = $dashboard['bet_sum']/$daynumber;
      $dashboard['avgPerCount'] =  floatval (sprintf("%.2f", ($avgCountMonth/$avgCount*100)-100));
      $dashboard['avgPerValue'] =  floatval (sprintf("%.2f", ($avgValueMonth/$avgValue*100)-100));
    }else{
      $dashboard['bet_sum'] = 0;
      $dashboard['avgPerCount'] =  0;
      $dashboard['avgPerValue'] =  0;
    }/


    /*********LAST 6 MONTH BET COUNT*********/
    $queryBetByMonth->execute(array(
        ':user_id'   => $user['user_id'],
        ':starter_month'   => $starter_month
    )); 

    $betsCount=[0,0,0,0,0,0];
    $betsValue=[0,0,0,0,0,0];
    $index = 0;
    while($row = $queryBetByMonth->fetch(PDO::FETCH_ASSOC))
    { 
      $intMonth=intval($starter_month_small);
      $thistMonth=intval(date("m", strtotime($row['time'])));
      $i=($thistMonth-$intMonth)<0?$thistMonth-$intMonth+12:$thistMonth-$intMonth;

      $betsCount[$i] ++; 
      $betsValue[$i] += $row['value']; 
      $index++;
    }
    $dashboard['bets_month']['bets_value'] = $betsValue;
    $dashboard['bets_month']['bets_count'] = $betsCount;
    $dashboard['bets_month']['month'] = intval($starter_month_small);

    /*********DEALER COUNT*********/
    $queryDealer->execute(array(
        ':user_id'   => $user['user_id'],
    )); 
    $result_dealer = $queryDealer->fetch(PDO::FETCH_ASSOC); 
    $dashboard['dealer_count'] = $result_dealer['quant'];
    
    $dashboard['bet_count'] = is_null($dashboard['bet_count'])?0:$dashboard['bet_count'];
    $dashboard['bet_sum'] = is_null($dashboard['bet_sum'])?0:$dashboard['bet_sum'];
    http_response_code(201);
    $data = [
      'data'=>$dashboard,
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
  
?>
