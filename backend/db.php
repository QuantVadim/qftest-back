<?php
include 'config.php';
try {
    $host = 'localhost';
    //$port = 5432;
    $dbname = DB_NAME;//'find_jobs';
    //$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $dsn = "mysql:host=$host;dbname=$dbname;charset=UTF8";
    $username = DB_USER;
    $passwd = DB_PASSWORD;
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