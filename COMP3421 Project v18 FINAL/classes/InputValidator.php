<?php
class InputValidator {
    /**
     * Validate and sanitize a text input
     * 
     * @param string $input The input to validate
     * @param int $minLength Minimum length (optional)
     * @param int $maxLength Maximum length (optional)
     * @param string $regex Regular expression pattern to match (optional)
     * @return array ['valid' => bool, 'sanitized' => string, 'errors' => array]
     */
    public static function validateText($input, $minLength = null, $maxLength = null, $regex = null) {
        $errors = [];
        $sanitized = self::sanitizeText($input);
        
        // Check minimum length
        if ($minLength !== null && strlen($sanitized) < $minLength) {
            $errors[] = "Input must be at least $minLength characters long";
        }
        
        // Check maximum length
        if ($maxLength !== null && strlen($sanitized) > $maxLength) {
            $errors[] = "Input must be less than $maxLength characters long";
        }
        
        // Check regex pattern
        if ($regex !== null && !preg_match($regex, $sanitized)) {
            $errors[] = "Input format is invalid";
        }
        
        return [
            'valid' => empty($errors),
            'sanitized' => $sanitized,
            'errors' => $errors
        ];
    }
    
    /**
     * Validate and sanitize an email address
     * 
     * @param string $email The email to validate
     * @return array ['valid' => bool, 'sanitized' => string, 'errors' => array]
     */
    public static function validateEmail($email) {
        $errors = [];
        $sanitized = self::sanitizeText($email);
        
        // Use PHP's built-in email validation
        if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        return [
            'valid' => empty($errors),
            'sanitized' => $sanitized,
            'errors' => $errors
        ];
    }
    
    /**
     * Validate and sanitize a username
     * 
     * @param string $username The username to validate
     * @return array ['valid' => bool, 'sanitized' => string, 'errors' => array]
     */
    public static function validateUsername($username) {
        // Usernames should be 3-50 characters and contain only alphanumeric chars, underscores, and hyphens
        return self::validateText(
            $username, 
            3, 
            50, 
            '/^[a-zA-Z0-9_-]+$/'
        );
    }
    
    /**
     * Validate and sanitize a URL
     * 
     * @param string $url The URL to validate
     * @return array ['valid' => bool, 'sanitized' => string, 'errors' => array]
     */
    public static function validateUrl($url) {
        $errors = [];
        $sanitized = self::sanitizeText($url);
        
        // Use PHP's built-in URL validation
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid URL format";
        }
        
        return [
            'valid' => empty($errors),
            'sanitized' => $sanitized,
            'errors' => $errors
        ];
    }
    
    /**
     * Validate an integer within a specified range
     * 
     * @param mixed $input The input to validate
     * @param int $min Minimum value (optional)
     * @param int $max Maximum value (optional)
     * @return array ['valid' => bool, 'sanitized' => int, 'errors' => array]
     */
    public static function validateInteger($input, $min = null, $max = null) {
        $errors = [];
        
        // Try to convert to integer
        $sanitized = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($sanitized === false) {
            $errors[] = "Input must be an integer";
            return [
                'valid' => false,
                'sanitized' => null,
                'errors' => $errors
            ];
        }
        
        // Check minimum value
        if ($min !== null && $sanitized < $min) {
            $errors[] = "Value must be at least $min";
        }
        
        // Check maximum value
        if ($max !== null && $sanitized > $max) {
            $errors[] = "Value must be at most $max";
        }
        
        return [
            'valid' => empty($errors),
            'sanitized' => $sanitized,
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize text input to prevent XSS attacks
     * 
     * @param string $input The input to sanitize
     * @return string Sanitized input
     */
    public static function sanitizeText($input) {
        // Convert special characters to HTML entities
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Escape HTML in text for safe output
     * 
     * @param string $input The input to escape
     * @return string Escaped input
     */
    public static function escapeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize data for database insertion (removes HTML and PHP tags)
     * 
     * @param string $input The input to sanitize
     * @return string Sanitized input
     */
    public static function sanitizeForDb($input) {
        return strip_tags(trim($input));
    }
    
    /**
     * Validate against common attack patterns
     * 
     * @param string $input The input to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateAgainstAttacks($input) {
        $errors = [];
        
        // Check for SQL injection attempts
        if (preg_match('/(union|select|insert|update|delete|drop|alter|exec|--|\/\*|\*\/)/i', $input)) {
            $errors[] = "Potential SQL injection detected";
        }
        
        // Check for XSS attempts
        if (preg_match('/<script|javascript:|on\w+=/i', $input)) {
            $errors[] = "Potential XSS attack detected";
        }
        
        // Check for path traversal attempts
        if (preg_match('/\.\.\/|\.\.\\\/i', $input)) {
            $errors[] = "Path traversal attempt detected";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?> 