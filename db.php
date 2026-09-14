<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$host='localhost';
$dbname='ink_inventory';
$username='YOUR_DB_USERNAME';
$password='YOUR_DB_PASSWORD';

try{
    $pdo=new PDO("mysql:host=$host;dbname=$dbname",$username,$password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e){
    die("Database connection failed: " . $e->getMessage());
}

?>