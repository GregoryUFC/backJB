<?php

function getUserId($token,$con){
    $decode = base64_decode($token);
    $pieces = explode("/", $decode);
    if(count($pieces)!=2){
        throw new Exception('Token não valido');
    }
    $check = $con->prepare('SELECT * FROM auth WHERE uuid = :uuid AND type = :type');
    $check->execute(array(
    ':uuid' => $pieces[0],
    ':type' => $pieces[1]
    ));     
    $auth = $check->fetch(PDO::FETCH_ASSOC); 
    switch ($pieces[1]) {
        case 'admin':
            $stmt = $con->prepare('SELECT banca_id AS user_id FROM banca WHERE auth_id = :auth_id');
            break;
        case 'manager':
            $stmt = $con->prepare('SELECT manager_id AS user_id, banca_id FROM manager WHERE auth_id = :auth_id');
            break;
        case 'dealer':
            $stmt = $con->prepare('SELECT dealer_id AS user_id, banca_id FROM dealers WHERE auth_id = :auth_id');
            break;
    }
    
    $stmt->execute(array(
    ':auth_id'   => $auth['auth_id']
    ));     
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC); 
    $resultado['type'] = $pieces[1];
    return $resultado; 
}

?>
 