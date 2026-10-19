<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

class SmtpTransportFactory
{
    /** @param array<string, mixed> $config */
    public function create(array $config): EsmtpTransport
    {
        $scheme = $config['scheme'] ?: ((int) $config['port'] === 465 ? 'smtps' : 'smtp');
        $transport = (new EsmtpTransportFactory)->create(new Dsn(
            $scheme,
            $config['host'],
            $config['username'] ?? null,
            $config['password'] ?? null,
            (int) $config['port'],
            [...$config, 'verify_peer' => true, 'auto_tls' => true],
        ));

        if (! $transport instanceof EsmtpTransport) {
            throw new RuntimeException('The SMTP configuration must create an SMTP transport.');
        }

        $stream = $transport->getStream();
        if (! $stream instanceof SocketStream) {
            throw new RuntimeException('The SMTP transport must use a TLS-capable socket stream.');
        }
        $options = $stream->getStreamOptions();
        $options['ssl']['verify_peer'] = true;
        $options['ssl']['verify_peer_name'] = true;

        if ($caFile = $config['ca_file'] ?? null) {
            $path = is_file($caFile) ? $caFile : base_path($caFile);
            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException('MAIL_SSL_CA_FILE must point to a readable PEM certificate file.');
            }
            $options['ssl']['cafile'] = $path;
        }

        if ($peerName = $config['tls_server_name'] ?? null) {
            $options['ssl']['peer_name'] = $peerName;
        }

        $stream->setStreamOptions($options);
        $stream->setTimeout((float) ($config['timeout'] ?? 15));

        return $transport;
    }
}
