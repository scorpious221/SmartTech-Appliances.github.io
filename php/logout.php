<?php
session_start();

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
location.replace('../index.html');
</script>

</body>
</html>