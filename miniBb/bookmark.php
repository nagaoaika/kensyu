<?php
session_start();
require('dbconnect.php');
require('dbconnect2.php');

//メンバー情報を取得する
$sql = sprintf('SELECT * FROM members WHERE id=%d',
	mysqli_real_escape_string($db, $_SESSION['id'])
);
$record = mysqli_query($db, $sql) or die(mysqli_error($db));
$member = mysqli_fetch_assoc($record);

//お気に入り解除の場合
if (!empty($_POST)) {
	if ($_POST['post_id'] != '') {
			$sql = sprintf('DELETE from favorites WHERE post_id = %d AND member_id = %d',
			mysqli_real_escape_string($db, $_POST['post_id']),
			mysqli_real_escape_string($db, $_SESSION['id']));
	    mysqli_query($db, $sql) or die(mysqli_error($db));

  }
}

//投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);
// 最終ページを取得する
$sql = sprintf('SELECT COUNT(*) AS cnt FROM favorites WHERE member_id=%d',$_SESSION['id']);
$tables = $dbh->query($sql);
$table = $tables->fetch(PDO::FETCH_ASSOC);
$maxPage = ceil($table['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);

$stmt = $dbh -> query("SET NAMES utf8mb4;");
$sql = sprintf("SELECT f.*, m.name, m.picture, p.message, p.created_user_id, p.reply_post_id, p.created FROM favorites f LEFT JOIN (posts p LEFT JOIN members m ON p.created_user_id = m.id) ON f.post_id = p.id WHERE f.member_id=%d ORDER BY f.created_favorite DESC LIMIT %d, 5", $_SESSION['id'],$start);

 $favorites = $dbh->query($sql);

// $favorite = $favorites->fetchAll(PDO::FETCH_ASSOC);
// var_dump($favorite);
// $sql = sprintf('SELECT f.*, m.name, m.picture, p.message, p.created_user_id, p.reply_post_id, p.created FROM favorites f LEFT JOIN members m ON f.member_id = m.id LEFT JOIN posts p ON f.post_id = p.id');
// $favorites = mysqli_query($db, $sql) or die(mysqli_error($db));


// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
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
			<p>★<?php echo htmlspecialchars($member['name']); ?>さんのお気に入り★</p>
			<p>&laquo;<a href="index.php">一覧にもどる</a></p>
		</div>

<?php
  while($favorite = $favorites->fetch(PDO::FETCH_ASSOC)):
?>
  <div class="msg">
    <img src="member_picture/<?php echo h($favorite['picture']); ?>" width="48" height="48" alt="<?php echo h($favorite['name']); ?>" />
		<p><?php echo makeLink(h($favorite['message'])); ?><span class="name">（<?php echo h($favorite['name']); ?>）</span>[<a href="index.php?res=<?php echo h($favorite['post_id']); ?>">Re</a>]
		<form action="bookmark.php?page=<?php echo h($page);?>" method="post">
			<input type="hidden" name="member_id" value="<?php echo h($_SESSION['id']); ?>">
	    <input type="image" src="images/favorited.png" name="post_id" value="<?php echo h($favorite['post_id']); ?>" alt="button_image">
			</form>
		<p class="day"><a href="view.php?id=<?php echo h($favorite['post_id']); ?>"><?php echo h($favorite['created']); ?></a>

<?php
if ($favorite['reply_post_id'] > 0):
?>
<a href="view.php?id=<?php echo h($favorite['reply_post_id']);
?>">返信元のメッセージ</a>

<?php
endif;
?>

<?php
if ($_SESSION['id'] == $favorite['created_user_id']):
?>
[<a href="delete.php?id=<?php echo h($favorite['post_id']); ?>&access_source=bookmark" style="color: #F33;">削除</a>]
<?php
endif;
?>


</p>


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
