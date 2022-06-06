<?php

function adm_get_users(){
    global $R, $DB, $ME, $RET;

    if( isset($R['findText']) ){
        $text = '%'.trim($R['findText']).'%';
        $number = intval($R['findText']);
        if(is_numeric($R['findText']) ){
            $RET = GetAutoList("SELECT * from users where (first_name LIKE :first_name or last_name LIKE :last_name or usr_id = :usr_id) ", 
            'users', 'usr_id', [
            ['first_name', $text, PDO::PARAM_STR],
            ['last_name', $text, PDO::PARAM_STR],
            ['usr_id', $number, PDO::PARAM_INT]
            ]);
        }else{
            $RET = GetAutoList("SELECT * from users where (first_name LIKE :first_name or last_name LIKE :last_name) ", 
            'users', 'usr_id', [
            ['first_name', $text, PDO::PARAM_STR],
            ['last_name', $text, PDO::PARAM_STR]
            ]);
        }
        
    }else{
        $RET = GetAutoList("SELECT * from users", 'users', 'usr_id');
    }
}

function adm_get_user(){
    global $R, $DB, $ME, $RET;

    $q = $DB->prepare("SELECT * from users where usr_id = :usr_id limit 1");
    $q->bindValue('usr_id', $R['usr_id'], PDO::PARAM_INT);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $RET = [
            'data'=>$row,
        ];
    }else{
        $RET = [
            'error'=>'Ошибка',
        ];
    }
}

function adm_user_save(){
    global $R, $DB, $ME, $RET;

    $q = $DB->prepare("UPDATE users set first_name = :first_name, last_name = :last_name, user_type = :user_type where usr_id = :usr_id");
    BindExecute($q, [
        ['first_name', $R['data']['first_name'], PDO::PARAM_STR],
        ['last_name', $R['data']['last_name'], PDO::PARAM_STR],
        ['user_type', is_null($R['data']['user_type']) ? NULL : $R['data']['user_type'], is_null($R['data']['user_type']) ? PDO::PARAM_NULL : PDO::PARAM_STR],
        ['usr_id', $R['data']['usr_id'], PDO::PARAM_INT]
    ], true);
    $q2 = $DB->prepare("SELECT * from users where usr_id = :usr_id limit 1");
    $q2->bindValue('usr_id', $R['data']['usr_id'], PDO::PARAM_INT);
    $q2->execute();
    if($row = $q2->fetch(PDO::FETCH_ASSOC)){
        $RET = ['data'=>$row];
    }else{
        $RET = ['error'=>'Ошибка'];
    }
}

function adm_user_delete(){
    global $R, $DB, $ME, $RET;

    $qr = $DB->prepare("SELECT def_users.creator_usr_id, users.usr_id from users 
        left join def_users on (users.social_network = 'def' and users.social_id = def_users.def_usr_id) 
        where usr_id = :usr_id limit 1");
    $qr->bindValue('usr_id', $R['usr_id'], PDO::PARAM_INT);
    $qr->execute();
    if($row = $qr->fetch(PDO::FETCH_ASSOC)){
        if( ($ME['user_type'] == 'admin' || ($ME['user_type'] == 'mentor' && $row['creator_usr_id'] == $ME['usr_id'] ))  ){
            $q = $DB->prepare("DELETE FROM users where usr_id = :usr_id");
            $q->bindValue('usr_id', $R['usr_id'], PDO::PARAM_INT);
            $q->execute();
            if(empty($q->errorInfo()[1])){
                $RET = ['data'=> $R['usr_id']];
            }else{
                $RET = ['error'=> $q->errorInfo()[2] ];//'Ошибка удаления'];
            }
        }else{
            $RET = ['error'=> 'Недостаточно прав'];
        }
    }else{
        $RET = ['error'=> 'Запись не найдена'];
    }
    
}


function adm_get_def_users(){
    global $R, $DB, $ME, $RET;

    if( isset($R['findText']) ){
        $text = '%'.trim($R['findText']).'%';
        $number = intval($R['findText']);
        if(is_numeric($R['findText']) ){
            $RET = GetAutoList("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\"
                from def_users left join users creators on def_users.creator_usr_id = creators.usr_id 
                where (def_users.first_name LIKE :first_name or def_users.last_name LIKE :last_name or def_usr_id = :def_usr_id or login LIKE :login) ", 
            'def_users', 'def_usr_id', [
            ['first_name', $text, PDO::PARAM_STR],
            ['last_name', $text, PDO::PARAM_STR],
            ['login', $text, PDO::PARAM_STR],
            ['def_usr_id', $number, PDO::PARAM_INT]
            ]);
        }else{
            $RET = GetAutoList("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\"
                from def_users left join users creators on def_users.creator_usr_id = creators.usr_id 
                where (def_users.first_name LIKE :first_name or def_users.last_name LIKE :last_name or login LIKE :login) ", 
            'def_users', 'def_usr_id', [
            ['first_name', $text, PDO::PARAM_STR],
            ['last_name', $text, PDO::PARAM_STR],
            ['login', $text, PDO::PARAM_STR],
            ]);
        }
        
    }else{
        $RET = GetAutoList("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\"
            from def_users left join users creators on def_users.creator_usr_id = creators.usr_id ", 'def_users', 'def_usr_id');
    }
}

function adm_get_def_user(){
    global $R, $DB, $ME, $RET;

    $q = $DB->prepare("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\" 
        from def_users left join users creators on def_users.creator_usr_id = creators.usr_id where def_usr_id = :def_usr_id limit 1");
    $q->bindValue('def_usr_id', $R['def_usr_id'], PDO::PARAM_INT);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $RET = [
            'data'=>$row,
        ];
    }else{
        $RET = [
            'error'=>'Ошибка',
        ];
    }
}

function adm_def_user_save(){
    global $R, $DB, $ME, $RET;
    
    if( is_null($R['data']['def_usr_id']) ){ //Создание аккаунта
        $q = $DB->prepare("INSERT INTO def_users (first_name,  last_name, user_type, login, password, creator_usr_id) VALUES ( :first_name, :last_name, :user_type, :login, :password, :creator_usr_id)");
        BindExecute($q, [
            ['login', $R['data']['login'], PDO::PARAM_STR],
            ['password', $R['data']['password'], PDO::PARAM_STR],
            ['first_name', $R['data']['first_name'], PDO::PARAM_STR],
            ['last_name', $R['data']['last_name'], PDO::PARAM_STR],
            ['user_type', is_null($R['data']['user_type']) ? NULL : $R['data']['user_type'], is_null($R['data']['user_type']) ? PDO::PARAM_NULL : PDO::PARAM_STR],
            ['creator_usr_id', $ME['usr_id'], PDO::PARAM_INT]
        ], true);
        $q2 = $DB->prepare("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\"
            from def_users left join users creators on def_users.creator_usr_id = creators.usr_id 
            where creator_usr_id = :creator_usr_id order by def_usr_id desc limit 1");
        $q2->bindValue('creator_usr_id', $ME['usr_id'], PDO::PARAM_INT);
        $q2->execute();
        if($row = $q2->fetch(PDO::FETCH_ASSOC)){
            $RET = ['data'=>$row, 'event'=>'onCreated'];
        }else{
            $RET = ['error'=>'Ошибка'];
        }  
    }else{ //Редактирование
        $q = $DB->prepare("UPDATE def_users set first_name = :first_name, last_name = :last_name, user_type = :user_type, login = :login, password = :password where def_usr_id = :def_usr_id");
        BindExecute($q, [
            ['login', $R['data']['login'], PDO::PARAM_STR],
            ['password', $R['data']['password'], PDO::PARAM_STR],
            ['first_name', $R['data']['first_name'], PDO::PARAM_STR],
            ['last_name', $R['data']['last_name'], PDO::PARAM_STR],
            ['user_type', is_null($R['data']['user_type']) ? NULL : $R['data']['user_type'], is_null($R['data']['user_type']) ? PDO::PARAM_NULL : PDO::PARAM_STR],
            ['def_usr_id', $R['data']['def_usr_id'], PDO::PARAM_INT]
        ], true);
        $q2 = $DB->prepare("SELECT def_users.*, (CONCAT(CONCAT(creators.last_name, ' '), creators.first_name)) as \"creator_name\" 
            from def_users left join users creators on def_users.creator_usr_id = creators.usr_id  
            where def_usr_id = :def_usr_id limit 1");
        $q2->bindValue('def_usr_id', $R['data']['def_usr_id'], PDO::PARAM_INT);
        $q2->execute();
        if($row = $q2->fetch(PDO::FETCH_ASSOC)){
            $RET = ['data'=>$row, 'event'=>'onEdited'];
        }else{
            $RET = ['error'=>'Ошибка'];
        }     
    }
}

function adm_def_user_delete(){
    global $R, $DB, $ME, $RET;
    
    $q = $DB->prepare("DELETE FROM def_users where def_usr_id = :def_usr_id");
    $q->bindValue('def_usr_id', $R['def_usr_id'], PDO::PARAM_INT);
    $q->execute();
    if(empty($q->errorInfo()[1])){
        $RET = ['data'=> $R['def_usr_id']];
    }else{
        $RET = ['error'=> 'Ошибка удаления'];
    }
}

//Классы:
function adm_get_communities(){
    global $R, $DB, $ME, $RET;

    if( isset($R['findText']) ){
        $text = '%'.trim($R['findText']).'%';
        $number = intval($R['findText']);
        if(is_numeric($R['findText']) ){
            $RET = GetAutoList("SELECT communities.*, (SELECT count(*) from memberships where memberships.com_id = communities.com_id) as \"count_users\",
                (SELECT count(*) from groups_default where groups_default.com_id = communities.com_id) as \"count_groups\"
                from communities where (name LIKE :name or description LIKE :description or com_id = :com_id) ", 
            'communities', 'com_id', [
            ['name', $text, PDO::PARAM_STR],
            ['description', $text, PDO::PARAM_STR],
            ['com_id', $number, PDO::PARAM_INT]
            ]);
        }else{
            $RET = GetAutoList("SELECT  communities.*, (SELECT count(*) from memberships where memberships.com_id = communities.com_id) as \"count_users\",
                (SELECT count(*) from groups_default where groups_default.com_id = communities.com_id) as \"count_groups\"  
                from communities where (name LIKE :name or description LIKE :description)  ", 
            'communities', 'com_id', [
            ['name', $text, PDO::PARAM_STR],
            ['description', $text, PDO::PARAM_STR],
            ]);
        }
    }else{
        $RET = GetAutoList("SELECT communities.*, (SELECT count(*) from memberships where memberships.com_id = communities.com_id) as \"count_users\",
            (SELECT count(*) from groups_default where groups_default.com_id = communities.com_id) as \"count_groups\"  
            from communities", 'communities', 'com_id');
    }
}

function adm_get_community(){
    global $R, $DB, $ME, $RET;
    $q = $DB->prepare("SELECT * from communities where com_id = :com_id limit 1");
    $q->bindValue('com_id', $R['com_id'], PDO::PARAM_INT);
    $q->execute();
    $q2 = $DB->prepare("SELECT memberships.*, users.last_name, users.first_name from memberships 
        left join users on users.usr_id = memberships.usr_id 
        where com_id = :com_id");
    $q2->bindValue('com_id', $R['com_id'], PDO::PARAM_INT);
    $q2->execute();
    $q3 = $DB->prepare("SELECT groups_default.*, groups.name from groups_default 
        left join groups on groups.gr_id = groups_default.gr_id 
        where com_id = :com_id");
    $q3->bindValue('com_id', $R['com_id'], PDO::PARAM_INT);
    $q3->execute();
    $users = $q2->fetchAll(PDO::FETCH_ASSOC);
    $groups = $q3->fetchAll(PDO::FETCH_ASSOC);
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $RET = [
            'data'=>$row,
            'users'=>$users,
            'groups'=>$groups,
        ];
    }else{
        $RET = [
            'error'=>'Ошибка',
        ];
    }
}

function adm_community_save(){
    global $R, $DB, $ME, $RET;
    
    if( is_null($R['data']['com_id']) ){ //Создание аккаунта
        $q = $DB->prepare("INSERT INTO communities (name, description) VALUES ( :name, :description)");
        BindExecute($q, [
            ['name', $R['data']['name'], PDO::PARAM_STR],
            ['description', $R['data']['description'], PDO::PARAM_STR],
        ], true);
        $q2 = $DB->prepare("SELECT * from communities order by com_id desc limit 1");
        $q2->execute();
        if($row = $q2->fetch(PDO::FETCH_ASSOC)){
            $RET = ['data'=>$row, 'event'=>'onCreated'];
        }else{
            $RET = ['error'=>'Ошибка'];
        }  
    }else{ //Редактирование
        $q = $DB->prepare("UPDATE communities set name = :name, description = :description where com_id = :com_id");
        BindExecute($q, [
            ['name', $R['data']['name'], PDO::PARAM_STR],
            ['description', $R['data']['description'], PDO::PARAM_STR],
            ['com_id', $R['data']['com_id'], PDO::PARAM_INT]
        ], true);
    
        //Установка списка пользователей:
        if(isset($R['users'])){
            ChangeTablePart('memberships', 'users', 'usr_id', 'com_id', $R['data']['com_id'], $R['users']);
        }
        //Установка списка групп:
        if(isset($R['groups'])){
            ChangeTablePart('groups_default', 'groups', 'gr_id', 'com_id', $R['data']['com_id'], $R['groups']);
        }

        $q2 = $DB->prepare("SELECT communities.*, (SELECT count(*) from memberships where memberships.com_id = communities.com_id) as \"count_users\",
            (SELECT count(*) from groups_default where groups_default.com_id = communities.com_id) as \"count_groups\" 
            from communities where com_id = :com_id limit 1");
        $q2->bindValue('com_id', $R['data']['com_id'], PDO::PARAM_INT);
        $q2->execute();
        if($row = $q2->fetch(PDO::FETCH_ASSOC)){
            $RET = ['data'=>$row, 'event'=>'onEdited'];
        }else{
            $RET = ['error'=>'Ошибка'];
        }
    }
}

function adm_community_delete(){
    global $R, $DB, $ME, $RET;
    
    $q = $DB->prepare("DELETE FROM communities where com_id = :com_id");
    $q->bindValue('com_id', $R['com_id'], PDO::PARAM_INT);
    $q->execute();
    if(empty($q->errorInfo()[1])){
        $RET = ['data'=> $R['com_id']];
    }else{
        $RET = ['error'=> 'Ошибка удаления'];
    }
}

//Группы
function adm_get_groups(){
    global $R, $DB, $ME, $RET;

    if( isset($R['findText']) ){
        $text = '%'.trim($R['findText']).'%';
        $number = intval($R['findText']);
        if(is_numeric($R['findText']) ){
            $RET = GetAutoList("SELECT groups.*
                from groups where (name LIKE :name or description LIKE :description or gr_id = :gr_id) ", 
            'groups', 'gr_id', [
            ['name', $text, PDO::PARAM_STR],
            ['description', $text, PDO::PARAM_STR],
            ['gr_id', $number, PDO::PARAM_INT]
            ]);
        }else{
            $RET = GetAutoList("SELECT  groups.* 
                from groups where (name LIKE :name or description LIKE :description)  ", 
            'groups', 'gr_id', [
            ['name', $text, PDO::PARAM_STR],
            ['description', $text, PDO::PARAM_STR],
            ]);
        }
    }else{
        $RET = GetAutoList("SELECT groups.* from groups", 'groups', 'gr_id');
    }
}


?>