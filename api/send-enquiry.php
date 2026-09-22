<?php
/**
 * Helper Home â€” Enquiry endpoint
 * Receives JSON POST, emails business + customer via Gmail SMTP.
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail config missing. Copy api/config.example.php to api/config.php.']);
    exit;
}

$config = require $configPath;
require_once __DIR__ . '/SimpleSmtpMailer.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

function field(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

$fullName        = field($data, 'fullName');
$phone           = field($data, 'phone');
$email           = field($data, 'email');
$cityArea        = field($data, 'cityArea');
$serviceRequired = field($data, 'serviceRequired');
$dutyHours       = field($data, 'dutyHours');
$startDate       = field($data, 'startDate');
$requirements    = field($data, 'requirements');
$honeypot        = field($data, 'website'); // bots fill this

if ($honeypot !== '') {
    echo json_encode(['ok' => true, 'message' => 'Enquiry received.']);
    exit;
}

$errors = [];
if (mb_strlen($fullName) < 2) {
    $errors[] = 'Please enter your full name.';
}
if (!preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}
if (mb_strlen($cityArea) < 2) {
    $errors[] = 'Please enter your city or locality.';
}
if ($serviceRequired === '') {
    $errors[] = 'Please select a service.';
}
$allowedDuty = ['part_time', 'full_time', 'live_in_24_hours'];
if (!in_array($dutyHours, $allowedDuty, true)) {
    $errors[] = 'Please select Duty Hours.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $errors[0], 'errors' => $errors]);
    exit;
}

if (
    empty($config['smtp_pass']) ||
    $config['smtp_pass'] === 'YOUR_GMAIL_APP_PASSWORD_HERE'
) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Email is not configured yet. Please add your Gmail App Password in api/config.php.',
    ]);
    exit;
}

$serviceLabels = [
    'maid'            => 'Maid Service',
    'servant'         => 'Servant Service',
    'babysitter'      => 'Babysitter Service',
    'japa-maid'       => 'Japa Maid / Nanny',
    'elderly-care'    => 'Elderly Caretaker',
    'patient-care'    => 'Patient Caretaker',
    'cook'            => 'Cook Service',
    'driver'          => 'Driver Service',
    'domestic-couple' => 'Domestic Couple Service',
];
$dutyLabels = [
    'part_time'         => 'Part Time',
    'full_time'         => 'Full Time',
    'live_in_24_hours'  => '24 Hours / Live-In',
];
$serviceLabel = $serviceLabels[$serviceRequired] ?? $serviceRequired;
$dutyLabel = $dutyLabels[$dutyHours] ?? $dutyHours;
$safe = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};

$submittedAt = date('d M Y, h:i A') . ' IST';
$officeAddress = 'A - 318/319 Swaminarayan Avenue, Nr AEC Cross Road, Naranpura, Ahmedabad - 380013';
$officePhone = '+91 87995 44275';

$adminHtml = '
<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#171512;line-height:1.5;">
  <h2 style="color:#C38F38;margin:0 0 12px;">New Service Enquiry</h2>
  <p style="margin:0 0 16px;">A customer submitted an enquiry from the Helper Home website.</p>
  <table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:640px;">
    <tr style="background:#f7f4ef;"><td><strong>Name</strong></td><td>' . $safe($fullName) . '</td></tr>
    <tr><td><strong>Phone</strong></td><td>' . $safe($phone) . '</td></tr>
    <tr style="background:#f7f4ef;"><td><strong>Email</strong></td><td>' . $safe($email) . '</td></tr>
    <tr><td><strong>City / Area</strong></td><td>' . $safe($cityArea) . '</td></tr>
    <tr style="background:#f7f4ef;"><td><strong>Service</strong></td><td>' . $safe($serviceLabel) . '</td></tr>
    <tr><td><strong>Duty Hours</strong></td><td>' . $safe($dutyLabel) . '</td></tr>
    <tr style="background:#f7f4ef;"><td><strong>Preferred Start</strong></td><td>' . $safe($startDate !== '' ? $startDate : 'Not specified') . '</td></tr>
    <tr><td><strong>Requirements</strong></td><td>' . nl2br($safe($requirements !== '' ? $requirements : 'â€”')) . '</td></tr>
    <tr style="background:#f7f4ef;"><td><strong>Submitted</strong></td><td>' . $safe($submittedAt) . '</td></tr>
  </table>
</body></html>';

$customerHtml = '
<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#171512;line-height:1.6;">
  <h2 style="color:#C38F38;margin:0 0 12px;">Thank you, ' . $safe($fullName) . '</h2>
  <p>We have received your enquiry for <strong>' . $safe($serviceLabel) . '</strong> (<strong>' . $safe($dutyLabel) . '</strong>).</p>
  <p>Our coordination team will review your requirements and contact you shortly on <strong>' . $safe($phone) . '</strong> or <strong>' . $safe($email) . '</strong>.</p>
  <p style="margin-top:20px;"><strong>Helper Home</strong><br>
  Home Care &amp; Domestic Services<br>
  ' . $safe($officeAddress) . '<br>
  Phone: <a href="tel:+918799544275">' . $safe($officePhone) . '</a><br>
  Email: <a href="mailto:helperhomeahmedabad@gmail.com">helperhomeahmedabad@gmail.com</a></p>
  <p style="color:#666;font-size:12px;margin-top:24px;">This is an automated confirmation. Please do not reply to this email unless instructed.</p>
</body></html>';

try {
    $mailer = new SimpleSmtpMailer(
        $config['smtp_host'],
        (int) $config['smtp_port'],
        $config['smtp_user'],
        $config['smtp_pass']
    );

    // 1) Notify business inbox
    $mailer->send(
        $config['from_email'],
        $config['from_name'],
        $config['to_email'],
        $config['to_name'],
        'New Enquiry: ' . $serviceLabel . ' â€” ' . $fullName,
        $adminHtml,
        '',
        $email,
        $fullName
    );

    // 2) Confirmation to customer
    $mailer->send(
        $config['from_email'],
        $config['from_name'],
        $email,
        $fullName,
        'We received your Helper Home enquiry',
        $customerHtml
    );

    echo json_encode([
        'ok' => true,
        'message' => 'Enquiry sent successfully. Our team and you will receive an email shortly.',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Unable to send email right now. Please call +91 87995 44275 or try again later.',
        'detail' => $e->getMessage(),
    ]);
}

