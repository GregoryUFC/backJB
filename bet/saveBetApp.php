<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');
  include_once('../functions/cashier.php');
  include_once('../functions/checkToken.php');
  include_once('../functions/checkBase.php');
  
  $headers = apache_request_headers() ;

  function pc_next_permutation($p, $size) {
    // slide down the array looking for where we're smaller than the next guy
    for ($i = $size - 1; $p[$i] >= $p[$i+1]; --$i) { }

    // if this doesn't occur, we've finished our permutations
    // the array is reversed: (1, 2, 3, 4) => (4, 3, 2, 1)
    if ($i == -1) { return false; }

    // slide down the array looking for a bigger number than what we found before
    for ($j = $size; $p[$j] <= $p[$i]; --$j) { }

    // swap them
    $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

    // now reverse the elements in between by swapping the ends
    for (++$i, $j = $size; $i < $j; ++$i, --$j) {
         $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
    }

    return $p;
  }

  function joinArray($p,$n)
  {
    return substr(join('', $p), $n);
      
  }

  function fat($number)
  {
    $i = $number;
    $calc = 1;
    while ($i > 1)
    {
      $calc *= $i;
      $i--;
    }
    return $calc;
  }

  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $day = date("Y-m-d H:i:s", $request->dados->day);
    $extraction_id = $request->dados->extraction;
    $time =  date("Y-m-d H:i:s", time() -(3*60*60));
    $bids = $request->dados->items;
    $value = $request->dados->value;
    $status = 'perm';

    try {
      $token = $headers['Auth'];
      $user=[];

  
      if(is_base64($token)){
        $user = getUserId($token,$conn);
      }else{
        throw new Exception('Token não válido');
      }

      if($user['type']=='dealer'){
        $dealer_id = $user['user_id'];
      }else{
        throw new Exception('Usuário não válido');
      }

      $stmt = $conn->prepare("INSERT INTO bets(dealer_id,value,time,extraction_id,day,status,code) VALUES ('$dealer_id','$value','$time','$extraction_id','$day','$status','aaaaaaaa')");
      $stmt->execute(array());

      $id = $conn->lastInsertId();
      $code = base_convert($dealer_id.$id, 10, 16);

      $update = $conn->prepare("UPDATE bets SET code= :code WHERE bet_id= :bet_id");
      $update->execute([
        ':code' => $code,
        ':bet_id'   => $id,
      ]);

      $stm = $conn->prepare("INSERT INTO bids(bet_id,game_id,games,prize,value,prob) VALUES (?,?,?,?,?,?)");
      $index =0;
      foreach ($bids as $bid) {
        $fullgame=$bid->game;
        
        $prizes = explode("/", $bid->joinPrizes);
        $prizeCount = array_count_values($prizes);
        
        $gamesCount = count($bid->bids);
        $probability = 1;

        if($fullgame->acronym=='G'||$fullgame->acronym=='DZ'||$fullgame->acronym=='C'||$fullgame->acronym=='M')
        {
          $probability = $prizeCount['true'] * $gamesCount;
        }
        
        else if($fullgame->acronym=='M INV')
        {
          $probability = 0;
          foreach ($bid->bids as $keyGames => $game)
          {
            $perms=[];
            $set = str_split($game);
            $size = count($set) - 1;
            $perm = range(0,$size);
            $j = 0;

            do { 
                 foreach ($perm as $i) { $perms[$j][] = $set[$i]; }
            } while ($perm = pc_next_permutation($perm, $size) and ++$j);

            $n = -4;
            $b = array_map( function($item) use ($n) { return joinArray($item, $n); }, $perms);
            $probability += count( array_unique($b))*$prizeCount['true'];
          }
        }
        else if($fullgame->acronym=='C INV')
        {
          $probability = 0;
          foreach ($bid->bids as $keyGames => $game)
          {
            $perms=[];
            $set = str_split($game);
            $size = count($set) - 1;
            $perm = range(0,$size);
            $j = 0;
            do { 
                 foreach ($perm as $i) { $perms[$j][] = $set[$i]; }
            } while ($perm = pc_next_permutation($perm, $size) and ++$j);
            $n = -3;
            $b = array_map( function($item) use ($n) { return joinArray($item, $n); }, $perms);
            $probability += count( array_unique($b))*$prizeCount['true'];
          }
        }
        else if($fullgame->acronym=='DG COMB'||$fullgame->acronym=='DDZ COMB'||$fullgame->acronym=='TG COMB'||$fullgame->acronym=='TDZ COMB')
        {
          $n = (strpos($fullgame->acronym, 'DG') !== false|| strpos($fullgame->acronym, 'DDZ') !== false)?2:3;
          $probability = fat($gamesCount)/(fat($n)*fat($gamesCount-$n));
        }

        $row = [
          $id,
          $fullgame->game_id,
          $bid->joinBids,
          $bid->joinPrizes,
          $bid->value,
          $probability
        ];
        $stm->execute($row);
        $index ++;
      }
      cashierNewBet($value,$dealer_id,$time,$conn);

      http_response_code(201);
      $data = [
        'data'=>$code,
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
