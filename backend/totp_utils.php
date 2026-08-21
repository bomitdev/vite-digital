<?php

class GoogleAuthenticator {

    public function verifyCode(string $secret, string $code, int $discrepancy = 1, ?int $currentTimeSlice = null): bool {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }

        if (strlen($code) != 6) {
            return false;
        }

        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($this->timingSafeEquals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    public function getCode(string $secret, ?int $timeSlice = null): string {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretkey = $this->base32Decode($secret);

        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        
        // Unpack binary value
        $value = unpack('N', $hashpart);
        $value = $value[1];
        
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;
        
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret) {
        if (empty($secret)) {
            return '';
        }
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], str_split($base32chars))) {
                return false;
            }
            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }
        return $binaryString;
    }

    private function timingSafeEquals(string $safeString, string $userString): bool {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $userLen; ++$i) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }

        return $result === 0;
    }
}
