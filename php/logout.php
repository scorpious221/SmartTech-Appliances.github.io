<?php
session_start();

$next=$_GET["next"]??"../index.html";
$redirect=in_array($next,["../index.html","../login.php"],true)?$next:"../index.html";

$_SESSION=[];
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Logging out...</title>
</head>

<body>

<script>
sessionStorage.removeItem('st_admin_session');
localStorage.removeItem('st_session');
location.replace(<?php echo json_encode($redirect); ?>);
</script>

</body>
</html>