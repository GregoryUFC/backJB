<?php
  include_once('models/headers.php');
  include_once('models/connection.php');
  
  $dados = file_get_contents("php://input");
   
  if(isset($dados) && !empty($dados)){
    $request = json_decode($dados);
    $code = $request->dados->code;
    $dealer_id = $request->dados->dealer_id;

      try {
        $stmt = $conn->prepare('SELECT * FROM bets WHERE code = :code AND status = :status');
        $stmt->execute(array(
        ':code'   => $code,
        ':status' => 'temp'
        ));     
        $count = $stmt->rowCount();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 

        if($count <= 0 AND !$resultado){
          throw new Exception('Código não existe');
        }

        $stm = $conn->prepare(" UPDATE bets SET status = 'perm', dealer_id = '".$dealer_id."' WHERE bet_id = '".$resultado['bet_id']."' ");
        $stm->execute();

        $update = $conn->prepare("UPDATE dealers D, commissions C 
        SET D.balance = D.balance + :balance - :balance/100*C.value
        WHERE D.dealer_id = :dealer_id AND D.commission_id = C.commission_id");
  
        $update->execute([
          ':balance'=> $resultado['value'],
          ':dealer_id'=> $dealer_id,
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
