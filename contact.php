<?php
//セッションを開始
session_start();
//セッションIDを更新して変更（セッションハイジャック対策）
session_regenerate_id( TRUE );
//エスケープ処理やデータチェックを行う関数のファイルの読み込み
require './libs/functions.php';
//初回以外ですでにセッション変数に値が代入されていれば、その値を。そうでなければNULLで初期化
$name = isset( $_SESSION[ 'name' ] ) ? $_SESSION[ 'name' ] : NULL;
$email = isset( $_SESSION[ 'email' ] ) ? $_SESSION[ 'email' ] : NULL;
$email_check = isset( $_SESSION[ 'email_check' ] ) ? $_SESSION[ 'email_check' ] : NULL;
$tel = isset( $_SESSION[ 'tel' ] ) ? $_SESSION[ 'tel' ] : NULL;
$subject = isset( $_SESSION[ 'subject' ] ) ? $_SESSION[ 'subject' ] : NULL;
$body = isset( $_SESSION[ 'body' ] ) ? $_SESSION[ 'body' ] : NULL;
$error = isset( $_SESSION[ 'error' ] ) ? $_SESSION[ 'error' ] : NULL;
//個々のエラーを初期化
$error_name = isset( $error['name'] ) ? $error['name'] : NULL;
$error_email = isset( $error['email'] ) ? $error['email'] : NULL;
$error_email_check = isset( $error['email_check'] ) ? $error['email_check'] : NULL;
$error_tel = isset( $error['tel'] ) ? $error['tel'] : NULL;
$error_tel_format = isset( $error['tel_format'] ) ? $error['tel_format'] : NULL;
$error_subject = isset( $error['subject'] ) ? $error['subject'] : NULL;
$error_body = isset( $error['body'] ) ? $error['body'] : NULL;
//CSRF対策の固定トークンを生成
if ( !isset( $_SESSION[ 'ticket' ] ) ) {
  //セッション変数にトークンを代入
  $_SESSION[ 'ticket' ] = sha1( uniqid( mt_rand(), TRUE ) );
}
//トークンを変数に代入
$ticket = $_SESSION[ 'ticket' ];
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
  <h2>お問い合わせフォーム</h2>
  <p>以下のフォームからお問い合わせください。</p>
  <form id="main_contact" method="post" action="confirm.php">
    <div class="form-group">
      <label for="name">お名前（必須） 
        <span class="error"><?php echo h( $error_name ); ?></span>
      </label>
      <input type="text" class="form-control validate max50 required" id="name" name="name" placeholder="氏名" value="<?php echo h($name); ?>">
    </div>
    <div class="form-group">
      <label for="email">Email（必須） 
        <span class="error"><?php echo h( $error_email ); ?></span>
      </label>
      <input type="text" class="form-control validate mail required" id="email" name="email" placeholder="Email アドレス" value="<?php echo h($email); ?>">
    </div>
    <div class="form-group">
      <label for="email_check">Email（確認用 必須） 
        <span class="error"><?php echo h( $error_email_check ); ?></span>
      </label>
      <input type="text" class="form-control validate email_check required" id="email_check" name="email_check" placeholder="Email アドレス（確認のためもう一度ご入力ください。）" value="<?php echo h($email_check); ?>">
    </div>
    <div class="form-group">
      <label for="tel">お電話番号（半角英数字） 
        <span class="error"><?php echo h( $error_tel ); ?></span>
        <span class="error"><?php echo h( $error_tel_format ); ?></span>
      </label>
      <input type="text" class="validate max30 tel form-control" id="tel" name="tel" value="<?php echo h($tel); ?>" placeholder="お電話番号（半角英数字でご入力ください）">
    </div>
    <div class="form-group">
      <label for="subject">件名（必須） 
        <span class="error"><?php echo h( $error_subject ); ?></span> 
      </label>
      <input type="text" class="form-control validate max100 required" id="subject" name="subject" placeholder="件名" value="<?php echo h($subject); ?>">
    </div>
    <div class="form-group">
      <label for="body">お問い合わせ内容（必須） 
        <span class="error"><?php echo h( $error_body ); ?></span>
      </label>
      <span id="count"> </span>/1000
      <textarea class="form-control validate max1000 required" id="body" name="body" placeholder="お問い合わせ内容（1000文字まで）をお書きください" rows="3"><?php echo h($body); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">確認画面へ</button>
    <!--確認ページへトークンをPOSTする、隠しフィールド「ticket」-->
    <input type="hidden" name="ticket" value="<?php echo h($ticket); ?>">
  </form>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script> 
<script>
jQuery(function($){
  
  //エラーを表示する関数（error クラスの p 要素を追加して表示）
  function show_error(message, this$) {
    text = this$.parent().find('label').text() + message;
    this$.parent().append("<p class='error'>" + text + "</p>");
  }
  
  //フォームが送信される際のイベントハンドラの設定
  $("#main_contact").submit(function(){
    //エラー表示の初期化
    $("p.error").remove();
    $("div").removeClass("error");
    var text = "";
    $("#errorDispaly").remove();
    
    //メールアドレスの検証
    var email =  $.trim($("#email").val());
    if(email && !(/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/gi).test(email)){
      $("#email").after("<p class='error'>メールアドレスの形式が異なります</p>");
    }
    //確認用メールアドレスの検証
    var email_check =  $.trim($("#email_check").val());
    if(email_check && email_check != $.trim($("input[name="+$("#email_check").attr("name").replace(/^(.+)_check$/, "$1")+"]").val())){
      show_error("が異なります", $("#email_check"));
    }
    //電話番号の検証
    var tel = $.trim($("#tel").val());
    if(tel && !(/^\(?\d{2,5}\)?[-(\.\s]{0,2}\d{1,4}[-)\.\s]{0,2}\d{3,4}$/gi).test(tel)){
      $("#tel").after("<p class='error'>電話番号の形式が異なります（半角英数字でご入力ください）</p>");
    }
    
    //1行テキスト入力フォームとテキストエリアの検証
    $(":text,textarea").filter(".validate").each(function(){
      //必須項目の検証
      $(this).filter(".required").each(function(){
        if($(this).val()==""){
          show_error(" は必須項目です", $(this));
        }
      });
      //文字数の検証
      $(this).filter(".max30").each(function(){
        if($(this).val().length > 30){
          show_error(" は30文字以内です", $(this));
        }
      });
      $(this).filter(".max50").each(function(){
        if($(this).val().length > 50){
          show_error(" は50文字以内です", $(this));
        }
      });
      $(this).filter(".max100").each(function(){
        if($(this).val().length > 100){
          show_error(" は100文字以内です", $(this));
        }
      });
      //文字数の検証
      $(this).filter(".max1000").each(function(){
        if($(this).val().length > 1000){
          show_error(" は1000文字以内でお願いします", $(this));
        }
      });
    });
 
    //error クラスの追加の処理
    if($("p.error").length > 0){
      $("p.error").parent().addClass("error");
      $('html,body').animate({ scrollTop: $("p.error:first").offset().top-180 }, 'slow');
      return false;
    }
  });
  
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