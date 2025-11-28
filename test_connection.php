<?php
require "db_connect.php";

$conn = db_connect();

if ($conn) {
    echo "Connection successful";
} else {
    echo "Connection failed";
}
