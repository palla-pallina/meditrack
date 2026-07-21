<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($recipientEmail, $recipientName, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {

        // SMTP Configuration
        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username = 'atsaratom@gmail.com';

        $mail->Password = 'joqw ceem hjaz opsw';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        // Sender
        $mail->setFrom(
            'atsaratom@gmail.com',
            'MediTrack'
        );

        // Recipient
        $mail->addAddress(
            $recipientEmail,
            $recipientName
        );

        $mail->isHTML(true);

        $mail->Subject = $subject;

        $mail->Body = $body;

        $mail->send();

        return true;

    } catch (Exception $e) {

        throw new Exception($mail->ErrorInfo);

    }
}