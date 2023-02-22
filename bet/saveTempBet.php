<?php
  include_once('../models/headers.php');
  include_once('../models/connection.php');

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

  function getName($n) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
  
    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
  
    return $randomString;
}
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $day = date("Y-m-d H:i:s", $request->dados->day);
    $extraction_id = $request->dados->extraction_id;
    $time =  date("Y-m-d H:i:s", $request->dados->time);
    $bids = $request->dados->listBet;
    $value = $request->dados->totalValue;
    $code = getName(8);
    $status = 'temp';

    
    try {
      $stmt = $conn->prepare("INSERT INTO bets(value,time,extraction_id,day,status,code) VALUES ('$value','$time','$extraction_id','$day','$status','$code')");
      $stmt->execute(array());
      $id = $conn->lastInsertId();

      $stm = $conn->prepare("INSERT INTO bids(bet_id,game_id,games,prize,value) VALUES (?,?,?,?,?)");
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
        
        else if($fullgame->acronym=='M INV'||$fullgame->acronym=='C INV')
        {
          $probability = 0;
          foreach ($bid->bids as $keyGames => $game)
          {
            $set = str_split($game); // like array('she', 'sells', 'seashells')
            $size = count($set) - 1;
            $perm = range(0,$size);
            $j = 0;
            do { 
                 foreach ($perm as $i) { $perms[$j][] = $set[$i]; }
            } while ($perm = pc_next_permutation($perm, $size) and ++$j);
            $n = $fullgame->acronym=='M INV'?-4:-3;

            $b = array_map( function($item) use ($n) { return joinArray($item, $n); }, $perms);
            $probability += count( array_unique($b))*$prizeCount['true'];
          }
        }
        else if($fullgame->acronym=='DG COMB'||$fullgame->acronym=='DDZ COMB'||$fullgame->acronym=='TG COMB'||$fullgame->acronym=='TDZ COMB')
        {
          $n = $fullgame->acronym=='DG COMB'||$fullgame->acronym=='DDZ COMB'?2:3;
          $i = $gamesCount;
          $j = $gamesCount-$n;
          $calcI = 1;
          $calcJ = 1;
          $calcN = 1;
          while ($i > 1)
          {
            $calcI *= $i;
            $i--;
          }
          while ($j > 1)
          {
            $calcJ *= $j;
            $j--;
          }
          while ($n > 1)
          {
            $calcN *= $n;
            $n--;
          }
          $probability = fat(count($games))/(fat($n)*fat(count($games)-$n));
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
      }
      http_response_code(201);
      $data = [
          'code' =>  $code,
      ];
      echo json_encode(['data'=>$data]);
        
    } catch (Exception $e) {
      http_response_code(404);
      $error = [
        'error' => $e->getMessage()
      ];
      echo json_encode(['data'=>$error]);
    }

  }
?>
