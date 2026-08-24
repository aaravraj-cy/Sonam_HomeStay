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
        [
            'id' => 0,
            'name' => 'Kanchenjunga View Double',
            'room_type' => 'Double Room',
            'description' => 'A bright double room with warm bedding, natural light, and a calm mountain-stay feel for couples or two friends.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 1800,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_2353.jpeg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Pinewood Family Suite',
            'room_type' => 'Family Room',
            'description' => 'A spacious family room with a homely layout, extra seating, and enough room for parents with children.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 2600,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_5058.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Garden View Single',
            'room_type' => 'Single Room',
            'description' => 'A compact private room facing greenery, ideal for solo travelers who want a peaceful place to rest.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1200,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_3471.jpeg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Himalayan Balcony Double',
            'room_type' => 'Double Room',
            'description' => 'A comfortable double room styled for slow mornings, tea by the window, and relaxed mountain evenings.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 2100,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_8952.jpeg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Village Comfort Family Room',
            'room_type' => 'Family Room',
            'description' => 'A practical room for small families, with cozy sleeping space and a simple local homestay atmosphere.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 2400,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_7222.jpeg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Quiet Workation Single',
            'room_type' => 'Single Room',
            'description' => 'A neat single room for remote workers and solo guests, with a restful setup after day trips.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1350,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_8963.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Sunrise Window Double',
            'room_type' => 'Double Room',
            'description' => 'A warm double room inspired by mountain-view stays, with soft bedding and clean natural textures.',
            'max_guests' => 2,
            'beds' => 1,
            'price_per_night' => 1900,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_6594.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Lake Trail Family Room',
            'room_type' => 'Family Room',
            'description' => 'A family-friendly room for guests visiting Khecheopalri Lake, village trails, and nearby viewpoints.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 2750,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_3159.jpeg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Attic Style Single',
            'room_type' => 'Single Room',
            'description' => 'A simple private room with a cozy retreat feel, suited for backpackers and solo nature stays.',
            'max_guests' => 1,
            'beds' => 1,
            'price_per_night' => 1100,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_2178.jpg',
            'is_fallback' => true,
        ],
        [
            'id' => 0,
            'name' => 'Premium Mountain Family Suite',
            'room_type' => 'Family Room',
            'description' => 'A larger family suite with a premium homestay feel, ideal for guests who want more room and privacy.',
            'max_guests' => 4,
            'beds' => 2,
            'price_per_night' => 3200,
            'cleaning_fee' => 0,
            'cover_image' => 'room_photo_1787497039_8284.jpg',
            'is_fallback' => true,
        ],
    ];
}

function fallback_gallery_items($limit = 10)
{
    $urls = array_merge(
        uploaded_asset_urls(UPLOAD_GALLERY, 'assets/uploads/gallery'),
        uploaded_asset_urls(UPLOAD_ROOMS, 'assets/uploads/rooms'),
        uploaded_asset_urls(UPLOAD_HOMESTAYS, 'assets/uploads/homestays')
    );
    $urls = array_values(array_unique($urls));
    if ($limit > 0) {
        $urls = array_slice($urls, 0, $limit);
    }

    return array_map(function ($url, $index) {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $name = pathinfo(urldecode(basename($path)), PATHINFO_FILENAME);
        $title = trim(ucwords(str_replace(['-', '_'], ' ', $name)));
        return [
            'id' => 0,
            'image_url' => $url,
            'image_path' => basename($path),
            'title' => $title ?: 'Sonam Homestay Photo ' . ($index + 1),
            'city' => 'Sikkim',
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
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
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
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        return false;
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $path = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $newName;
    }
    return false;
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
