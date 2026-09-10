<?php
session_start();
session_unset();
session_destroy();
header('Location: preventa_login.php');
