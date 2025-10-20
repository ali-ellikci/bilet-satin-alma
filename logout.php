<?php
// Session'ı başlat
session_start();

// Tüm session değişkenlerini sil
$_SESSION = array();

// Session'ı yok et
session_destroy();

// Kullanıcıyı ana sayfaya yönlendir
header("location: index.php");
exit;
?>