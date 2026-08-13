<?php

namespace App\Enums;

enum LegalDocumentType: string
{
    case TermsOfService = 'terms_of_service';
    case PrivacyPolicy = 'privacy_policy';
    case RefundPolicy = 'refund_policy';
    case CookiePolicy = 'cookie_policy';

    public function label(): string
    {
        return match ($this) {
            self::TermsOfService => 'Terms of Service',
            self::PrivacyPolicy => 'Privacy Policy',
            self::RefundPolicy => 'Refund Policy',
            self::CookiePolicy => 'Cookie Policy',
        };
    }
}
