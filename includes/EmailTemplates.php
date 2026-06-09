<?php

declare(strict_types=1);

class EmailTemplates
{
    public static function wrap(string $title, string $bodyHtml): string
    {
        $siteName = getSetting('site_name', 'DogeSeeds.org') ?? 'DogeSeeds.org';
        $logoUrl = siteUrl() . '/assets/img/DogeSeeds_logo.png';
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
</head>
<body style="margin:0;padding:0;background:#f4f8fb;font-family:'Nunito',Arial,sans-serif;color:#1A2B45;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f8fb;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:2px solid #e8eef3;border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(26,43,69,0.12);">
<tr><td style="padding:28px 28px 16px;text-align:center;background:linear-gradient(135deg,rgba(76,175,80,0.12),#ffffff);">
<img src="{$logoUrl}" alt="{$siteName}" width="160" style="display:block;margin:0 auto 12px;height:auto;">
<h1 style="margin:0;font-size:1.35rem;font-weight:800;color:#1A2B45;">{$title}</h1>
</td></tr>
<tr><td style="padding:8px 28px 28px;font-size:0.95rem;line-height:1.6;">
{$bodyHtml}
</td></tr>
<tr><td style="padding:16px 28px 24px;border-top:1px solid #e8eef3;font-size:0.78rem;color:#6b7c8f;text-align:center;">
<p style="margin:0;">&copy; {$year} {$siteName} - Do Only Good Everyday</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    public static function button(string $label, string $url): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        return '<p style="text-align:center;margin:24px 0;"><a href="' . $safeUrl . '" style="display:inline-block;padding:12px 28px;background:#4CAF50;color:#ffffff;text-decoration:none;font-weight:800;border-radius:12px;">' . $safeLabel . '</a></p>';
    }

    public static function welcome(string $name, string $verifyUrl): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $body = '<p>Hi <strong>' . $safeName . '</strong>,</p>';
        $body .= '<p>Welcome to DogeSeeds! Your account is ready. Confirm your email to get started sharing and finding help on the map.</p>';
        $body .= self::button('Confirm my email', $verifyUrl);
        $body .= '<p style="font-size:0.85rem;color:#6b7c8f;">If you did not create this account, you can ignore this email.</p>';
        return self::wrap('Welcome to DogeSeeds', $body);
    }

    public static function listingPublished(string $name, string $orgName, string $pickupStart, string $pickupEnd, string $mapUrl): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeOrg = htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8');
        $safeStart = htmlspecialchars($pickupStart, ENT_QUOTES, 'UTF-8');
        $safeEnd = htmlspecialchars($pickupEnd, ENT_QUOTES, 'UTF-8');
        $body = '<p>Hi <strong>' . $safeName . '</strong>,</p>';
        $body .= '<p>Your listing <strong>' . $safeOrg . '</strong> is now live on the map.</p>';
        $body .= '<table role="presentation" width="100%" style="margin:16px 0;background:#f4f8fb;border-radius:12px;padding:14px;">';
        $body .= '<tr><td style="font-size:0.9rem;"><strong>Pickup window</strong><br>' . $safeStart . ' &rarr; ' . $safeEnd . '</td></tr>';
        $body .= '</table>';
        $body .= '<p>People nearby can now see your offer and schedule a pickup.</p>';
        $body .= self::button('View on map', $mapUrl);
        return self::wrap('Your listing is live', $body);
    }

    public static function passwordReset(string $name, string $resetUrl): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $body = '<p>Hi <strong>' . $safeName . '</strong>,</p>';
        $body .= '<p>We received a request to reset your DogeSeeds password. Use the button below to choose a new one. This link expires in 1 hour.</p>';
        $body .= self::button('Reset my password', $resetUrl);
        $body .= '<p style="font-size:0.85rem;color:#6b7c8f;">If you did not request this, you can ignore this email.</p>';
        return self::wrap('Reset your password', $body);
    }

    public static function listingInquiry(
        string $listingName,
        string $orgName,
        string $senderName,
        string $senderEmail,
        string $message,
        bool $isOwnerCopy = true,
        string $listingAddress = ''
    ): string {
        $safeListing = htmlspecialchars($listingName, ENT_QUOTES, 'UTF-8');
        $safeOrg = htmlspecialchars($orgName, ENT_QUOTES, 'UTF-8');
        $safeSender = htmlspecialchars($senderName ?: 'Someone on the map', ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($senderEmail ?: 'Not provided', ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $safeAddress = htmlspecialchars($listingAddress, ENT_QUOTES, 'UTF-8');
        $sameName = strcasecmp(trim($listingName), trim($orgName)) === 0;

        if ($isOwnerCopy) {
            $title = 'New message about your listing';
            $body = '<p>Someone sent you a message through DogeSeeds. They are reaching out about this listing:</p>';
        } else {
            $title = 'Copy of your message';
            $body = '<p>Here is a copy of what you sent. You contacted this listing:</p>';
        }

        $body .= '<table role="presentation" width="100%" style="margin:16px 0;background:#e8f5e9;border:1px solid rgba(76,175,80,0.25);border-radius:12px;padding:14px;">';
        $body .= '<tr><td style="font-size:0.9rem;"><strong>Listing</strong><br>' . $safeListing . '</td></tr>';
        if (!$sameName) {
            $body .= '<tr><td style="font-size:0.9rem;padding-top:10px;"><strong>Organisation</strong><br>' . $safeOrg . '</td></tr>';
        }
        if ($listingAddress !== '') {
            $body .= '<tr><td style="font-size:0.9rem;padding-top:10px;"><strong>Address</strong><br>' . $safeAddress . '</td></tr>';
        }
        $body .= '</table>';

        $body .= '<table role="presentation" width="100%" style="margin:16px 0;background:#f4f8fb;border-radius:12px;padding:14px;">';
        $body .= '<tr><td style="font-size:0.9rem;"><strong>From</strong><br>' . $safeSender . '</td></tr>';
        $body .= '<tr><td style="font-size:0.9rem;padding-top:10px;"><strong>Email</strong><br>' . $safeEmail . '</td></tr>';
        $body .= '<tr><td style="font-size:0.9rem;padding-top:10px;"><strong>Message</strong><br>' . $safeMessage . '</td></tr>';
        $body .= '</table>';

        return self::wrap($title, $body);
    }
}
