<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SmtpConfigurationTest extends TestCase
{
    public function test_smtp_applies_certificate_and_peer_name_without_disabling_verification(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'evalio-ca-');
        try {
            config(['mail.mailers.smtp.host' => '192.0.2.1', 'mail.mailers.smtp.ca_file' => $path,
                'mail.mailers.smtp.tls_server_name' => 'mail.example.com']);
            $transport = Mail::mailer('smtp')->getSymfonyTransport();
            $ssl = $transport->getStream()->getStreamOptions()['ssl'];
            $this->assertSame($path, $ssl['cafile']);
            $this->assertSame('mail.example.com', $ssl['peer_name']);
            $this->assertTrue($ssl['verify_peer']);
            $this->assertTrue($ssl['verify_peer_name']);
            $this->assertTrue($transport->isTlsRequired());
        } finally {
            unlink($path);
        }
    }

    public function test_missing_certificate_file_is_rejected(): void
    {
        config(['mail.mailers.smtp.ca_file' => '/missing/evalio-cert.pem']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MAIL_SSL_CA_FILE');
        Mail::mailer('smtp');
    }

    public function test_public_smtp_uses_default_trust_without_a_custom_certificate(): void
    {
        config(['mail.mailers.smtp.ca_file' => null, 'mail.mailers.smtp.tls_server_name' => null]);
        $ssl = Mail::mailer('smtp')->getSymfonyTransport()->getStream()->getStreamOptions()['ssl'];
        $this->assertArrayNotHasKey('cafile', $ssl);
        $this->assertArrayNotHasKey('peer_name', $ssl);
        $this->assertTrue($ssl['verify_peer']);
    }
}
