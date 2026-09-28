<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function sendSystemEmail(
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $htmlBody
): bool
{
    $mail = new PHPMailer(true);

    try
    {
        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Your Gmail address
        $mail->Username = 'Prime_Stock_Lanka@gmail.com';

        // Google App Password, not your normal Gmail password
        $mail->Password = 'YOUR_APP_PASSWORD';

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
            'YOUR_EMAIL@gmail.com',
            'Inventory Management System'
        );

        $mail->addAddress(
            $recipientEmail,
            $recipientName
        );

        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();

        return true;
    }
    catch(Exception $error)
    {
        error_log(
            "Email sending failed: " .
            $mail->ErrorInfo
        );

        return false;
    }
}