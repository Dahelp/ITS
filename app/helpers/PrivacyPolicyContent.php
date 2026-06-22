<?php

namespace app\helpers;

class PrivacyPolicyContent
{
    public static function html(): string
    {
        $script = dirname(__DIR__, 2) . '/scripts/update_privacy_policy.php';
        $source = is_file($script) ? file_get_contents($script) : '';

        if ($source === false || $source === '') {
            return '';
        }

        if (preg_match("/\\\$content\\s*=\\s*<<<'HTML'\\R(.*)\\RHTML;/s", $source, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
