<?php
class PasswordPolicy {
    // Minimum password length
    private const MIN_LENGTH = 8;
    
    // Maximum password length
    private const MAX_LENGTH = 100;
    
    // Password must contain at least one uppercase letter
    private const REQUIRE_UPPERCASE = true;
    
    // Password must contain at least one lowercase letter
    private const REQUIRE_LOWERCASE = true;
    
    // Password must contain at least one number
    private const REQUIRE_NUMBER = true;
    
    // Password must contain at least one special character
    private const REQUIRE_SPECIAL = true;
    
    // Special characters that are allowed
    private const SPECIAL_CHARS = '!@#$%^&*()-_=+[]{}|;:,.<>?/';
    
    /**
     * Validates a password against the password policy
     * 
     * @param string $password The password to validate
     * @return array An array with 'valid' boolean and 'errors' array
     */
    public static function validate($password) {
        $errors = [];
        
        // Check password length
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_LENGTH . " characters long";
        }
        
        if (strlen($password) > self::MAX_LENGTH) {
            $errors[] = "Password must be less than " . self::MAX_LENGTH . " characters long";
        }
        
        // Check for uppercase letters
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        // Check for lowercase letters
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        // Check for numbers
        if (self::REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        // Check for special characters
        if (self::REQUIRE_SPECIAL && !preg_match('/[' . preg_quote(self::SPECIAL_CHARS, '/') . ']/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Checks if a password is in a list of commonly breached passwords
     * This is a simplified version - in production, you might want to use an API like "Have I Been Pwned"
     * 
     * @param string $password The password to check
     * @return bool True if password is commonly used/breached, false otherwise
     */
    public static function isBreachedPassword($password) {
        // Common passwords list - in a real application, this would be much larger
        // or would use an external API like "Have I Been Pwned"
        $commonPasswords = [
            'password', '123456', '123456789', '12345678', '12345', 'qwerty',
            'abc123', 'football', 'monkey', 'letmein', '111111', '1234567',
            'dragon', 'baseball', 'sunshine', 'iloveyou', 'trustno1', 'princess',
            'admin', 'welcome', 'password1', 'qwerty123', 'admin123', 'passw0rd'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Generate a secure random password that meets all policy requirements
     * 
     * @return string A secure random password
     */
    public static function generateSecurePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . self::SPECIAL_CHARS;
        $password = '';
        
        // Ensure we have at least one of each required character type
        if (self::REQUIRE_UPPERCASE) $password .= 'A';
        if (self::REQUIRE_LOWERCASE) $password .= 'a';
        if (self::REQUIRE_NUMBER) $password .= '1';
        if (self::REQUIRE_SPECIAL) $password .= self::SPECIAL_CHARS[random_int(0, strlen(self::SPECIAL_CHARS) - 1)];
        
        // Fill the rest with random characters
        $remainingLength = max($length, self::MIN_LENGTH) - strlen($password);
        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }
}
?> 