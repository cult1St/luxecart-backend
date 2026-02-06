<?php

namespace Helpers;

use DateTime;
use Throwable;
use Model\ApiToken;
use Includes\Database;
use InvalidArgumentException;

class Utility
{

    protected $responseBody, $db;
    public $pageLimit;
    public const CODE_EXPIRY_MINUTES = 15;

    function __construct($db)
    {
        $this->db = $db;
        $this->responseBody    = array();
        $this->pageLimit = 20;
    }

    /**
     * convert objects to array
     *
     * @param array $array
     * @return object
     */
    public function arrayToObject($array)
    {
        return (object) $array;
    }

    /**
     * convert arrays to object
     *
     * @param object $object
     * @return array
     */
    public function objectToArray($object)
    {
        return (array) $object;
    }

    public function niceDateFormat($date, $format = "date_time")
    {

        if ($format == "date_time") {
            $format = "D j, M Y h:ia";
        } else {
            $format = "D j, M Y";
        }

        $timestamp = strtotime($date);
        $niceFormat = date($format, $timestamp);

        return $niceFormat;
    }


    public function timeNow($type = 'date')
    {
        if ($type == 'date') {
            return date('Y-m-d');
        } elseif ($type == 'datetime') {
            return date('Y-m-d H:i:s');
        }
    }
     

    public static function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function generateExpiry(): string
    {
        return date(
            'Y-m-d H:i:s',
            strtotime('+' . self::CODE_EXPIRY_MINUTES . ' minutes')
        );
    }

    public function generateReference()
    {
        return date("YmdHis") . $this->randID('numeric', 2); // 2024101312491010
    }

    public function randID(string $character, int $length = 5): string
    {

        $numericChars = '0123456789';
        $alphaChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphanumericChars = $numericChars . $alphaChars;

        $idNumber = '';

        switch (strtolower($character)) {
            case 'numeric':
                $min = 10 ** ($length - 1);
                $max = (10 ** $length) - 1;
                $idNumber = (string) random_int($min, $max);
                break;

            case 'alphabetic':
            case 'alpha':
                for ($i = 0; $i < $length; $i++) {
                    $idNumber .= $alphaChars[random_int(0, strlen($alphaChars) - 1)];
                }
                break;

            case 'alphanumeric':
                for ($i = 0; $i < $length; $i++) {
                    $idNumber .= $alphanumericChars[random_int(0, strlen($alphanumericChars) - 1)];
                }
                break;

            default:
                throw new InvalidArgumentException('Unsupported character type. Use "numeric", "alphabetic", or "alphanumeric".');
        }

        return $idNumber;
    }

    public function generateApiToken()
    {
        return $this->randID('alphanumeric', 64);
    }

    public function returnFormInput($name)
    {
        $formInput = '';
        if (isset($_SESSION['formInput'][$name])) {
            $formInput = $_SESSION['formInput'][$name];
            // unset($_SESSION['formInput'][$name]);
        }
        echo $formInput;
    }

   

    public static function log_txt(string $logFile, string|array|null $dataToLog, string $filePath)
    {
        // Define the full path to store today's logs
        $todayStoragePath = BASE_PATH . "/" . $filePath . "/" . date("Y-m-d") . "/";

        // Check if the directory exists, if not, create it with the appropriate permissions
        if (!is_dir($todayStoragePath)) {
            mkdir($todayStoragePath, 0755, true); // Declare file permission, set recursive to true
        }

        // Define the full log file path
        $logFile = $todayStoragePath . $logFile . ".txt";

        // Ensure the file exists, if not, create an empty file
        if (!file_exists($logFile)) {
            touch($logFile); // Creates an empty file
        }

        // Convert array to JSON string, or just use the string as-is
        $data = is_array($dataToLog) ? json_encode($dataToLog) : $dataToLog;

        // Prepare log contents with timestamp
        $arrayContents = [
            "==========" . date("H:i:s") . "============",
            $data,
            "===========end============",
        ];

        // Write each content line to the log file, appending it
        foreach ($arrayContents as $content) {
            file_put_contents($logFile, $content . "\r\n", FILE_APPEND);
        }
    }

    public function transformRemoveDuplicate($receiver)
    {
        try {

            //Convert the recipient...
            $recipient_explode = explode(",", str_replace("'", '', $receiver));
            $validNumber = [];

            foreach ($recipient_explode as $recipientExplode) {
                //We need to remove spaces and symbols out...
                $filterNo = str_replace(array(" ", "+", "'", "/", '"'), "", $recipientExplode);

                if (substr($filterNo, 0, 1) == 0 and strlen($filterNo) == 11) {
                    $validNumber[] = '234' . substr($filterNo, 1);
                } else if (strlen($filterNo) >= 11) {
                    $validNumber[] = $recipientExplode;
                }
            }

            if (!empty($validNumber)) {
                $responseBody = implode(",", array_unique($validNumber));
            } else {
                $responseBody = false;
            }

            return $responseBody;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function isEmail($data): bool
    {
        try {
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        } catch (Throwable $e) {
            throw $e;
        }
    }


    public function paymentProviderImages(string $gateway_code)
    {
        switch ($gateway_code) {
            case "paystack":
                $imagePath = route("assets/img/paymentgateways/paystack.png");
                break;
            case "flutterwave":
                $imagePath = route("assets/img/paymentgateways/flutterwave_logo.png");
                break;
            default:
                $imagePath = route("assets/img/paymentgateways/default.png");
                break;
        }

        return $imagePath;
    }

    // Use to check if input has no space and consist of a number minimum, then meet the number of length too
    public function validateInput($inputValue, $minLength = 5)
    {
        // Regular expression to check:
        // ^ = start of string
        // (?=.*\d) = must contain at least one digit
        // \S{minLength,} = no spaces, and at least minLength characters long
        $regex = '/^(?=.*\d)\S{' . $minLength . ',}$/';

        // Test the input value against the regular expression
        if (preg_match($regex, $inputValue)) {
            return true;
        } else {
            return false;
        }
    }

    //sanitize my inputs
    public static function sanitizeInput($inputValue): string
    {
        $inputValue = trim($inputValue);
        $inputValue = strip_tags($inputValue);

        return $inputValue;
    }

    function removeArrayIndexes(&$array, $indexes)
    {
        foreach ($indexes as $index) {
            if (isset($array[$index])) {
                unset($array[$index]); // Remove the index
            }
        }
    }

    public function hashPassword(string $password)
    {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
            $this->responseBody = $hash;
            return $this->responseBody;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function calcTimeLeftInMin($unblockTime)
    {
        try {
            // Convert the unblock_time to a DateTime object
            $unblockTime = new DateTime($unblockTime);

            // Get the current time
            $currentTime = new DateTime();

            // Check if the unblock time has already passed
            if ($currentTime >= $unblockTime) {
                return false; // Return message indicating the account is unblocked
            }

            // Calculate the difference between the unblock_time and current time
            $interval = $currentTime->diff($unblockTime);

            // Calculate total minutes left
            $totalMinutesLeft = ($interval->h * 60) + $interval->i; // Convert hours to minutes and add remaining minutes

            // Format the duration left as minutes and seconds
            $durationLeft = sprintf('%d minutes and %d seconds', $totalMinutesLeft, $interval->s);

            return $durationLeft;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function slugify($text)
    {
        try {
            $text = str_replace(array("'", "_", "."), "", $text);
            $text = str_replace(array(" "), "_", $text);
            return strtolower($text);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function check_date($given_date, $checkType = 'future_date')
    {
        try {
            $date = DateTime::createFromFormat('Y-m-d', $given_date);
            if ($date && $date->format('Y-m-d') === $given_date) {
                $rephrased_date = $date->format('Y-m-d');
                if ($checkType == 'future_date') {
                    return strtotime($rephrased_date) >= strtotime(date("Y-m-d")) ? true : false;
                }

                if ($checkType == 'past_date') {
                    return strtotime($rephrased_date) < strtotime(date("Y-m-d")) ? true : false;
                }
            } else {
                return "Invalid date format.";
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function check_date_time($given_date_time, $checkType = 'future_date')
    {
        try {
            // Parse the given date and time string into a DateTime object
            $dateTime = DateTime::createFromFormat('Y-m-d H:i', $given_date_time);

            if ($dateTime && $dateTime->format('Y-m-d H:i') === $given_date_time) {
                // Current date and time
                $currentDateTime = new DateTime();

                if ($checkType == 'future_date') {
                    return $dateTime > $currentDateTime;
                }

                if ($checkType == 'past_date') {
                    return $dateTime < $currentDateTime;
                }
            } else {
                return "Invalid date-time format.";
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }


    public function encryptData($data, $cipher = 'AES-256-CBC')
    {
        $iv_length = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $cipher, ENCRYPT_CODE, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decryptData($encryptedData, $cipher = 'AES-256-CBC')
    {
        try {
            // Validate the input format
            $decodedData = base64_decode($encryptedData, true);
            if ($decodedData === false || strpos($decodedData, '::') === false) {
                $this->log_txt('decrypt_data', 'Invalid encrypted data format: ' . $encryptedData, 'logs');
                return null;
            }

            // Extract the encrypted string and IV
            list($encrypted, $iv) = explode('::', $decodedData, 2);

            // Validate IV length
            $ivLength = openssl_cipher_iv_length($cipher);
            if (strlen($iv) !== $ivLength) {
                $this->log_txt('decrypt_data', 'Invalid IV length: ' . $encryptedData, 'logs');
                return null;
            }

            // Decrypt the data
            $decrypted = openssl_decrypt($encrypted, $cipher, ENCRYPT_CODE, 0, $iv);
            if ($decrypted === false) {
                $this->log_txt('decrypt_data', 'Decryption failed: ' . $encryptedData, 'logs');
                return null;
            }

            return $decrypted;
        } catch (Throwable $e) {
            $this->log_txt('decrypt_data', 'Decryption Error: ' . $e->getMessage(), 'logs');
            return null;
        }
    }

    public function formatNumber($number)
    {
        try {
            $suffix = '';
            if ($number >= 1000000000000000) {
                $number = $number / 1000000000000000;
                $suffix = 'Q'; //Quadrillion
            } else if ($number >= 1000000000000) {
                $number = $number / 1000000000000;
                $suffix = 'T';
            } else if ($number >= 1000000000) {
                $number = $number / 1000000000;
                $suffix = 'B';
            } elseif ($number >= 1000000) {
                $number = $number / 1000000;
                $suffix = 'M';
            } elseif ($number >= 1000) {
                $number = $number / 1000;
                $suffix = 'K';
            }

            return number_format($number, 2) . $suffix;
        } catch (Throwable $e) {
            throw $e;
        }
    }



    public function isStrictlyNumber($input): bool
    {
        return preg_match('/^\d+(\.\d+)?$/', $input);
    }

    public function generateSlug(string $data): string
    {
        // Convert to lowercase
        $string = strtolower($data);

        // Replace spaces and hyphens with underscores
        $string = preg_replace('/[\s\-]+/', '_', $string);

        // Remove multiple underscores (just in case)
        $string = preg_replace('/_+/', '_', $string);

        // Trim leading/trailing underscores
        return trim($string, '_');
    }

    function clean_irregular_spaces($string)
    {
        // Trim overall string
        $string = trim($string);

        // Remove spaces around hyphens and underscores
        $string = preg_replace('/\s*([-_])\s*/', '$1', $string);

        // Replace multiple spaces with a single space
        $string = preg_replace('/\s+/', ' ', $string);

        return $string;
    }

    public function isTokenBlacklisted(string $token): bool{
        $request = $this->db->fetch('SELECT * FROM jwt_blacklist WHERE token = ?', [$token]);
        return !$request;
    }
}
