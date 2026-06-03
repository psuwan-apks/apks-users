<?php
// Set timezone
date_default_timezone_set('Asia/Bangkok');

function get($name, $default = '')
{
    return $_REQUEST[$name] ?? $default;
}

function token_gen($length)
{
    // Define the characters to use in the token
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomToken = '';

    // Generate the random token
    for ($i = 0; $i < $length; $i++) {
        $randomToken .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomToken;
}

function log_event($event_type, $result, $message, $user = null, $context = [], $log_file = null)
{
    if ($log_file === null) {
        $log_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'events.log';
    }

    $payload = [
        'timestamp'   => date('c'),
        'event_type'  => $event_type,
        'result'      => $result,
        'message'     => $message,
        'user'        => $user,
    ];

    if (!empty($context)) {
        $payload['context'] = $context;
    }

    $dir = dirname($log_file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $bytes = @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
    return $bytes !== false;
}

/**
 * Generate a RFC-4122 version 4 UUID and return as BINARY(16).
 * By default returns in "MySQL UUID_TO_BIN(...,1)" ordering (swap=true).
 *
 * PHP 7.4 compatible.
 */

/**
 * Generate UUID v4 as binary(16).
 *
 * @param bool $swap If true produce MySQL-optimized ordering (UUID_TO_BIN(uuid, 1)).
 * @return string 16-byte binary string
 * @throws Exception on random_bytes failure
 */
function generateUuidV4Binary(bool $swap = true): string
{
    $data = random_bytes(16);
    $bytes = array_values(unpack('C*', $data)); // 0-indexed

    // Set version (4) in time_high_and_version (byte 6)
    $bytes[6] = ($bytes[6] & 0x0f) | 0x40;
    // Set variant (10xx) in clock_seq_hi_and_reserved (byte 8)
    $bytes[8] = ($bytes[8] & 0x3f) | 0x80;

    if ($swap) {
        // MySQL UUID_TO_BIN(...,1) ordering: High, Mid, Low, Seq, Node
        // Standard: Low(0-3), Mid(4-5), High(6-7), Seq(8-9), Node(10-15)
        // Output: High(6-7), Mid(4-5), Low(0-3), Seq(8-9), Node(10-15)
        $order = [6, 7, 4, 5, 0, 1, 2, 3, 8, 9, 10, 11, 12, 13, 14, 15];
        $out = [];
        foreach ($order as $i) {
            $out[] = $bytes[$i];
        }
        return pack('C*', ...$out);
    }

    return pack('C*', ...$bytes);
}

/**
 * Convert 16-byte binary UUID to canonical string (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).
 *
 * @param string $bin 16-byte binary UUID
 * @param bool $swap If true, assume input is in MySQL swapped ordering and undo it.
 * @return string UUID string
 */
function binaryToUuid(string $bin, bool $swap = true): string
{
    if (strlen($bin) !== 16) {
        throw new InvalidArgumentException('binaryToUuid expects 16 bytes');
    }

    $hex = bin2hex($bin);

    if ($swap) {
        // MySQL BIN_TO_UUID(..., 1)
        // Input: High(4chars) Mid(4) Low(8) Seq(4) Node(12)
        // Output: Low(8) Mid(4) High(4) Seq(4) Node(12)
        
        $high = substr($hex, 0, 4);
        $mid = substr($hex, 4, 4);
        $low = substr($hex, 8, 8);
        $seq = substr($hex, 16, 4);
        $node = substr($hex, 20);
        
        $hex = $low . $mid . $high . $seq . $node;
    }

    // Format: 8-4-4-4-12
    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20)
    );
}

/**
 * Convert UUID string to 16-byte binary. Accepts canonical UUID (with dashes).
 *
 * @param string $uuid e.g. "550e8400-e29b-41d4-a716-446655440000"
 * @param bool $swap If true, return MySQL swapped ordering (UUID_TO_BIN(...,1) style)
 * @return string 16-byte binary
 */
function uuidToBinary(string $uuid, bool $swap = true): string
{
    // normalize to hex only
    $hex = str_replace('-', '', $uuid);
    if (strlen($hex) !== 32 || !ctype_xdigit($hex)) {
        throw new InvalidArgumentException('Invalid UUID string');
    }
    
    if ($swap) {
        // MySQL UUID_TO_BIN(..., 1)
        // Input: Low(8) Mid(4) High(4) Seq(4) Node(12)
        // Output: High(4) Mid(4) Low(8) Seq(4) Node(12)
        
        $low = substr($hex, 0, 8);
        $mid = substr($hex, 8, 4);
        $high = substr($hex, 12, 4);
        $seq = substr($hex, 16, 4);
        $node = substr($hex, 20);
        
        $hex = $high . $mid . $low . $seq . $node;
    }
    
    return hex2bin($hex);
}
