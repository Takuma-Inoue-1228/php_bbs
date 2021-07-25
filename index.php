<?php
session_start();
require('dbconnect.php');

// 1時間何もしない状態でログアウト
if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
  $_SESSION['time'] = time();

  $members = $db->prepare('SELECT * FROM members WHERE id=?');
  $members->execute(array($_SESSION['id']));
  $member = $members->fetch();
} else {
  header('Location: login.php');
  exit();
}

try {
  $db = new PDO('mysql:dbname=mini_bbs;host=localhost;charset=utf8', 'root', 'root');
} catch (PDOException $e) {
  print('DB接続エラー:' . $e->getMessage());
}

// 投稿ボタンが押された時
if (!empty($_POST)) {
  // テキストエリアが空でなければ
  if ($_POST['message'] !== '') {
    $message = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_message_id=?, created=NOW()');
    $message->execute(array(
      $member['id'],
      $_POST['message'],
      $_POST['reply_message_id']
    ));
    // 更新に夜重複を避けるためindex.phpの呼び出し
    header('Location: index.php');
  }
}

$posts = $db->query('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id ORDER BY p.created DESC');

// resに値が入っていれば->'re'がクリックされた時に
if (isset($_REQUEST['res'])) {
  // 以下返信処理
  $response = $db->prepare('SELECT m.name, m.picture, p.* FROM members m, posts p WHERE m.id=p.member_id AND p.id=?');
  $response->execute((array($_REQUEST['res'])));

  $table = $response->fetch();
  $message = '@' . $table['name'] . ' ' . $table['message'];
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>ひとこと掲示板</title>

  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div id="wrap">
    <div id="head">
      <h1>ひとこと掲示板</h1>
    </div>
    <div id="content">
      <div style="text-align: right"><a href="logout.php">ログアウト</a></div>
      <form action="" method="post">
        <dl>
          <dt><?php print(htmlspecialchars($member['name'], ENT_QUOTES)); ?>さん、メッセージをどうぞ</dt>
          <dd>
            <textarea name="message" cols="50" rows="5"><?php print(htmlspecialchars($message, ENT_QUOTES)); ?></textarea>
            <input type="hidden" name="reply_message_id" value="<?php print(htmlspecialchars($_REQUEST['res'], ENT_QUOTES)); ?>" />
          </dd>
        </dl>
        <div>
          <p>
            <input type="submit" value="投稿する" />
          </p>
        </div>
      </form>

      <?php foreach ($posts as $post) : ?>
        <div class="msg">
          <img src="member_picture/<?php print(htmlspecialchars($post['picture'], ENT_QUOTES)); ?>" width="48" height="48" alt="<?php print(htmlspecialchars($post['name'], ENT_QUOTES)); ?>" />
          <p><?php print(htmlspecialchars($post['message'], ENT_QUOTES)); ?><span class="name">（<?php print(htmlspecialchars($post['name'], ENT_QUOTES)); ?>）</span>[<a href="index.php?res=<?php print(htmlspecialchars($post['id'], ENT_QUOTES)); ?>">Re</a>]</p>
          <p class="day"><a href="view.php?id="><?php print(htmlspecialchars($post['created'], ENT_QUOTES)); ?></a>
            <a href="view.php?id=">
              返信元のメッセージ</a>
            [<a href="delete.php?id=" style="color: #F33;">削除</a>]
          </p>
        </div>
      <?php endforeach; ?>

      <ul class="paging">
        <li><a href="index.php?page=">前のページへ</a></li>
        <li><a href="index.php?page=">次のページへ</a></li>
      </ul>
    </div>
  </div>
</body>

</html>