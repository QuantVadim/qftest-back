<?php

include 'modules/classSimpleImage.php';
include 'db.php';
include 'config.php';    

$USER = false;
if(isset($_POST['info']) && $info = json_decode($_POST['info']) 
){
    $uid = (int)($info->me->usr_id);
    $key = $info->me->mykey;
    $q = $DB->prepare("SELECT users.usr_id 'usr_id', users.first_name 'first_name', users.last_name 'last_name', users.mykey 'mykey' from users 
        where users.usr_id = :uid and users.mykey = :key limit 1");
    $q->bindValue('uid', $uid, PDO::PARAM_INT);
    $q->bindValue('key', $key, PDO::PARAM_STR);
    $q->execute();
    if($row = $q->fetch(PDO::FETCH_ASSOC)){
        $USER = $row;
        $USER = (object)$USER;
    }
}

if($USER != false){
    if ( 0 < $_FILES['image']['error'] ) {
        echo 'Error: ' . $_FILES['image']['error'] . '<br>';
    }
    else if($_FILES['image']['size'] < MAX_SIZE_UPLOADED_IMAGE && 
        ($_FILES['image']['type'] == 'image/png' || $_FILES['image']['type'] == 'image/jpeg') &&
        (exif_imagetype($_FILES['image']['tmp_name']) == IMAGETYPE_JPEG || exif_imagetype($_FILES['image']['tmp_name']) == IMAGETYPE_PNG)
    ) {
        $date = new DateTime();
        $maxsize = 1024;
        $path = 'uploaded/';
        $catalogName = "1";
        $type = $info->type;
        $compression = 50;
        $fname = '';
        $isSquare = false;
        switch ($info->type) {
            case 'ava':
                $maxsize = 300;
                $path = 'uploaded/';
                $fname = 'ava'.$USER->usr_id.'_'.$date->getTimestamp().'_'.rand(1000, 9999).'.jpg';
                $isSquare = true;
                break;
            case 'ico':
                $maxsize = 170;
                $compression = 90;
                $path = 'uploaded/';
                $fname = 'ico'.$USER->usr_id.'_'.$date->getTimestamp().'_'.rand(1000, 9999).'.jpg';
                $isSquare = true;
                break;
            default:
                $compression = 80;
                $type = 'img';
                $path = 'uploaded/';
                $fname = 'img'.$USER->usr_id.'_'.$date->getTimestamp().'_'.rand(1000, 9999).'.jpg';
                $maxsize = 500;
                break;
        }
        $image = new SimpleImage();
        $image->load($_FILES['image']['tmp_name']);
        if($isSquare){
            $imgX = imagesx($image->image);
            $imgY = imagesy($image->image);
            $cropSize = $imgX > $imgY ? $imgY : $imgX;
            $startX = round(($imgX - $cropSize)/2);
            $startY = round(($imgY - $cropSize)/2);
            $image->image = imagecrop($image->image, ['x' => $startX, 'y' => $startY, 'width' => $cropSize, 'height' => $cropSize]);
        }
        $sz = getimagesize($_FILES['image']['tmp_name']);
        $minByWidth = $sz[0] < $sz[1];
        if($sz[0] > $maxsize || $sz[1] > $maxsize){ 
            if($minByWidth){
                 $image->resizeToHeight($maxsize);
            }else{
                $image->resizeToWidth($maxsize);
            }
        }
        
        $image->save($_FILES['image']['tmp_name'], IMAGETYPE_JPEG, $compression);
        //Запрос последнего добавленного изображения:
        $q3 = $DB->prepare("SELECT img_id from images order by img_id desc limit 1");
        $q3->execute();
        if($row1 = $q3->fetch(PDO::FETCH_ASSOC)) {
            $lastID = (int)($row1['img_id']);
            $catalogName = round(($lastID+500)/1000); 
        }
        $fullPath = '../'.$path."$catalogName/";
        if(!file_exists($fullPath)){
            if(!mkdir($fullPath)) $catalogName = '1';
        }
        move_uploaded_file($_FILES['image']['tmp_name'], '../'.$path."$catalogName/".$fname);
        $filePath = "$catalogName/".$fname;
        $pt = $fullPath.$fname;
        $userID = (int)($USER->usr_id);
        $fileSize = filesize($pt);
        //Загрузка нового изображения:
        $q = $DB->prepare("INSERT into images (usr_id, url, type, size) values( :usr_id, :url, :type, :size)");
        $q->bindValue('usr_id', $userID, PDO::PARAM_INT);
        $q->bindValue('url', $filePath, PDO::PARAM_STR);
        $q->bindValue('type', $type, PDO::PARAM_STR);
        $q->bindValue('size', $fileSize, PDO::PARAM_INT);
        $q->execute();
        //Установка аватара пользователю:
        if($info->type == 'ava'){
            $q2 = $DB->prepare("UPDATE users set avatar = :url where usr_id = :usr_id");
            $q2->bindValue('url', $pt, PDO::PARAM_STR);
            $q2->bindValue('usr_id', $userID, PDO::PARAM_STR);
            $q2->execute();
        }
        //Запрос информации о загруженном изображении:
        $q3 = $DB->prepare("SELECT * from images where usr_id = :usr_id order by img_id desc limit 1");
        $q3->bindValue('usr_id', $userID, PDO::PARAM_INT);
        $q3->execute();
        if($row = $q3->fetch(PDO::FETCH_ASSOC)) {
            $ret = [
                'img_id'=>$row['img_id'],
                'usr_id'=>$row['usr_id'],
                'type'=>$row['type'],
                'size'=>$row['size'],
                'url'=>LINK.'/uploaded/'.$row['url'],
                'path'=>$filePath,
            ];
        }else{
            $ret = [
            'img'=>LINK.'/'.$pt,
            'path'=>$pt,
            ];
        }
        
        echo json_encode((object)$ret);
    }else{
        $ret = [
            'error'=>'Неподходящий файл'
        ];
        echo json_encode((object)$ret);
    }
}
?>