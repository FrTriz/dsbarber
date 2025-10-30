<?php

require_once '../session-manager.php';


session_unset();
session_destroy();

header('Location: ../../html/index.php');
exit();
?>