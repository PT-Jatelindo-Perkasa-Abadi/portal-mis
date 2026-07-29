<?php

class App_Plugin_SecurityHeaders extends Zend_Controller_Plugin_Abstract
{
    public function dispatchLoopShutdown()
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();

        /**
         * Prevent Clickjacking
         */
        $response->setHeader(
            'X-Frame-Options',
            'SAMEORIGIN',
            true
        );

        /**
         * Prevent MIME Sniffing
         */
        $response->setHeader(
            'X-Content-Type-Options',
            'nosniff',
            true
        );

        /**
         * Referrer Policy
         */
        $response->setHeader(
            'Referrer-Policy',
            'strict-origin-when-cross-origin',
            true
        );

        /**
         * Permissions Policy
         */
        $response->setHeader(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
            true
        );

        /**
         * Cross-Origin Headers
         */

        // Isolasi window/tab browser
        $response->setHeader(
            'Cross-Origin-Opener-Policy',
            'same-origin',
            true
        );

        // Resource hanya boleh digunakan oleh origin yang sama
        $response->setHeader(
            'Cross-Origin-Resource-Policy',
            'same-origin',
            true
        );

        // Tahap awal gunakan unsafe-none agar tidak merusak CDN eksternal
        $response->setHeader(
            'Cross-Origin-Embedder-Policy',
            'unsafe-none',
            true
        );

        /**
         * Content Security Policy
         */
        $csp = implode('; ', array(
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "img-src 'self' data: blob:",
            "object-src 'none'",
            "frame-ancestors 'self'"
        ));

        $response->setHeader(
            'Content-Security-Policy',
            $csp,
            true
        );

        /**
         * HSTS hanya untuk production
         */
        if (APPLICATION_ENV === 'production') {
            $response->setHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
                true
            );
        }
    }
}