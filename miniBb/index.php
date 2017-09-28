<?php
//セッションの再開
session_start();

//データベース接続設定の取得
require('dbconnect.php');
require('dbconnect2.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
	// ログインしている
	$_SESSION['time'] = time();

	$sql = sprintf('SELECT * FROM users WHERE id=%d',
		mysqli_real_escape_string($db, $_SESSION['id'])
	);
	$record = mysqli_query($db, $sql) or die(mysqli_error($db));
	$user = mysqli_fetch_assoc($record);
	} else {
	// ログインしていない
	header('Location: login.php');
	exit();
}

// 投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		$sql = sprintf('INSERT INTO posts SET user_id=%d, message="%s", reply_post_id=%d, created=NOW()',
			mysqli_real_escape_string($db, $user['id']),
			mysqli_real_escape_string($db, $_POST['message']),
			mysqli_real_escape_string($db, $_POST['reply_post_id'])
		);
	mysqli_query($db, $sql) or die(mysqli_error($db));
	header('Location: index.php');
	exit();
	}
}

// 投稿を取得する
//ページの取得
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

//表示する投稿の取得
$sql = sprintf('SELECT u.name, u.picture, p.* FROM users u, posts p WHERE u.id=p.user_id ORDER BY p.created DESC LIMIT %d, 5',
	$start
);

$posts = mysqli_query($db, $sql) or die(mysqli_error($db));

// 返信の場合　
if (isset($_REQUEST['res'])) {
	$sql = sprintf('SELECT u.name, u.picture, p.* FROM users u, posts p WHERE u.id=p.user_id AND p.id=%d ORDER BY p.created DESC',
		mysqli_real_escape_string($db, $_REQUEST['res'])
	);
	$record = mysqli_query($db, $sql) or die(mysqli_error($db));
	$table = mysqli_fetch_assoc($record);
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

//お気に入り登録 or 解除の場合
if (!empty($_POST)) {
	if ($_POST['post_id'] != '') {
		$state='';//作業の状態
		if($_POST['status_flag'] == 0){
			$sql = "INSERT INTO user_favorite_posts SET post_id = :post_id, user_id = :user_id, created=NOW()";
			$stmt = $dbh->prepare($sql);
			$params = array(':post_id'=>$_POST['post_id'],':user_id'=>$_SESSION['id']);
			$state = '登録';
		}else{
			$sql = "DELETE from user_favorite_posts WHERE post_id = :post_id AND user_id = :user_id";
			$stmt = $dbh->prepare($sql);
			$params = array(':post_id'=>$_POST['post_id'],':user_id'=>$_SESSION['id']);
			$state = '削除';
		}
		$flag = $stmt->execute($params);
		if ($flag){
			print("データの$stateに成功しました<br>");
		}else{
			print("データの$stateに失敗しました<br>");
		}
		header("Location: index.php?page=$page");
	  exit();
	}
}

//お気に入り情報を取得する
$sql = sprintf("SELECT post_id from user_favorite_posts WHERE user_id =%d", $_SESSION['id']);
$favorites = $dbh->query($sql);
$favorite = $favorites->fetchAll(PDO::FETCH_UNIQUE);

// htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// 本文内のURLにリンクを設定
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
	<div id="content">
		<a href="bookmark.php" class="square_btn">★<?php echo htmlspecialchars($user['name']); ?>さんのお気に入り一覧</a>
		<div style="text-align: right"><a href="logout.php">ログアウト</a></div>
	  <form action="" method="post">
	  	<dl>
		    <dt><?php echo htmlspecialchars($user['name']); ?>さん、メッセージをどうぞ</dt>
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
		  //投稿の表示。
      while($post = mysqli_fetch_assoc($posts)):
      	$disabled = '';
	      $status_flag = 0;
	      $favorite_class = '';
				//お気に入りの判別
	      if(array_key_exists($post['id'],$favorite)){
	       	$status_flag = 1;
	       	$favorite_class = 'favorited';
	      }else{
	    	  $favorite_class = 'star';
      	}
    ?>

	  <div class="msg">
	    <img src="user_picture/<?php echo h($post['picture']); ?>" width="48" height="48" alt="<?php echo h($post['name']); ?>" /><!--ユーザーの写真-->
      <p><span class="name">　<?php echo h($post['name']); ?>　</span><span class="day"><a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a></span></p><!--名前と日付-->
			<p style="margin-left:70px;"><?php echo makeLink(h($post['message'])); ?><!--本文-->
      <div class="post_bottom" style="margin-left:70px;">
        [<a href="index.php?res=<?php echo h($post['id']); ?>">Re</a>]
        <?php
          if ($post['reply_post_id'] > 0):
        ?>
	      　<a href="view.php?id=<?php echo h($post['reply_post_id']);?>">返信元のメッセージ</a>
        <?php
          endif;
        ?>
        <?php
          if ($_SESSION['id'] == $post['user_id']):
        ?>
      	　[<a href="delete.php?id=<?php echo h($post['id']); ?>&access_source=index" style="color: #F33;">削除</a>]
        <?php
          endif;
        ?>
				<form action="http://192.168.33.10/index.php?page=<?php echo h($page); ?>" method="post">
					<input type="hidden" name="status_flag" value= <?=$status_flag?>>
					<input type="submit" name="post_id" value="<?php echo h($post['id']); ?>" class="<?php echo h($favorite_class); ?>">
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
