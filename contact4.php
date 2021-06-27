<?php 
//エスケープ処理やデータチェックを行う関数のファイルの読み込み
require './libs/functions.php';
require './libs/mailvars.php';
//お問い合わせ日時を日本時間に
date_default_timezone_set('Asia/Tokyo'); 
 
//POSTされたデータを変数に格納
$name = isset( $_POST[ 'name' ] ) ? $_POST[ 'name' ] : NULL;
$email = isset( $_POST[ 'email' ] ) ? $_POST[ 'email' ] : NULL;
$email_check = isset( $_POST[ 'email_check' ] ) ? $_POST[ 'email_check' ] : NULL;
$tel = isset( $_POST[ 'tel' ] ) ? $_POST[ 'tel' ] : NULL;
$subject = isset( $_POST[ 'subject' ] ) ? $_POST[ 'subject' ] : NULL;
$body = isset( $_POST[ 'body' ] ) ? $_POST[ 'body' ] : NULL;
 
//POSTされたデータを整形（前後にあるホワイトスペースを削除）
$name = trim( $name );
$email = trim( $email );
$email_check = trim( $email_check );
$tel = trim( $tel );
$subject = trim( $subject );
$body = trim( $body );
 
if (isset($_POST['submitted'])) {
 
  //POSTされたデータをチェック  
  $_POST = checkInput( $_POST ); 
 
  //エラーメッセージを保存する配列の初期化
  $error = array();
  
  //値の検証
  if ( $name == '' ) {
    $error['name'] = '*お名前は必須項目です。';
    //制御文字でないことと文字数をチェック
  } else if ( preg_match( '/\A[[:^cntrl:]]{1,30}\z/u', $name ) == 0 ) {
    $error['name'] = '*お名前は30文字以内でお願いします。';
  }
  if ( $email == '' ) {
    $error['email'] = '*メールアドレスは必須です。';
  } else { //メールアドレスを正規表現でチェック
    $pattern = '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/uiD';
    if ( !preg_match( $pattern, $email ) ) {
      $error['email'] = '*メールアドレスの形式が正しくありません。';
    }
  }
  if ( $email_check == '' ) {
    $error['email_check'] = '*確認用メールアドレスは必須です。';
  } else { //メールアドレスを正規表現でチェック
    if ( $email_check !== $email ) {
      $error['email_check'] = '*メールアドレスが一致しません。';
    }
  }
  if ( preg_match( '/\A[[:^cntrl:]]{0,30}\z/u', $tel ) == 0 ) {
    $error['tel'] = '*電話番号は30文字以内でお願いします。';
  }
  if ( $tel != '' && preg_match( '/\A\(?\d{2,5}\)?[-(\.\s]{0,2}\d{1,4}[-)\.\s]{0,2}\d{3,4}\z/u', $tel ) == 0 ) {
    $error['tel_format'] = '*電話番号の形式が正しくありません。';
  }
  if ( $subject == '' ) {
    $error['subject'] = '*件名は必須項目です。';
    //制御文字でないことと文字数をチェック
  } else if ( preg_match( '/\A[[:^cntrl:]]{1,100}\z/u', $subject ) == 0 ) {
    $error['subject'] = '*件名は100文字以内でお願いします。';
  }
  if ( $body == '' ) {
    $error['body'] = '*内容は必須項目です。';
    //制御文字（タブ、復帰、改行を除く）でないことと文字数をチェック
  } else if ( preg_match( '/\A[\r\n\t[:^cntrl:]]{1,1050}\z/u', $body ) == 0 ) {
    $error['body'] = '*内容は1000文字以内でお願いします。';
  }
  
  //エラーがなく且つ POST でのリクエストの場合
  if (empty($error) && $_SERVER['REQUEST_METHOD']==='POST') {
    //メールアドレス等を記述したファイルの読み込み
    require '../libs/mailvars.php'; 
 
    //メール本文の組み立て
    $mail_body = 'コンタクトページからのお問い合わせ' . "\n\n";
    $mail_body .=  date("Y年m月d日 D H時i分") . "\n\n";
    $mail_body .=  "お名前： " .h($name) . "\n";
    $mail_body .=  "Email： " . h($email) . "\n"  ;
    $mail_body .=  "お電話番号： " . h($tel) . "\n\n" ;
    $mail_body .=  "＜お問い合わせ内容＞" . "\n" . h($body);
 
    //--------sendmail------------
 
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
 
    //メールの送信（メールの送信結果を変数 $result に代入） 
    //セーフモードがOnの場合は第5引数が使えない
    if ( ini_get( 'safe_mode' ) ) {
      $result = mb_send_mail( $mailTo, $subject, $mail_body, $header );
    } else {
      $result = mb_send_mail( $mailTo, $subject, $mail_body, $header, '-f' . $returnMail );
    }
    
    //メール送信の結果判定
    if ( $result ) {
      
      //自動返信メール
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
      
      //自動返信メールの送信（自動返信メールの送信結果を変数 $result2 に代入） 
      if ( ini_get( 'safe_mode' ) ) {
        //セーフモードがOnの場合は第5引数が使えない
        $result2 = mb_send_mail( $email, $ar_subject, $ar_body , $ar_header  );
      } else {
        $result2 = mb_send_mail( $email, $ar_subject, $ar_body , $ar_header , '-f' . $returnMail );
      }
      
      $_POST = array(); //空の配列を代入し、すべてのPOST変数を消去
      //変数の値も初期化
      $name = '';
      $email = '';
      $email_check = '';
      $tel = '';
      $subject = '';
      $body = '';
      
      //再読み込みによる二重送信の防止
      $params = '?result='. $result .'&result2=' . $result2;
      $url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']; 
      header('Location:' . $url . $params);
      exit;
    } 
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>コンタクトフォーム</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<link href="./styles.css" rel="stylesheet">
</head>
<body>
<div class="container">
  <h2 class="">お問い合わせフォーム</h2>
  <?php  if ( isset($_GET['result']) && $_GET['result'] ) : ?>
  <h4>送信完了!</h4>
  <p>送信完了いたしました。</p>
  <?php  if ( isset($_GET['result2']) && $_GET['result2'] ) : ?>
  <p>確認の自動返信メールをお送りいたしました。</p>
  <?php elseif (isset($_GET['result2']) && !$_GET['result2']): ?>
  <p>確認の自動返信メールが送信できませんでした。</p>
  <?php endif; ?>
  <hr>
  <?php elseif (isset($result) && !$result ): ?>
  <h4>送信失敗</h4>
  <p>申し訳ございませんが、送信に失敗しました。</p>
  <p>しばらくしてもう一度お試しになるか、メールにてご連絡ください。</p>
  <p>メール：<a href="mailto:info@example.com">Contact</a></p>
  <hr>
  <?php endif; ?>
  <p>以下のフォームからお問い合わせください。</p>
  <form id="form" method="post">
    <div class="form-group">
      <label for="name">お名前（必須） 
        <span class="error"><?php if ( isset( $error['name'] ) ) echo h( $error['name'] ); ?></span>
      </label>
      <input type="text" class="form-control validate max50 required" id="name" name="name" placeholder="氏名" value="<?php echo h($name); ?>">
    </div>
    <div class="form-group">
      <label for="email">Email（必須） 
        <span class="error"><?php if ( isset( $error['email'] ) ) echo h( $error['email'] ); ?></span>
      </label>
      <input type="text" class="form-control validate mail required" id="email" name="email" placeholder="Email アドレス" value="<?php echo h($email); ?>">
    </div>
    <div class="form-group">
      <label for="email_check">Email（確認用 必須） 
        <span class="error"><?php if ( isset( $error['email_check'] ) ) echo h( $error['email_check'] ); ?></span>
      </label>
      <input type="text" class="form-control validate email_check required" id="email_check" name="email_check" placeholder="Email アドレス（確認のためもう一度ご入力ください。）" value="<?php echo h($email_check); ?>">
    </div>
    <div class="form-group">
      <label for="tel">お電話番号（半角英数字） 
        <span class="error"><?php if ( isset( $error['tel'] ) ) echo h( $error['tel'] ); ?></span>
        <span class="error"><?php if ( isset( $error['tel_format'] ) ) echo '<br>'. h( $error['tel_format'] ); ?></span>
      </label>
      <input type="text" class="validate max30 tel form-control" id="tel" name="tel" value="<?php echo h($tel); ?>" placeholder="お電話番号（半角英数字でご入力ください）">
    </div>
    <div class="form-group">
      <label for="subject">件名（必須） 
        <span class="error"><?php if ( isset( $error['subject'] ) ) echo h( $error['subject'] ); ?></span> 
      </label>
      <input type="text" class="form-control validate max100 required" id="subject" name="subject" placeholder="件名" value="<?php echo h($subject); ?>">
    </div>
    <div class="form-group">
      <label for="body">お問い合わせ内容（必須） 
        <span class="error"><?php if ( isset( $error['body'] ) ) echo h( $error['body'] ); ?></span>
      </label>
      <span id="count"> </span>/1000
      <textarea class="form-control validate max1000 required" id="body" name="body" placeholder="お問い合わせ内容（1000文字まで）をお書きください" rows="3"><?php echo h($body); ?></textarea>
    </div>
    <button name="submitted" type="submit" class="btn btn-primary">送信</button>
  </form>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script> 
<script>
jQuery(function($){
  
  function show_error(message, this$) {
    text = this$.parent().find('label').text() + message;
    this$.parent().append("<p class='error'>" + text + "</p>")
  }
  
  $("form#form").submit(function(){  
    //エラー表示の初期化
    $("p.error").remove();
    $("div").removeClass("error");
    var text = "";
    $("#errorDispaly").remove();
    
    //メールアドレスの検証
    var email =  $.trim($("#email").val());
    if(email && !(/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/gi).test(email)){
      $("#email").after("<p class='error'>メールアドレスの形式が異なります</p>")
    }
    //確認用メールアドレスの検証
    var email_check =  $.trim($("#email_check").val());
    if(email_check && email_check != $.trim($("input[name="+$("#email_check").attr("name").replace(/^(.+)_check$/, "$1")+"]").val())){
      show_error("が一致しません", $("#email_check"));
    }
    //電話番号の検証
    var tel = $.trim($("#tel").val());
    if(tel && !(/^\(?\d{2,5}\)?[-(\.\s]{0,2}\d{1,4}[-)\.\s]{0,2}\d{3,4}$/gi).test(tel)){
      $("#tel").after("<p class='error'>電話番号の形式が異なります（半角英数字でご入力ください）</p>")
    }
    
    //1行テキスト入力フォームとテキストエリアの検証
    $(":text,textarea").filter(".validate").each(function(){
      
      $(this).filter(".required").each(function(){
        if($(this).val()==""){
          show_error(" は必須項目です", $(this));
        }
      })
        
      //文字数の検証
      $(this).filter(".max30").each(function(){
        if($(this).val().length > 30){
          show_error(" は30文字以内です", $(this));
        }
      })
      $(this).filter(".max50").each(function(){
        if($(this).val().length > 50){
          show_error(" は50文字以内です", $(this));
        }
      })
      //文字数の検証
      $(this).filter(".max100").each(function(){
        if($(this).val().length > 100){
          show_error(" は100文字以内です", $(this));
        }
      })
      //文字数の検証
      $(this).filter(".max1000").each(function(){
        if($(this).val().length > 1000){
          show_error(" は1000文字以内でお願いします", $(this));
        }
      })
    })
    //error クラスの追加の処理
    if($("p.error").length > 0){
      $("p.error").parent().addClass("error");
      $('html,body').animate({ scrollTop: $("p.error:first").offset().top-180 }, 'slow');
      return false;
    }
  }) //ここまでが submit イベントを使った検証
  //テキストエリアに入力された文字数を表示
  $("textarea").on('keydown keyup change', function() {
    var count = $(this).val().length;
    $("#count").text(count);
    if(count > 1000) {
      $("#count").css({color: 'red', fontWeight: 'bold'});
    }else{
      $("#count").css({color: '#333', fontWeight: 'normal'});
    }
  });
})
</script>
</body>
</html>
