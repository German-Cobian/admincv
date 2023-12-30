<?php 

$conn= new mysqli('localhost','root','','smartpun_chrysalis')or die("Could not connect to mysql".mysqli_error($con));
$conn->set_charset('utf8');