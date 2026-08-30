<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_destroy();
redirect('index.php');