<?php

//Массив всех карточек тела теста без папок (карточки из теста извлекаются)
function GetExtractedCards($body){
  $ret = [];
  for ($i=0; $i < count($body); $i++) { 
    $card = $body[$i];
    $cardType = mb_strtolower($card['type']);
    if( $cardType != 'folder'){
      $ret[] = $body[$i];
    }else{
      $sBody = $body[$i]['body'];
      for ($j=0; $j < count($sBody); $j++) { 
        $ret[] = $sBody[$j];
      }
    }
  }
  return $ret;
}

function GetCardNoAnswer($card)//Получение карточки вопроса без правильного ответа
{
  $ret = $card;
  switch ($card['type']) {
    case 'Simple':
      $ret['answer'] = "";
      break;
    case 'Choice':
      for ($i=0; $i < count($ret['choices']); $i++) { 
        $ret['choices'][$i]['selected'] = false;
        $ret['answer'] = false;
      } 
      break;
    case 'Orthoepy':
      $ret['word'] =  mb_strtolower($ret['word']);
      $ret['word'] = str_replace('ё', 'е', $ret['word']);
      break;
  }
  return $ret;
}

//Проверка ответа
function checkCard($origin, $draft)
{
  $res = $draft;
  switch ($origin['type']) {
    case 'Simple':
      if ($origin['answer'] == $draft['answer']) {
        $res['score'] = $origin['score'];
      } else {
        $res['score'] = 0;
      }
      break;
    case 'Choice':
      $isCorrect = true;
      if($origin['isMultiple']){
        for ($i=0; $i < count($draft['choices']); $i++) { 
          for ($j=0; $j < count($origin['choices']); $j++) { 
            if($draft['choices'][$i]['id'] == $origin['choices'][$j]['id']){
              //Правильная отметка
              if($draft['choices'][$i]['selected'] == true 
                && $origin['choices'][$i]['selected'] == true ){
                $res['choices'][$i]['state'] = 'correct';
              }
              //Неверная отметка
              if($draft['choices'][$i]['selected'] == true && 
                $origin['choices'][$i]['selected'] == false){
                  $res['choices'][$i]['state'] = 'incorrect' ;
                  $isCorrect = false;
              }
              //Не отмечен правильный вариант
              if($draft['choices'][$i]['selected'] == false &&
                $origin['choices'][$i]['selected'] == true){
                  $res['choices'][$i]['state'] = 'not_marked';
                  $isCorrect = false;
              }
            }
          }
        }
      }else{
        $isCorrect = false;
        for ($i=0; $i < count($draft['choices']); $i++) {
          if($draft['choices'][$i]['id'] == $origin['answer'] && $origin['answer'] == $draft['answer'] ){
            $res['choices'][$i]['state'] = 'correct';
            $isCorrect = true;
          }else if($draft['choices'][$i]['id'] == $draft['answer'] && $draft['answer'] != $origin['answer'] ){
            $res['choices'][$i]['state'] = 'incorrect';
          }else if($draft['choices'][$i]['id'] == $origin['answer'] && $origin['answer'] != $draft['answer'] ){
            $res['choices'][$i]['state'] = 'not_marked';
          }
        }
      }
      if($isCorrect) $res['score'] = $origin['score'];
      else $res['score'] = 0;
      break;
      case 'Orthoepy':
        $res['word'];
        $arWord = preg_split('//u', $origin['word'], -1, PREG_SPLIT_NO_EMPTY);
        $glas = ['А', 'О', 'Э', 'Е', 'И', 'Ы', 'У', 'Ё', 'Ю', 'Я'];
        $correctWord = '';
        for ($i=0; $i < count($arWord); $i++) {
          $isyy = false;
          for ($j=0; $j < count($glas); $j++) { 
            if($arWord[$i] == $glas[$j]){
              $isyy = true; break;
            }
          }
          if($isyy == false){
            $arWord[$i] = mb_strtolower($arWord[$i]);
          }
          $correctWord.=$arWord[$i];
        }
        $a = str_replace('Е', 'ё', $draft['word']);
        $b = str_replace(['Ё', 'Е'], 'ё', $correctWord );
        if( strcmp($a, $b) == 0 ){
          $res['score'] = $origin['score'];
          $res['word'] = $origin['word'];
        }
        else $res['score'] = 0;
        $res['correct'] = $correctWord; //$correctWord;
        break;
    default:
      # code...
      break;
  }
  return $res;
}

//Перенос ответов с одних карточек в другие. Возвращает карточки с перенесенными ответами.
function TransferAnswers($fromCards, $inCards){
  $toCards = $inCards;
  for ($i=0; $i < count($toCards); $i++) { 
    for ($j=0; $j < count($fromCards); $j++) { 
      if($toCards[$i]['id'] == $fromCards[$j]['id']){
        switch ($toCards[$i]['type']) {
          case 'Simple':
            $toCards[$i]['answer'] = $fromCards[$i]['answer'];
            break;
          case 'Orthoepy':
            $toCards[$i]['word'] = $fromCards[$i]['word'];
            break;
          case 'Choice':
            if( $toCards[$i]['isMultiple'] ){
              $toCards[$i]['choices'] = $fromCards[$i]['choices'];
            }else{
              $toCards[$i]['answer'] = $fromCards[$i]['answer'];
            }
            break;
          default:
            break;
        }
      }
    }
  }
  return $toCards;
}

function test_send()
{
  global $R, $DB, $ME, $RET;
  $test = $R['test'];
  $cards = $R['test']['body'];
  if(isset($test['test_id'])){
    $qt = $DB->prepare('SELECT * from tests where test_id = :test_id limit 1');
    $qt->bindValue('test_id', $test['test_id'], PDO::PARAM_INT);
  }else if(isset($test['gt_id'])){
    $qt = $DB->prepare('SELECT tests.* from gtests inner join tests on gtests.ref_test_id = tests.test_id where gtests.gt_id = :gt_id limit 1');
    $qt->bindValue('gt_id', $test['gt_id'], PDO::PARAM_INT);
  }else if(isset($test['res_id'])){
    $qt = $DB->prepare('SELECT tests.* from gtests inner join tests on gtests.ref_test_id = tests.test_id 
      inner join results on results.ref_test_id = gtests.gt_id
      where results.res_id = :res_id and results.usr_id = :usr_id limit 1');
    $qt->bindValue('res_id', $test['res_id'], PDO::PARAM_INT);
    $qt->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
    $isResult = true;
  }
  
  $qt->execute();
  if ($origin = $qt->fetch(PDO::FETCH_ASSOC)) {
    $originCards = GetExtractedCards(json_decode($origin['body'], true));
    $max_score = 0;
    $score = 0;
    for ($i = 0; $i < count($cards); $i++) {
      for ($j = 0; $j < count($originCards); $j++) {
        if ($cards[$i]['id'] == $originCards[$j]['id']) {
          $cards[$i] = checkCard($originCards[$j], $cards[$i]);
          $max_score += $originCards[$j]['score'];
          $score += $cards[$i]['score'];
        }
      }
    }
    if($isResult){
      $curTime = date('Y-m-d H:i:s', time());
      $qs = $DB->prepare("UPDATE results set score = :score, max_score = :max_score, body = :body, ready = :ready, time_end = :time_end where res_id = :res_id");
      $qs->bindValue('score', $score, PDO::PARAM_INT);
      $qs->bindValue('max_score', $max_score, PDO::PARAM_INT);
      $qs->bindValue('body', json_encode($cards, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
      $qs->bindValue('ready', 1, PDO::PARAM_INT);
      $qs->bindValue('time_end', $curTime, PDO::PARAM_STR);
      $qs->bindValue('res_id', $test['res_id'], PDO::PARAM_INT);
      $qs->execute();
      if( empty($qs->errorInfo()[1]) ){
        $RET = ['data' => $test['res_id']];
      }else{
        $RET = ['error' => $qs->errorInfo()[2]];
      }
    }else{
      $qs = $DB->prepare("INSERT INTO results (name, description, usr_id_auditor, ref_test_id, usr_id, score, max_score, body, gr_id) 
        VALUES (:name, :description, :usr_id_auditor, :ref_test_id, :usr_id, :score, :max_score, :body, :gr_id)");
      $qs->bindValue('name', $origin['name'], PDO::PARAM_STR);
      $qs->bindValue('description', $origin['description'], PDO::PARAM_STR);
      $qs->bindValue('usr_id_auditor', $origin['usr_id'], PDO::PARAM_INT);
      $qs->bindValue('ref_test_id', isset($test['gr_id']) ? $test['gt_id'] : $origin['test_id'], PDO::PARAM_INT);
      $qs->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
      $qs->bindValue('score', $score, PDO::PARAM_INT);
      $qs->bindValue('max_score', $max_score, PDO::PARAM_INT);
      $qs->bindValue('body', json_encode($cards, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
      $qs->bindValue('gr_id', isset($test['gr_id']) ? $test['gr_id'] : null, PDO::PARAM_INT);
      $qs->execute();
      if (empty($qs->errorInfo()[1])) {
        $qr = $DB->prepare("SELECT res_id FROM results where usr_id = :usr_id order by res_id desc limit 1");
        $qr->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
        $qr->execute();
        $row = $qr->fetch(PDO::FETCH_ASSOC);
        $RET = ['data' => $row['res_id']];
      } else {
        $RET = ['error' => $qs->errorInfo()[2]];
      }
    }
    
  }else{
    $RET = ['error' => 'Тест был удален', 'info'=>$qt->errorInfo()[2]];
  }
}

function test_save()
{
  global $R, $DB, $ME, $RET;
  $name = $R['test']['name'];
  $description = $R['test']['description'];
  $body = $R['test']['body'];
  $img_id = (int)($R['test']['ico']);
  if ($R['test']['test_id'] == 'new') {
    $q = $DB->prepare("INSERT INTO tests (usr_id, name, description, body, ico) VALUES(:usr_id, :name, :description, :body, :ico)");
  } else {
    $q = $DB->prepare("UPDATE tests SET usr_id = :usr_id, name = :name, description = :description, body = :body, ico = :ico where test_id = :test_id");
    $q->bindValue("test_id", $R['test']['test_id'], PDO::PARAM_INT);
  }
  $q->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
  $q->bindValue("ico", $img_id, PDO::PARAM_INT);
  $q->bindValue("name", $name, PDO::PARAM_STR);
  $q->bindValue('description', $description, PDO::PARAM_STR);
  $q->bindValue('body',  json_encode($body, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
  $q->execute();
  if (empty($q->errorInfo()[1])) {
    if ($R['test']['test_id'] == 'new') {
      $q2 = $DB->prepare("SELECT test_id from tests where usr_id = :usr_id order by test_id desc limit 1");
      $q2->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
      $q2->execute();
      $test_id = $q2->fetch(PDO::FETCH_ASSOC)['test_id'];
      $RET = ['data' => $test_id];
    } else {
      $RET = ['data' => $R['test']['test_id']];
    }
  } else {
    $RET = ['error' => $q->errorInfo()[1]];
  }
}

function get_test_editor()
{
  global $R, $DB, $ME, $RET;
  $test_id = $R['test_id'];
  $q = $DB->prepare("SELECT tests.*, images.url \"ico_url\" from tests left join images on tests.ico = images.img_id where tests.test_id = :test_id and tests.usr_id = :usr_id limit 1");
  $q->bindValue(':test_id', $test_id, PDO::PARAM_INT);
  $q->bindValue(':usr_id', $ME['usr_id'], PDO::PARAM_INT);
  $q->execute();
  if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $row['body'] = json_decode($row['body']);
    //Установление иконки теста
    if(strlen($row['ico_url']) > 0){
      $row['ico_url'] = LINK.'/uploaded/'.$row['ico_url']; 
    }else{
      $row['ico_url'] = LINK.'/img/test_default.jpg'; 
    }
    
    $RET = ['data' => $row];
  } else {
    $RET = ['error' => 'Тест не найден', 'info'=>$q->errorinfo()[2]];
  }
}

//Генерирует тело теста исхордя из параметров
function GenerateTestBody($body, $settings = []){
  $cards = json_decode($body, true);
  $ret = [];
  for ($i = 0; $i < count($cards); $i++) {
    if($cards[$i]['type'] != 'Folder'){//Обычная карточка:
      $ret[] = GetCardNoAnswer($cards[$i]);
    }else{//Папка:
      $props = $cards[$i]['props'];
      $sBody = $cards[$i]['body'];  
      $isShuffle = false;
      $select = count($sBody);
      //Установка параметров:
      if(isset($props)){
        $isShuffle = boolval($props['isShuffle']);
        $select = intval($props['select']);
        $select = $select > count($sBody) ? count($sBody) : $select;
        $select = $select < 0 ? 0 : $select;
      }
      //Добавление карточек:
      if($isShuffle){
        shuffle($sBody);
        for ($j=0; $j < $select; $j++) { 
          $ret[] = GetCardNoAnswer($sBody[$j]);
        }
      }else{
        for ($j=0; $j < count($sBody); $j++) {
          $ret[] = GetCardNoAnswer($sBody[$j]);
        }
      }
    }
  }
  return $ret;
}


function GTestResult($gt_id){//Получение/Создание решения теста группы. Возвращает TestResult
  global $DB, $ME;
  $RET = false;
  //Поиск решения теста группы:
  $q = $DB->prepare("SELECT results.*, images.url \"ico_url\", requests.req_id, tests.usr_id, gtests.date_start, gtests.date_end, gtests.duration_time,
      (Select count(*) from results where results.gr_id = gtests.gr_id and results.ref_test_id = gtests.gt_id and results.usr_id = :usr_id and results.ready = true) \"my_attempts\"
      from results 
      inner join gtests on gtests.gt_id = results.ref_test_id
      inner join tests on gtests.ref_test_id = tests.test_id 
      left join requests on (gtests.gr_id = requests.gr_id and requests.usr_id = :usr_id and requests.accepted = true) 
      left join images on images.img_id = tests.ico
      where results.gr_id is not null and results.ref_test_id = :gt_id and results.ready = false");
  $q->bindValue('gt_id', $gt_id, PDO::PARAM_INT);
  $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
  $q->execute();
  if($rrow = $q->fetch(PDO::FETCH_ASSOC)){
    $RET = $rrow;
  }else{//Создание решения теста группы:
    $isOk = true;
    $qc = $DB->prepare("SELECT gtests.attempts, 
      (SELECT count(*) from results where results.ref_test_id = gtests.gt_id and results.usr_id = :usr_id and results.gr_id is not null) \"my_attempts\" from gtests 
      where gtests.gt_id = :gt_id limit 1");
    $qc->bindValue('gt_id', $gt_id, PDO::PARAM_INT);
    $qc->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
    $qc->execute();
    if($row = $qc->fetch(PDO::FETCH_ASSOC)){
      if( isset($row['attempts']) && ($row['attempts'] - $row['my_attempts']) <=0 ){
        $isOk = false;
        $errorName = "NoAttempts";
      }
    }
    if($isOk){
      $q = $DB->prepare("SELECT gtests.*, images.url \"ico_url\", requests.req_id, tests.name, tests.description, tests.body, tests.ico, tests.usr_id,
      (Select count(*) from results where results.gr_id = gtests.gr_id and results.ref_test_id = gtests.gt_id and results.usr_id = :usr_id and results.ready = true) \"my_attempts\"
      from gtests inner join tests on gtests.ref_test_id = tests.test_id 
      left join requests on (gtests.gr_id = requests.gr_id and requests.usr_id = :usr_id and requests.accepted = true) 
      left join images on images.img_id = tests.ico
      where gtests.gt_id = :gt_id limit 1");
      $q->bindValue('gt_id', $gt_id, PDO::PARAM_INT);
      $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
      $q->execute();
      if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $cards = GenerateTestBody($row['body']);
        $qs = $DB->prepare("INSERT INTO results (name, description, usr_id_auditor, ref_test_id, usr_id, score, max_score, body, gr_id, time_end, ready) 
        VALUES (:name, :description, :usr_id_auditor, :ref_test_id, :usr_id, :score, :max_score, :body, :gr_id, :time_end, :ready)");
        $qs->bindValue('name', $row['name'], PDO::PARAM_STR);
        $qs->bindValue('description', $row['description'], PDO::PARAM_STR);
        $qs->bindValue('usr_id_auditor', $row['usr_id'], PDO::PARAM_INT);
        $qs->bindValue('ref_test_id', $row['gt_id'], PDO::PARAM_INT);
        $qs->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
        $qs->bindValue('score', 0, PDO::PARAM_INT);
        $qs->bindValue('max_score', 0, PDO::PARAM_INT);
        $qs->bindValue('body', json_encode($cards, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR);
        $qs->bindValue('gr_id', $row['gr_id'], PDO::PARAM_INT);
        $qs->bindValue('ready', 0, PDO::PARAM_INT);
        $qs->bindValue('time_end', NULL, PDO::PARAM_NULL);
        $qs->execute();
        if (empty($qs->errorInfo()[1])) {
          $qr = $DB->prepare("SELECT res_id FROM results where usr_id = :usr_id and gr_id = :gr_id order by res_id desc limit 1");
          $qr->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
          $qr->bindValue("gr_id", $row['gr_id'], PDO::PARAM_INT);
          $qr->execute();
          $res_id = $qr->fetch(PDO::FETCH_ASSOC)['res_id'];
          //Получение созданного решения теста группы:
          $q = $DB->prepare("SELECT results.*, images.url \"ico_url\", requests.req_id, tests.usr_id, gtests.date_start, gtests.date_end, gtests.duration_time,
            (Select count(*) from results where results.gr_id = gtests.gr_id and results.ref_test_id = gtests.gt_id and results.usr_id = :usr_id and results.ready = true) \"my_attempts\" from results 
            inner join gtests on gtests.gt_id = results.ref_test_id
            inner join tests on gtests.ref_test_id = tests.test_id 
            left join requests on (gtests.gr_id = requests.gr_id and requests.usr_id = :usr_id and requests.accepted = true) 
            left join images on images.img_id = tests.ico
            where results.gr_id is not null and results.res_id = :res_id and results.ready = false");
          $q->bindValue('res_id', $res_id, PDO::PARAM_INT);
          $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
          $q->execute();
          if($rrow = $q->fetch(PDO::FETCH_ASSOC)){
            $RET = $rrow;
          }
        }else{
          echo $qs->errorInfo()[2];
        }
      }
    }else{
      $RET = $row;
    }
    
  }
  return $RET;
}


function save_gtest_result(){
  global $R, $DB, $ME, $RET;

  $newCards = $R['test']['body'];
  $res_id = $R['test']['res_id'];
  $q = $DB->prepare("SELECT body from results where res_id = :res_id and usr_id = :usr_id limit 1");
  BindExecute($q, [['res_id', $res_id, PDO::PARAM_INT], ['usr_id', $ME['usr_id'], PDO::PARAM_INT]]);
  if($row = $q->fetch(PDO::FETCH_ASSOC)){
    $cards = json_decode($row['body'], true);
    $sCards = TransferAnswers($newCards, $cards); //Перенос ответов
    $q2 = $DB->prepare("UPDATE results set body = :body where res_id = :res_id and usr_id = :usr_id");
    BindExecute($q2, [
      ['body', json_encode( $sCards, JSON_UNESCAPED_UNICODE), PDO::PARAM_STR], 
      ['res_id', $res_id, PDO::PARAM_INT], 
      ['usr_id', $ME['usr_id'], PDO::PARAM_INT]]);
    if(empty($q2->errorInfo()[1]) ){
      $RET = ['data'=>$res_id];
    }else{
      $RET = ['error'=>$q2->errorInfo()[2]];
    }
  }else{
    $RET = ['error'=>'Сохранение невозможно. Решение не найдено'];
  }

}

function get_test_basic(){
  global $R, $DB, $ME, $RET;
  $errorName='NoTest';
  $test_id = $R['test_id'];
  $gtest_id = $R['gtest_id'];

  if (isset($test_id)){ //Прямой тест
    $q = $DB->prepare("SELECT tests.*, images.url \"ico_url\" from tests left join images on images.img_id = tests.ico where tests.test_id = :test_id limit 1");
    $q->bindValue(':test_id', $test_id, PDO::PARAM_INT);
    $q->execute();
  }else if (isset($gtest_id)) { //Тест группы
    $rrow = GTestResult($gtest_id);
  }
  if ($row = $rrow ? $rrow : $q->fetch(PDO::FETCH_ASSOC)) {
    $isSend = false;
    //Если пользователь является участником группы:
    if ($row['req_id'] != '' || isset($test_id)){
      $isSend = true;
    //Если администратор группы:
    }else{
      $q2 = $DB->prepare("SELECT usr_id from groups where gr_id = :gr_id limit 1");
      $q2->bindValue("gr_id", $row['gr_id'], PDO::PARAM_INT);
      $q2->execute();
      if($rg = $q2->fetch(PDO::FETCH_ASSOC)){
        if($rg['usr_id'] == $ME['usr_id'])
        $isSend = true;
      }
    }
    if( isset($row['attempts']) && ($row['attempts'] - $row['my_attempts']) <=0 ){
      $isSend = false;
      $errorName = "NoAttempts";
    }
    //Проверка на доступность во временой период
    $date_start = isset($row['date_start']) ? DateTime::createFromFormat('Y-m-d H:i:s', $row['date_start'])->getTimestamp() : false;
    $date_end = isset($row['date_end']) ? DateTime::createFromFormat('Y-m-d H:i:s', $row['date_end'])->getTimestamp() : false;
    $date_cur = time();
    if($date_start && $date_cur < $date_start){
      $isSend = false;
      $errorName = "NotStarted";
    }
    if($date_end && $date_cur > $date_end){
      $isSend = false;
      $errorName = "Closed";
    }

    if($isSend){

      $cards = json_decode($row['body'], true);
      if(isset($test_id)){
        $cards = GenerateTestBody($row['body']);
      }
      /////
      if($row['gr_id'] > 0 ){
        $qg = $DB->prepare("SELECT groups.name as \"group_name\", groups.gr_id, groups.description,
          users.first_name, users.last_name, users.avatar as \"user_avatar\" from groups
          left join users on users.usr_id = groups.usr_id where gr_id = :gr_id limit 1");
        $qg->bindValue('gr_id', $row['gr_id'], PDO::PARAM_INT);
        $qg->execute();
        if($group = $qg->fetch(PDO::FETCH_ASSOC)){
          $row['group'] = $group;
        }
      }else{
        $qa = $DB->prepare("SELECT first_name, last_name, avatar, usr_id from users where usr_id = :usr_id limit 1");
        $qa->bindValue('usr_id', $row['usr_id'], PDO::PARAM_INT);
        $qa->execute();
        if($autor = $qa->fetch(PDO::FETCH_ASSOC)){
          $row['autor_test'] = $autor;
        }
      }
      /////
      $row['body'] = $cards;
      //Установление иконки теста:
      $row['ico_url'] = getImgURL($row['ico_url'], 'test');
      $RET = ['data' => $row];
    }else{
      switch ($errorName) {
        case 'NoAttempts': $RET = ['error' => 'Нет попыток', 'info'=> $test_id]; break;
        case 'NotStarted' : $RET = ['error' => 'Тестирование еще не началось', 'info'=> $test_id]; break;
        case 'Closed' : $RET = ['error' => 'Тестирование уже завершено', 'info'=> [$date_cur, $date_start, $date_end, $row['date_end']]]; break;
        default:
          $RET = ['error' => 'Тест не найден', 'info'=> $test_id];
          break;
      }
    }
  } else {
    $RET = ['error' => 'Тест не найден', 'info' => $q->errorInfo()[2]];
  }
}

//Получение моих решений
function get_test_result()
{
  global $R, $DB, $ME, $RET;
  $res_id = $R['res_id'];
  $q = $DB->prepare("SELECT results.*, images.url \"ico_url\" from results 
    left join tests on tests.test_id = results.ref_test_id
    left join images on images.img_id = tests.ico
    where results.res_id = :res_id limit 1");
  $q->bindValue(':res_id', $res_id, PDO::PARAM_INT);
  $q->execute();
  if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    $cards = json_decode($row['body'], true);
    $row['body'] = $cards;
    if($row['gr_id'] > 0 ){
      $qg = $DB->prepare("SELECT groups.name as \"group_name\", groups.gr_id, groups.description,
        users.first_name, users.last_name, users.avatar as \"user_avatar\" from groups
        left join users on users.usr_id = groups.usr_id where gr_id = :gr_id limit 1");
      $qg->bindValue('gr_id', $row['gr_id'], PDO::PARAM_INT);
      $qg->execute();
      if($group = $qg->fetch(PDO::FETCH_ASSOC)){
        $row['group'] = $group;
      }
    }else{
      $qa = $DB->prepare("SELECT first_name, last_name, avatar, usr_id from users where usr_id = :usr_id limit 1");
      $qa->bindValue('usr_id', $row['usr_id_auditor'], PDO::PARAM_INT);
      $qa->execute();
      if($autor = $qa->fetch(PDO::FETCH_ASSOC)){
        $row['autor_test'] = $autor;
      }
    }
    //Установление иконки теста:
    if(strlen($row['ico_url']) > 0){
      $row['ico_url'] = LINK.'/uploaded/'.$row['ico_url']; }else{
      $row['ico_url'] = LINK.'/img/test_default.jpg'; 
    }
    $qu = $DB->prepare("SELECT first_name, last_name, usr_id, avatar from users where usr_id = :usr_id limit 1");
    $qu->bindValue('usr_id', $row['usr_id'], PDO::PARAM_INT);
    $qu->execute();
    if($user = $qu->fetch(PDO::FETCH_ASSOC)){
      $row['user'] = $user; 
    }
    $RET = ['data' => $row];
  } else {
    $RET = ['error' => 'Решение не найдено'];
  }
}

//Получение моих тестов
function get_my_tests()
{
  global $R, $DB, $ME, $RET;

  $count = empty($R['count']) ? 20 : $R['count'];
  $count = $count > 100 ? 100 : $count;
  $sign = empty($R['desc']) ? '>' : '<';
  $insertDesc = empty($R['desc']) ? '' : 'desc';

  if (empty($R['point'])) {
    $q = $DB->prepare("SELECT tests.*, images.url \"ico_url\", users.first_name, users.last_name from tests left join users on tests.usr_id = users.usr_id left join images on tests.ico = images.img_id where tests.usr_id = :usr_id
      order by tests.test_id $insertDesc limit :count");
  } else {
    $q = $DB->prepare("SELECT tests.*, images.url \"ico_url\", users.first_name, users.last_name from tests left join users on tests.usr_id = users.usr_id left join images on tests.ico = images.img_id where tests.usr_id = :usr_id and tests.test_id $sign :point 
      order by tests.test_id $insertDesc limit :count");
    $q->bindValue('point', $R['point'], PDO::PARAM_INT);
  }
  $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
  $q->bindValue('count', $count, PDO::PARAM_INT);
  $q->execute();
  if (empty($q->errorInfo()[1])) {
    $rows = $q->fetchALL(PDO::FETCH_ASSOC);
    for($i = 0; $i< count($rows); $i++){
      $rows[$i]['date_created'] = NormalTime( $rows[$i]['date_created']);
      if(strlen($rows[$i]['ico_url']) > 0){
        $rows[$i]['ico_url'] = LINK.'/uploaded/'.$rows[$i]['ico_url']; }else{
        $rows[$i]['ico_url'] = LINK.'/img/test_default.jpg'; 
      }
    }
    $RET = ['data' => $rows];
  } else {
    $RET = ['error' => $q->errorInfo()[2]];
  }
}

//Получение моих результатов
function get_my_results(){
  global $R, $DB, $ME, $RET;

  $count = empty($R['count']) ? 20 : $R['count'];
  $count = $count > 100 ? 100 : $count;
  $sign = empty($R['desc']) ? '>' : '<';
  $insertDesc = empty($R['desc']) ? '' : 'desc';

  if (empty($R['point'])) {
    $q = $DB->prepare("SELECT results.*, images.url \"ico_url\", users.first_name, users.last_name from results left join users on results.usr_id = users.usr_id 
      left join tests on tests.test_id = results.ref_test_id
      left join images on tests.ico = images.img_id
      where results.usr_id = :usr_id
      order by results.res_id $insertDesc limit :count");
  } else {
    $q = $DB->prepare("SELECT results.*, images.url \"ico_url\", users.first_name, users.last_name from results left join users on results.usr_id = users.usr_id 
      left join tests on tests.test_id = results.ref_test_id 
      left join images on tests.ico = images.img_id 
      where results.usr_id = :usr_id and results.res_id $sign :point 
      order by results.res_id $insertDesc limit :count");
    $q->bindValue('point', $R['point'], PDO::PARAM_INT);
  }
  $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
  $q->bindValue('count', $count, PDO::PARAM_INT);
  $q->execute();
  if (empty($q->errorInfo()[1])) {
    $rows = $q->fetchALL(PDO::FETCH_ASSOC);
    for($i = 0; $i< count($rows); $i++){
      $rows[$i]['date_created'] = NormalTime( $rows[$i]['date_created']);
      //Установление изображения:
      if(strlen($rows[$i]['ico_url']) > 0){
        $rows[$i]['ico_url'] = LINK.'/uploaded/'.$rows[$i]['ico_url']; }else{
        $rows[$i]['ico_url'] = LINK.'/img/test_default.jpg'; 
      }//
    }
    $RET = ['data' => $rows];
  } else {
    $RET = ['error' => $q->errorInfo()[2]];
  }
}


function share_tests(){
  global $R, $DB, $ME, $RET;
  $groups = $R['groups'];
  $ids = isset($groups) ? $groups : [];
  $sttg = $R['settings'];
  if(count($ids) > 0){
    $in  = getBindLine(':gr_id', count($ids));
    $q = $DB->prepare("SELECT gr_id FROM groups WHERE gr_id IN ($in) and usr_id = :usr_id limit :count");
    $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
    $q->bindValue('count', count($ids), PDO::PARAM_INT);
    for ($i=0; $i < count($ids); $i++) { 
      $q->bindValue('gr_id_'.$i, $ids[$i], PDO::PARAM_INT);
    }
    $q->execute();
    $nids = [];
    while($row = $q->fetch(PDO::FETCH_ASSOC)){
      $nids[] = $row['gr_id'];
    }
    $others = [];//Названия других параметров
    $oValues = [];//Значения других параметров
    if(isset($R['comment'])){
      $others[] = 'comment';
      $oValues['comment'] = $R['comment'];
    }
    //Проверка на наличия параметров
    if(isset($sttg)){
      if(isset($sttg['is_limit_attempts'])){
        $others[] = 'attempts';
        $oValues['attempts'] = NULL;
        if($sttg['is_limit_attempts'] == true){
          if(isset($sttg['limit_attempts'])){
            $oValues['attempts'] = $sttg['limit_attempts'] >= 0 ? $sttg['limit_attempts'] : 0;
          }
        }
      }
      if(isset($sttg['is_date_start'])){
        $others[] = 'date_start';
        $oValues['date_start'] = NULL;
        if($sttg['is_date_start'] == true){
          if(isset($sttg['date_start'])){
            $oValues['date_start'] = $sttg['date_start'];
          }
        }
      }
      if(isset($sttg['is_date_end'])){
        $others[] = 'date_end';
        $oValues['date_end'] = NULL;
        if($sttg['is_date_end'] == true){
          if(isset($sttg['date_end'])){
            $oValues['date_end'] = $sttg['date_end'];
          }
        }
      }
      if(isset($sttg['is_duration_time'])){
        $others[] = 'duration_time';
        $oValues['duration_time'] = NULL;
        if($sttg['is_duration_time'] == true){
          if(isset($sttg['duration_time'])){
            $oValues['duration_time'] = $sttg['duration_time'] > 0 ? $sttg['duration_time'] : 1;
          }
        }
      }
    }
    //end
    $insertSettings = ''; //, comment, attempts
    $insertBindSettings = ''; //, :comment, :attempts
    for ($i=0; $i < count($others) ; $i++) { 
      $insertSettings.=', '.$others[$i];
      $insertBindSettings.=', :'.$others[$i];
    }

    $query = "INSERT into gtests (gr_id, ref_test_id $insertSettings) VALUES ";
    for ($i=0; $i < count($nids); $i++) { 
      $query.="( :gr_id_$i, :ref_test_id $insertBindSettings)".($i < count($nids)-1 ? ', ' : '');
    }
    $qr=$DB->prepare($query);
    $qr->bindValue('ref_test_id', $R['test_id'], PDO::PARAM_INT);
    for ($i=0; $i < count($nids); $i++){ 
      $qr->bindValue('gr_id_'.$i, $nids[$i], PDO::PARAM_INT);
    }
    //Бинд соответствующих значений
    for ($i=0; $i < count($others) ; $i++) { 
      switch ($others[$i]) {
        case 'comment': 
          $qr->bindValue('comment', $oValues['comment'], PDO::PARAM_STR); 
          break;
        case 'attempts':
          $qr->bindValue('attempts',
            $oValues['attempts'],
            $oValues['attempts'] == NULL ? PDO::PARAM_NULL : PDO::PARAM_INT);
          break;
        case 'date_start': $qr->bindValue('date_start', dateFormatJStoDB($oValues['date_start']), 
            $oValues['date_start'] == NULL ? PDO::PARAM_NULL : PDO::PARAM_STR);
          break;
        case 'date_end': $qr->bindValue('date_end', dateFormatJStoDB($oValues['date_end']), 
            $oValues['date_end'] == NULL ? PDO::PARAM_NULL : PDO::PARAM_STR);
          break;
        case 'duration_time': $qr->bindValue('duration_time', $oValues['duration_time'], 
            $oValues['duration_time'] == NULL ? PDO::PARAM_NULL : PDO::PARAM_INT);
          break;
        default:
          break;
      }
    }//end
    $qr->execute();
    if( empty($qr->errorInfo()[1]) ){
      $RET = ['data'=>'Все ок'];
    }else{
      $RET = ['error'=>$qr->errorInfo()[2]];
    }
    //$qt = $DB->prepare($query);
  }
}

function get_test_info(){
  global $R, $DB, $ME, $RET;

  $type = isset($R['gtest_id']) ? 'gtest' : (isset($R['test_id']) ? 'test' : 'none'); // gtest / test / none
  $id = isset($R['gtest_id']) ? $R['gtest_id'] : $R['test_id'];
  switch ($type) {
    case 'gtest':
      $q = $DB->prepare("SELECT gtests.*, images.url \"ico_url\", results.res_id, requests.req_id, tests.name, tests.description, tests.ico, tests.usr_id,
      (Select count(*) from results where results.gr_id = gtests.gr_id and results.ref_test_id = gtests.gt_id and results.usr_id = :usr_id and results.ready = true) \"my_attempts\"
      from gtests inner join tests on gtests.ref_test_id = tests.test_id 
      left join results on (results.ref_test_id = gtests.gt_id and results.ready = false)
      left join requests on (gtests.gr_id = requests.gr_id and requests.usr_id = :usr_id and requests.accepted = true) 
      left join images on images.img_id = tests.ico
      where gtests.gt_id = :gt_id limit 1");
      BindExecute($q, [
        ['gt_id', $id, PDO::PARAM_INT], 
        ['usr_id', $ME['usr_id'], PDO::PARAM_INT]]
      );
      if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $row['ico_url'] = getImgURL($row['ico_url']);
      }
      $qg = $DB->prepare("SELECT groups.name as \"group_name\", groups.gr_id, groups.description,
          users.first_name, users.last_name, users.avatar as \"user_avatar\" from groups
          left join users on users.usr_id = groups.usr_id where gr_id = :gr_id limit 1");
        $qg->bindValue('gr_id', $row['gr_id'], PDO::PARAM_INT);
        $qg->execute();
        if($group = $qg->fetch(PDO::FETCH_ASSOC)){
          $row['group'] = $group;
        }
      $RET = ['data'=> $row, 'inf'=> $q->errorInfo()[1]];
      break;
    case 'test':
      $q = $DB->prepare("SELECT tests.*, images.url \"ico_url\" from tests left join images on images.img_id = tests.ico where tests.test_id = :test_id limit 1");
      $q->bindValue('test_id', $id, PDO::PARAM_INT);
      $q->execute();
      if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $row['ico_url'] = getImgURL($row['ico_url']);
      }
      $qa = $DB->prepare("SELECT first_name, last_name, avatar, usr_id from users where usr_id = :usr_id limit 1");
      $qa->bindValue('usr_id', $row['usr_id'], PDO::PARAM_INT);
      $qa->execute();
      if($autor = $qa->fetch(PDO::FETCH_ASSOC)){
        $row['autor_test'] = $autor;
      }
      $RET = ['data'=> $row, 'inf'=> $q->errorInfo()[1]];
      break;
    default:
      # code...
      break;
  }
  
}

function delete_test(){
  global $R, $DB, $ME, $RET;

  $q = $DB->prepare("SELECT usr_id from tests where test_id = :test_id limit 1");
  $q->bindValue('test_id', $R['test_id'], PDO::PARAM_INT);
  $q->execute();
  if($row = $q->fetch(PDO::FETCH_ASSOC)){
    if($row['usr_id'] == $ME['usr_id']){
      $qd = $DB->prepare("DELETE FROM tests where test_id = :test_id");
      $qd->bindValue('test_id', $R['test_id'], PDO::PARAM_INT);
      $qd->execute();
      if( empty($qd->errorInfo()[1]) ){
        $RET = ['data'=>'Тест удален успешно'];
      }else{
        $RET = ['error'=> $qd->errorInfo()[2]];
      }
    }else{
      $RET = ['error'=> 'Нет доступа'];
    }
  }else{
    $RET = ['error'=> 'Тест не найден'];
  }
}
