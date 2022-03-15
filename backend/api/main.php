

<?php

function NormalTime($dt) { // преобразовываем время в нормальный вид для чтения человеком
    $b = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
    $a = $b->getTimestamp();
    $ndate = date('d.m.Y', $a);
    $ndate_time = date('H:i', $a);
    $ndate_exp = explode('.', $ndate);
    if($ndate_exp[2] != explode('-',$dt)[0])
    $year = substr($ndate_exp[2], 2).'г.';
    else $year = "";
    $nmonth = array(
     1 => 'янв', 2 => 'фев',
     3 => 'мар', 4 => 'апр',
     5 => 'мая', 6 => 'июн',
     7 => 'июл', 8 => 'авг',
     9 => 'сен', 10 => 'окт',
     11 => 'ноя', 12 => 'дек'
    );
    foreach ($nmonth as $key => $value) {
     if($key == intval($ndate_exp[1])) $nmonth_name = $value;
    }
    if($ndate == date('d.m.Y')) return 'сегодня в '.$ndate_time;
    elseif($ndate == date('d.m.Y', strtotime('-1 day'))) return 'вчера в '.$ndate_time;
    else return trim($ndate_exp[0].' '.$nmonth_name.' '.$ndate_time.' '.$year);
}

function getBindLine($name, $count){
    $ret = "";
    for ($i=0; $i < $count; $i++) { 
        $ret.=$name.'_'.$i.($i < $count-1 ? ', ':'');
    }
    return $ret;
}

include '../db.php';

include './helpers.php';
include './modules/users.php';
include './modules/tests.php';
include './modules/groups.php';

$R = json_decode(file_get_contents('php://input'), true);
$RET = ['error'=> 'Пусто']; //Ответ сервера
$ME = $R['me'] ? $R['me'] : false; //Пользователь

if($R['q'] != 'login'){
    $qusr = $DB->prepare("SELECT usr_id, mykey from users where usr_id = :usr_id limit 1");
$qusr->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
$qusr->execute();
if($rowusr = $qusr->fetch(PDO::FETCH_ASSOC)){
    if($ME['mykey'] != $rowusr['mykey']){
        echo json_encode($RET);
        exit;
    }
}else{
   $ME = 0;
}
}



switch($R['q']){
    case 'login': login(); break;
    case 'get_user': get_user(); break;
    case 'test_save': test_save(); break;
    case 'test_send': test_send(); break;
    case 'get_test_editor': get_test_editor(); break;
    case 'get_test_basic': get_test_basic(); break;
    case 'get_test_result': get_test_result(); break;
    case 'get_my_tests': get_my_tests(); break;
    case 'get_my_results': get_my_results(); break;
    case 'get_my_groups': get_my_groups(); break;
    case 'create_group': create_group(); break;
    case 'get_group_info': get_group_info(); break;
    case 'switch_joining_grop': switch_joining_grop(); break;
    case 'join_group': join_group(); break;
    case 'get_group_users': get_group_users(); break;
    case 'join_request_action': join_request_action(); break;
    case 'get_group_tests': get_group_tests(); break;
    case 'get_group_results': get_group_results(); break;
    case 'share_tests' : share_tests(); break;
    case 'edit_gtest' : edit_gtest(); break;
    case 'delete_result' : delete_result(); break;
    case 'delete_gtest' : delete_gtest(); break;
    case 'delete_test' : delete_test(); break;
    case 'get_all_groups_tests' : get_all_groups_tests(); break;
    case 'get_images' : get_images(); break;
    case 'delete_image' : delete_image(); break;
    case 'set_group_ico' : set_group_ico(); break;
    case 'get_test_info' : get_test_info(); break;
    case 'save_gtest_result' : save_gtest_result(); break;
    case 'set_group_info' : set_group_info(); break;
    case 'set_close_group' : set_close_group(); break;
    case 'delete_group' : delete_group(); break;
    default:

    break;
    
}




echo json_encode($RET) ?>