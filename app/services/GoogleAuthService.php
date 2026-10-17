<?php
/**
 * Dịch vụ xử lý Google OAuth 2.0 & Google Identity Services (GIS)
 * Hỗ trợ cả đăng nhập thực tế (Google API) và Chế độ Trải Nghiệm Nhanh (Demo Simulator)
 */
class GoogleAuthService {
    private array $config;
    private string $lastError = '';

    public function __construct(?array $config = null) {
        if ($config !== null) {
            $this->config = $config;
        } else {
            $configFile = __DIR__ . '/../../config/google.php';
            if (file_exists($configFile)) {
                $this->config = require $configFile;
            } else {
                $this->config = [
                    'client_id' => '',
                    'client_secret' => '',
                    'redirect_uri' => '',
                    'demo_mode' => true,
                ];
            }
        }
    }

    public function getClientId(): string {
        return trim($this->config['client_id'] ?? '');
    }

    public function getClientSecret(): string {
        return trim($this->config['client_secret'] ?? '');
    }

    public function getRedirectUri(): string {
        if (!empty($this->config['redirect_uri'])) {
            return $this->config['redirect_uri'];
        }
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $rawDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/phone-store')), '/.');
        $scriptDir = (!empty($rawDir) && $rawDir !== '.') ? $rawDir : '/phone-store';
        return $protocol . '://' . $host . $scriptDir . '/index.php?page=auth&action=google_callback';
    }

    public function isConfigured(): bool {
        $cid = $this->getClientId();
        $secret = $this->getClientSecret();
        return !empty($cid) && !empty($secret) 
            && strpos($cid, 'YOUR_GOOGLE_CLIENT_ID') === false 
            && strpos($secret, 'YOUR_GOOGLE_CLIENT_SECRET') === false;
    }

    public function isDemoMode(): bool {
        return false;
    }

    /**
     * Tạo đường dẫn đăng nhập Google OAuth 2.0
     */
    public function getAuthUrl(string $state = ''): string {
        $params = [
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Đổi mã authorization code lấy Access Token & ID Token từ Google
     */
    public function exchangeCodeForTokens(string $code): ?array {
        $tokenEndpoint = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code' => $code,
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'redirect_uri' => $this->getRedirectUri(),
            'grant_type' => 'authorization_code',
        ];

        $ch = curl_init($tokenEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['access_token'])) {
                return $data;
            }
        }
        $errData = json_decode($response ?: '', true);
        $this->lastError = $errData['error_description'] ?? ($errData['error'] ?? ('HTTP ' . $httpCode));
        return null;
    }

    public function getLastError(): string {
        return $this->lastError;
    }

    /**
     * Lấy thông tin người dùng từ Google bằng Access Token
     */
    public function getUserInfoFromAccessToken(string $accessToken): ?array {
        $userInfoEndpoint = 'https://www.googleapis.com/oauth2/v3/userinfo';

        $ch = curl_init($userInfoEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['sub'])) {
                return [
                    'google_id' => $data['sub'],
                    'email' => strtolower(trim($data['email'] ?? '')),
                    'fullname' => trim($data['name'] ?? 'Google User'),
                    'avatar' => $data['picture'] ?? null,
                    'email_verified' => !empty($data['email_verified']),
                ];
            }
        }
        return null;
    }

    /**
     * Xác thực ID Token (JWT) từ Google Identity Services Client
     */
    public function verifyIdToken(string $idToken): ?array {
        // Cách 1: Xác thực qua Google tokeninfo endpoint
        $endpoint = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (is_array($data) && !empty($data['sub']) && !empty($data['email'])) {
                return [
                    'google_id' => $data['sub'],
                    'email' => strtolower(trim($data['email'])),
                    'fullname' => trim($data['name'] ?? 'Google User'),
                    'avatar' => $data['picture'] ?? null,
                    'email_verified' => !empty($data['email_verified']),
                ];
            }
        }

        // Cách 2: Parse JWT Payload nếu tokeninfo gặp sự cố mạng
        $parts = explode('.', $idToken);
        if (count($parts) === 3) {
            $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
            if ($payloadJson) {
                $payload = json_decode($payloadJson, true);
                if (is_array($payload) && !empty($payload['sub']) && !empty($payload['email'])) {
                    return [
                        'google_id' => $payload['sub'],
                        'email' => strtolower(trim($payload['email'])),
                        'fullname' => trim($payload['name'] ?? 'Google User'),
                        'avatar' => $payload['picture'] ?? null,
                        'email_verified' => !empty($payload['email_verified']),
                    ];
                }
            }
        }

        return null;
    }

    public function getDemoUsers(): array {
        return [];
    }
}

