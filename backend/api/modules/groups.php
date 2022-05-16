<?php

//Получает уровень доступа 0-нет; 1-Подписчик, 2-Создатель
function isAccessGroup($usr_id, $gr_id)
{
	global $DB;
	$ret = 0;
	$q = $DB->prepare("SELECT groups.usr_id as \"autor_group\", requests.usr_id from groups left join requests on 
	(requests.gr_id = groups.gr_id and requests.usr_id = :usr_id and requests.accepted = true)
	where groups.gr_id = :gr_id limit 1");
	$q->bindValue('usr_id', $usr_id, PDO::PARAM_INT);
	$q->bindValue('gr_id', $gr_id, PDO::PARAM_INT);
	$q->execute();
	if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
		if ($row['autor_group'] == $usr_id) $ret = 2;
		else if ($row['usr_id'] == $usr_id) $ret = 1;
	}
	return $ret;
}



function create_group()
{
	global $R, $DB, $ME, $RET;

	$q = $DB->prepare("INSERT INTO groups (name, description, usr_id) VALUES(:name, :description, :usr_id)");
	$q->bindValue("name", $R['name'], PDO::PARAM_STR);
	$q->bindValue("description", $R['description'], PDO::PARAM_STR);
	$q->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_STR);
	$q->execute();
	if (empty($q->errorInfo()[1])) {
		$q2 = $DB->prepare("SELECT gr_id FROM groups where usr_id = :usr_id order by gr_id desc limit 1");
		$q2->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
		$q2->execute();
		if ($row = $q2->fetch(PDO::FETCH_ASSOC)) {
			$RET = ['data' => $row['gr_id']];
		} else {
			$RET = ['error' => 'Ошибка'];
		}
	} else {
		$RET = ['error' => $q->errorInfo()[2]];
	}
}


function get_group_info()
{
	global $R, $DB, $ME, $RET;

	$q = $DB->prepare("SELECT groups.*, images.url \"ico_url\" FROM groups left join images on images.img_id = groups.img_id where groups.gr_id = :gr_id limit 1");
	$q->bindValue("gr_id", $R['gr_id'], PDO::PARAM_INT);
	$q->execute();
	if ($ret = $q->fetch(PDO::FETCH_ASSOC)) {
		if ($ME['usr_id'] == $ret['usr_id']) {
      //Установление изображения:
      if(strlen($ret['ico_url']) > 0){
        $ret['ico_url'] = LINK.'/uploaded/'.$ret['ico_url']; }else{
        $ret['ico_url'] = LINK.'/img/group_default.jpg'; 
      }//
			$RET = ['data' => $ret];
		} else {
			$q2 = $DB->prepare("SELECT groups.* , images.url \"ico_url\", requests.req_id from groups inner join requests on requests.gr_id = groups.gr_id 
        left join images on images.img_id = groups.img_id 
				where requests.gr_id = :gr_id and requests.usr_id = :usr_id and requests.accepted = true limit 1 ");
			$q2->bindValue("gr_id", $R['gr_id'], PDO::PARAM_INT);
			$q2->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
			$q2->execute();
			if ($ret2 = $q2->fetch(PDO::FETCH_ASSOC)) {
			//Установление изображения:
        if(strlen($ret2['ico_url']) > 0){
          $ret2['ico_url'] = LINK.'/uploaded/'.$ret2['ico_url']; }else{
          $ret2['ico_url'] = LINK.'/img/group_default.jpg'; 
        }//

		if( isset($ret2['assessment']) && strlen($ret2['assessment']) > 0 ){
			if($assess = json_decode($ret2['assessment'])){
				$ret2['assessment'] = $assess;
			}else{
				$ret2['assessment'] = '';
			}
		}
		
        $RET = ['data' => $ret2];
			} else {
				$RET = ['error' => 'Нет доступа'];
			}
		}
	} else {
		$RET = ['error' => 'Ошибка'];
	}
}

function switch_joining_grop()
{
	global $R, $DB, $ME, $RET;
	$R['is_joining'];

	$q = $DB->prepare("SELECT gr_id, usr_id from groups where gr_id = :gr_id limit 1");
	$q->bindValue("gr_id", $R['gr_id'], PDO::PARAM_INT);
	$q->execute();
	if ($group = $q->fetch(PDO::FETCH_ASSOC)) {
		if ($group['usr_id'] == $ME['usr_id']) {
			$join_key = $R['is_joining'] == true ? generate_string(8) : '';
			$q2 = $DB->prepare("UPDATE groups set join_key = :join_key where gr_id = :gr_id");
			$q2->bindValue("gr_id", $group['gr_id'], PDO::PARAM_INT);
			$q2->bindValue('join_key', $join_key, PDO::PARAM_STR);
			$q2->execute();
			if (empty($q2->errorInfo()[1])) {
				$RET = ['data' => $join_key];
			} else {
				$RET = ['error' => 'Ошибка'];
			}
		} else {
			$RET = ['error' => 'Отказано в доступе'];
		}
	} else {
		$RET = ['error' => 'Группа не найдена'];
	}
}


function join_group()
{
	global $R, $DB, $ME, $RET;

	$parts = explode('/', $R['join_code']);
	$gr_id = (int)($parts[0]);
	$join_key = $parts[1];
	$q = $DB->prepare("SELECT gr_id, join_key, count_users, usr_id from groups where gr_id = :gr_id limit 1");
	$q->bindValue("gr_id", $gr_id, PDO::PARAM_INT);
	$q->execute();
	if ($group = $q->fetch(PDO::FETCH_ASSOC)) {
		if (
			strlen($group['join_key']) > 0
			&& $group['join_key'] == $join_key
			&& $group['usr_id'] != $ME['usr_id']
		) {
			$q2 = $DB->prepare("INSERT into requests (usr_id, gr_id, name) VALUES(:usr_id, :gr_id, :name)");
			$q2->bindValue("name", $ME['last_name'] . ' ' . $ME['first_name'], PDO::PARAM_STR);
			$q2->bindValue("usr_id", $ME['usr_id'], PDO::PARAM_INT);
			$q2->bindValue("gr_id", $gr_id, PDO::PARAM_INT);
			$q2->execute();
			if (empty($q2->errorInfo()[1])) {
				$RET = ['data' => 'ok', 'message' => 'Заявка на вступление создана'];
			} else {
				$RET = ['data' => 'ok2', 'message' => 'Заявка на вступление уже была создана ранее'];
			}
		} else {
			if ($group['usr_id'] == $ME['usr_id']) {
				$RET = ['data' => "ok2", 'message' => 'Вы являетесь создателем этой группы'];
			} else {
				$RET = ['error' => 'Ошибка доступа'];
			}
		}
	}
}

function get_group_users()
{
	global $R, $DB, $ME, $RET;
	$table_name = "requests";

	$count = isset($R['count']) ? $R['count'] : 30;
	$count = $count > 100 ? 100 : $count;
	$sign = $R['desc'] == true ? '<' : '>';
	$insertDesc = $R['desc'] == true ? 'desc' : '';
	if (isset($R['point'])) {
		$q = $DB->prepare("SELECT $table_name.*, users.first_name, users.last_name, users.usr_id, users.avatar
			from $table_name left join users on $table_name.usr_id = users.usr_id 
			where $table_name.gr_id = :gr_id and $table_name.accepted = :accepted 
			and $table_name.req_id $sign :point order by $table_name.req_id $insertDesc limit :count");
		$q->bindValue('point', $R['point'], PDO::PARAM_INT);
	} else {
		$q = $DB->prepare("SELECT $table_name.*, users.first_name, users.last_name, users.usr_id, users.avatar
			from $table_name left join users on $table_name.usr_id = users.usr_id 
			where $table_name.gr_id = :gr_id and $table_name.accepted = :accepted
			order by $table_name.req_id $insertDesc limit :count");
	}
	$q->bindValue('gr_id', $R['gr_id'], PDO::PARAM_INT);
	$q->bindValue('accepted', $R['accepted'], PDO::PARAM_BOOL);
	$q->bindValue('count', $count, PDO::PARAM_INT);
	$q->execute();
	if (empty($q->errorInfo()[1])) {
		$rows = $q->fetchALL(PDO::FETCH_ASSOC);
		$RET = ['data' => $rows, 'info' => $R];
	} else {
		$RET = ['error' => $q->errorInfo()[2]];
	}
}


function join_request_action()
{
	global $R, $DB, $ME, $RET;

	$q = $DB->prepare("SELECT groups.usr_id from groups left join requests on requests.gr_id = groups.gr_id where req_id = :req_id limit 1");
	$q->bindValue("req_id", $R['req_id'], PDO::PARAM_INT);
	$q->execute();
	$isSend = false;
	if (($q->fetch(PDO::FETCH_ASSOC))['usr_id'] == $ME['usr_id']) {
		switch ($R['action']) {
			case 'set_accepted':
				$q2 = $DB->prepare("UPDATE requests set accepted = :accepted where req_id = :req_id");
				$q2->bindValue('req_id', $R['req_id'], PDO::PARAM_INT);
				$q2->bindValue('accepted', $R['accepted'], PDO::PARAM_BOOL);
				$q2->execute();
				if (empty($q2->errorInfo()[1])) {
					$isSend = true;
				} else {
				}
				break;
			case 'set_name':
				//Если имя есть
				if (mb_strlen($R['name']) > 0) {
					$q2 = $DB->prepare("UPDATE requests set name = :name where req_id = :req_id");
					$q2->bindValue('req_id', $R['req_id'], PDO::PARAM_INT);
					$q2->bindValue('name', trim($R['name']), PDO::PARAM_STR);
					$q2->execute();
					if (empty($q2->errorInfo()[1])) {
						//USPEX
						$isSend = true;
					}
				} else {
					$q2 = $DB->prepare("SELECT users.first_name, users.last_name from users
						left join requests on requests.usr_id = users.usr_id where req_id = :req_id limit 1");
					$q2->bindValue('req_id', $R['req_id'], PDO::PARAM_INT);
					$q2->execute();
					if ($row = $q2->fetch(PDO::FETCH_ASSOC)) {
						$q3 = $DB->prepare("UPDATE requests set name = :name where req_id = :req_id");
						$q3->bindValue('req_id', $R['req_id'], PDO::PARAM_INT);
						$q3->bindValue('name', $row['last_name'] . ' ' . $row['first_name'], PDO::PARAM_STR);
						$q3->execute();
						if (empty($q3->errorInfo()[1])) {
							//USPEX
							$isSend = true;
						}
					}
				}
				break;
			case 'delete':
				$q2 = $DB->prepare("DELETE from requests where req_id = :req_id");
				$q2->bindValue('req_id', $R['req_id'], PDO::PARAM_INT);
				$q2->execute();
				if (empty($q2->errorInfo()[1])) {
					$isSend = false;
				} else $isSend = true;
			default:
				# code...
				break;
		}
		if ($isSend) {
			$qr = $DB->prepare("SELECT requests.*, users.first_name, users.last_name, users.usr_id, users.avatar 
			from requests left join users on requests.usr_id = users.usr_id
			where requests.req_id = :req_id limit 1");
			$qr->bindValue("req_id", $R['req_id'], PDO::PARAM_INT);
			$qr->execute();
			if ($rowReq = $qr->fetch(PDO::FETCH_ASSOC)) {
				$RET = ['data' => $rowReq, 'info' => 'Пока тут пусто'];
			}
		} else {
			$RET = ['data' => 'Удалено', 'deleted' => true, 'error' => $q2->errorInfo()[2]];
		}
	} else {
		$RET = ['error' => $q->errorInfo()[2]];
	}
}


//Получение списка групп
function get_my_groups()
{
	global $R, $DB, $ME, $RET;

	$count = empty($R['count']) ? 20 : $R['count'];
	$count = $count > 100 ? 100 : $count;
	$sign = empty($R['desc']) ? '>' : '<';
	$insertDesc = empty($R['desc']) ? '' : 'desc';

	//Мои групы
	if ($R['type'] == 'my') {
		if (empty($R['point'])) {
			$q = $DB->prepare("SELECT groups.*, images.url \"ico_url\", users.first_name, users.last_name from groups left join users on groups.usr_id = users.usr_id 
        left join images on groups.img_id = images.img_id 
        where groups.usr_id = :usr_id
				order by groups.gr_id $insertDesc limit :count");
		} else {
			$q = $DB->prepare("SELECT groups.*, images.url \"ico_url\", users.first_name, users.last_name from groups left join users on groups.usr_id = users.usr_id 
        left join images on groups.img_id = images.img_id 
        where groups.usr_id = :usr_id and groups.gr_id $sign :point 
				order by groups.gr_id $insertDesc limit :count");
			$q->bindValue('point', $R['point'], PDO::PARAM_INT);
		}
		//Под моим управлением
	} else {
		if (empty($R['point'])) {
			$q = $DB->prepare("SELECT groups.*, images.url \"ico_url\", users.first_name, users.last_name from groups left join users on groups.usr_id = users.usr_id inner join requests on requests.gr_id = groups.gr_id 
        left join images on groups.img_id = images.img_id 
        where requests.usr_id = :usr_id
				order by requests.req_id $insertDesc limit :count");
		} else {
			$q = $DB->prepare("SELECT groups.*, images.url \"ico_url\", users.first_name, users.last_name from groups left join users on groups.usr_id = users.usr_id inner join requests on requests.gr_id = groups.gr_id 
        left join images on groups.img_id = images.img_id 
        where requests.usr_id = :usr_id and requests.req_id $sign :point 
				order by requests.req_id $insertDesc limit :count");
			$q->bindValue('point', $R['point'], PDO::PARAM_INT);
		}
	}

	$q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
	$q->bindValue('count', $count, PDO::PARAM_INT);
	$q->execute();
	if (empty($q->errorInfo()[1])) {
		$rows = $q->fetchALL(PDO::FETCH_ASSOC);
    for ($i=0; $i < count($rows); $i++) { 
      //Установление изображения:
      if(strlen($rows[$i]['ico_url']) > 0){
        $rows[$i]['ico_url'] = LINK.'/uploaded/'.$rows[$i]['ico_url']; }else{
        $rows[$i]['ico_url'] = LINK.'/img/group_default.jpg'; 
      }//
    }
    $RET = ['data' => $rows];
	} else {
		$RET = ['error' => $q->errorInfo()[2]];
	}
}


function get_group_tests()
{
	global $R, $DB, $ME, $RET;
	$table_name = "gtests";

	$count = isset($R['count']) ? $R['count'] : 30;
	$count = $count > 100 ? 100 : $count;
	$sign = $R['desc'] == true ? '<' : '>';
	$insertDesc = $R['desc'] == true ? 'desc' : '';

	$AccessLevel = isAccessGroup($ME['usr_id'], $R['gr_id']);

	if ($AccessLevel) {
		if (isset($R['point'])) {
			$q = $DB->prepare("SELECT $table_name.*, tests.name, tests.description, images.url \"ico_url\"
			from $table_name left join tests on $table_name.ref_test_id = tests.test_id
			left join images on tests.ico = images.img_id
			where $table_name.gr_id = :gr_id 
			and $table_name.gt_id $sign :point
			order by $table_name.gt_id $insertDesc limit :count");
			$q->bindValue('point', $R['point'], PDO::PARAM_INT);
		} else {
			$q = $DB->prepare("SELECT $table_name.*, tests.name, tests.description, images.url \"ico_url\"
			from $table_name left join tests on $table_name.ref_test_id = tests.test_id 
			left join images on tests.ico = images.img_id
			where $table_name.gr_id = :gr_id
			order by $table_name.gt_id $insertDesc limit :count");
		}
		$q->bindValue('gr_id', $R['gr_id'], PDO::PARAM_INT);
		$q->bindValue('count', $count, PDO::PARAM_INT);
		$q->execute();
		if (empty($q->errorInfo()[1])) {
			$rows = $q->fetchALL(PDO::FETCH_ASSOC);
			for($i = 0; $i< count($rows); $i++){
				$rows[$i]['date_created'] = NormalTime( $rows[$i]['date_created']);
				if($AccessLevel==2) $rows[$i]['usr_id']=$ME['usr_id'];
				//Установление иконки
				if(strlen($rows[$i]['ico_url']) > 0){
					$rows[$i]['ico_url'] = LINK.'/uploaded/'.$rows[$i]['ico_url']; 
				}else{
					$rows[$i]['ico_url'] = LINK.'/img/test_default.jpg'; 
				}
			}
			$RET = ['data' => $rows, 'info' => $R];
		} else {
			$RET = ['error' => $q->errorInfo()[2]];
		}
	}else{
		$RET = ['error' => 'Нет доступа'];
	}
}

function get_group_results(){
	global $R, $DB, $ME, $RET;
	$table_name = "results";
	$isAccess = isAccessGroup($ME['usr_id'], $R['gr_id']);

	$count = isset($R['count']) ? $R['count'] : 30;
	$count = $count > 100 ? 100 : $count;
	$sign = $R['desc'] == true ? '<' : '>';
	$insertDesc = $R['desc'] == true ? 'desc' : '';

	if ( $isAccess ) {
		$insUser = $isAccess == 1 ? 'and requests.usr_id = :usr_id' : '';
		if (isset($R['point'])) {
			$q = $DB->prepare("SELECT $table_name.res_id, $table_name.ready, $table_name.time_end, $table_name.score, $table_name.max_score, $table_name.date_created, $table_name.usr_id,
			requests.name as \"user_name\", users.first_name, users.last_name, users.avatar, $table_name.ref_test_id
			from $table_name left join users on $table_name.usr_id = users.usr_id
			left join requests on (requests.usr_id = users.usr_id and requests.gr_id = :gr_id )
			where $table_name.gr_id = :gr_id and $table_name.ref_test_id = :gt_id
			and $table_name.res_id $sign :point
			$insUser
			order by $table_name.res_id $insertDesc limit :count");
			$q->bindValue('point', $R['point'], PDO::PARAM_INT);
		} else {
			$q = $DB->prepare("SELECT $table_name.res_id, $table_name.ready, $table_name.time_end, $table_name.score, $table_name.max_score, $table_name.date_created, $table_name.usr_id,
			requests.name as \"user_name\", users.first_name, users.last_name, users.avatar, $table_name.ref_test_id
			from $table_name left join users on $table_name.usr_id = users.usr_id
			left join requests on (requests.usr_id = users.usr_id and requests.gr_id = :gr_id )
			where $table_name.gr_id = :gr_id and $table_name.ref_test_id = :gt_id
			$insUser
			order by $table_name.res_id $insertDesc limit :count");
		}
		if($isAccess == 1)  $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
		$q->bindValue('gt_id', $R['gt_id'], PDO::PARAM_INT);
		$q->bindValue('gr_id', $R['gr_id'], PDO::PARAM_INT);
		$q->bindValue('count', $count, PDO::PARAM_INT);
		$q->execute();
		if (empty($q->errorInfo()[1])) {
			$rows = $q->fetchALL(PDO::FETCH_ASSOC);
			//START: Присвоение системы оценивания результатам:
			$assessmentDefault = '';
			$qas = $DB->prepare("SELECT assessment, (SELECT assessment from groups where gr_id = :gr_id limit 1) as \"assessment_default\" from gtests where gt_id = :gt_id limit 1");
			BindExecute($qas, [['gr_id', $R['gr_id'], PDO::PARAM_INT], ['gt_id', $R['gt_id'], PDO::PARAM_INT]]);
			if( $row_assess = $qas->fetch(PDO::FETCH_ASSOC)){
				$assessmentDefault = (isset($row_assess['assessment']) && strlen($row_assess['assessment']) > 0 ) ? $row_assess['assessment'] : $row_assess['assessment_default'];
			}
			for ($i=0; $i < count($rows); $i++) { 
				$rows[$i]['assessment'] = $assessmentDefault;
			}//END: Присвоение системы оценивания результатам:
			$RET = ['data' => $rows, 'info' => $R];
		} else {
			$RET = ['error' => $q->errorInfo()[2]];
		}
	}else{
		$RET = ['error' => 'Нет доступа'];
	}

}


function edit_gtest(){
	global $R, $DB, $ME, $RET;
	$groups = $R['groups'];
	$sttg = $R['settings'];
	if(isAccessGroup($ME['usr_id'], $R['gr_id']) == 2 ){
		$others = [];//Названия других параметров
		$oValues = [];//Значения других параметров
		if(isset($R['comment'])){
			$others[] = 'comment';
			$oValues['comment'] = $R['comment'];
		}
		//Проверка на наличие параметров
		if(isset($sttg)){
			if(isset($sttg['is_limit_attempts'])){
				$others[] = 'attempts';
				$oValues['attempts'] = NULL;
				if($sttg['is_limit_attempts'] == true){
					if(isset($sttg['limit_attempts'])){
						$oValues['attempts'] = $sttg['limit_attempts'] > 0 ? $sttg['limit_attempts'] : 1;
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
	  if(isset($sttg['assessment'])){
        $others[] = 'assessment';
        $oValues['assessment'] = NULL;
        if(isset($sttg['assessment']) && is_array($sttg['assessment'])){
			if($sttg['assessment']['name'] == 'default'){
				$oValues['assessment'] = NULL;
			}else{
				$oValues['assessment'] = json_encode($sttg['assessment']);
			}
        }
      }
		}
		//end
		$insertSettings = ''; //comment = :comment, attempts = :attempts
		for ($i=0; $i < count($others) ; $i++) {
			$insertSettings.=$others[$i].' = :'.$others[$i].($i < count($others)-1 ? ', ' : '');
		}

		$query = "UPDATE gtests set $insertSettings where gt_id = :gt_id ";
		$qr=$DB->prepare($query);
		$qr->bindValue('gt_id', $R['gt_id'], PDO::PARAM_INT);
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
		case 'assessment': $qr->bindValue('assessment', $oValues['assessment'], 
		  $oValues['assessment'] == NULL ? PDO::PARAM_NULL : PDO::PARAM_STR);
		break;
				default:
					break;
			}
		}//end
		$qr->execute();
		if( empty($qr->errorInfo()[1]) ){
			$qgt = $DB->prepare("SELECT gtests.*, images.url \"ico_url\", tests.name, tests.description
			from gtests left join tests on gtests.ref_test_id = tests.test_id 
      left join images on tests.ico = images.img_id 
			where gtests.gt_id = :gt_id and gtests.gr_id = :gr_id limit 1");
			$qgt->bindValue('gt_id', $R['gt_id'], PDO::PARAM_INT);
			$qgt->bindValue('gr_id', $R['gr_id'], PDO::PARAM_INT);
			$qgt->execute();
			$row = $qgt->fetch(PDO::FETCH_ASSOC);
			$row['date_created'] = NormalTime( $row['date_created']);
			$row['usr_id'] = $ME['usr_id'];
      if(strlen($row['ico_url']) > 0){
        $row['ico_url'] = LINK.'/uploaded/'.$row['ico_url']; }else{
        $row['ico_url'] = LINK.'/img/test_default.jpg'; 
      }
			$RET = ['data'=> $row, 'R'=>$R];
		}else{
			$RET = ['error'=>$qr->errorInfo()[2]];
		}
	}else{
		$RET = ['error'=>'Отказано в доступе'];
	}
}

function delete_result(){
	global $R, $DB, $ME, $RET;

	$toDelete = false;
	$q = $DB->prepare("SELECT results.*, gtests.gt_id as \"gt_exists\" from results left join gtests on (gtests.gr_id = results.gr_id and gtests.gt_id = results.ref_test_id) where res_id = :res_id limit 1");
	$q->bindValue('res_id', $R['res_id'], PDO::PARAM_INT);
	$q->execute();
	if( $row = $q->fetch(PDO::FETCH_ASSOC) ){
		//Если тест относится к группе
		if($row['gr_id'] > 0){
			//Если тест существет
			if( $row['gt_exists'] > 0 ){
				$lvlAccess = isAccessGroup($ME['usr_id'], $row['gr_id']);
				$toDelete = $lvlAccess == 2;
				
			}else{
				$toDelete = $ME['usr_id'] == $row['usr_id'] ;
			} 
		}else{
			$toDelete = $ME['usr_id'] == $row['usr_id'] ;
		}
	}
	if($toDelete){
		$qd = $DB->prepare("DELETE from results where res_id = :res_id");
		$qd->bindValue('res_id', $R['res_id'], PDO::PARAM_INT);
		$qd->execute();
		if( empty($qd->errorInfo()[1])){
			$RET = ['data'=>'Результат успешно удален'];
		}else{
			$RET = ['error'=>$qd->errorInfo()[2]];
		}
	}else{
		$RET = ['error'=>'Недостаточно прав для удаления', 'info'=>$row['gt_exists']];
	}
}

function delete_gtest(){
	global $R, $DB, $ME, $RET;
	$q = $DB->prepare("SELECT gr_id from gtests where gt_id = :gt_id limit 1");
	$q->bindValue("gt_id", $R['gt_id'], PDO::PARAM_INT);
	$q->execute();
	if($row = $q->fetch(PDO::FETCH_ASSOC)){
		if(isAccessGroup($ME['usr_id'], $row['gr_id']) == 2){
			$qd = $DB->prepare("DELETE from gtests where gt_id = :gt_id");
			$qd->bindValue('gt_id', $R['gt_id'], PDO::PARAM_INT);
			$qd->execute();
			if(empty($qd->errorInfo()[1])){
				$RET = ['data'=>'Успешно удалено'];
			}else $RET = ['error'=>$qd->errorInfo()[2] ];
		}else{
			$RET = ['error'=>'Нет доступа' ];
		}
	}else{
		$RET = ['error'=>'Объект не найден'];
	}

}


function get_all_groups_tests()
{
	global $R, $DB, $ME, $RET;
	$table_name = "gtests";

	$count = isset($R['count']) ? $R['count'] : 30;
	$count = $count > 100 ? 100 : $count;
	$sign = $R['desc'] == true ? '<' : '>';
	$insertDesc = $R['desc'] == true ? 'desc' : '';

	if (isset($R['point'])) {
		$q = $DB->prepare("SELECT $table_name.*, images.url \"test_ico_url\", images_gr.url \"group_ico_url\", tests.name, tests.description
			from $table_name left join tests on $table_name.ref_test_id = tests.test_id 
			inner join requests on (requests.gr_id = gtests.gr_id)
			left join images on tests.ico = images.img_id 
      left join images images_gr on groups.img_id = images_gr.img_id 
      inner join groups on (groups.gr_id = $table_name.gr_id)
			where requests.usr_id = :usr_id
			and groups.closed = false
			and $table_name.gt_id $sign :point
			order by $table_name.gt_id $insertDesc limit :count");
		$q->bindValue('point', $R['point'], PDO::PARAM_INT);
	} else {
		$q = $DB->prepare("SELECT $table_name.*, images.url \"test_ico_url\", images_gr.url \"group_ico_url\", tests.name, tests.description,
			users.avatar, groups.name as \"group_name\"
			from $table_name left join tests on $table_name.ref_test_id = tests.test_id 
			inner join requests on (requests.gr_id = gtests.gr_id)
			inner join groups on (groups.gr_id = $table_name.gr_id)
			left join users on (groups.usr_id = users.usr_id)
			left join images on tests.ico = images.img_id 
      left join images images_gr on groups.img_id = images_gr.img_id 
			where requests.usr_id = :usr_id
			and groups.closed = false
			order by $table_name.gt_id $insertDesc limit :count");
	}
	$q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
	$q->bindValue('count', $count, PDO::PARAM_INT);
	$q->execute();
	if (empty($q->errorInfo()[1])) {
		$rows = $q->fetchALL(PDO::FETCH_ASSOC);
		for ($i = 0; $i < count($rows); $i++) {
			$rows[$i]['date_created'] = NormalTime($rows[$i]['date_created']);
			//Установление иконки теста
			if(strlen($rows[$i]['test_ico_url']) > 0){
				$rows[$i]['test_ico_url'] = LINK.'/uploaded/'.$rows[$i]['test_ico_url']; }else{
				$rows[$i]['test_ico_url'] = LINK.'/img/test_default.jpg'; 
			}
      //Установление иконки группы:
      if(strlen($rows[$i]['group_ico_url']) > 0){
        $rows[$i]['group_ico_url'] = LINK.'/uploaded/'.$rows[$i]['group_ico_url']; }else{
        $rows[$i]['group_ico_url'] = LINK.'/img/group_default.jpg'; 
      }//
		}
		$RET = ['data' => $rows];
	} else {
		$RET = ['error' => $q->errorInfo()[2]];
	}
}


function set_group_ico(){
	global $R, $DB, $ME, $RET;

  $img_id = $R['img_id'];
  $gr_id = $R['gr_id'];
  $usr_id = $ME['usr_id'];
  $RET = ['error'=>'Недостаточно прав'];
  $q = $DB->prepare("SELECT * from images where img_id = :img_id limit 1");
  BindExecute($q, [['img_id', $img_id, PDO::PARAM_INT]]);
  if($row = $q->fetch(PDO::FETCH_ASSOC)){
    if($row['usr_id'] == $usr_id){
      $q2 = $DB->prepare("SELECT usr_id from groups where gr_id = :gr_id limit 1");
      BindExecute($q2, [['gr_id', $gr_id, PDO::PARAM_INT]]);
      if($row2 = $q2->fetch(PDO::FETCH_ASSOC)){
        if($row2['usr_id'] == $usr_id){
          $q3 = $DB->prepare('UPDATE groups SET img_id = :img_id where gr_id = :gr_id');
          BindExecute($q3, [
            ['img_id', $img_id, PDO::PARAM_INT], 
            ['gr_id', $gr_id, PDO::PARAM_INT]
          ]);
          if( empty($q3->errorInfo()[1])){
            $RET = ['data'=>LINK.'/uploaded/'.$row['url']];
          }
        }
      }else{
        $RET = ['error'=>'Недостаточно прав на группу'];
      }
    }else{
      $RET = ['error'=>'Недостаточно прав на изображение'];
    }
  }else{
    $RET = ['error'=>'Изображение не найдено'];
  }
}

function set_group_info(){
	global $R, $DB, $ME, $RET;

	$gr_id = $R['gr_id'];
	$name = trim($R['info']['name']);
	$description = trim($R['info']['description']);
	if(mb_strlen($name) > 1){
		$q = $DB->prepare("SELECT usr_id, gr_id from groups where gr_id = :gr_id limit 1");
		$q->bindValue("gr_id", (int)$gr_id, PDO::PARAM_INT);
		$q->execute();
		if($group = $q->fetch(PDO::FETCH_ASSOC)){
			if($group['usr_id'] == $ME['usr_id']){
				$q2 = $DB->prepare("UPDATE groups set name = :name, description = :description where gr_id = :gr_id");
				BindExecute($q2, [
					['name', $name, PDO::PARAM_STR], 
					['description', $description, PDO::PARAM_STR], 
					['gr_id', $gr_id, PDO::PARAM_INT]]);
				if( empty($q2->errorInfo()[1]) ){
					$RET = ['data'=>$gr_id, 'info'=>['name'=>$name, 'description'=>$description]];
				}
			}else{
				$RET = ['error'=>'Нет доступа к группе'];
			}
		}
	}else{
		$RET = ['error'=>'Название группы должно быть более одного символа'];
	}
}

function set_close_group(){
	global $R, $DB, $ME, $RET;

	$gr_id = $R['gr_id'];
	$value = $R['value'] == true ? true : false;
	$q = $DB->prepare("SELECT usr_id, gr_id, closed from groups where gr_id = :gr_id limit 1");
	$q->bindValue('gr_id', $gr_id, PDO::PARAM_INT);
	$q->execute();
	if($group = $q->fetch(PDO::FETCH_ASSOC)){
		if($ME['usr_id'] == $group['usr_id']){
			$q2 = $DB->prepare("UPDATE groups set closed = :closed where gr_id = :gr_id and usr_id = :usr_id");
			BindExecute($q2, [
				['closed', $value, PDO::PARAM_BOOL],
				['gr_id', $gr_id, PDO::PARAM_INT],
				['usr_id', $ME['usr_id'], PDO::PARAM_INT]]);
			if(empty( $q2->errorInfo()[1] )){
				$RET = ['data'=>'ok'];
			}else{
				$RET = ['error'=>'Ошибка в запросе'];
			}
		}else{
			$RET = ['error'=>'Ошибка доступа'];
		}

	}else{
		$RET = ['error'=>'Группа не найдена'];
	}

}

function delete_group(){
	global $R, $DB, $ME, $RET;

	$gr_id = $R['gr_id'];

	$q = $DB->prepare("SELECT gr_id, usr_id, closed from groups where gr_id = :gr_id limit 1");
	$q->bindValue('gr_id', $gr_id, PDO::PARAM_INT);
	$q->execute();
	if($group = $q->fetch(PDO::FETCH_ASSOC)){
		if($group['usr_id'] == $ME['usr_id']){
			if($group['closed'] == 1){
				$q2 = $DB->prepare("DELETE from groups where gr_id = :gr_id and usr_id = :usr_id");
				BindExecute($q2, [['gr_id', $gr_id, PDO::PARAM_INT], ['usr_id', $ME['usr_id'], PDO::PARAM_INT]]);
				if( empty($q2->errorInfo()[1]) ){
					$RET = ['data'=>'ok'];
				}else{
					$RET = ['error'=>$q2->errorInfo()[2]];
				}
			}else{
				$RET = ['error'=>'Для удаления группы, необходимо сперва ее закрыть'];
			}
		}else{
			$RET = ['error'=>'Ошибка доступа'];
		}
	}else{
		$RET = ['error'=>'Группа не найдена'];
	}
}

function group_set_assessment(){
	global $R, $DB, $ME, $RET;
	$gr_id = $R['gr_id'];
	$q = $DB->prepare("SELECT gr_id, usr_id from groups where gr_id = :gr_id limit 1");
	$q->bindValue('gr_id', $gr_id, PDO::PARAM_INT);
	$q->execute();
	if($group = $q->fetch(PDO::FETCH_ASSOC)){
		if($group['usr_id'] == $ME['usr_id']){
			$q2 = $DB->prepare("UPDATE groups set assessment = :assessment where gr_id = :gr_id");
			BindExecute($q2, [
				['assessment', $R['assessment'], PDO::PARAM_STR],
				['gr_id', $gr_id, PDO::PARAM_INT]
			]);
			if(empty($q2->errorInfo()[1])){
				$RET = ['data'=>$R['assessment']];
			}else{
				$RET = ['error'=>'Ошибка'];
			}
		}else{
			$RET = ['error'=>'Недостаточно прав'];
		}
	}else{
		$RET = ['error'=>'Группа не существует'];
	}
}