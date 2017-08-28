<?php
session_start();
require('dbconnect.php');
require('dbconnect2.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$sql = sprintf('SELECT * FROM members WHERE id=%d',
		mysqli_real_escape_string($db, $_SESSION['id'])
	);
	$record = mysqli_query($db, $sql) or die(mysqli_error($db));
	$member = mysqli_fetch_assoc($record);
	} else {
	// ログインしていない
	header('Location: login.php');
	exit();
}

// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$sql = sprintf('INSERT INTO posts SET created_user_id=%d, message="%s", reply_post_id=%d, created=NOW()',
			mysqli_real_escape_string($db, $member['id']),
			mysqli_real_escape_string($db, $_POST['message']),
			mysqli_real_escape_string($db, $_POST['reply_post_id'])
		);
	mysqli_query($db, $sql) or die(mysqli_error($db));
			header('Location: indedx.php');
	exit();
	}
}

// 投稿を取得する

$page = $_REQUEST['page'];
if ($page == '') {
	$page = 1;
}
$page = max($page, 1);
// 最終ページを取得する
$sql = 'SELECT COUNT(*) AS cnt FROM posts';
$recordSet = mysqli_query($db, $sql);
$table = mysqli_fetch_assoc($recordSet);
$maxPage = ceil($table['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page - 1) * 5;
$start = max(0, $start);


$sql = sprintf('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.created_user_id ORDER BY p.created DESC LIMIT %d, 5',
	$start
);

$posts = mysqli_query($db, $sql) or die(mysqli_error($db));
// 返信の場合　
if (isset($_REQUEST['res'])) {
	$sql = sprintf('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.created_user_id AND p.id=%d ORDER BY p.created DESC',
		mysqli_real_escape_string($db, $_REQUEST['res'])
	);
	$record = mysqli_query($db, $sql) or die(mysqli_error($db));
	$table = mysqli_fetch_assoc($record);
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

//お気に入り登録 or 解除の場合
if (!empty($_POST)) {
	if ($_POST['post_id'] != '') {
		if($_POST['status_flag'] == 0){
			$sql = sprintf('INSERT INTO favorites SET post_id=%d, member_id=%d, created_favorite=NOW()',
			mysqli_real_escape_string($db, $_POST['post_id']),
			mysqli_real_escape_string($db, $_POST['member_id']));
		}else{
			$sql = sprintf('DELETE from favorites WHERE post_id = %d AND member_id = %d',
			mysqli_real_escape_string($db, $_POST['post_id']),
			mysqli_real_escape_string($db, $_POST['member_id']));
		}
	mysqli_query($db, $sql) or die(mysqli_error($db));
			header("Location: index.php?page=$page");
	exit();
	}
}
//お気に入情報を取得する
$sql = sprintf("SELECT post_id from favorites WHERE member_id =%d", $_SESSION['id']);
$favorites = $dbh->query($sql);
$favorite = $favorites->fetchAll(PDO::FETCH_UNIQUE);

// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定します
function makeLink($value) {
	return mb_ereg_replace("(https?)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>' , $value);
}
?>

<!DOCTYPE html >
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><link rel="stylesheet" type="text/css" href="style.css" />
<title>ひとこと掲示板</title>
</head>

<body>
<div id="wrap">
	<div id="head">
		<h1>ひとこと掲示板</h1>
	</div>
	<form action="bookmark.php" method="post">
		<input type="hidden" name="member_id" value="<?php echo h($_SESSION['id']); ?>">
	  <input type="submit" onclick="location.href='bookmark.php'"value="<?php echo htmlspecialchars($member['name']); ?>さんのお気に入り一覧">
  </form>
	<div id="content">
		<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
	<form action="" method="post">
		<dl>
			<dt><?php echo htmlspecialchars($member['name']); ?>さん、メッセージをどうぞ</dt>
			<dd>
			<textarea name="message" cols="50" rows="5"><?php echo h($message, ENT_QUOTES, 'UTF-8'); ?></textarea>
			<input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res'], ENT_QUOTES, 'UTF-8'); ?>" />
			</dd>
		</dl>
		<div>
		<p>
			<input type="submit" value="投稿する" />
		</p>
		</div>
	</form>

<?php
while($post = mysqli_fetch_assoc($posts)):
	$disabled = '';
	$status_flag = 0;
	$button_image = '';
	if(array_key_exists($post['id'],$favorite)){
		// $disabled = 'disabled';
		$status_flag = 1;
		$button_image = 'favorited.png';
	}else{
		$button_image = 'favorite.png';
	}
?>

	<div class="msg">
	<img src="member_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" />

	<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>[<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
  <form action="http://192.168.33.10/index.php?page=<?php echo h($page); ?>" method="post">
    <input type="hidden" name="member_id" value="<?php echo h($_SESSION['id']); ?>">
    <input type="image" src="images/<?php echo $button_image; ?>" style = "float:left;" name="post_id" value="<?php echo h($post['id']); ?>" alt="button_image">
		<input type="hidden" name="status_flag" value= <?=$status_flag?>>
	</form>
  <p class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>

<?php
if ($post['reply_post_id'] > 0):
?>
	<a href="view.php?id=<?php echo h($post['reply_post_id']);
?>">返信元のメッセージ</a>

<?php
endif;
?>

<?php
if ($_SESSION['id'] == $post['created_user_id']):
?>
	[<a href="delete.php?id=<?php echo h($post['id']); ?>&access_source=index" style="color: #F33;">削除</a>]
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
<li><a href="index.php?page=<?php print($page - 1); ?>">前のページへ
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
	<li><a href="index.php?page=<?php print($page + 1); ?>">次のページへ
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
