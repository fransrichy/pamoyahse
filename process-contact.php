<?php
// process-contact.php - Handles contact form submissions, sends beautiful HTML email with logo, includes bot verification (math CAPTCHA + honeypot)

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.html?error=method");
    exit;
}

// --- Honeypot check (hidden field "website" must be empty) ---
if (!empty($_POST['website'])) {
    // Bot detected
    header("Location: thank-you.html?error=bot");
    exit;
}

// --- Sanitize and validate inputs ---
$fullname = trim(htmlspecialchars($_POST['fullname'] ?? ''));
$email = trim(htmlspecialchars($_POST['email'] ?? ''));
$phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
$service = trim(htmlspecialchars($_POST['service_interest'] ?? 'Not specified'));
$message = trim(htmlspecialchars($_POST['message'] ?? ''));
$captcha_user = trim($_POST['captcha'] ?? '');
$captcha_expected = trim($_POST['captcha_expected'] ?? '');

$errors = [];

if (empty($fullname)) $errors[] = "Full name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (empty($message)) $errors[] = "Message is required.";
if (empty($captcha_user) || $captcha_user != $captcha_expected) $errors[] = "Incorrect CAPTCHA answer. Please try again.";

if (!empty($errors)) {
    $error_string = implode("+", $errors);
    header("Location: contact.html?error=" . urlencode($error_string));
    exit;
}

// --- Email configuration ---
$to = "info@pamoyahse.com";          // Your email address
$subject = "New HSE Contact Inquiry from $fullname";

// HTML Email Template with Logo
$logo_url = "https://www.pamoyahse.com/images/logo.png"; // Update to your actual domain path
if (!filter_var($logo_url, FILTER_VALIDATE_URL)) {
    $logo_url = "images/logo.png"; // fallback relative path
}

$html_message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>HSE Contact Inquiry</title>
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 35px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(105deg, #0f3b5f, #2e7d64); padding: 30px; text-align: center; }
        .logo { max-width: 140px; margin-bottom: 10px; }
        .header h2 { color: white; margin: 0; font-family: 'Cormorant Garamond', serif; font-weight: 600; }
        .content { padding: 30px; }
        .field { margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .field strong { color: #0f3b5f; display: inline-block; min-width: 120px; }
        .field span { color: #2d3e50; }
        .message-box { background: #fefcf7; padding: 16px; border-radius: 16px; margin-top: 10px; border-left: 4px solid #9b6a4b; }
        .footer { background: #f0f3f2; padding: 20px; text-align: center; font-size: 12px; color: #5b6e6b; }
        .btn { display: inline-block; background: #2e7d64; color: white; padding: 10px 20px; border-radius: 40px; text-decoration: none; margin-top: 15px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='$logo_url' alt='HSE Solutions Logo' class='logo' style='max-width:140px;'>
            <h2>New Contact Request</h2>
        </div>
        <div class='content'>
            <div class='field'><strong>Name:</strong> <span>$fullname</span></div>
            <div class='field'><strong>Email:</strong> <span>$email</span></div>
            <div class='field'><strong>Phone:</strong> <span>" . ($phone ?: "Not provided") . "</span></div>
            <div class='field'><strong>Service Interest:</strong> <span>$service</span></div>
            <div class='field'><strong>Message:</strong>
                <div class='message-box'>" . nl2br($message) . "</div>
            </div>
            <div style='text-align: center;'>
                <a href='mailto:$email' class='btn'>Reply to $fullname</a>
            </div>
        </div>
        <div class='footer'>
            © 2026 HSE Solutions — Protecting People & Environment<br>
            This inquiry was sent via www.pamoyahse.com contact form.
        </div>
    </div>
</body>
</html>
";

// Plain text alternative (for non-HTML mail clients)
$plain_message = "New HSE Contact Inquiry\n\n";
$plain_message .= "Name: $fullname\n";
$plain_message .= "Email: $email\n";
$plain_message .= "Phone: " . ($phone ?: "Not provided") . "\n";
$plain_message .= "Service: $service\n";
$plain_message .= "Message:\n$message\n";

// Email headers
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: HSE Solutions <noreply@pamoyahse.com>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
$mail_sent = mail($to, $subject, $html_message, $headers);

// If you prefer SMTP (more reliable), integrate PHPMailer – but for basic hosting mail() works.
// For production, consider using PHPMailer with SMTP credentials.

if ($mail_sent) {
    header("Location: thank-you.html?status=success");
    exit;
} else {
    header("Location: contact.html?error=mail_failed");
    exit;
}
?>