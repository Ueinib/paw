<?php
require "db_connect.php";
$conn = db_connect();

$students = $conn->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);

foreach ($students as $s) {
    echo $s["id"]." - ".$s["fullname"]." - ".$s["matricule"]." - ".$s["group_id"].
         " <a href='update_student.php?id=".$s["id"]."'>Edit</a> ".
         " <a href='delete_student.php?id=".$s["id"]."'>Delete</a><br>";
}
