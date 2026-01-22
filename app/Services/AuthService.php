<?php
namespace App\Services;

use App\Models\ApiToken;
use Core\Database;

class AuthService
{
    public function __construct(
        private Database $db
    ) {}

    public function generateToken(int $userId, int $expiryHours = 2): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);

        // Optional: delete existing tokens (single-session policy)
        $apiTokenModel->deleteUserTokens($userId);

        $apiTokenModel->createToken([
            'user_id' => $userId,
            'token' => $hashedToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date(
                'Y-m-d H:i:s',
                strtotime("+{$expiryHours} hours")
            ),
        ]);

        return $plainToken; // sent ONCE to client
    }

    public function validateToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);

        $apiTokenModel = new ApiToken($this->db);
        $tokenData = $apiTokenModel->getByToken($hashedToken);

        if (!$tokenData) {
            return null;
        }

        if (strtotime($tokenData['expires_at']) <= time()) {
            return null;
        }

        // Optional IP check
        if ($tokenData['ip_address'] !== null) {
            if ($tokenData['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? null)) {
                return null;
            }
        }

        return $tokenData;
    }
}
