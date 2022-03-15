<?php

try {
    $host = 'localhost';
    //$port = 5432;
    $dbname = 'rutest';//'find_jobs';
    //$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $dsn = "mysql:host=$host;dbname=$dbname;charset=UTF8";
    $username = 'rutest';
    $passwd = '123qweasdzxc';
    $DB = new PDO($dsn, $username, $passwd);
} 
catch (PDOException $e)
{
    //print "Error!: " . $e->getMessage() . "<br />";
	echo $e->getMessage();
}

function getResponse($request){
	return json_decode(file_get_contents($request), true);
}
 
function generate_string($strength = 16) {
	$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($permitted_chars);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $permitted_chars[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
    return $random_string;
}

?>