<?php
/**
 * API: Parse Quotation Image/PDF using Gemini AI
 * Extracts vendor info, item list, prices, quantities, and VAT mode
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Helper to get environment variable from backend/.env
function getEnvValue($key, $default = '') {
    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($k, $v) = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if ($k === $key) {
                    return $v;
                }
            }
        }
    }
    return getenv($key) ?: $default;
}

// Helper to save env value to backend/.env
function setEnvValue($key, $value) {
    $envPath = __DIR__ . '/../../.env';
    $content = file_exists($envPath) ? file_get_contents($envPath) : '';
    if (preg_match("/^{$key}=.*/m", $content)) {
        $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
    } else {
        $content .= "\n{$key}={$value}\n";
    }
    file_put_contents($envPath, $content);
}

// GET request: check if API key exists
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $apiKey = getEnvValue('GEMINI_API_KEY');
    echo json_encode([
        'status' => 'success',
        'has_key' => !empty($apiKey),
        'key_preview' => !empty($apiKey) ? substr($apiKey, 0, 6) . '...' . substr($apiKey, -4) : ''
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $apiKey = getEnvValue('GEMINI_API_KEY');
    
    // Check if user provided API key in the request to save it
    $customKey = trim($_POST['gemini_api_key'] ?? '');
    if (!empty($customKey)) {
        $apiKey = $customKey;
        setEnvValue('GEMINI_API_KEY', $customKey);
    }

    // If just saving API key
    if (isset($_POST['action']) && $_POST['action'] === 'save_key') {
        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึก Gemini API Key เรียบร้อยแล้ว',
            'has_key' => !empty($apiKey)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($apiKey)) {
        echo json_encode([
            'status' => 'need_key',
            'message' => 'ยังไม่ได้ระบุ Google Gemini API Key กรุณากรอก API Key เพื่อเปิดใช้งานระบบ AI อ่านใบเสนอราคา (สามารถรับ Key ฟรีได้ที่ aistudio.google.com)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        // Check if passed as base64 in json
        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);
        if (!empty($jsonInput['base64_data'])) {
            $mimeType = $jsonInput['mime_type'] ?? 'image/jpeg';
            $base64Data = $jsonInput['base64_data'];
            // remove prefix if present
            if (strpos($base64Data, ',') !== false) {
                list(, $base64Data) = explode(',', $base64Data, 2);
            }
        } else {
            throw new Exception('กรุณาเลือกไฟล์ใบเสนอราคา (รูปภาพ หรือ PDF)');
        }
    } else {
        $tmpPath = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'] ?? '';
        $origMime = $_FILES['file']['type'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $mimeType = function_exists('mime_content_type') ? mime_content_type($tmpPath) : '';
        if (empty($mimeType) || $mimeType === 'application/octet-stream') {
            if (in_array($ext, ['jpg', 'jpeg'])) $mimeType = 'image/jpeg';
            elseif ($ext === 'png') $mimeType = 'image/png';
            elseif ($ext === 'webp') $mimeType = 'image/webp';
            elseif ($ext === 'pdf') $mimeType = 'application/pdf';
            elseif (!empty($origMime)) $mimeType = $origMime;
        }

        $fileBytes = file_get_contents($tmpPath);
        $base64Data = base64_encode($fileBytes);
    }

    // Supported mime types for Gemini Vision
    $supportedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
    if (!in_array(strtolower($mimeType), $supportedMimes)) {
        // Fallback to image/jpeg if image
        if (strpos($mimeType, 'image/') === 0) {
            $mimeType = 'image/jpeg';
        } else {
            throw new Exception("ประเภทไฟล์ ({$mimeType}) ไม่รองรับ กรุณาใช้ไฟล์รูปภาพ (JPG, PNG) หรือ PDF");
        }
    }

    // Prompt for Gemini AI
    $prompt = <<<EOT
You are an expert AI parser for Thai procurement documents (ใบเสนอราคา / Quotation).
Analyze this uploaded quotation and extract all details into a clean structured JSON object.

Extract the following fields accurately:
1. vendor_name: Full company/vendor name (e.g. ห้างหุ้นส่วนจำกัด อุบลคอมพิวเตอร์ แอนด์ เทเลคอมเซอร์วิส, บริษัท ริโก้(ประเทศไทย) จำกัด)
2. vendor_tax_id: 13-digit Tax ID (เลขประจำตัวผู้เสียภาษี)
3. vendor_address: Address of the vendor
4. vendor_phone: Phone number (เบอร์โทรศัพท์)
5. quotation_no: Quotation number (เลขที่ใบเสนอราคา)
6. quotation_date: Date of quotation in YYYY-MM-DD format (convert Thai Buddhist year e.g. 2567 to CE 2024 if applicable)
7. vat_mode: 
   - "included" if unit prices/totals already include 7% VAT (รวมภาษีมูลค่าเพิ่มในตัว)
   - "excluded" if 7% VAT is calculated on top separately at the bottom (แยกภาษีมูลค่าเพิ่ม 7%)
   - "none" if VAT is 0% or tax exempt (ยกเว้นภาษี)
8. total_amount: Grand total amount (ยอดรวมสุทธิทั้งสิ้น)
9. items: Array of ordered items, each containing:
   - item_name: Thai description/name of the item
   - item_name_en: English name, model, cartridge code, brand, or detailed specifications (e.g. "Toner Cartridge MX-60AT BA", "ABC Toner Cartridge MLT-D203E", "SSD M.2 500GB NVMe")
   - qty_ordered: Number of quantity (integer or float)
   - unit_name: Unit of measurement in Thai (e.g. ตลับ, อัน, กล่อง, เครื่อง, เส้น, แผ่น)
   - unit_price: Price per unit (float)
   - total_price: Line total price (float)

Output format must be ONLY raw valid JSON:
{
  "vendor_name": "...",
  "vendor_tax_id": "...",
  "vendor_address": "...",
  "vendor_phone": "...",
  "quotation_no": "...",
  "quotation_date": "YYYY-MM-DD",
  "vat_mode": "included",
  "total_amount": 0.00,
  "items": [
    {
      "item_name": "...",
      "item_name_en": "...",
      "qty_ordered": 1,
      "unit_name": "...",
      "unit_price": 0.00,
      "total_price": 0.00
    }
  ]
}
EOT;

    // Call Gemini API with modern high-speed vision models
    $modelsToTry = ['gemini-flash-lite-latest', 'gemini-3.5-flash-lite'];
    $geminiResponse = null;
    $lastError = '';

    foreach ($modelsToTry as $model) {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json'
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && !empty($result)) {
            $geminiResponse = $result;
            break;
        } else {
            $lastError = "Model {$model} returned HTTP {$httpCode}: {$result} {$curlErr}";
        }
    }

    if (!$geminiResponse) {
        throw new Exception("ไม่สามารถติดต่อ Gemini AI ได้ ({$lastError})");
    }

    $resData = json_decode($geminiResponse, true);
    $textOutput = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($textOutput)) {
        throw new Exception('Gemini AI ไม่ส่งคืนข้อความผลลัพธ์');
    }

    // Clean JSON markdown blocks if any
    $cleanJson = trim($textOutput);
    if (preg_match('/```json\s*(.*?)\s*```/s', $cleanJson, $matches)) {
        $cleanJson = trim($matches[1]);
    } elseif (preg_match('/```\s*(.*?)\s*```/s', $cleanJson, $matches)) {
        $cleanJson = trim($matches[1]);
    }

    $parsed = json_decode($cleanJson, true);
    if (!is_array($parsed)) {
        throw new Exception('ผลลัพธ์จาก AI ไม่ใช่รูปแบบ JSON ที่ถูกต้อง: ' . substr($cleanJson, 0, 200));
    }

    // Match vendor with existing vendors in inv_vendors table
    if (!empty($pdo2) && !empty($parsed['vendor_name'])) {
        $vTax = trim($parsed['vendor_tax_id'] ?? '');
        $vName = trim($parsed['vendor_name']);
        
        $stV = null;
        if (!empty($vTax)) {
            $stV = $pdo2->prepare("SELECT * FROM inv_vendors WHERE tax_id = ? OR vendor_name LIKE ? LIMIT 1");
            $stV->execute([$vTax, "%{$vName}%"]);
        } else {
            $stV = $pdo2->prepare("SELECT * FROM inv_vendors WHERE vendor_name LIKE ? LIMIT 1");
            $stV->execute(["%{$vName}%"]);
        }
        $matchedVendor = $stV->fetch(PDO::FETCH_ASSOC);
        if ($matchedVendor) {
            $parsed['vendor_id'] = $matchedVendor['id'];
            $parsed['vendor_code'] = $matchedVendor['vendor_code'];
            $parsed['matched_vendor'] = true;
            if (empty($parsed['vendor_address'])) $parsed['vendor_address'] = $matchedVendor['address'];
            if (empty($parsed['vendor_phone'])) $parsed['vendor_phone'] = $matchedVendor['phone'];
            if (empty($parsed['vendor_tax_id'])) $parsed['vendor_tax_id'] = $matchedVendor['tax_id'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $parsed
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
