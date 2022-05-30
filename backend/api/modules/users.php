<?php

function login(){
    global $R, $DB, $ME, $RET;
    
    $q = $DB->prepare("SELECT usr_id, first_name, last_name, avatar, mykey, user_type FROM users where usr_id = :usr_id limit 1");
    $q->bindValue('usr_id', $R['data']['usr_id'], PDO::PARAM_INT);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        if($row['mykey'] == $R['data']['mykey']){
            $row['user_type'] = empty($row['user_type']) ? 'default' : $row['user_type'];
            $RET = ['data' => $row ];
        }else{  
            $RET = ['error' => 'Ошибка авторизации'];
        }
    }else{
        $RET = ['error' => 'Пользователь не найден'];
    }

}

function get_user(){
    global $R, $DB, $ME, $RET;
    
    $q = $DB->prepare("SELECT usr_id, first_name, last_name, avatar, user_type FROM users where usr_id = :usr_id limit 1");
    $q->bindValue('usr_id', $R['data']['usr_id'], PDO::PARAM_INT);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $row['user_type'] = empty($row['user_type']) ? 'default' : $row['user_type'];
        $RET = ['data' => $row ];
    }else{
        $RET = ['error' => 'Пользователь не найден'];
    }
}

function get_images(){
    global $R, $DB, $ME, $RET;
    $type = isset($R['info']['type']) ? $R['info']['type'] : 'img';
    $RET = GetAutoList("SELECT * from images where usr_id = :usr_id and type = :type", 'images', 'img_id', [['type', $type, PDO::PARAM_STR]]);
    for ($i=0; $i < count($RET['data']); $i++) { 
        $RET['data'][$i]['url'] = LINK.'/uploaded/'.$RET['data'][$i]['url'];
    }
}

function delete_image(){
    global $R, $DB, $ME, $RET;
    $imgID = $R['img_id'];
    $q = $DB->prepare("SELECT * from images where img_id = :img_id limit 1");
    $q->bindValue('img_id', $imgID, PDO::PARAM_INT);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        if($row['usr_id'] == $ME['usr_id']){
            $filePath = "../../uploaded/".$row['url'];
            if(unlink($filePath)){
                $qr = $DB->prepare("DELETE FROM images where img_id = :img_id");
                $qr->bindValue("img_id", $imgID, PDO::PARAM_INT);
                $qr->execute();
                $RET = ['data'=>$imgID];
            }else{
                $RET = ['error'=>"Файл не найден"];
            }
        }else{
            $RET = ['error'=>"Нет доступа"];
        }
    }else{
        $RET = ['error'=>"Объект не найден"];
    }
}

?>