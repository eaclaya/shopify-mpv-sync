<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class WhatsappDecryptService
{
    private $appInfo = [
        "image" => "WhatsApp Image Keys",
        "video" => "WhatsApp Video Keys",
        "audio" => "WhatsApp Audio Keys",
        "document" => "WhatsApp Document Keys",
        "image/webp" => "WhatsApp Image Keys",
        "image/jpeg" => "WhatsApp Image Keys",
        "image/png" => "WhatsApp Image Keys",
        "video/mp4" => "WhatsApp Video Keys",
        "audio/aac" => "WhatsApp Audio Keys",
        "audio/ogg" => "WhatsApp Audio Keys",
        "audio/wav" => "WhatsApp Audio Keys",
    ];

    private $extension = [
        "image" => "jpg",
        "video" => "mp4",
        "audio" => "ogg",
        "document" => "bin",
    ];

    public function HKDF($key, $length, $appInfo = ""): string
    {
        $key = hash_hmac('sha256', $key, str_repeat("\0", 32), true);
        $keyStream = "";
        $keyBlock = "";
        $blockIndex = 1;
//        $appInfo = pack("H*", bin2hex($appInfo));
        while (strlen($keyStream) < $length) {
            $keyBlock = hash_hmac('sha256', $keyBlock . $appInfo . chr($blockIndex), $key, true);
            $blockIndex++;
            $keyStream .= $keyBlock;
        }
        return substr($keyStream, 0, $length);
    }

    public function AESDecrypt($key, $ciphertext, $iv): bool|string
    {
        return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

    public function decrypt($fileName, $mediaKey, $mediaType, $output): bool
    {
        /* EGfRci4OGx0pHzHbu5diTfctscnEqB9Ak9JHX0oIjtg= */
        $mediaKeyExpanded = $this->HKDF($mediaKey, 112, $this->appInfo[$mediaType]);
        $mediaData = Storage::disk('public')->get($fileName);
//        dump($mediaData);

        $file = substr($mediaData, 0, -10);
        $mac = substr($mediaData, -10);

        $data = $this->AESDecrypt(substr($mediaKeyExpanded, 16, 32), $file, substr($mediaKeyExpanded, 0, 16));

        if ($output === null) {
            if (strpos($mediaType, "/") !== false) {
                $fileExtension = explode("/", $mediaType)[1];
            } else {
                $fileExtension = $this->extension[$mediaType];
            }
            $output = str_replace('.enc', '.' . $fileExtension, $fileName);
        }
        Storage::disk('public')->put($output, $data);

        return true;
    }
}
