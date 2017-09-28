<?php
//セッションの再開
session_start();

//データベース接続に関するファイルの読み込み
require('dbconnect.php');//mysqli接続
require('dbconnect2.php');//PDO接続

//user情報を取得する
$sql = sprintf('SELECT * FROM users WHERE id=%d',
  mysqli_real_escape_string($db, $_SESSION['id'])
);
$record = mysqli_query($db, $sql) or die(mysqli_error($db));
$user = mysqli_fetch_assoc($record);

//お気に入り解除の場合
if (!empty($_POST)) {
  // if ($_POST['post_id'] != '') {
	//   $sql = sprintf('DELETE from user_favorite_posts WHERE post_id = %d AND user_id = %d',
	// 	  mysqli_real_escape_string($db, $_POST['post_id']),
	// 		mysqli_real_escape_string($db, $_SESSION['id']));
	// 	mysqli_query($db, $sql) or die(mysqli_error($db));//mysqliで記述

  $sql = "DELETE from user_favorite_posts WHERE post_id = :post_id AND user_id = :user_id";
  $stmt = $dbh->prepare($sql);
  $params = array(':post_id'=>$_POST['post_id'],':user_id'=>$_SESSION['id']);
  $flag = $stmt->execute($params);//PDO接続
  if ($flag){
    print('お気に入りの削除に成功しました<br>');
  }else{
    print('お気に入りの削除に失敗しました<br>');
  }
}

//投稿を取得する
//ページの取得
$page = $_REQUEST['page'];
if ($page == '') {
  $page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$sql = sprintf('SELECT COUNT(*) AS cnt FROM user_favorite_posts WHERE user_id=%d',$_SESSION['id']);
$tables = $dbh->query($sql);
$table = $tables->fetch(PDO::FETCH_ASSOC);
$maxPage = ceil($table['cnt'] / 5);
$page = min($page, $maxPage);

//表示する投稿の取得
$start = ($page - 1) * 5;
$start = max(0, $start);

$stmt = $dbh -> query("SET NAMES utf8mb4;");//文字コードの設定
$sql = sprintf("SELECT f.id, f.post_id, u.name, u.picture, p.message, p.user_id, p.reply_post_id, p.created, p.modified FROM user_favorite_posts f LEFT JOIN (posts p LEFT JOIN users u ON p.user_id = u.id) ON f.post_id = p.id WHERE f.user_id=%d ORDER BY f.created DESC LIMIT %d, 5", $_SESSION['id'],$start);
$favorites = $dbh->query($sql);


// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定
function makeLink($value) {
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<title>お気に入り一覧</title>
</head>
<body>
<div id="wrap">
	<div id="head">
		<h1>お気に入り一覧</h1>
	</div>

	<div id="content">
		<div id="lead">
			<p>★<?php echo h($user['name']); ?>さんのお気に入り★</p>
			<p>&laquo;<a href="index.php">一覧にもどる</a></p>
		</div>

    <?php
      while($favorite = $favorites->fetch(PDO::FETCH_ASSOC)):
    ?>
    <div class="msg">
      <img src="user_picture/<?php echo h($favorite['picture']); ?>" width="48" height="48" alt="<?php echo h($favorite['name']); ?>" />
	  	<p><span class="name">　<?php echo h($favorite['name']); ?>　</span><span class="day"><a href="view.php?id=<?php echo h($favorite['post_id']); ?>"><?php echo h($favorite['created']); ?></a></span></p>
      <p style="margin-left:70px;"><?php echo makeLink(h($favorite['message'])); ?></p>
      <div class="post_bottom" style="margin-left:70px;">
      [<a href="index.php?res=<?php echo h($favorite['post_id']); ?>">Re</a>]
        <?php
          if ($favorite['reply_post_id'] > 0):
        ?>
        　<a href="view.php?id=<?php echo h($favorite['reply_post_id']);?>">返信元のメッセージ</a>
        <?php
          endif;
        ?>
        <?php
          if ($_SESSION['id'] == $favorite['user_id']):
        ?>
      　  [<a href="delete.php?id=<?php echo h($favorite['post_id']); ?>&access_source=bookmark" style="color: #F33;">削除</a>]
        <?php
          endif;
        ?>
        <form action="bookmark.php?page=<?php echo h($page);?>" method="post">
  	      <input type="submit" name="post_id" value="<?php echo h($favorite['post_id']); ?>" class="favorited">
  	  	</form>
      </div>
    </div>

    <?php
      endwhile;
    ?>
    <ul class="paging">
      <?php
        if ($page > 1) {
      ?>
      <li><a href="bookmark.php?page=<?php print($page - 1); ?>">前のページへ
      </a></li>
      <?php
        } else {
      ?>
      <li>前のページへ</li>
      <?php
        }
      ?>
      <?php
        if ($page < $maxPage) {
      ?>
	    <li><a href="bookmark.php?page=<?php print($page + 1); ?>">次のページへ
      </a></li>
      <?php
        } else {
      ?>
	    <li>次のページへ</li>
      <?php
        }
      ?>
    </ul>
	</div>
	<div id="foot">
		<p><img src="images/txt_copyright.png" width="136" 	height="15" alt="(C) H2O SPACE, Mynavi" /></p>
	</div>
</div>
</body>
</html>
