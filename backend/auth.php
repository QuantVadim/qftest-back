<?php
include 'db.php';
$_POST = json_decode(file_get_contents('php://input'), true);


$sn = $_POST['sn']; //Социальная сеть
$return = ['error'=>'Пусто', 'dt'=>$_POST];

if( isset($_POST['isBinding']) && $_POST['isBinding'] == true ){
	if($sn == 'vk' && isset($_POST['login']) && isset($_POST['password']) ){
		binding_vk();
	}

}else{
	switch ($sn) {
		case 'vk':
			auth_vk();
			break;
		case 'def':
			auth_def();
			break;
		default:
			# code...
			break;
	}
}

echo json_encode($return);



function auth_vk(){
	global $DB;
	global $_POST;
	global $return;
	$q = $DB->prepare("SELECT * from users where social_network = \"vk\" and social_id = :user_id limit 1");
	$q->bindValue('user_id', $_POST['user_id'], PDO::PARAM_INT);
	$q->execute();
	$access_token = $_POST['access_token'];
	$request = "https://api.vk.com/method/users.get?fields=id,first_name,last_name,photo_100&access_token=".$access_token."&v=".VK_API_VERSION;
	$response = getResponse($request)['response'];
	$mykey = generate_string(20);
	if($row = $q->fetch(PDO::FETCH_ASSOC)){
		$usr_id = $row['usr_id'];
		if( isset($response[0]['first_name'])){
			$q2 = $DB->prepare("UPDATE users set sn_access_token = :access_token, mykey = :mykey, avatar = :avatar where usr_id = :usr_id");
			$q2->bindValue('usr_id', $usr_id, PDO::PARAM_INT);
			$q2->bindValue('access_token', $access_token, PDO::PARAM_STR);
			$q2->bindValue('avatar', $response[0]['photo_100'], PDO::PARAM_STR);
			$q2->bindValue('mykey', $mykey, PDO::PARAM_STR);
			$q2->execute();
			if(empty($q2->errorInfo()[1])){
				$return = [
					'usr_id'=>$usr_id,
					'mykey'=>$mykey
				];
			}
		}else{
			$return = [
				'error'=>'Ошибка1',
				'body'=>$response,
				'req'=>$request,
			];
		}
	}else{
		$user = $response[0];
		// $q2 = $DB->prepare("INSERT into users (social_network, social_id, sn_access_token, first_name, last_name, avatar, mykey) values (:social_network, :social_id, :sn_access_token, :first_name, :last_name, :avatar, :mykey)");
		// $q2->bindValue('social_network', 'vk', PDO::PARAM_STR);
		// $q2->bindValue('social_id', $user['id'], PDO::PARAM_STR);
		// $q2->bindValue('sn_access_token', $access_token, PDO::PARAM_STR);
		// $q2->bindValue('first_name', $user['first_name'], PDO::PARAM_STR);
		// $q2->bindValue('last_name', $user['last_name'], PDO::PARAM_STR);
		// $q2->bindValue('avatar', $user['photo_100'], PDO::PARAM_STR);
		// $q2->bindValue('mykey', $mykey, PDO::PARAM_STR);
		// $q2->execute();
		// if(empty($q2->errorInfo()[1])){
		// 	$q3 = $DB->prepare("SELECT * from users where social_id = :social_id and social_network = \"vk\" limit 1");
		// 	$q3->bindValue('social_id', $user['id'], PDO::PARAM_STR);
		// 	$q3->execute();
		// 	if($ruser = $q3->fetch(PDO::FETCH_ASSOC)){
		// 		$return = [
		// 		'usr_id'=>$ruser['usr_id'],
		// 		'mykey'=>$mykey
		// 		];
		// 	}else{
		// 		$return = [
		// 		'error'=>'Ошибка2',
		// 		];
		// 	}	
		// }else{
		// 	$return = [
		// 		'error'=> $q2->errorInfo(),
		// 		'dt'=>$user,
		// 	];
		// }
		$user['photo'] = $user['photo_100'];
		$return = [
			'error'=>'Пользователь не найден',
			'code'=> 1,
			'user'=>$user,
			];
	}
}

function binding_vk(){
	global $DB, $_POST, $return;

	$access_token = $_POST['access_token'];
	$request = "https://api.vk.com/method/users.get?fields=id,first_name,last_name,photo_100&access_token=".$access_token."&v=".VK_API_VERSION;
	$response = getResponse($request)['response'];
	$mykey = generate_string(20);

	$q = $DB->prepare("SELECT * from def_users where login = :login and password = :password limit 1");
	$q->bindValue('login', $_POST['login'], PDO::PARAM_STR);
	$q->bindValue('password', $_POST['password'], PDO::PARAM_STR);
	$q->execute();
	if($dusr = $q->fetch(PDO::FETCH_ASSOC)){
		$user = $response[0];
		$q2 = $DB->prepare("UPDATE users set social_network = :social_network, social_id = :social_id, sn_access_token = :sn_access_token, avatar = :avatar, mykey = :mykey where social_id = :def_usr_id and social_network = 'def'");
		$q2->bindValue('social_network', 'vk', PDO::PARAM_STR);
		$q2->bindValue('social_id', $user['id'], PDO::PARAM_STR);
		$q2->bindValue('sn_access_token', $access_token, PDO::PARAM_STR);
		$q2->bindValue('avatar', $user['photo_100'], PDO::PARAM_STR);
		$q2->bindValue('mykey', $mykey, PDO::PARAM_STR);
		$q2->bindValue('def_usr_id', $dusr['def_usr_id'], PDO::PARAM_STR);
		$q2->execute();
		if(empty($q2->errorInfo()[1])){
			$qd = $DB->prepare("DELETE FROM def_users where def_usr_id = :def_usr_id");
			$qd->bindValue('def_usr_id', $dusr['def_usr_id'], PDO::PARAM_INT);
			$qd->execute();

			$q3 = $DB->prepare("SELECT * from users where social_id = :social_id and social_network = \"vk\" limit 1");
			$q3->bindValue('social_id', $user['id'], PDO::PARAM_STR);
			$q3->execute();
			if($ruser = $q3->fetch(PDO::FETCH_ASSOC)){
				$return = [
				'usr_id'=>$ruser['usr_id'],
				'mykey'=>$mykey
				];
			}else{
				$return = [
				'error'=>'Ошибка2',
				];
			}	
		}else{
			$return = [
				'error'=> $q2->errorInfo(),
				'dt'=>$user,
			];
		}
	}else{
		$return = ['error'=>'Логин или пароль неверен']; 
	}
}


function auth_def(){
	global $DB;
	global $_POST;
	global $return;
	$q = $DB->prepare("SELECT * from def_users where login = :login and password = :password limit 1");
	$q->bindValue('login', $_POST['login'], PDO::PARAM_STR);
	$q->bindValue('password', $_POST['password'], PDO::PARAM_STR);
	$q->execute();
	if($dusr = $q->fetch(PDO::FETCH_ASSOC)){

		$qu = $DB->prepare("SELECT * from users where social_network = :social_network and social_id = :social_id limit 1");
		$qu->bindValue('social_network', 'def', PDO::PARAM_STR);
		$qu->bindValue('social_id', $dusr['def_usr_id'], PDO::PARAM_STR);
		$qu->execute();

		$mykey = generate_string(20);
		if($row = $qu->fetch(PDO::FETCH_ASSOC)){
			$usr_id = $row['usr_id'];
			$q2 = $DB->prepare("UPDATE users set mykey = :mykey where usr_id = :usr_id");
			$q2->bindValue('usr_id', $usr_id, PDO::PARAM_INT);
			$q2->bindValue('mykey', $mykey, PDO::PARAM_STR);
			$q2->execute();
			if(empty($q2->errorInfo()[1])){
				$return = [
					'usr_id'=>$usr_id,
					'mykey'=>$mykey
				];
			}

		}else{
			$user = $dusr;
			$q2 = $DB->prepare("INSERT into users (social_network, social_id, first_name, last_name, mykey) values (:social_network, :social_id,  :first_name, :last_name,  :mykey)");
			$q2->bindValue('social_network', 'def', PDO::PARAM_STR);
			$q2->bindValue('social_id', $user['def_usr_id'], PDO::PARAM_STR);
			$q2->bindValue('first_name', $user['first_name'], PDO::PARAM_STR);
			$q2->bindValue('last_name', $user['last_name'], PDO::PARAM_STR);
			$q2->bindValue('mykey', $mykey, PDO::PARAM_STR);
			$q2->execute();
			if(empty($q2->errorInfo()[1])){
				$q3 = $DB->prepare("SELECT * from users where social_id = :social_id and social_network = \"def\" limit 1");
				$q3->bindValue('social_id', $user['def_usr_id'], PDO::PARAM_STR);
				$q3->execute();
				if($ruser = $q3->fetch(PDO::FETCH_ASSOC)){
					$return = [
					'usr_id'=>$ruser['usr_id'],
					'mykey'=>$mykey
					];
				}else{
					$return = [
					'error'=>'Ошибка',
					];
				}	
			}else{
				$return = [
					'error'=> $q2->errorInfo()[2],
				];
			}
		}

	}else{
		$return = ['error'=>'Неверный логин или пароль'];
	}

	
}





?>