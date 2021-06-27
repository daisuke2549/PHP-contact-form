<?php
//セッションを開始
session_start();
//エスケープ処理やデータをチェックする関数を記述したファイルの読み込み
require './libs/functions.php';
//メールアドレス等を記述したファイルの読み込み
require './libs/mailvars.php';
 
//reCAPTCHA サイトキーを記述したファイルの読み込み（★追加）
require './libs/recaptchavars.php';
// reCAPTCHA サイトキー（★追加）
$siteKey = V3_SITEKEY;
// reCAPTCHA シークレットキー（★追加）
$secretKey = V3_SECRETKEY;
 
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
 
//reCAPTCHA トークン（★追加）
$token = isset( $_POST[ 'g-recaptcha-response' ] ) ? $_POST[ 'g-recaptcha-response' ] : NULL;
//reCAPTCHA アクション名 （★追加）
$action = isset( $_POST[ 'action' ] ) ? $_POST[ 'action' ] : NULL;
//reCAPTCHA の検証を通過したかどうかの真偽値（★追加）
$rcv3_result = false;
 
// reCAPTCHA のトークンとアクション名が取得できていれば（★追加）
if ( $token && $action ) {
 
  //cURL セッションを初期化（API のレスポンスの取得）
  $ch = curl_init();
  // curl_setopt() により転送時のオプションを設定
  //URL の指定
  curl_setopt( $ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify" );
  //HTTP POST メソッドを使う
  curl_setopt( $ch, CURLOPT_POST, true );
  //API パラメータの指定
  curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( array(
    'secret' => $secretKey,
    'response' => $token
  ) ) );
  //curl_execの返り値を文字列にする
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
  //転送を実行してレスポンスを $api_response に格納
  $api_response = curl_exec( $ch );
  //セッションを終了
  curl_close( $ch );
 
  //レスポンスの $json（JSON形式）をデコード
  $rc_result = json_decode( $api_response );
 
  //レスポンスの値を判定
  if ( $rc_result->success && $rc_result->action === $action && $rc_result->score >= 0.5 ) {
    //success が true でアクション名が一致し、スコアが 0.5 以上の場合は合格
    $rcv3_result = true;
  } else {
    // 上記以外の場合は 不合格
    $rcv3_result = false;
  }
}
 
//メールの送信結果の初期値を false に
$result = true;
 
//reCAPTCHA の検証結果が合格の場合はメール送信処理を実行
if ( $rcv3_result ) { //（★追加）
 
  //お問い合わせ日時を日本時間に
  date_default_timezone_set( 'Asia/Tokyo' );
 
  //変数にエスケープ処理したセッション変数の値を代入
  $name = h( $_SESSION[ 'name' ] );
  $email = h( $_SESSION[ 'email' ] );
  $tel = h( $_SESSION[ 'tel' ] );
  $subject = h( $_SESSION[ 'subject' ] );
  $body = h( $_SESSION[ 'body' ] );
 
  //メール本文の組み立て
  $mail_body = 'コンタクトページからのお問い合わせ' . "\n\n";
  $mail_body .= date( "Y年m月d日 H時i分" ) . "\n\n";
  $mail_body .= "お名前： " . $name . "\n";
  $mail_body .= "Email： " . $email . "\n";
  $mail_body .= "お電話番号： " . $tel . "\n\n";
  $mail_body .= "＜お問い合わせ内容＞" . "\n" . $body;
 
  //-------- sendmail（mb_send_mail）を使ったメールの送信処理------------
 
  //メールの宛先（名前<メールアドレス> の形式）。値は mailvars.php に記載
  $mailTo = mb_encode_mimeheader( MAIL_TO_NAME ) . "<" . MAIL_TO . ">";
 
  //Return-Pathに指定するメールアドレス
  $returnMail = MAIL_RETURN_PATH; //
  //mbstringの日本語設定
  mb_language( 'ja' );
  mb_internal_encoding( 'UTF-8' );
 
  // 送信者情報（From ヘッダー）の設定
  $header = "From: " . mb_encode_mimeheader( $name ) . "<" . $email . ">\n";
  $header .= "Cc: " . mb_encode_mimeheader( MAIL_CC_NAME ) . "<" . MAIL_CC . ">\n";
  $header .= "Bcc: <" . MAIL_BCC . ">";
 
  //メールの送信（結果を変数 $result に格納）
  if ( ini_get( 'safe_mode' ) ) {
    //セーフモードがOnの場合は第5引数が使えない
    $result = mb_send_mail( $mailTo, $subject, $mail_body, $header );
  } else {
    $result = mb_send_mail( $mailTo, $subject, $mail_body, $header, '-f' . $returnMail );
  }
}
 
//メール送信の結果で分岐
if ( $result ) {
  //成功した場合はセッションを破棄
  $_SESSION = array(); //空の配列を代入し、すべてのセッション変数を消去 
  session_destroy(); //セッションを破棄
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
  <!-- <?php if ( $result ): ?> -->
  <h3>送信完了!</h3>
  <p>お問い合わせいただきありがとうございます。</p>
  <p>送信完了いたしました。</p>
  <?php else: ?>
  <p>申し訳ございませんが、送信に失敗しました。</p>
  <p>しばらくしてもう一度お試しになるか、メールにてご連絡ください。</p>
  <p>ご迷惑をおかけして誠に申し訳ございません。</p>
  <?php endif; ?>
  
  <!-- ここから reCAPTCHA 結果表示（テスト用）-->
  <?php if (isset($rc_result )): ?>
  <h4 style="margin: 20px 0;">reCAPTCHA 判定結果表示</h4>
  <ul>
    <li><?php echo 'success 判定 ：' . $rc_result->success; ?></li>
    <li><?php echo 'アクション名 ： ' . $rc_result->action ?></li>
    <li><?php echo 'スコア ： ' . $rc_result->score; ?></li>
  </ul>
  <h4 style="margin: 20px 0;">reCAPTCHA API レスポンス</h4>
  <pre><?php var_dump($rc_result ); ?></pre>
  <?php endif; ?>
  <!-- ここまで reCAPTCHA 結果表示（テスト用）--> 
  
</div>
</body>
</html>