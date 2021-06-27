<?php
  mb_language("ja");
  mb_internal_encording("UTF-8");
  mb_send_mail("hoge@fuga.com", "表題subject", "本文の内容\r\n次の行");
?>