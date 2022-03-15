<?php 

function dateFormatJStoDB($dt) { // преобразовывает в московское время
	if($dt != NULL){
		$b = DateTime::createFromFormat('Y-m-d\TH:i:s', explode('.', $dt)[0] );
    	$a = $b->getTimestamp()+TIMEZONE_OFFSET;//добавление 3-х часов
    	$ndate = date('Y-m-d H:i', $a);
    	return $ndate;
	}else return NULL;
	
}


function GetAutoList($query, $table, $column, $params = []) //[['name', 'Vadim', PDO::PARAM_STR], [..], [..]]
{
  global $R, $DB, $ME;

  $RET = ['data'=>[]];
  $count = empty($R['count']) ? 20 : $R['count'];
  $count = $count > 100 ? 100 : $count;
  $sign = empty($R['desc']) ? '>' : '<';
  $insertDesc = empty($R['desc']) ? '' : 'desc';

  if (empty($R['point'])) {
    $q = $DB->prepare("$query order by $table.$column $insertDesc limit :count");
  } else {
  	$insertWhere = strpos(mb_strtolower($query), 'where') ? 'and' : 'where';
    $q = $DB->prepare("$query $insertWhere $table.$column $sign :point 
      order by $table.$column $insertDesc limit :count");
    $q->bindValue('point', $R['point'], PDO::PARAM_INT);
  }
  $q->bindValue('usr_id', $ME['usr_id'], PDO::PARAM_INT);
  $q->bindValue('count', $count, PDO::PARAM_INT);
  if(count($params) > 0){
  	foreach ($params as $key => $param) {
  		$q->bindValue($param[0], $param[1], $param[2]);	
  	}
  }
  $q->execute();
  if (empty($q->errorInfo()[1])) {
    $rows = $q->fetchALL(PDO::FETCH_ASSOC);
    for($i = 0; $i< count($rows); $i++){
    	if(isset($rows[$i]['date_created'])) $rows[$i]['date_created'] = NormalTime( $rows[$i]['date_created']);
    }
    $RET = ['data' => $rows];
  } else {
    $RET = ['error' => $q->errorInfo()[2]];
  }
  return $RET;
}

function BindExecute(&$Q, $params, $execute = true){
	//$params = [['column', 10, PDO::PARAM_INT], ['colum2', 15, PDO::PARAM_INT]]
	for ($i=0; $i < count($params); $i++) { 
		$Q->bindValue($params[$i][0], $params[$i][1], $params[$i][2]);
	}
	if($execute) $Q->execute(); 
}

function getImgURL($path, $default_name = 'test'){
	$ret = $path;
	//Установление изображения:
	if(strlen($path) > 0){
    	$ret = LINK.'/uploaded/'.$ret; 
    }else{
      	$ret = LINK.'/img/'.$default_name.'_default.jpg'; 
    }//
    return $ret;
}



?>