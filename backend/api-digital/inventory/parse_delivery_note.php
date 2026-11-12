<?php
/**
 * API: Parse Delivery Note / Invoice Image or PDF using Gemini AI
 * Extracts vendor info, delivery date, invoice number, item list, quantities, units, prices, lot, and expiry
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_log_helper.php';

// Helper to get environment variable from backend/.env
function getEnvValue(string $key, string $default = ''): string {
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
function setEnvValue(string $key, string $value): void {
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
            'message' => 'ยังไม่ได้ระบุ Google Gemini API Key กรุณากรอก API Key เพื่อเปิดใช้งานระบบ AI อ่านใบส่งของ (สามารถรับ Key ฟรีได้ที่ aistudio.google.com)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $warehouse_id = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : (int)($_GET['warehouse_id'] ?? 0);

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);
        if (!empty($jsonInput['base64_data'])) {
            $mimeType = $jsonInput['mime_type'] ?? 'image/jpeg';
            $base64Data = $jsonInput['base64_data'];
            if (strpos($base64Data, ',') !== false) {
                list(, $base64Data) = explode(',', $base64Data, 2);
            }
            if (!empty($jsonInput['warehouse_id'])) {
                $warehouse_id = (int)$jsonInput['warehouse_id'];
            }
        } else {
            throw new Exception('กรุณาเลือกไฟล์ใบส่งของ หรือเอกสารรับเข้า (รูปภาพ หรือ PDF)');
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

    $supportedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
    if (!in_array(strtolower($mimeType), $supportedMimes)) {
        if (strpos($mimeType, 'image/') === 0) {
            $mimeType = 'image/jpeg';
        } else {
            throw new Exception("ประเภทไฟล์ ({$mimeType}) ไม่รองรับ กรุณาใช้ไฟล์รูปภาพ (JPG, PNG) หรือ PDF");
        }
    }

    // Specialized Prompt for Thai Delivery Notes (ใบส่งของ / ใบกำกับภาษี / ใบเสร็จรับเงิน)
    $prompt = <<<EOT
You are an expert AI parser specialized in Thai warehouse receiving and procurement documents:
- ใบส่งของ (Delivery Order / Delivery Note)
- ใบกำกับภาษี / ใบเสร็จรับเงิน (Tax Invoice / Receipt)
- บิลส่งสินค้า / บิลเงินสด (Cash Bill / Packing Slip)

Analyze this uploaded document image/PDF and extract all invoice headers and item table details into a clean structured JSON object.

Extract the following fields accurately:
1. vendor_name: Full supplier/company name (e.g. หจก. อุบลคอมพิวเตอร์ แอนด์ เทเลคอมเซอร์วิส, บริษัท ซัมซุง อิเลคโทรนิคส์, บริษัท สยาม พัสดุ)
2. vendor_tax_id: 13-digit Tax ID (เลขประจำตัวผู้เสียภาษี) if available
3. invoice_no: The invoice number or delivery note number (เลขที่ใบส่งของ, เลขที่ใบกำกับภาษี, เลขที่บิล, เช่น IV-2569/001, DO-6902, 67-089)
4. delivery_date: Date of document or delivery in YYYY-MM-DD format (convert Thai Buddhist year e.g. 2567 or 2569 to CE 2024 or 2026)
5. po_number: Reference PO number if visible on the document (เลขที่ใบสั่งซื้อ เช่น PO-69001) or null
6. total_amount: Grand total amount (ยอดรวมสุทธิทั้งสิ้น) as float
7. remark: Any visible notes, payment conditions, or special instructions
8. items: Array of delivered items from the table:
   - item_name: Thai description/name of the item
   - item_code: Item model, part code, cartridge code, brand, or SKU if visible (e.g. "MLT-D203E", "MX-60AT", "CAT6-UTP", "A4 80g Double A") or ""
   - lot_number: Lot or Batch number if mentioned (e.g. "Lot 240815", "Batch B-01") or ""
   - expiry_date: Expiry date in YYYY-MM-DD if mentioned (or null)
   - qty: Delivered quantity as float/int (e.g. 5, 10.5)
   - unit_name: Unit of measurement in Thai (e.g. กล่อง, ตลับ, อัน, เครื่อง, เส้น, แผ่น, ชิ้น, รีม, แพ็ค, ชุด)
   - unit_price: Price per unit as float (0.00 if not shown)
   - total_price: Line total amount as float (0.00 if not shown)

Output format must be ONLY raw valid JSON (no markdown text, no explanation outside JSON):
{
  "vendor_name": "...",
  "vendor_tax_id": "...",
  "invoice_no": "...",
  "delivery_date": "YYYY-MM-DD",
  "po_number": null,
  "total_amount": 0.00,
  "remark": "...",
  "items": [
    {
      "item_name": "...",
      "item_code": "...",
      "lot_number": "",
      "expiry_date": null,
      "qty": 1,
      "unit_name": "...",
      "unit_price": 0.00,
      "total_price": 0.00
    }
  ]
}
EOT;

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
        unset($ch);

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
            $cleanVName = preg_replace('/^(บริษัท|ห้างหุ้นส่วนจำกัด|หจก\.|บจก\.)\s*/u', '', $vName);
            $stV = $pdo2->prepare("SELECT * FROM inv_vendors WHERE vendor_name LIKE ? OR vendor_name LIKE ? LIMIT 1");
            $stV->execute(["%{$vName}%", "%{$cleanVName}%"]);
        }
        $matchedVendor = $stV->fetch(PDO::FETCH_ASSOC);
        if ($matchedVendor) {
            $parsed['vendor_id'] = $matchedVendor['id'];
            $parsed['vendor_code'] = $matchedVendor['vendor_code'];
            $parsed['matched_vendor_name'] = $matchedVendor['vendor_name'];
            $parsed['matched_vendor'] = true;
        } else {
            $parsed['vendor_id'] = '';
            $parsed['matched_vendor'] = false;
        }
    }

    // Match items with existing inv_items for the warehouse
    if (!empty($pdo2) && !empty($parsed['items']) && is_array($parsed['items'])) {
        // Fetch all active items for this warehouse to perform fuzzy / clean matching
        $whParam = $warehouse_id > 0 ? "AND warehouse_id = " . (int)$warehouse_id : "";
        $stmtAllItems = $pdo2->query("
            SELECT i.id, i.item_code, i.item_name, i.item_name_en, i.unit_id, i.standard_price, i.lot_control, i.expiry_control,
                   u.name as unit_name
            FROM inv_items i
            LEFT JOIN inv_units u ON i.unit_id = u.id
            WHERE i.active = 1 {$whParam}
        ");
        $masterItems = $stmtAllItems->fetchAll(PDO::FETCH_ASSOC);

        foreach ($parsed['items'] as &$it) {
            $itName = trim($it['item_name'] ?? '');
            $itCode = trim($it['item_code'] ?? '');
            
            $bestMatch = null;

            // 1. Match by item_code if provided
            if (!empty($itCode)) {
                foreach ($masterItems as $m) {
                    if (strcasecmp(trim($m['item_code']), $itCode) === 0 ||
                        (!empty($m['item_name_en']) && stripos($m['item_name_en'], $itCode) !== false) ||
                        stripos($m['item_name'], $itCode) !== false) {
                        $bestMatch = $m;
                        break;
                    }
                }
            }

            // 2. Match by exact or partial item_name
            if (!$bestMatch && !empty($itName)) {
                $cleanItName = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $itName);
                $keywords = array_filter(explode(' ', $cleanItName), function($w) {
                    return mb_strlen(trim($w)) >= 3;
                });

                $maxScore = 0;
                foreach ($masterItems as $m) {
                    $mName = $m['item_name'] . ' ' . ($m['item_name_en'] ?? '');
                    if (strcasecmp(trim($m['item_name']), $itName) === 0) {
                        $bestMatch = $m;
                        break;
                    }
                    if (stripos($mName, $itName) !== false || stripos($itName, $m['item_name']) !== false) {
                        $bestMatch = $m;
                        break;
                    }

                    // Keyword matching score
                    $score = 0;
                    foreach ($keywords as $kw) {
                        if (stripos($mName, $kw) !== false) {
                            $score++;
                        }
                    }
                    if ($score > $maxScore && $score >= 2) {
                        $maxScore = $score;
                        $bestMatch = $m;
                    }
                }
            }

            if ($bestMatch) {
                $it['matched_item_id'] = $bestMatch['id'];
                $it['matched_item_code'] = $bestMatch['item_code'];
                $it['matched_item_name'] = $bestMatch['item_name'];
                $it['matched_unit_id'] = $bestMatch['unit_id'];
                $it['matched_unit_name'] = $bestMatch['unit_name'] ?: $it['unit_name'];
                $it['lot_control'] = (int)$bestMatch['lot_control'];
                $it['expiry_control'] = (int)$bestMatch['expiry_control'];
                $it['is_matched'] = true;
            } else {
                $it['matched_item_id'] = 0;
                $it['matched_item_code'] = '';
                $it['matched_item_name'] = '';
                $it['matched_unit_id'] = 0;
                $it['matched_unit_name'] = $it['unit_name'] ?? 'หน่วย';
                $it['lot_control'] = 0;
                $it['expiry_control'] = 0;
                $it['is_matched'] = false;
            }

            // Default selected for import
            $it['selected'] = true;
        }
        unset($it);
    }

    recordInvActivityLog($pdo2, [
        'warehouse_id' => isset($warehouseId) && $warehouseId ? (int)$warehouseId : null,
        'page_code' => 'receive',
        'page_name' => 'ตรวจรับพัสดุเข้าคลัง',
        'action' => 'AI_PARSE_DELIVERY_NOTE',
        'target_type' => 'delivery_note',
        'target_id' => isset($parsed['invoice_no']) ? (string)$parsed['invoice_no'] : null,
        'details' => 'สแกนและอ่านใบส่งของด้วย AI จำนวน ' . count($parsed['items'] ?? []) . ' รายการ',
        'meta' => ['items_count' => count($parsed['items'] ?? []), 'vendor_name' => $parsed['vendor_name'] ?? null],
    ], $pdo3 ?? null);

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
