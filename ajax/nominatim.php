<?php
header("Content-Type: application/json");

// Ensure session is started to access user's location context
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raw_q = isset($_GET["q"]) ? trim((string)$_GET["q"]) : '';
$q   = $raw_q !== '' ? urlencode($raw_q) : null;
$lat = isset($_GET["lat"]) ? $_GET["lat"] : null;
$lon = isset($_GET["lon"]) ? $_GET["lon"] : null;

// Get user's country/county from session for localized search
$user_country_code = $_SESSION['country_code'] ?? null;
$user_county       = $_SESSION['county'] ?? null;

if ($q) {
    // Build search URL with optional country/county bias
    $search_query = $raw_q;

    // Bias the query text towards the user's county/state without excluding
    // valid results if Nominatim stores the area under a different field.
    if (!empty($user_county) && stripos($search_query, (string)$user_county) === false) {
        $search_query .= ', ' . (string)$user_county;
    }

    $url = "https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=" . urlencode($search_query);
    
    // Add country bias to search if available (improves relevance)
    if (!empty($user_country_code)) {
        $url .= "&countrycodes=" . strtolower((string)$user_country_code);
    }
} elseif ($lat && $lon) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
} else {
    echo json_encode(["error" => "missing parameters"]);
    exit;
}

$opts = [
    "http" => [
        "header" => "User-Agent: RescueCentre/1.0 (yourdomain)\r\n"
    ]
];

$response = file_get_contents($url, false, stream_context_create($opts));
$data     = json_decode($response, true);

// Prefer local county/state matches first, but keep all results as fallback.
if (!empty($user_county) && is_array($data)) {
    $county_fields = ['county', 'state', 'state_district', 'province', 'region'];

    usort($data, function ($a, $b) use ($user_county, $county_fields) {
        $score = static function ($item) use ($user_county, $county_fields): int {
            $address = is_array($item['address'] ?? null) ? $item['address'] : [];
            foreach ($county_fields as $field) {
                if (isset($address[$field]) && stripos((string)$address[$field], (string)$user_county) !== false) {
                    return 1;
                }
            }
            return 0;
        };

        return $score($b) <=> $score($a);
    });

    $data = array_values($data);
}

echo json_encode($data);
