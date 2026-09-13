<?php
// Common helper functions - simple style
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function input_string($value, $maxLength = 255)
{
    $value = trim(str_replace("\0", '', (string)$value));
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    if ($value === null) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength, 'UTF-8');
    }
    return substr($value, 0, $maxLength);
}

function valid_name($value, $min = 2, $max = 120)
{
    $len = strlen(trim((string)$value));
    return $len >= $min && $len <= $max;
}

function valid_phone($value)
{
    $value = trim((string)$value);
    return $value === '' || (strlen($value) <= 20 && preg_match('/^\+?[0-9][0-9\s().-]{6,19}$/', $value));
}

function valid_password($value)
{
    return strlen((string)$value) >= 8;
}

function valid_money($value, $min = 0.01, $max = 100000)
{
    return is_numeric($value) && (float)$value >= $min && (float)$value <= $max;
}

function rate_limit($key, $maxAttempts, $seconds)
{
    $now = time();
    $bucketKey = 'rate_' . preg_replace('/[^a-z0-9_.-]/i', '_', $key);
    $bucket = $_SESSION[$bucketKey] ?? ['count' => 0, 'reset' => $now + $seconds];

    if (($bucket['reset'] ?? 0) <= $now) {
        $bucket = ['count' => 0, 'reset' => $now + $seconds];
    }

    $bucket['count']++;
    $_SESSION[$bucketKey] = $bucket;

    return $bucket['count'] <= $maxAttempts;
}

function login_url($redirectTo = '')
{
    $url = BASE_URL . 'authentication/login.php';
    $redirectTo = trim((string)$redirectTo);
    if ($redirectTo !== '') {
        $url .= '?redirect=' . rawurlencode($redirectTo);
    }
    return $url;
}

function money($amount)
{
    return '₹' . number_format((float)$amount, 2);
}

function format_date($date)
{
    if (!$date) return '';
    return date('d M Y', strtotime($date));
}

function asset($path)
{
    return BASE_URL . 'assets/' . ltrim($path, '/');
}

function profile_img($filename)
{
    if ($filename && file_exists(UPLOAD_PROFILES . $filename)) {
        return BASE_URL . 'assets/uploads/profiles/' . $filename;
    }
    return asset('images/default-avatar.svg');
}

function homestay_img($filename)
{
    return homestay_image_url($filename);
}

function upload_asset_url($filename, $uploadDir, $publicDir, $fallback = null)
{
    $fallback = $fallback ?: asset('images/placeholder-homestay.svg');
    $filename = trim((string)($filename ?? ''));

    if ($filename === '') {
        return $fallback;
    }

    $relative = ltrim(str_replace('\\', '/', $filename), '/');
    if (substr($relative, 0, 7) === 'assets/') {
        $localPath = BASE_PATH . $relative;
        if (file_exists($localPath)) {
            return BASE_URL . $relative;
        }
    }

    $basename = basename($relative);
    if ($basename !== '' && file_exists(rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $basename)) {
        return BASE_URL . trim($publicDir, '/') . '/' . rawurlencode($basename);
    }

    return $fallback;
}

function uploaded_asset_urls($uploadDir, $publicDir, $limit = 0)
{
    if (!is_dir($uploadDir)) {
        return [];
    }

    $files = glob(rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];
    usort($files, function ($a, $b) {
        return strcmp(basename($a), basename($b));
    });

    if ($limit > 0) {
        $files = array_slice($files, 0, $limit);
    }

    return array_map(function ($file) use ($publicDir) {
        return BASE_URL . trim($publicDir, '/') . '/' . rawurlencode(basename($file));
    }, $files);
}

function fallback_room_image_url($seed = 0)
{
    $urls = uploaded_asset_urls(UPLOAD_ROOMS, 'assets/uploads/rooms');
    if (empty($urls)) {
        return asset('images/placeholder-homestay.svg');
    }

    return $urls[abs((int)$seed) % count($urls)];
}

function room_image_url($filename, $seed = 0)
{
    return upload_asset_url($filename, UPLOAD_ROOMS, 'assets/uploads/rooms', fallback_room_image_url($seed));
}

function room_photo_urls($imagePaths, $seed = 0)
{
    $urls = [];
    foreach ((array)$imagePaths as $index => $imagePath) {
        $imagePath = trim((string)$imagePath);
        if ($imagePath === '') {
            continue;
        }

        $url = room_image_url($imagePath, $seed + $index);
        if (!in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    }

    if (empty($urls)) {
        $urls[] = fallback_room_image_url($seed);
    }

    return $urls;
}

function fallback_public_rooms()
{
    return [
        // 3 Single Rooms
        [
            'id' => 0,
            'name' => 'Pine Ridge Solo Room',
            'room_type' => 'Single Room',
            'description' => 'A cozy mountain solo room with warm pine wood interiors, comfortable single bed, plush wool duvet, brass reading lamp, and large window framing misty Himalayan pine forests.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1500,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-single-cozy.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Attic Sanctuary Single Room',
            'room_type' => 'Single Room',
            'description' => 'A peaceful attic retreat featuring exposed wooden timber rafters, cozy single bed with traditional Sikkimese quilt, small writing desk, and dormer window opening to alpine peaks.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1650,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-single-attic.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Garden Meadow Single Room',
            'room_type' => 'Single Room',
            'description' => 'A bright ground-floor single room with natural cedar wood finish, attached bath, and large windows looking onto the homestay flower garden and organic orchard.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1400,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-single-garden.jpg',
            'is_fallback' => true,
        ],
        // 3 Double Rooms
        [
            'id' => 0,
            'name' => 'Kanchenjunga Vista Double',
            'room_type' => 'Double Room',
            'description' => 'A premier double room featuring a handcrafted king-sized wooden bed, soft linen, warm ambient lighting, and panoramic bay windows overlooking the majestic Kanchenjunga range.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 2400,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-double-kanchenjunga.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Himalayan Balcony Double',
            'room_type' => 'Double Room',
            'description' => 'A romantic couple room featuring wooden floors, artisanal textiles, tea-seating nook, and direct access to a private wooden balcony with valley breezes.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 2100,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-double-balcony.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Heritage Timber Double Room',
            'room_type' => 'Double Room',
            'description' => 'Richly finished in local pine and cedar, this heritage room offers a comfortable queen bed, carved wooden accents, traditional brass kettle set, and scenic hill views.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 2250,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-double-heritage.jpg',
            'is_fallback' => true,
        ],
        // 3 Family Rooms
        [
            'id' => 0,
            'name' => 'Pinewood Family Suite',
            'room_type' => 'Family Room',
            'description' => 'A spacious multi-bed family suite with two queen beds, warm wooden paneling, traditional choktse tea table, and expansive forest and valley viewpoints.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 3600,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-family-suite.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Valley View Family Cottage',
            'room_type' => 'Family Room',
            'description' => 'A cottage-style family haven with exposed stone accents, two large beds, wool rugs, cozy sitting space, and sweeping views of the Khecheopalri ridges.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 3850,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-family-cottage.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Alpine Loft Family Room',
            'room_type' => 'Family Room',
            'description' => 'A warm, split-layout family room with two comfortable double beds, wooden ceiling beams, dedicated seating area, and morning sunrise views over the Sikkim hills.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 3400,
            'cleaning_fee' => 0,
            'cover_image' => 'sikkim-family-loft.jpg',
            'is_fallback' => true,
        ],
    ];
}

function gallery_meta_for_file($filename)
{
    static $map = [
        'img_1787408512_7207.jpg' => ['title' => 'Misty Mountain Ranges of West Sikkim', 'city' => 'West Sikkim'],
        'img_1787408513_8655.jpeg' => ['title' => 'Snow-Capped Kanchenjunga Peak', 'city' => 'West Sikkim'],
        'img_1787408513_5684.jpg' => ['title' => 'Himalayan Butterfly on Wildflower', 'city' => 'West Sikkim'],
        'img_1787408513_3255.jpg' => ['title' => 'Rhododendron Blossoms & Snow Peaks', 'city' => 'West Sikkim'],
        'img_1787408513_2903.jpg' => ['title' => 'Tibetan Snow Lion Guardian at Monastery', 'city' => 'West Sikkim'],
        'img_1787408513_2774.jpg' => ['title' => 'Golden Valley Sunset in West Sikkim', 'city' => 'West Sikkim'],
        'img_1787408513_2574.jpeg' => ['title' => 'Green Meadow & Mountain Ridge', 'city' => 'West Sikkim'],
        'img_1787408513_4199.jpg' => ['title' => 'Sonam Homestay Village Cottages', 'city' => 'West Sikkim'],
        'img_1787408513_1035.jpeg' => ['title' => 'Travelers Gathering with Royal Enfield', 'city' => 'West Sikkim'],
        'img_1787408513_2150.jpeg' => ['title' => 'Sikkim Sacred Lakes Trek Night Camping', 'city' => 'West Sikkim'],
        'img_1787408513_7151.jpeg' => ['title' => 'Morning Meditation on the Ridge', 'city' => 'West Sikkim'],
        'img_1787408513_8162.jpeg' => ['title' => 'Winter Snowfall & Prayer Flags', 'city' => 'West Sikkim'],
        'img_1787408514_1785.jpeg' => ['title' => 'Cozy Dining with Guests & Host Family', 'city' => 'West Sikkim'],
        'img_1787408514_6766.jpeg' => ['title' => 'Golden Yellow Orchids in Garden', 'city' => 'West Sikkim'],
        'img_1787408514_7212.jpeg' => ['title' => 'Cymbidium Orchids of Sikkim', 'city' => 'West Sikkim'],
        'img_1787408514_4750.jpeg' => ['title' => 'Blue Hydrangeas Along Stone Path', 'city' => 'West Sikkim'],
        'img_1787408514_7532.jpeg' => ['title' => 'Himalayan Fuchsia Flowers', 'city' => 'West Sikkim'],
        'img_1787408514_8751.jpeg' => ['title' => 'Pink Mountain Bell Flower', 'city' => 'West Sikkim'],
        'img_1787408514_2395.jpg' => ['title' => 'Azure Skies & Himalayan Vista', 'city' => 'West Sikkim'],
        'img_1787408514_1429.jpg' => ['title' => 'Sunset Glow on Himalayan Range', 'city' => 'West Sikkim'],
        'img_1787408514_2834.jpg' => ['title' => 'Fresh Organic Nakima & Wild Delicacies', 'city' => 'West Sikkim'],
        'img_1787408514_6541.jpg' => ['title' => 'Sacred Khecheopalri Wish-Fulfilling Lake', 'city' => 'Khechuperi Lake'],
        'img_1787408514_6141.jpg' => ['title' => 'Aerial Map - Homestay & Lake Location', 'city' => 'West Sikkim'],
        'img_1787408514_1707.jpg' => ['title' => 'Khecheopalri Monastery Pagoda Shrine', 'city' => 'Khechuperi'],
        'img_1787408515_6185.jpg' => ['title' => 'Alpenglow Sunset on Mt. Pandim', 'city' => 'West Sikkim'],
        'img_1787408515_6193.jpg' => ['title' => 'Golden Sunrise Over Western Hills', 'city' => 'West Sikkim'],
        'img_1787408515_1747.jpg' => ['title' => 'Ancient Pine & Mossy Forest Trail', 'city' => 'Yuksom'],
    ];

    $basename = basename((string)$filename);
    if (isset($map[$basename])) {
        return $map[$basename];
    }

    $clean = trim(ucwords(str_replace(['-', '_'], ' ', pathinfo($basename, PATHINFO_FILENAME))));
    return ['title' => $clean ?: 'Sonam Homestay Photo', 'city' => 'Sikkim'];
}

function fallback_gallery_items($limit = 10)
{
    $urls = array_merge(
        uploaded_asset_urls(UPLOAD_GALLERY, 'assets/uploads/gallery'),
        uploaded_asset_urls(UPLOAD_HOMESTAYS, 'assets/uploads/homestays')
    );
    $urls = array_values(array_unique($urls));
    if ($limit > 0) {
        $urls = array_slice($urls, 0, $limit);
    }

    return array_map(function ($url, $index) {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $base = basename($path);
        $meta = gallery_meta_for_file($base);
        return [
            'id' => 0,
            'image_url' => $url,
            'image_path' => $base,
            'title' => $meta['title'],
            'city' => $meta['city'],
            'sort_order' => $index + 1,
            'is_fallback' => true,
        ];
    }, $urls, array_keys($urls));
}

function fallback_owner_reviews()
{
    return [
        [
            'id' => 0,
            'rating' => 5,
            'title' => 'Peaceful mountain stay',
            'comment' => 'The rooms felt warm, the view was beautiful, and the host family made the stay feel personal.',
            'owner_reply' => '',
            'full_name' => 'Rohit Sharma',
            'guest' => 'Rohit Sharma',
            'profile_image' => '',
            'property_title' => 'Sonam Homestay',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'rating' => 5,
            'title' => 'Clean rooms and kind hosts',
            'comment' => 'Everything was simple, clean, and comfortable. The local food and guidance were excellent.',
            'owner_reply' => '',
            'full_name' => 'Ananya Rai',
            'guest' => 'Ananya Rai',
            'profile_image' => '',
            'property_title' => 'Sonam Homestay',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'rating' => 4,
            'title' => 'Great base near the lake',
            'comment' => 'A quiet place for Khecheopalri Lake and village walks. The room photos matched the stay expectations.',
            'owner_reply' => '',
            'full_name' => 'Milan Gurung',
            'guest' => 'Milan Gurung',
            'profile_image' => '',
            'property_title' => 'Sonam Homestay',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks')),
            'is_fallback' => true,
        ],
    ];
}

function ensure_owner_sonam_inventory($ownerId)
{
    global $conn;
    $ownerId = (int)$ownerId;
    if ($ownerId <= 0) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT id FROM homestays WHERE owner_id = ? ORDER BY id ASC LIMIT 1');
    $stmt->execute([$ownerId]);
    $homestayId = (int)$stmt->fetchColumn();

    if ($homestayId <= 0) {
        $slug = make_slug('Sonam Homestay');
        $suffix = 1;
        $check = $conn->prepare('SELECT id FROM homestays WHERE slug = ?');
        while (true) {
            $check->execute([$slug]);
            if (!$check->fetchColumn()) {
                break;
            }
            $slug = make_slug('Sonam Homestay') . '-' . $suffix++;
        }

        $conn->prepare('INSERT INTO homestays (owner_id, title, slug, description, property_type, address, city, state, country, pincode, cover_image, house_rules, is_featured, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $ownerId,
            'Sonam Homestay',
            $slug,
            'A peaceful family-run hilltop homestay near Khecheopalri Lake, offering warm rooms, local meals, mountain air, and authentic Sikkim hospitality.',
            'Homestay',
            'Near Khecheopalri Lake, West Sikkim',
            'Khechuperi',
            'West Sikkim',
            'India',
            '737113',
            'hs_cover_1787497039_6210.jpg',
            'Quiet hours after 10 PM. Please respect local customs and keep shared spaces clean.',
            1,
            1,
        ]);
        $homestayId = (int)$conn->lastInsertId();
    }

    $roomExists = $conn->prepare('SELECT id FROM rooms WHERE homestay_id = ? AND name = ? LIMIT 1');
    $insertRoom = $conn->prepare('INSERT INTO rooms (homestay_id, name, description, room_type, max_guests, beds, bathrooms, price_per_night, cleaning_fee, quantity, cover_image, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,1)');
    foreach (fallback_public_rooms() as $room) {
        $roomExists->execute([$homestayId, $room['name']]);
        if (!$roomExists->fetchColumn()) {
            $insertRoom->execute([
                $homestayId,
                $room['name'],
                $room['description'],
                $room['room_type'],
                (int)$room['max_guests'],
                (int)$room['beds'],
                1,
                (float)$room['price_per_night'],
                (float)$room['cleaning_fee'],
                1,
                $room['cover_image'],
            ]);
        }
    }

    return $homestayId;
}

function homestay_image_url($filename)
{
    return upload_asset_url($filename, UPLOAD_HOMESTAYS, 'assets/uploads/homestays');
}

function gallery_image_url($filename)
{
    return upload_asset_url($filename, UPLOAD_GALLERY, 'assets/uploads/gallery');
}

function homepage_hero_image()
{
    return asset('images/sonam-homestay-hero.png');
}

// Nice photo for cards/gallery when owner has not uploaded yet
function display_image($h)
{
    if (!empty($h['cover_image'])) {
        $uploaded = homestay_image_url($h['cover_image']);
        if ($uploaded !== asset('images/placeholder-homestay.svg')) {
            return $uploaded;
        }
    }
    $city = strtolower(trim($h['city'] ?? ''));
    $photos = [
        'khechuperi' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=1920&q=80',
        'pelling'    => 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?w=1920&q=80',
        'yuksom'     => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1920&q=80',
        'geyzing'    => 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?w=1920&q=80',
        'sikkim'     => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=1920&q=80',
        'manali'     => 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?w=1920&q=80',
        'goa'        => 'https://images.unsplash.com/photo-1512343879784-a960cd418056?w=1920&q=80',
    ];
    if (isset($photos[$city])) {
        return $photos[$city];
    }
    // Stable random-looking image per id
    $id = (int)($h['id'] ?? 1);
    $pool = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=900&q=80',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=900&q=80',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=900&q=80',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=900&q=80',
        'https://images.unsplash.com/photo-1499793983690-e8b21befc6c4?w=900&q=80',
    ];
    return $pool[$id % count($pool)];
}

function first_name($fullName)
{
    $fullName = trim($fullName ?? '');
    if ($fullName === '') return 'User';
    $parts = explode(' ', $fullName);
    return $parts[0];
}

// Simple image upload
function upload_image($file, $folder)
{
    if (!isset($file['error'], $file['size'], $file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return false;
    }
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $dimensions = @getimagesize($file['tmp_name']);
    if (!isset($allowed[$mime]) || $dimensions === false || $dimensions[0] > 8000 || $dimensions[1] > 8000) {
        return false;
    }

    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        return false;
    }
    if (!is_writable($folder)) {
        return false;
    }

    $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $path = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        @chmod($path, 0644);
        return $newName;
    }
    return false;
}

function delete_uploaded_file($folder, $filename)
{
    $basename = basename((string)$filename);
    if ($basename === '' || $basename !== (string)$filename) {
        return false;
    }

    $path = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $basename;
    $realFolder = realpath($folder);
    $realPath = realpath($path);
    if ($realFolder === false || $realPath === false || strpos($realPath, $realFolder . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    return is_file($realPath) && unlink($realPath);
}

function nights_between($checkIn, $checkOut)
{
    $d1 = new DateTime($checkIn);
    $d2 = new DateTime($checkOut);
    $n = (int)$d1->diff($d2)->days;
    return $n < 1 ? 1 : $n;
}

function booking_ref()
{
    return 'SN' . strtoupper(bin2hex(random_bytes(4)));
}

function stars($rating)
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    $html .= ' <small>' . number_format($rating, 1) . '</small>';
    return $html;
}

function status_badge($status)
{
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'checked_in' => 'info',
        'completed' => 'info', // Wait, completed is mapped to info here, confirmed is success.
        'rejected' => 'danger',
        'cancelled' => 'secondary',
        'paid' => 'success',
        'active' => 'success',
        'inactive' => 'secondary',
    ];
    $c = $colors[$status] ?? 'secondary';
    $label = $status === 'checked_in' ? 'Checked In' : ucfirst($status);
    return '<span class="badge bg-' . $c . '">' . e($label) . '</span>';
}

// Get owner id from logged in user
function get_owner_id()
{
    global $conn;
    if (!is_owner()) return 0;
    $stmt = $conn->prepare('SELECT id FROM owners WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : 0;
}

// Check if room is free for dates
function is_room_available($roomId, $checkIn, $checkOut)
{
    global $conn;
    $sql = "SELECT COUNT(*) FROM bookings b
            JOIN booking_details bd ON b.id = bd.booking_id
            WHERE bd.room_id = ?
            AND b.status IN ('pending', 'confirmed', 'checked_in')
            AND b.check_in < ? AND b.check_out > ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$roomId, $checkOut, $checkIn]);
    return (int)$stmt->fetchColumn() === 0;
}

// Create a notification
function add_notification($userId, $title, $message, $link = null)
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $link]);
}

function unread_count()
{
    global $conn;
    if (!is_logged_in()) return 0;
    $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// Simple homestay card HTML
function homestay_card($h)
{
    $img = display_image($h);
    $price = $h['min_price'] ?? 0;
    $rating = (float)($h['avg_rating'] ?? 0);
    $id = (int)($h['id'] ?? 0);

    $html = '<div class="homestay-card" data-aos="fade-up">';
    $html .= '<div class="card-img-wrap">';
    $html .= '<a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '">';
    $html .= '<img src="' . e($img) . '" alt="' . e($h['title']) . '" class="card-img" loading="lazy">';
    $html .= '</a>';
    $html .= '<span class="card-badge">' . e($h['property_type'] ?? 'Homestay') . '</span>';
    $html .= '</div>';
    $html .= '<div class="card-body-custom">';
    $html .= '<div class="card-location"><i class="fas fa-map-marker-alt"></i> ' . e($h['city']) . ', ' . e($h['state']) . '</div>';
    $html .= '<h3 class="card-title"><a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '">' . e($h['title']) . '</a></h3>';
    $html .= '<div class="card-meta">' . stars($rating) . '</div>';
    $html .= '<div class="card-footer-custom">';
    $html .= '<div class="card-price">' . money((float)$price) . ' <small>/ night</small></div>';
    $html .= '<a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '" class="btn btn-sm btn-primary">View</a>';
    $html .= '</div></div></div>';
    return $html;
}

// Count how many owners exist (system allows only 1)
function owner_count()
{
    global $conn;
    try {
        return (int)$conn->query('SELECT COUNT(*) FROM owners')->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function make_slug($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ? $text : 'homestay-' . time();
}

function time_elapsed($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

    $string = [
        'y' => ['year', $diff->y],
        'm' => ['month', $diff->m],
        'w' => ['week', $w],
        'd' => ['day', $d],
        'h' => ['hour', $diff->h],
        'i' => ['minute', $diff->i],
        's' => ['second', $diff->s],
    ];

    $result = [];
    foreach ($string as $k => $info) {
        $val = $info[1];
        $label = $info[0];
        if ($val > 0) {
            $result[$k] = $val . ' ' . $label . ($val > 1 ? 's' : '');
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}

function razorpay_key_id()
{
    return defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : 'rzp_test_TFfbjUQG491P06';
}

function razorpay_key_secret()
{
    return defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : 'SDBgRbud5fbmpPhvw2MuNbGSDf';
}

function razorpay_create_order($amountInRupees, $receipt = '', $notes = [], &$errorMsg = null)
{
    $keyId = razorpay_key_id();
    $keySecret = razorpay_key_secret();
    if (empty($keyId) || empty($keySecret)) {
        $errorMsg = 'Key ID or Key Secret is not configured.';
        return null;
    }

    $url = 'https://api.razorpay.com/v1/orders';
    $payload = [
        'amount' => (int)round((float)$amountInRupees * 100),
        'currency' => 'INR',
        'receipt' => $receipt ?: ('rcpt_' . bin2hex(random_bytes(4))),
        'notes' => $notes,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300 && $response) {
        $data = json_decode($response, true);
        if (!empty($data['id'])) {
            return $data;
        }
    }

    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['error']['description'])) {
            $errorMsg = 'Razorpay API (' . $httpCode . '): ' . $data['error']['description'];
        } else {
            $errorMsg = 'Razorpay API returned HTTP ' . $httpCode;
        }
    } else {
        $errorMsg = 'Unable to reach Razorpay API.';
    }

    return null;
}

function razorpay_verify_signature($orderId, $paymentId, $signature)
{
    $keySecret = razorpay_key_secret();
    if (empty($keySecret) || empty($orderId) || empty($paymentId) || empty($signature)) {
        return false;
    }
    $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
    return hash_equals($expected, $signature);
}
