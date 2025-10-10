<?php

namespace Aeros\Src\Classes;

class Security
{
    /**
     * Creates a hidden input with a token. This token helps to validate if a 
     * request is authorized.
     *
     * @return  string
     */
    public function csrf()
    {
        return component(
            'inputs.hidden', 
            [
                'id' => 'csrf_token',
                'name' => 'csrf_token',
                'value' => session()->csrf_token,
            ]
        );
    }

    /**
     * Validates CSRF token from request.
     *
     * @param   string|null     $token Token from request
     * @return  bool
     */
    public function validateCsrfToken(?string $token): bool
    {
        if (empty($token) || empty(session()->csrf_token)) {
            return false;
        }

        return hash_equals((string) session()->csrf_token, (string) $token);
    }

    /**
     * Gets Bearer token from Authorization header.
     *
     * @return string|null
     */
    public function getBearerToken(): ?string
    {
        $headers = request()->getHeaders();

        foreach ($headers as $header) {
            if (stripos($header, 'Authorization:') === 0) {
                $token = trim(substr($header, 14));

                // Extract Bearer token
                if (stripos($token, 'Bearer ') === 0) {
                    return trim(substr($token, 7));
                }
            }
        }

        return null;
    }

    /**
     * Regenerates CSRF token (call after successful form submission).
     *
     * @return  string  New token
     */
    public function regenerateCsrfToken(): string
    {
        return session()->csrf_token = \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    /**
     * Gets CSRF token from request (body or header).
     *
     * @return  string|null
     */
    public function getCsrfTokenFromRequest(): ?string
    {
        // Check POST/PUT/PATCH/DELETE body
        $token = request()->csrf_token ?? null;

        // Check headers (for AJAX requests)
        if (empty($token)) {

            $headers = request()->getHeaders();

            foreach ($headers as $header) {
                if (stripos($header, 'X-CSRF-TOKEN:') === 0) {
                    $token = trim(substr($header, 13));
                    break;
                }
            }
        }

        return $token;
    }
}
