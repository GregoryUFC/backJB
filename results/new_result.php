<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/cashier.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');

  function cut($n)
  {
      return substr($n, -2);
  }
  function group($n)
  {
    $group = ceil((floatval(substr($n, -2)))/4);
    $group = $group==0?25:$group;
    return $group;
  }

  function pc_array_power_set($array) {
    // initialize by adding the empty set
    $results = array(array( ));

    foreach ($array as $element)
        foreach ($results as $combination)
            array_push($results, array_merge(array($element), $combination));

    return $results;
  }

  $headers = apache_request_headers() ;
  $dados = file_get_contents("php://input");
  
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $extraction_id = $request->dados->extraction_id;
    $day =  date("Y-m-d H:i:s", $request->dados->day);
    $allPrizes = $request->dados->prizes;
    $prize1 = $request->dados->prize1;
    $prize2 = $request->dados->prize2;
    $prize3 = $request->dados->prize3;
    $prize4 = $request->dados->prize4;
    $prize5 = $request->dados->prize5;
    $prize6 = $request->dados->prize6;
    $prize7 = $request->dados->prize7;
    $teste = [];

    try {
      $token = $headers['Auth'];
      $user=[];
  
      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      if($user['type']=='admin'){
        $stmte = $conn->prepare('SELECT B.*,D.region_id FROM bets AS B 
        LEFT JOIN dealers as D on D.dealer_id = B.dealer_id 
        WHERE day= :day AND extraction_id= :extraction_id AND B.status="perm" AND D.banca_id=:user_id ORDER BY bet_id');

        $stmte->execute(array(
          ':day'   =>  $day,
          ':extraction_id'   =>  $extraction_id,
          ':user_id'   => $user['user_id'],
        ));
      }else{
        throw new Exception('Usuário não válido');
      }

      $consulta = $conn->prepare("SELECT B.*,G.*,GR.quotation FROM bids AS B 
      LEFT JOIN games AS G ON B.game_id = G.game_id 
      LEFT JOIN game_region AS GR ON GR.game_id = G.game_id AND GR.region_id = :region_id
      WHERE bet_id = :bet_id ORDER BY bid_id");  

      $update = $conn->prepare("UPDATE bids SET win= :win, reward= :reward WHERE bid_id = :bid_id");  

      while($row = $stmte->fetch(PDO::FETCH_ASSOC))
      { 
        $consulta->execute(array(
          ':bet_id'   => $row['bet_id'],
          ':region_id'   => $row['region_id'],
        ));
        
        while($col = $consulta->fetch(PDO::FETCH_ASSOC))
        {
          $games = explode(" ", $col['games']);
          $prizes = explode("/",$col['prize']);
          $wins = 0;

          if($col['acronym']=='G'||
            $col['acronym']=='DZ'||
            $col['acronym']=='C'||
            $col['acronym']=='M'||
            $col['acronym']=='M INV'||
            $col['acronym']=='C INV')
          {
            foreach ($prizes as $keyPrize => $prize)
            {
              if($prize=='true')
              {
                foreach ($games as $keyGames => $game)
                {
                  switch ($col['acronym']) {
                    case "G":
                      $group = ceil((floatval(substr($allPrizes[$keyPrize], -2)))/4);
                      $group = $group==0?25:$group;
                      if($group==$game)
                      {
                        $wins++;
                      }
                      break;
                    case "DZ":
                      if(substr($allPrizes[$keyPrize], -2)==$game)
                      {
                        $wins++;
                      }
                      break;
                    case "C":
                      if(substr($allPrizes[$keyPrize], -3)==$game)
                      {
                        $wins++;
                      }
                      break;
                    case "M":
                      if($allPrizes[$keyPrize]==$game)
                      {
                        $wins++;
                      }
                      break;
                    case "M INV":
                      if(!array_diff(str_split($allPrizes[$keyPrize]), str_split($game))&&
                      !array_diff(str_split($game),str_split($allPrizes[$keyPrize])))
                      {
                        $wins++;
                      }
                      break;
                    case "C INV":
                      if(!array_diff(str_split(substr($allPrizes[$keyPrize], -3)), str_split($game)) && 
                      !array_diff(str_split($game),str_split(substr($allPrizes[$keyPrize], -3))))
                      {
                        $wins++;
                      }
                      break;
                  }
                  
                }
              }
            }
          }
          else if($col['acronym']=='DG'||
                  $col['acronym']=='TG'||
                  $col['acronym']=='DG COMB'||
                  $col['acronym']=='TG COMB')
          {
            $n = strpos($col['acronym'], 'DG') !== false?2:3;
            $cutPrizes = array_map('group', $allPrizes);
            array_splice($cutPrizes, -2,2);
            foreach (pc_array_power_set($games) as $combination)
            {
              if ($n == count($combination)) { 
                if(!array_diff($combination, $cutPrizes) &&count(array_diff($cutPrizes, $combination))<=5-$n)
                {
                  $wins++;
                }
              }
            }
          }
          else if($col['acronym']=='DDZ'||
                  $col['acronym']=='TDZ'||
                  $col['acronym']=='DDZ COMB'||
                  $col['acronym']=='TDZ COMB')
          {
            $n = strpos($col['acronym'], 'DDZ') !== false?2:3;
            $cutPrizes = array_map('cut', $allPrizes);
            array_splice($cutPrizes, -2,2);
            foreach (pc_array_power_set($games) as $combination)
            {
              if ($n == count($combination)) { 
                if(!array_diff($combination, $cutPrizes) &&count(array_diff($cutPrizes, $combination))<=5-$n)
                {
                  $wins++;
                }
              }
            }
          }
          else if($col['acronym']=='PV'||
                  $col['acronym']=='PVV')
          {
            $first=false;
            $second=false;
            foreach ($allPrizes as $keyPrize => $prize)
            {
              if($keyPrize<5){
                $group = ceil((floatval(substr($prize, -2)))/4);
                $group = $group==0?25:$group;
                switch ($col['acronym']) {
                  case "PV":
                    if($group==$games[0] && $keyPrize==0)
                    {
                      $first=true;
                    }else if($group==$games[1] && $keyPrize>0){
                      $second=true;
                      break 2;
                    }
                    break;
                  case "PVV":
                      if($group==$games[0]&&$keyPrize==0)
                      {
                        $first=true;
                      }
                      else if($group==$games[1]&&$keyPrize==0)
                      {
                        $second=true;
                      }

                      if($first && $group==$games[1] && $keyPrize>0)
                      {
                        $second=true;
                        break 2;
                      }
                      else if($second && $group==$games[0]&& $keyPrize>0)
                      {
                        $first=true;
                        break 2;
                      }
                    break;
                }
              }else{
                break;
              }
            }
            if($first&&$second){
              $wins = 1;
            }
            
          }
          else if($col['acronym']=='PSD')
          {
            $first=false;
            $second=false;
            $third=false;
            foreach ($allPrizes as $keyPrize => $prize)
            {
              if($keyPrize<5){
                $group = ceil((floatval(substr($prize, -2)))/4);
                $group = $group==0?25:$group;
                if($group==$games[0]&&$keyPrize==0)
                {
                  $first=true;
                }
                else if($group==$games[1]&&$keyPrize==0)
                {
                  $second=true;
                }

                if($first && $group==$games[1] && $keyPrize==1)
                {
                  $second=true;
                  break;
                }
                else if($second && $group==$games[0]&& $keyPrize==1)
                {
                  $first=true;
                  break;
                }

                if($first && $group==$games[1] && $keyPrize>1)
                {
                  $third=true;
                  break;
                }
                else if($second && $group==$games[0]&& $keyPrize>1)
                {
                  $third=true;
                  break;
                }
                
              }else{
                break;
              }
            }
            if($first && $second){
              $wins = 1;
            }else if($third){
              $wins = 0.5;
            }
            
          }


          $reward = bcdiv( $col['quotation'] * $col['value'] * ($wins/$col['prob']),1,2);

          $update->execute([
            'win'=> $wins==0?0:1,
            'reward'=> $reward,
            'bid_id'=> $col['bid_id']
          ]);
          if($wins>0){
            cashierNewResult($reward,$row['dealer_id'],$row['time'],$conn);
          }
         
        }
      }

      $stmt = $conn->prepare("INSERT INTO results(extraction_id,banca_id,day,prize1,prize2,prize3,prize4,prize5,prize6,prize7) VALUES (?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([
        $extraction_id,
        $user['user_id'],
        $day,
        $prize1,
        $prize2,
        $prize3,
        $prize4,
        $prize5,
        $prize6,
        $prize7,
      ]);

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