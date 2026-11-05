/**
 * Vercel serverless enquiry handler.
 * Sends lead emails to Helper Home inbox (and optional customer confirmation).
 *
 * Priority:
 * 1) SMTP via env (SMTP_HOST/SMTP_USER/SMTP_PASS) when configured
 * 2) FormSubmit fallback to ENQUIRY_TO_EMAIL / helperhomeahmedabad@gmail.com
 */

const DEFAULT_TO = 'helperhomeahmedabad@gmail.com';

const SERVICE_LABELS = {
  maid: 'Maid Service',
  servant: 'Servant Service',
  babysitter: 'Babysitter Service',
  'japa-maid': 'Japa Maid / Nanny',
  'elderly-care': 'Elderly Caretaker',
  'patient-care': 'Patient Caretaker',
  cook: 'Cook Service',
  driver: 'Driver Service',
  'domestic-couple': 'Domestic Couple Service',
  housekeeping: 'Housekeeping Service',
  'deep-cleaning': 'Deep Cleaning Service',
};

const DUTY_LABELS = {
  part_time: 'Part Time',
  full_time: 'Full Time',
  live_in_24_hours: '24 Hours / Live-In',
};

function field(data, ...keys) {
  for (const key of keys) {
    const value = data?.[key];
    if (value !== undefined && value !== null && String(value).trim() !== '') {
      return String(value).trim();
    }
  }
  return '';
}

function json(res, status, payload) {
  res.statusCode = status;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
  res.end(JSON.stringify(payload));
}

async function sendViaFormSubmit(toEmail, payload) {
  const response = await fetch(`https://formsubmit.co/ajax/${encodeURIComponent(toEmail)}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message = data.message || data.error || `FormSubmit failed (${response.status})`;
    throw new Error(message);
  }
  return data;
}

async function sendViaSmtp({ to, subject, html, replyTo }) {
  const nodemailer = await import('nodemailer');
  const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST || 'smtp.gmail.com',
    port: Number(process.env.SMTP_PORT || 587),
    secure: false,
    auth: {
      user: process.env.SMTP_USER || process.env.MAIL_USERNAME,
      pass: process.env.SMTP_PASS || process.env.MAIL_PASSWORD,
    },
  });

  await transporter.sendMail({
    from: `"${process.env.MAIL_FROM_NAME || 'Helper Home'}" <${process.env.MAIL_FROM_ADDRESS || process.env.SMTP_USER || DEFAULT_TO}>`,
    to,
    subject,
    html,
    replyTo: replyTo || undefined,
  });
}

module.exports = async function handler(req, res) {
  if (req.method === 'OPTIONS') {
    return json(res, 204, {});
  }

  if (req.method !== 'POST') {
    return json(res, 405, { success: false, ok: false, message: 'Method not allowed.' });
  }

  let body = req.body;
  if (typeof body === 'string') {
    try {
      body = JSON.parse(body);
    } catch {
      body = {};
    }
  }
  body = body && typeof body === 'object' ? body : {};

  const honeypot = field(body, 'website');
  if (honeypot) {
    return json(res, 201, {
      success: true,
      ok: true,
      message: 'Enquiry received successfully.',
      data: { enquiry_code: null },
    });
  }

  const fullName = field(body, 'fullName', 'name');
  const phone = field(body, 'phone', 'mobile_number').replace(/[^\d+]/g, '');
  const email = field(body, 'email');
  const cityArea = field(body, 'cityArea', 'city');
  const serviceRequired = field(body, 'serviceRequired', 'service_slug');
  const dutyHours = field(body, 'dutyHours', 'duty_type_slug');
  const startDate = field(body, 'startDate', 'preferred_start_date');
  const requirements = field(body, 'requirements', 'message');
  const sourcePage = field(body, 'source_page') || 'website';

  const errors = [];
  if (fullName.length < 2) errors.push('Please enter your full name.');
  if (!/^\+?\d{10,15}$/.test(phone)) errors.push('Please enter a valid mobile number.');
  if (email && !/^\S+@\S+\.\S+$/.test(email)) errors.push('Please enter a valid email address.');
  if (cityArea.length < 2) errors.push('Please enter your city or locality.');
  if (!serviceRequired) errors.push('Please select the service you require.');
  if (!['part_time', 'full_time', 'live_in_24_hours'].includes(dutyHours)) {
    errors.push('Please select Duty Hours.');
  }

  if (errors.length) {
    return json(res, 422, {
      success: false,
      ok: false,
      message: errors[0],
      errors: { form: errors },
    });
  }

  const serviceLabel = SERVICE_LABELS[serviceRequired] || serviceRequired;
  const dutyLabel = DUTY_LABELS[dutyHours] || dutyHours;
  const enquiryCode = `HH-WEB-${Date.now().toString().slice(-8)}`;
  const toEmail = (process.env.ENQUIRY_TO_EMAIL || process.env.LEAD_NOTIFICATION_EMAIL || DEFAULT_TO).trim();
  const submittedAt = new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata' });

  const html = `
    <h2 style="color:#C38F38;margin:0 0 12px;">New Service Enquiry</h2>
    <p>A customer submitted an enquiry from the Helper Home website.</p>
    <table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:640px;">
      <tr style="background:#f7f4ef;"><td><strong>Enquiry ID</strong></td><td>${enquiryCode}</td></tr>
      <tr><td><strong>Name</strong></td><td>${fullName}</td></tr>
      <tr style="background:#f7f4ef;"><td><strong>Phone</strong></td><td>${phone}</td></tr>
      <tr><td><strong>Email</strong></td><td>${email || '—'}</td></tr>
      <tr style="background:#f7f4ef;"><td><strong>City / Area</strong></td><td>${cityArea}</td></tr>
      <tr><td><strong>Service</strong></td><td>${serviceLabel}</td></tr>
      <tr style="background:#f7f4ef;"><td><strong>Duty Hours</strong></td><td>${dutyLabel}</td></tr>
      <tr><td><strong>Preferred Start</strong></td><td>${startDate || 'Not specified'}</td></tr>
      <tr style="background:#f7f4ef;"><td><strong>Requirements</strong></td><td>${(requirements || '—').replace(/\n/g, '<br>')}</td></tr>
      <tr><td><strong>Source</strong></td><td>${sourcePage}</td></tr>
      <tr style="background:#f7f4ef;"><td><strong>Submitted</strong></td><td>${submittedAt} IST</td></tr>
    </table>
  `;

  try {
    const smtpPass = process.env.SMTP_PASS || process.env.MAIL_PASSWORD;
    if (smtpPass) {
      await sendViaSmtp({
        to: toEmail,
        subject: `New Enquiry: ${serviceLabel} — ${fullName}`,
        html,
        replyTo: email || undefined,
      });

      if (email) {
        await sendViaSmtp({
          to: email,
          subject: 'We received your Helper Home enquiry',
          html: `<p>Hi ${fullName},</p><p>Thank you for contacting Helper Home. We received your enquiry for <strong>${serviceLabel}</strong> (${dutyLabel}). Our team will contact you shortly on <strong>${phone}</strong>.</p><p><strong>Helper Home</strong><br>Phone: +91 87995 44275<br>Email: helperhomeahmedabad@gmail.com</p>`,
        });
      }
    } else {
      await sendViaFormSubmit(toEmail, {
        name: fullName,
        phone,
        email: email || 'noreply@helperhome.in',
        city: cityArea,
        service: serviceLabel,
        duty_hours: dutyLabel,
        preferred_start: startDate || 'Not specified',
        requirements: requirements || '—',
        source_page: sourcePage,
        enquiry_id: enquiryCode,
        submitted_at: `${submittedAt} IST`,
        _subject: `New Helper Home Enquiry — ${serviceLabel}`,
        _template: 'table',
        _captcha: 'false',
        ...(email ? { _replyto: email } : {}),
      });
    }

    return json(res, 201, {
      success: true,
      ok: true,
      message: 'Enquiry received successfully.',
      data: { enquiry_code: enquiryCode },
    });
  } catch (error) {
    return json(res, 500, {
      success: false,
      ok: false,
      message: 'Unable to send enquiry email right now. Please call +91 87995 44275 or WhatsApp us.',
      detail: error.message,
    });
  }
};
