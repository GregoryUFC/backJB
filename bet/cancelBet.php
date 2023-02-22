<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/cashier.php');
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $bet_id = $request->dados->bet_id;
    $dealer_id = $request->dados->dealer_id;
    $value = $request->dados->value;
    $betTime =$request->dados->time;
    $time =  date("Y-m-d H:i:s", ($betTime+(30*60*60))/1000);

    try {
      $checkBet = $conn->prepare('SELECT * FROM bids WHERE bet_id = :bet_id');
      $checkBet->execute(array(
          ':bet_id'   => $bet_id
      )); 

      $index = 0;
      while($row = $checkBet->fetch(PDO::FETCH_ASSOC))
      { 
        if(!is_null($row['reward'])){
          throw new Exception('Aposta já foi lançada o resultado');
        }
      }

      $stmt = $conn->prepare("UPDATE bets SET status= :status WHERE bet_id= :bet_id");
      $stmt->execute([
        ':status' => 'cancel',
        ':bet_id' => $bet_id
      ]);

      cashierCancelBet($value,$dealer_id,$time,$conn);

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
