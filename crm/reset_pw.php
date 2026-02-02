<?php
  include_once '../gnuboard5/common.php';

  $new_password = 'sunil1234!';  // 
  $mb_id = 'admin';  // 

  $hash = get_encrypt_string($new_password);

  $sql = "UPDATE g5_member SET mb_password = '{$hash}' WHERE mb_id = '{$mb_id}'";
  sql_query($sql);

  echo "비밀번호가 '{$new_password}'로 변경되었습니다!";
  echo "<br>이 파일을 삭제하세요!";
  ?>