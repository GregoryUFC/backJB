<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');  
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;
  $dados = file_get_contents("php://input");
  
  $report = []; 

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

        if($user['type']=='dealer'){

            $dealerQuery = $conn->prepare('SELECT D.*,C.value AS commission_value FROM dealers AS D
            LEFT JOIN commissions as C 
            on D.commission_id = C.commission_id
            WHERE D.dealer_id = :user_id ORDER BY manager_id');

            $statementDebitQuery = $conn->prepare('SELECT SUM(value) AS debitValue FROM statement WHERE dealer_id = :user_id AND type=:type AND status=1 AND day BETWEEN :starter_date AND :end_date');
            $statementCreditQuery = $conn->prepare('SELECT SUM(value) AS creditValue FROM statement WHERE dealer_id = :user_id AND type=:type AND status=1 AND day BETWEEN :starter_date AND :end_date');

            $oldStatementDebitQuery = $conn->prepare('SELECT SUM(value) AS debitValue FROM statement WHERE dealer_id = :user_id AND type=:type AND status=1 AND day < :starter_date');
            $oldStatementCreditQuery = $conn->prepare('SELECT SUM(value) AS creditValue FROM statement WHERE dealer_id = :user_id AND type=:type AND status=1 AND day < :starter_date');

        }else{
            throw new Exception('Usuário não válido');
        }
        $dealerQuery->execute(array(
            ':user_id'   => $user['user_id'],
        ));

        $statementDebitQuery->execute(array(
            ':user_id'   => $user['user_id'],
            ':type'   => "debit",
            ':starter_date'   => $starter_date,
            ':end_date'   => $end_date,
        ));

        $statementCreditQuery->execute(array(
            ':user_id'   => $user['user_id'],
            ':type'   => "credit",
            ':starter_date'   => $starter_date,
            ':end_date'   => $end_date,
        ));

        $oldStatementDebitQuery->execute(array(
            ':user_id'   => $user['user_id'],
            ':type'   => "debit",
            ':starter_date'   => $starter_date,
        ));

        $oldStatementCreditQuery->execute(array(
            ':user_id'   => $user['user_id'],
            ':type'   => "credit",
            ':starter_date'   => $starter_date,
        ));

        $dealer = $dealerQuery->fetch(PDO::FETCH_ASSOC);
        $credit = $statementCreditQuery->fetch(PDO::FETCH_ASSOC);
        $debit = $statementDebitQuery->fetch(PDO::FETCH_ASSOC);
        $oldcredit = $oldStatementCreditQuery->fetch(PDO::FETCH_ASSOC);
        $olddebit = $oldStatementDebitQuery->fetch(PDO::FETCH_ASSOC);

        $report['preview'] = $oldcredit['creditValue']-$olddebit['debitValue'];

        $report['input'] = 0;
        $report['output_commission'] = 0;
        $report['output_prize'] = 0;
        $report['credit'] = $credit['creditValue'];
        $report['debit'] = $debit['debitValue'];
        $report['profit'] = $credit['creditValue']-$debit['debitValue']; 

        $bets = $conn->prepare('SELECT bet_id,value FROM bets WHERE dealer_id = :dealer_id AND status ="perm" AND time BETWEEN :starter_date AND :end_date');
        $bets->execute(array(
            ':dealer_id'   => $dealer['dealer_id'],
            ':starter_date'   => $starter_date,
            ':end_date'   => $end_date,
        )); 

        $oldBets = $conn->prepare('SELECT bet_id,value FROM bets WHERE dealer_id = :dealer_id AND status ="perm" AND time < :starter_date');
        $oldBets->execute(array(
            ':dealer_id'   => $dealer['dealer_id'],
            ':starter_date'   => $starter_date
        )); 
        
        $bids = $conn->prepare('SELECT reward FROM bids WHERE bet_id = :bet_id AND win =1');
        while($row = $bets->fetch(PDO::FETCH_ASSOC))
        {
          $output_commission = $row['value']*$dealer['commission_value']/100;
          $report['input'] += $row['value'];
          $report['output_commission'] += $output_commission;
          $report['profit'] += ($row['value']-$output_commission);
          $bids->execute(array(
            ':bet_id'   => $row['bet_id']
          ));  
          while($colRow = $bids->fetch(PDO::FETCH_ASSOC))
          {
            $report['output_prize'] += $colRow['reward'];
            $report['profit'] -= $colRow['reward'];
          }
        }

        while($col = $oldBets->fetch(PDO::FETCH_ASSOC))
        {
          $output_commission = $col['value']*$dealer['commission_value']/100;
          $report['preview'] += $col['value'];
          $report['preview'] -= $output_commission;
          $bids->execute(array(
            ':bet_id'   => $col['bet_id']
          ));  
          while($colRow = $bids->fetch(PDO::FETCH_ASSOC))
          {
            $report['preview'] -= $colRow['reward'];
          }
        }
        $report['profit'] = $report['profit']+ $report['preview'];

        $report['preview'] = is_null($report['preview'])?0:sprintf("%.2f", $report['preview']);
        $report['input'] = is_null($report['input'])?0:sprintf("%.2f", $report['input']);
        $report['output_commission'] = is_null($report['output_commission'])?0:sprintf("%.2f", $report['output_commission']);
        $report['output_prize'] = is_null($report['output_prize'])?0:sprintf("%.2f", $report['output_prize']);
        $report['credit'] = is_null($report['credit'])?0:sprintf("%.2f", $report['credit']);
        $report['debit'] = is_null($report['debit'])?0:sprintf("%.2f", $report['debit']);
        $report['profit'] =  is_null($report['profit'])?0:sprintf("%.2f", $report['profit']);
        $index ++;
      

      http_response_code(201);
      $data = [
        'data'=>$report,
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
