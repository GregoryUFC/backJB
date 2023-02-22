<?php
function _updateManagerNewBet($value,$ouput_value,$comm_inc,$comm_pro,$cashier_id,$con){
    $updateManager = $con->prepare("UPDATE cashier_manager 
    SET input = input + :value, 
    output_commission =  output_commission + :ouput_value, 
    commission_incoming	 =  commission_incoming	 + :comm_inc, 
    balance = balance +:value - :ouput_value - :comm_inc - (CASE
                                WHEN commission_profit >0 
                                THEN :comm_pro
                                WHEN commission_profit + :comm_pro <0 
                                THEN 0
                                WHEN commission_profit + :comm_pro >=0 
                                THEN commission_profit +:comm_pro
                            END),
    commission_profit =  commission_profit + :comm_pro
    WHERE cashier_id = :cashier_id");

    $updateManager->execute([
      ':value'=> $value,
      ':cashier_id'=> $cashier_id,
      ':ouput_value'=> $ouput_value,
      ':comm_inc'=> $comm_inc,
      ':comm_pro'=> $comm_pro
    ]);

}
function cashierNewBet($value,$dealer_id,$time,$con){

    $dt = new DateTime;
    $dt->sub(new DateInterval('PT3H'));
    $week = $dt->format('w')==0?7:$dt->format('w');
    $dt->sub(new DateInterval('P'.$week.'D'));
    $dt->add(new DateInterval('P1D'));
    $dt->setTime(0, 0);
    $starter_date = $dt->format('Y-m-d H:i:s');
    $dt->add(new DateInterval('P7D'));
    $end_date = $dt->format('Y-m-d H:i:s');
    
    try{
        $checkManager = $con->prepare('SELECT CM.cashier_id,M.comm_incoming,M.comm_profit FROM cashier_manager AS CM 
        LEFT JOIN manager as M 
        on CM.manager_id = M.manager_id
        LEFT JOIN dealers as D 
        on CM.manager_id = D.manager_id 
        WHERE D.dealer_id = :dealer_id AND :time BETWEEN CM.starter_date AND CM.end_date');
        $checkManager->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $countManager = $checkManager->rowCount();
        $cashierManager = $checkManager->fetch(PDO::FETCH_ASSOC);

        $checkDealer = $con->prepare('SELECT CD.*, C.value AS commission_value
        FROM cashier_dealer AS CD 
        LEFT JOIN dealers as D 
        on CD.dealer_id = D.dealer_id 
        LEFT JOIN commissions as C 
        on C.commission_id = D.commission_id
        WHERE CD.dealer_id = :dealer_id AND :time BETWEEN CD.starter_date AND CD.end_date');
        $checkDealer->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $countDealer = $checkDealer->rowCount();
        $cashierDealer = $checkDealer->fetch(PDO::FETCH_ASSOC);

        if($countDealer>0){
            
            $ouput_value = ($value*$cashierDealer['commission_value'])/100;
            $comm_inc = ($value - $ouput_value) *$cashierManager['comm_incoming']/100;
            $comm_pro = ($value- $ouput_value - $comm_inc) *$cashierManager['comm_profit']/100;

            _updateManagerNewBet($value,$ouput_value,$comm_inc,$comm_pro,$cashierManager['cashier_id'],$con);

            $updateDealer = $con->prepare("UPDATE cashier_dealer 
            SET input = input + :value, output_commission =  output_commission + :comm_value, balance = balance + :value - :comm_value
            WHERE cashier_id = :cashier_id");
      
            $updateDealer->execute([
              ':value'=> $value,
              ':cashier_id'=> $cashierDealer['cashier_id'],
              ':comm_value'=> ($value*$cashierDealer['commission_value'])/100,
            ]);
        
            
        }else{
            
            $dealerComm = $con->prepare('SELECT D.*, C.value AS commission_value
            FROM dealers AS D 
            LEFT JOIN commissions as C 
            on C.commission_id = D.commission_id
            WHERE D.dealer_id = :dealer_id');
            $dealerComm->execute(array(
                ':dealer_id'   => $dealer_id,
            )); 
            $dealer = $dealerComm->fetch(PDO::FETCH_ASSOC);

            if($countManager>0){
                $ouput_value = ($value*$dealer['commission_value'])/100;
                $comm_inc = ($value - $ouput_value) *$cashierManager['comm_incoming']/100;
                $comm_pro = ($value- $ouput_value - $comm_inc) *$cashierManager['comm_profit']/100;

                _updateManagerNewBet($value,$ouput_value,$comm_inc,$comm_pro,$cashierManager['cashier_id'],$con);
            }else{

                $managerComm = $con->prepare('SELECT D.manager_id,M.comm_incoming,M.comm_profit
                FROM manager AS M 
                LEFT JOIN dealers as D 
                on D.manager_id = M.manager_id
                WHERE D.dealer_id = :dealer_id');
                $managerComm->execute(array(
                    ':dealer_id'   => $dealer_id,
                )); 
                $manager = $managerComm->fetch(PDO::FETCH_ASSOC);

                $managerInsert = $con->prepare("INSERT INTO cashier_manager(manager_id,input,output_commission,output_prize,credit,debit,commission_incoming,commission_profit,balance,starter_date,end_date) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");

                $ouput_value = ($value*$dealer['commission_value'])/100;
                $comm_inc = ($value - $ouput_value) *$manager['comm_incoming']/100;
                $comm_pro = ($value- $ouput_value - $comm_inc)*$manager['comm_profit']/100;

                $managerInsert->execute([
                    $manager['manager_id'],
                    $value,
                    $ouput_value,
                    0,
                    0,
                    0,
                    $comm_inc,
                    $comm_pro,
                    $value-$ouput_value-$comm_inc-$comm_pro,
                    $starter_date,
                    $end_date
                ]);


            }

            $dealerInsert = $con->prepare("INSERT INTO cashier_dealer(dealer_id,input,output_commission,output_prize,credit,debit,balance,starter_date,end_date) 
            VALUES (?,?,?,?,?,?,?,?,?)");

            $dealerInsert->execute([
                $dealer_id,
                $value,
                $value*$dealer['commission_value']/100,
                0,
                0,
                0,
                $value- ($value*$dealer['commission_value']/100),
                $starter_date,
                $end_date
            ]);
        }

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierNewResult($value,$dealer_id,$time,$con){
    
    try{

        $updateDealer = $con->prepare("UPDATE cashier_dealer SET output_prize =  output_prize + :value, balance = balance - :value 
        WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date");
        $updateDealer->execute([
          ':value'=> $value,
          ':dealer_id'   => $dealer_id,
          ':time'   => $time,
        ]);

        $checkManager = $con->prepare('SELECT CM.cashier_id,M.comm_incoming,M.comm_profit FROM cashier_manager AS CM 
        LEFT JOIN manager as M 
        on CM.manager_id = M.manager_id
        LEFT JOIN dealers as D 
        on CM.manager_id = D.manager_id 
        WHERE D.dealer_id = :dealer_id AND :time BETWEEN CM.starter_date AND CM.end_date');
        $checkManager->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $countManager = $checkManager->rowCount();
        $cashierManager = $checkManager->fetch(PDO::FETCH_ASSOC);

        
        $updateManager = $con->prepare("UPDATE cashier_manager 
        SET output_prize =  output_prize + :value, 
        balance = balance - :value  + (CASE
                                        WHEN commission_profit <0 
                                        THEN 0
                                        WHEN commission_profit - :commission_output <0 
                                        THEN commission_profit 
                                        WHEN commission_profit - :commission_output >=0 
                                        THEN :commission_output
                                    END),
        commission_profit =  commission_profit - :commission_output
        WHERE cashier_id = :cashier_id");

        $updateManager->execute([
          ':value'=> $value,
          ':commission_output' => $value*$cashierManager['comm_profit']/100,
          ':cashier_id'=> $cashierManager['cashier_id'],

        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierEditResult($value,$oldValue,$dealer_id,$time,$con){
    
    try{

        $diff = $oldValue - $value;

        $updateDealer = $con->prepare("UPDATE cashier_dealer SET output_prize =  output_prize - :value, balance = balance + :value 
        WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date");
        $updateDealer->execute([
          ':value'=> $diff,
          ':dealer_id'   => $dealer_id,
          ':time'   => $time,
        ]);

        $checkManager = $con->prepare('SELECT CM.cashier_id,M.comm_incoming,M.comm_profit FROM cashier_manager AS CM 
        LEFT JOIN manager as M 
        on CM.manager_id = M.manager_id
        LEFT JOIN dealers as D 
        on CM.manager_id = D.manager_id 
        WHERE D.dealer_id = :dealer_id AND :time BETWEEN CM.starter_date AND CM.end_date');
        $checkManager->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $countManager = $checkManager->rowCount();
        $cashierManager = $checkManager->fetch(PDO::FETCH_ASSOC);

        
        $updateManager = $con->prepare("UPDATE cashier_manager 
        SET output_prize =  output_prize - :value, 
        balance = balance + :value  - (CASE
                                        WHEN commission_profit<0
                                        THEN (CASE
                                            WHEN commission_profit + :commission_output <0 
                                            THEN 0
                                            WHEN commission_profit + :commission_output >=0 
                                            THEN commission_profit + :commission_output
                                            END)
                                        ELSE (CASE
                                            WHEN commission_profit + :commission_output >=0 
                                            THEN :commission_output
                                            WHEN commission_profit + :commission_output <0 
                                            THEN -commission_profit
                                            END)
                                        END),
        commission_profit =  commission_profit + :commission_output
        WHERE cashier_id = :cashier_id");

        $updateManager->execute([
          ':value'=> $diff,
          ':commission_output' => $diff*$cashierManager['comm_profit']/100,
          ':cashier_id'=> $cashierManager['cashier_id'],

        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierCancelBet($value,$dealer_id,$time,$con){
    
    try{
        
        $checkManager = $con->prepare('SELECT CM.cashier_id,M.comm_incoming,M.comm_profit FROM cashier_manager AS CM 
        LEFT JOIN manager as M 
        on CM.manager_id = M.manager_id
        LEFT JOIN dealers as D 
        on CM.manager_id = D.manager_id 
        WHERE D.dealer_id = :dealer_id AND :time BETWEEN CM.starter_date AND CM.end_date');
        $checkManager->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $cashierManager = $checkManager->fetch(PDO::FETCH_ASSOC);

        $checkDealer = $con->prepare('SELECT CD.*, C.value AS commission_value
        FROM cashier_dealer AS CD 
        LEFT JOIN dealers as D 
        on CD.dealer_id = D.dealer_id 
        LEFT JOIN commissions as C 
        on C.commission_id = D.commission_id
        WHERE CD.dealer_id = :dealer_id AND :time BETWEEN CD.starter_date AND CD.end_date');
        $checkDealer->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $cashierDealer = $checkDealer->fetch(PDO::FETCH_ASSOC);

            
        $updateManager = $con->prepare("UPDATE cashier_manager 
        SET input = input - :value, 
        output_commission =  output_commission - :ouput_value, 
        commission_incoming	 =  commission_incoming	 - :comm_inc, 
        balance = balance -:value + :ouput_value + :comm_inc + (CASE
                                    WHEN commission_profit <0 
                                    THEN 0
                                    WHEN commission_profit - :comm_pro >=0 
                                    THEN :comm_pro
                                    WHEN commission_profit - :comm_pro <0 
                                    THEN commission_profit 
                                END),
        commission_profit =  commission_profit - :comm_pro
        WHERE cashier_id = :cashier_id");

        $ouput_value = ($value*$cashierDealer['commission_value'])/100;
        $comm_inc = ($value - $ouput_value) *$cashierManager['comm_incoming']/100;
        $comm_pro = ($value- $ouput_value - $comm_inc) *$cashierManager['comm_profit']/100;

        $updateManager->execute([
            ':value'=> $value,
            ':cashier_id'=> $cashierManager['cashier_id'],
            ':ouput_value'=> $ouput_value,
            ':comm_inc'=> $comm_inc,
            ':comm_pro'=> $comm_pro
        ]);

        $updateDealer = $con->prepare("UPDATE cashier_dealer 
        SET input = input - :value, output_commission =  output_commission - :comm_value, balance = balance - :value + :comm_value
        WHERE cashier_id = :cashier_id");
    
        $updateDealer->execute([
            ':value'=> $value,
            ':cashier_id'=> $cashierDealer['cashier_id'],
            ':comm_value'=> ($value*$cashierDealer['commission_value'])/100,
        ]);
        
            
        

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierDealerUpdatelStatement($value,$oldValue,$type,$dealer_id,$time,$con){
    $diff = $oldValue - $value;
    try{
        $updateDealer = $con->prepare("UPDATE cashier_dealer 
        SET credit = credit - :credit, debit =  debit - :debit, balance = balance - :credit + :debit
        WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date");
    
        $updateDealer->execute([
            ':debit'=> $type=='debit'? $diff:0,
            ':credit'=> $type=='credit'? $diff:0,
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierManagerUpdatelStatement($value,$oldValue,$type,$manager_id,$time,$con){
    $diff = $oldValue - $value;
    try{
        $updateManager = $con->prepare("UPDATE cashier_manager 
        SET credit = credit - :credit, debit =  debit - :debit, balance = balance - :credit + :debit
        WHERE manager_id = :manager_id AND :time BETWEEN starter_date AND end_date");

        $updateManager->execute([
            ':debit'=> $type=='debit'? $diff:0,
            ':credit'=> $type=='credit'? $diff:0,
            ':manager_id'   => $manager_id,
            ':time'   => $time,
        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierDealerCancelStatement($value,$type,$dealer_id,$time,$con){
    
    try{

        $updateDealer = $con->prepare("UPDATE cashier_dealer 
        SET credit = credit - :credit, debit =  debit - :debit, balance = balance - :credit + :debit
        WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date");
    
        $updateDealer->execute([
            ':debit'=> $type=='debit'? $value:0,
            ':credit'=> $type=='credit'? $value:0,
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierManagerCancelStatement($value,$type,$manager_id,$time,$con){
    
    try{

        $updateManager = $con->prepare("UPDATE cashier_manager 
        SET credit = credit - :credit, debit =  debit - :debit, balance = balance - :credit + :debit
        WHERE manager_id = :manager_id AND :time BETWEEN starter_date AND end_date");

        $updateManager->execute([
            ':debit'=> $type=='debit'? $value:0,
            ':credit'=> $type=='credit'? $value:0,
            ':manager_id'   => $manager_id,
            ':time'   => $time,
        ]);

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierManagerStatement($value,$type,$manager_id,$time,$con){
    $dt = new DateTime;
    $dt->sub(new DateInterval('PT3H'));
    $week = $dt->format('w')==0?7:$dt->format('w');
    $dt->sub(new DateInterval('P'.$week.'D'));
    $dt->add(new DateInterval('P1D'));
    $dt->setTime(0, 0);
    $starter_date = $dt->format('Y-m-d H:i:s');
    $dt->add(new DateInterval('P7D'));
    $end_date = $dt->format('Y-m-d H:i:s');
    try{
        
        $checkManager = $con->prepare('SELECT CM.cashier_id FROM cashier_manager AS CM 
        LEFT JOIN manager as M 
        on CM.manager_id = M.manager_id
        WHERE CM.manager_id = :manager_id AND :time BETWEEN CM.starter_date AND CM.end_date');
        $checkManager->execute(array(
            ':manager_id'   => $manager_id,
            ':time'   => $time,
        )); 
        $cashierManager = $checkManager->fetch(PDO::FETCH_ASSOC);
        $countManager = $checkManager->rowCount();

        if($countManager>0){
            $updateManager = $con->prepare("UPDATE cashier_manager 
            SET credit = credit + :credit, debit =  debit + :debit, balance = balance + :credit - :debit
            WHERE cashier_id = :cashier_id");
    
            $updateManager->execute([
                ':cashier_id'=> $cashierManager['cashier_id'],
                ':debit'=> $type=='debit'? $value:0,
                ':credit'=> $type=='credit'? $value:0
            ]);
        }else{
            $managerInsert = $con->prepare("INSERT INTO cashier_manager(manager_id,input,output_commission,output_prize,credit,debit,commission_incoming,commission_profit,balance,starter_date,end_date) 
            VALUES (?,?,?,?,?,?,?,?,?,?,?)");

            $managerInsert->execute([
                $manager_id,
                0,
                0,
                0,
                $type=='credit'? $value:0,
                $type=='debit'? $value:0,
                0,
                0,
                $type=='credit'? $value:-1*$value,
                $starter_date,
                $end_date
            ]);
        }

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}

function cashierDealerStatement($value,$type,$dealer_id,$time,$con){
    $dt = new DateTime;
    $dt->sub(new DateInterval('PT3H'));
    $week = $dt->format('w')==0?7:$dt->format('w');
    $dt->sub(new DateInterval('P'.$week.'D'));
    $dt->add(new DateInterval('P1D'));
    $dt->setTime(0, 0);
    $starter_date = $dt->format('Y-m-d H:i:s');
    $dt->add(new DateInterval('P7D'));
    $end_date = $dt->format('Y-m-d H:i:s');
    try{

        $checkDealer = $con->prepare('SELECT * FROM cashier_dealer WHERE dealer_id = :dealer_id AND :time BETWEEN starter_date AND end_date');
        $checkDealer->execute(array(
            ':dealer_id'   => $dealer_id,
            ':time'   => $time,
        )); 
        $cashierDealer = $checkDealer->fetch(PDO::FETCH_ASSOC);
        $countDealer = $checkDealer->rowCount();

        if($countDealer>0){

            $updateDealer = $con->prepare("UPDATE cashier_dealer 
            SET credit = credit + :credit, debit =  debit + :debit, balance = balance + :credit - :debit
            WHERE cashier_id = :cashier_id");
        
            $updateDealer->execute([
                ':cashier_id'=> $cashierDealer['cashier_id'],
                ':debit'=> $type=='debit'? $value:0,
                ':credit'=> $type=='credit'? $value:0,
            ]);

        }else{
            $dealerInsert = $con->prepare("INSERT INTO cashier_dealer(dealer_id,input,output_commission,output_prize,credit,debit,balance,starter_date,end_date) 
            VALUES (?,?,?,?,?,?,?,?,?)");

            $dealerInsert->execute([
                $dealer_id,
                0,
                0,
                0,
                $type=='credit'? $value:0,
                $type=='debit'? $value:0,
                $type=='credit'? $value:-1*$value,
                $starter_date,
                $end_date
            ]);
        }

    } catch (Exception $e) {
        echo $e->getMessage();
    }

}
?>
 