<?php
$to = "gabriel@smartpuntogob.mx";
$subject = "This is a test";
$message = "This is a PHP plain text email example.";
$headers =
    "From: hello@mailersend.com" .
    "\r\n" .
    "Reply-To: reply@mailersend.com" .
    "\r\n" .
    mail($to, $subject, $message, $headers);
?>
