<?php 
namespace App\Models;

use Helpers\Utility;

/**
 * Email Verification Model
 * Handles email verification tokens and processes
 */

class EmailVerification extends BaseModel
{
    protected $table = "email_verifications";
    protected array $fillable = [
       "user_id",
       "email",
       "code",
       "expires_at",
       "is_verified",
    ];
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRATION = (15 * 60); // 15 minutes


    public function createVerification(int $userId, string $email): object
    {
        $utility = new Utility();
        //generate a 6 digit code
        $code = $utility->randID('numeric', self::CODE_LENGTH);
        $expiresAt = date('Y-m-d H:i:s', time() + self::CODE_EXPIRATION);

        $verificationId = $this->create([
            "user_id" => $userId,
            "email" => $email,
            "code" => $code,
            "expires_at" => $expiresAt,
        ]);


        return $verificationId ? $this->find($verificationId) : null;
    }

    public function verifyCode(string $code): bool|object
    {
        $verification = $this->findBy("code", $code);
        if (!$verification || $verification?->is_verified !== 0 || strtotime($verification?->expires_at) < time()) {
            return false;
        }
        $this->update($verification->id, ["is_verified" => 1]);
        $verification->is_verified = 1;
        return $verification;
    }


}