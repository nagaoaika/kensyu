<?php
$dsn = 'mysql:dbhost=localhost;dbname=mini_bbs;charset=utf8mb4';
$user = 'root';
$password = 'root';
try {
  $dbh = new PDO($dsn, $user, $password);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e)  {
  echo 'データベースにアクセスできません！' . $e->getMessage();

  exit;
}
 ?>
