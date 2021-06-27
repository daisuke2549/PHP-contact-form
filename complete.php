<?php
//セッションを開始
session_start(); 
//エスケープ処理やデータをチェックする関数を記述したファイルの読み込み
require './libs/functions.php'; 
//メールアドレス等を記述したファイルの読み込み
require './libs/mailvars.php';
 
//お問い合わせ日時を日本時間に
date_default_timezone_set('Asia/Tokyo'); 
 
//POSTされたデータをチェック
$_POST = checkInput( $_POST );
//固定トークンを確認（CSRF対策）
if ( isset( $_POST[ 'ticket' ], $_SESSION[ 'ticket' ] ) ) {
  $ticket = $_POST[ 'ticket' ];
  if ( $ticket !== $_SESSION[ 'ticket' ] ) {
    //トークンが一致しない場合は処理を中止
    die( 'Access denied' );
  }
} else {
  //トークンが存在しない場合（入力ページにリダイレクト）
  //die( 'Access Denied（直接このページにはアクセスできません）' );  //処理を中止する場合
  $dirname = dirname( $_SERVER[ 'SCRIPT_NAME' ] );
  $dirname = $dirname == DIRECTORY_SEPARATOR ? '' : $dirname;
  $url = ( empty( $_SERVER[ 'HTTPS' ] ) ? 'http://' : 'https://' ) . $_SERVER[ 'SERVER_NAME' ] . $dirname . '/contact.php';
  header( 'HTTP/1.1 303 See Other' );
  header( 'location: ' . $url );
  exit; //忘れないように
}
 
//変数にエスケープ処理したセッション変数の値を代入
$name = h( $_SESSION[ 'name' ] );
$email = h( $_SESSION[ 'email' ] ) ;
$tel =  h( $_SESSION[ 'tel' ] ) ;
$subject = h( $_SESSION[ 'subject' ] );
$body = h( $_SESSION[ 'body' ] );
 
//メール本文の組み立て
$mail_body = 'コンタクトページからのお問い合わせ' . "\n\n";
$mail_body .=  date("Y年m月d日 H時i分") . "\n\n"; 
$mail_body .=  "お名前： " .$name . "\n";
$mail_body .=  "Email： " . $email . "\n"  ;
$mail_body .=  "お電話番号： " . $tel . "\n\n" ;
$mail_body .=  "＜お問い合わせ内容＞" . "\n" . $body;
  
//-------- sendmail（mb_send_mail）を使ったメールの送信処理------------
 
//メールの宛先（名前<メールアドレス> の形式）。値は mailvars.php に記載
$mailTo = mb_encode_mimeheader(MAIL_TO_NAME) ."<" . MAIL_TO. ">";
 
//Return-Pathに指定するメールアドレス
$returnMail = MAIL_RETURN_PATH; //
//mbstringの日本語設定
mb_language( 'ja' );
mb_internal_encoding( 'UTF-8' );
 
// 送信者情報（From ヘッダー）の設定
$header = "From: " . mb_encode_mimeheader($name) ."<" . $email. ">\n";
$header .= "Cc: " . mb_encode_mimeheader(MAIL_CC_NAME) ."<" . MAIL_CC.">\n";
$header .= "Bcc: <" . MAIL_BCC.">";
 
//メールの送信（結果を変数 $result に格納）
if ( ini_get( 'safe_mode' ) ) {
  //セーフモードがOnの場合は第5引数が使えない
  $result = mb_send_mail( $mailTo, $subject, $mail_body, $header );
} else {
  $result = mb_send_mail( $mailTo, $subject, $mail_body, $header, '-f' . $returnMail );
}
 
//メール送信の結果判定
if ( $result ) {
  //成功した場合はセッションを破棄
  $_SESSION = array(); //空の配列を代入し、すべてのセッション変数を消去 
  session_destroy(); //セッションを破棄
  
  //自動返信メールの送信処理
  //自動返信メールの送信が成功したかどうかのメッセージを表示する場合は true
  $show_autoresponse_msg = true;
  //ヘッダー情報
  $ar_header = "MIME-Version: 1.0\n";
  $ar_header .= "From: " . mb_encode_mimeheader( AUTO_REPLY_NAME ) . " <" . MAIL_TO . ">\n";
  $ar_header .= "Reply-To: " . mb_encode_mimeheader( AUTO_REPLY_NAME ) . " <" . MAIL_TO . ">\n";
  //件名
  $ar_subject = 'お問い合わせ自動返信メール';
  //本文
  $ar_body = $name." 様\n\n";
  $ar_body .= "この度は、お問い合わせ頂き誠にありがとうございます。" . "\n\n";
  $ar_body .= "下記の内容でお問い合わせを受け付けました。\n\n";
  $ar_body .= "お問い合わせ日時：" . date("Y-m-d H:i") . "\n";
  $ar_body .= "お名前：" . $name . "\n";
  $ar_body .= "メールアドレス：" . $email . "\n";
  $ar_body .= "お電話番号： " . $tel . "\n\n" ;
  $ar_body .="＜お問い合わせ内容＞" . "\n" . $body;
  
  //自動返信の送信（結果を変数 result2 に格納）
  if ( ini_get( 'safe_mode' ) ) {
    $result2 = mb_send_mail( $email, $ar_subject, $ar_body , $ar_header  );
  } else {
    $result2 = mb_send_mail( $email, $ar_subject, $ar_body , $ar_header , '-f' . $returnMail );
  }
} else {
  //送信失敗時（もしあれば）
}
 
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>コンタクトフォーム（完了）</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link href="./styles.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <h2>お問い合わせフォーム</h2>
  <?php if ( $result ): ?>
  <h3>送信完了!</h3>
  <p>お問い合わせいただきありがとうございます。</p>
  <p>送信完了いたしました。</p>
    <?php if ( $show_autoresponse_msg ): ?>
      <?php if ( $result2 ): ?>
      <p>確認の自動返信メールを <?php echo $email; ?> へお送りいたしました。</p>
      <?php else: ?>
      <p>確認の自動返信メールを送信できませんでした。</p>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
  <p>申し訳ございませんが、送信に失敗しました。</p>
  <p>しばらくしてもう一度お試しになるか、メールにてご連絡ください。</p>
  <p>ご迷惑をおかけして誠に申し訳ございません。</p>
  <?php endif; ?>
</div>
</body>
</html>