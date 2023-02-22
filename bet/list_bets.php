<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  $headers = apache_request_headers() ;

  $data['page'] = (!empty($_GET['page'])) ? $_GET['page'] : 1;
  $qnt_result_pg = 50;
  $inicio = ($qnt_result_pg * $data['page']) - $qnt_result_pg;

  $code = $_GET['code'];
  $starter_day = $_GET['starter_day'];
  $ending_day = $_GET['ending_day'];
  $dealer = $_GET['dealer']; 

  try {

    $bets=[];
    $token = $headers['Auth'];
    $user=[];

    if(is_base64($token)){
      $user = getUserId($token,$conn);
    }else{
      throw new Exception('Token não válido');
    }
    $sqlCode = $code? "AND code= :code":"";
    $sqlDealer = $dealer? "AND D.dealer_id= :dealer_id":"";
    $sqlDate = $starter_day&&$ending_day? "AND time BETWEEN :starter_day AND :ending_day":"";

    if($user['type']=='admin'){
        $queryBetCountString = 'SELECT COUNT(bet_id) AS quant FROM bets AS B 
        LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
        WHERE B.status !="temp"'.$sqlCode.''.$sqlDate.''.$sqlDealer.' AND D.banca_id =:user_id';
  
        $queryBetString = 'SELECT B.*,E.*,D.name as dealer_name FROM bets AS B 
        LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
        LEFT JOIN extraction as E on E.extraction_id = B.extraction_id 
        WHERE B.status !="temp"'.$sqlCode.''.$sqlDate.''.$sqlDealer.' AND D.banca_id =:user_id ORDER BY time DESC LIMIT :limit_start, :limit_quant';

        $queryCountBet = $conn->prepare($queryBetCountString);
        $queryBet = $conn->prepare($queryBetString);

    }else if($user['type']=='manager'){

      $queryBetCountString = 'SELECT COUNT(bet_id) AS quant FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE B.status !="temp"'.$sqlCode.''.$sqlDate.''.$sqlDealer.' AND D.manager_id =:user_id';

      $queryBetString = 'SELECT B.*,E.*,D.name as dealer_name FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      LEFT JOIN extraction as E on E.extraction_id = B.extraction_id 
      WHERE B.status !="temp"'.$sqlCode.''.$sqlDate.''.$sqlDealer.' AND D.manager_id =:user_id ORDER BY time DESC LIMIT :limit_start, :limit_quant';

      $queryCountBet = $conn->prepare($queryBetCountString);
      $queryBet = $conn->prepare($queryBetString);
    }else if($user['type']=='dealer'){
      $queryBetCountString = 'SELECT COUNT(bet_id) AS quant FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      WHERE D.dealer_id =:user_id';

      $queryBetString = 'SELECT B.*,E.*,D.name as dealer_name FROM bets AS B 
      LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
      LEFT JOIN extraction as E on E.extraction_id = B.extraction_id 
      WHERE D.dealer_id =:user_id ORDER BY time DESC LIMIT :limit_start, :limit_quant';

      $queryCountBet = $conn->prepare($queryBetCountString);
      $queryBet = $conn->prepare($queryBetString);
    }else{
      throw new Exception('Usuário não válido');
    }
    
    $queryBet->bindParam(':limit_start', $inicio, PDO::PARAM_INT); 
    $queryBet->bindParam(':limit_quant', $qnt_result_pg, PDO::PARAM_INT); 
    $queryBet->bindParam(':user_id',$user['user_id']);
    if($code){
      $queryBet->bindParam(':code',$code);
    }
    if($dealer){
      $queryBet->bindParam(':dealer_id',$dealer);
    }
    if($starter_day&&$ending_day){
      $queryBet->bindParam(':starter_day',$starter_day);
      $queryBet->bindParam(':ending_day',$ending_day);

    }
    $queryBet->execute(); 
    $count = $queryBet->rowCount();

    $queryCountBet->bindParam(':user_id',$user['user_id']);
    if($code){
      $queryCountBet->bindParam(':code',$code);
    }
    if($dealer){
      $queryCountBet->bindParam(':dealer_id',$dealer);
    }
    if($starter_day&&$ending_day){
      $queryCountBet->bindParam(':starter_day',$starter_day);
      $queryCountBet->bindParam(':ending_day',$ending_day);
    }
    $queryCountBet->execute(); 
    $result_bet = $queryCountBet->fetch(PDO::FETCH_ASSOC); 

    $data['quantPg'] = ceil($result_bet['quant'] / $qnt_result_pg);
    $data['count'] = $result_bet['quant'];

    
    if($count > 0){
      $index = 0;
      $queryBid = $conn->prepare("SELECT * FROM bids AS B 
      LEFT JOIN games as G on B.game_id = G.game_id 
      WHERE bet_id = :bet_id ORDER BY bid_id");  
 
      while($row = $queryBet->fetch(PDO::FETCH_ASSOC))
      { 
        
        $queryBid->execute(array(
          ':bet_id'   => $row['bet_id'],
        ));
        $bets[$index]['bet_id']    = $row['bet_id'];
        $bets[$index]['dealer_id'] = $row['dealer_id'];
        $bets[$index]['dealer_name'] = $row['dealer_name'];
        $bets[$index]['value'] = $row['value'];
        $bets[$index]['time'] = (strtotime($row['time'])+3*60*60)*1000;
        $bets[$index]['hour'] = $row['hour'];
        $bets[$index]['name'] = $row['name'];
        $bets[$index]['code'] = $row['code'];
        $bets[$index]['day'] = (strtotime($row['day'])+3*60*60)*1000;
        $bets[$index]['status'] = $row['status'];
        $bets[$index]['bids'] =[];
        $bidIndex = 0;
       
        while($col = $queryBid->fetch(PDO::FETCH_ASSOC))
        {
          $bets[$index]['bids'][$bidIndex]['bid_id'] = $col['bid_id'];
          $bets[$index]['bids'][$bidIndex]['game_name'] = $col['name'];
          $bets[$index]['bids'][$bidIndex]['game_acronym'] = $col['acronym'];
          $bets[$index]['bids'][$bidIndex]['games'] = $col['games'];
          $fullPrize='';
          if($col['prize']=='true/true/true/true/true'||$col['prize']=='true/true/true/true/true/false/false'){
            $fullPrize='1º ao 5º';
          }else if($col['prize']=='true/true/true/true/true/true/true'){
            $fullPrize='1º ao 7º';
          }else{
            $pieces = explode("/", $col['prize']);
            foreach ($pieces as $key => $val) {
              if($val=='true'&& $fullPrize==''){
                $fullPrize= $fullPrize.($key+1).'º';
              }else if($val=='true'&& $fullPrize!=''){
                $fullPrize= $fullPrize.', '.($key+1).'º';
              }
            }
          }
          $bets[$index]['bids'][$bidIndex]['prize'] = $fullPrize;
          $bets[$index]['bids'][$bidIndex]['value'] = $col['value'];
          $bets[$index]['bids'][$bidIndex]['win'] = $col['win'];
          $bets[$index]['bids'][$bidIndex]['reward'] = $col['reward'];
          $bidIndex++;
        }
        $index++; 
      }
      $data['bets']=$bets;
    }
    
    http_response_code(201);
    $data = [
      'data'=>$data,
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
