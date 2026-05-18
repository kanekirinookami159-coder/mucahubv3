<?php

if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$base_url = "http://localhost/school-lms/";

date_default_timezone_set("Asia/Manila");
