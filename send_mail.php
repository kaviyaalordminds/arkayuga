<?php
// send_mail.php
// Arkayuga contact form - mail backend using PHPMailer + Gmail SMTP

header('Content-Type: application/json');

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ---- CONFIG: fill these with your Gmail account ----
$SMTP_USER = 'info@arkayuga.com';       // Gmail address that sends the mail
$SMTP_PASS = 'your16charapppassword';     // Gmail App Password (NOT your normal password)
$TO_EMAIL  = 'info@arkayuga.com';       // Where the enquiry should land (can be same as above)
// ------------------------------------------------------

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// ---- Collect + sanitize input ----
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$company   = trim($_POST['company'] ?? '');
$subject   = trim($_POST['subject'] ?? '');
$message   = trim($_POST['message'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '' || $phone === '') {
    http_response_code(422);
    $response['message'] = 'Please fill all required fields.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

$fullName   = $firstName . ' ' . $lastName;
$mailSubject = $subject !== '' ? $subject : 'New enquiry from Arkayuga website';

$body = "
    <h2>New Contact Form Submission - Arkayuga</h2>
    <p><strong>Name:</strong> {$fullName}</p>
    <p><strong>Email:</strong> {$email}</p>
    <p><strong>Phone:</strong> {$phone}</p>
    <p><strong>Company:</strong> " . ($company !== '' ? $company : '-') . "</p>
    <p><strong>Subject:</strong> {$mailSubject}</p>
    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // From MUST be the authenticated Gmail account (else Gmail blocks it as spoofing)
    $mail->setFrom($SMTP_USER, 'Arkayuga Website');
    $mail->addAddress($TO_EMAIL);
    // So you can hit "Reply" and it goes straight to the visitor
    $mail->addReplyTo($email, $fullName);

    $mail->isHTML(true);
    $mail->Subject = $mailSubject;
    $mail->Body    = $body;
    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $body));

    $mail->send();

    $response['success'] = true;
    $response['message'] = 'Thank you ' . $firstName . '! We will contact you soon.';
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Message could not be sent. Please try again later.';
    echo json_encode($response);
}