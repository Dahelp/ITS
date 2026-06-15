<?php

namespace app\services\mail;

class SafeMailbox extends \PhpImap\Mailbox
{
    public function safeDisconnect(): void
    {
        try {
            $this->disconnect();
        } catch (\Throwable $e) {
            // The IMAP stream can already be closed by the server at shutdown.
        }
    }

    protected function disconnect()
    {
        try {
            $imapStream = $this->getImapStream(false);
            if ($imapStream && is_resource($imapStream)) {
                @imap_close($imapStream, CL_EXPUNGE);
            }
        } catch (\Throwable $e) {
            // Closing must never fail the cron after messages were processed.
        }
    }

    protected function convertStringEncoding($string, $fromEncoding, $toEncoding)
    {
        $fromEncoding = $this->normalizeEncoding((string)$fromEncoding);
        $toEncoding = $this->normalizeEncoding((string)$toEncoding);

        try {
            return parent::convertStringEncoding($string, $fromEncoding, $toEncoding);
        } catch (\ValueError $e) {
            return (string)$string;
        } catch (\Throwable $e) {
            return (string)$string;
        }
    }

    private function normalizeEncoding(string $encoding): string
    {
        $encoding = trim($encoding);
        $lower = strtolower($encoding);

        $aliases = [
            'ks_c_5601-1987' => 'EUC-KR',
            'ks_c_5601_1987' => 'EUC-KR',
            'ks_c_5601' => 'EUC-KR',
            'ks-c-5601-1987' => 'EUC-KR',
            'x-windows-949' => 'CP949',
            'windows-949' => 'CP949',
            'cp949' => 'CP949',
            'euc_kr' => 'EUC-KR',
            'euckr' => 'EUC-KR',
            'gb2312' => 'CP936',
            'gb_2312-80' => 'CP936',
            'x-gbk' => 'GBK',
            'big5-hkscs' => 'BIG-5',
            'iso-8859-8-i' => 'ISO-8859-8',
            'utf8' => 'UTF-8',
        ];

        return $aliases[$lower] ?? ($encoding !== '' ? $encoding : 'UTF-8');
    }
}
