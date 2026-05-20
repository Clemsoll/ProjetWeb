<?php
declare(strict_types=1);

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $path = trim($path);

    if ($path === '' || $path === '/') {
        return BASE_URL . '/';
    }

    return BASE_URL . '/' . ltrim($path, '/');
}

function absolute_url(string $path = ''): string
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . url($path);
}

function redirect_to(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(array|string $roles): bool
{
    $user = current_user();

    if ($user === null) {
        return false;
    }

    $roles = is_array($roles) ? $roles : [$roles];

    return in_array($user['role'], $roles, true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Veuillez vous connecter pour accéder à cette page.');
        redirect_to('pages/login.php');
    }
}

function require_role(array|string $roles): void
{
    require_login();

    if (!has_role($roles)) {
        set_flash('error', 'Vous n’avez pas accès à cette page.');
        redirect_to('index.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    sync_user_session($user);
}

function sync_user_session(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'prenom' => (string) $user['prenom'],
        'nom' => (string) $user['nom'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
        'statut_organisateur' => (string) ($user['statut_organisateur'] ?? 'valide'),
        'created_at' => (string) ($user['created_at'] ?? ''),
    ];
}

function logout_user(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function user_full_name(array $user): string
{
    return trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
}

function old(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function format_event_date(string $datetime, string $format = 'd/m/Y à H:i'): string
{
    return (new DateTime($datetime))->format($format);
}

function normalize_category(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $normalized = trim(mb_strtolower($value, 'UTF-8'));
    $normalized = strtr($normalized, [
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'à' => 'a',
        'â' => 'a',
        'î' => 'i',
        'ï' => 'i',
        'ô' => 'o',
        'ö' => 'o',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'Ã©' => 'e',
        'Ã¨' => 'e',
        'Ãª' => 'e',
        'Ã«' => 'e',
        'Ã ' => 'a',
        'Ã¢' => 'a',
        'Ã®' => 'i',
        'Ã¯' => 'i',
        'Ã´' => 'o',
        'Ã¶' => 'o',
        'Ã¹' => 'u',
        'Ã»' => 'u',
        'Ã¼' => 'u',
        'ÃƒÂ©' => 'e',
        'ÃƒÂ¨' => 'e',
        'ÃƒÂª' => 'e',
    ]);

    return match ($normalized) {
        'soiree' => 'Soiree',
        'sport' => 'Sport',
        'culture' => 'Culture',
        default => null,
    };
}

function display_category(?string $category): string
{
    return match (normalize_category($category)) {
        'Soiree' => 'Soirée',
        'Sport' => 'Sport',
        'Culture' => 'Culture',
        default => (string) $category,
    };
}

function category_slug(?string $category): string
{
    return match (normalize_category($category)) {
        'Soiree' => 'soiree',
        'Sport' => 'sport',
        'Culture' => 'culture',
        default => 'autre',
    };
}

function event_image(?string $image, ?string $category = null): string
{
    if ($image !== null && $image !== '') {
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return url($image);
    }

    return match (normalize_category($category)) {
        'Soiree' => url('images/Soiree.webp'),
        'Sport' => url('images/foot.webp'),
        default => url('images/reunion.webp'),
    };
}

function organizer_is_approved(?array $user = null): bool
{
    $user ??= current_user();

    if ($user === null) {
        return false;
    }

    if ($user['role'] === 'administrateur') {
        return true;
    }

    if ($user['role'] !== 'organisateur') {
        return false;
    }

    return ($user['statut_organisateur'] ?? 'en_attente') === 'valide';
}

function require_approved_organizer(): void
{
    require_role(['organisateur', 'administrateur']);

    if (!organizer_is_approved()) {
        set_flash('info', 'Votre compte organisateur est en attente de validation par un administrateur.');
        redirect_to('pages/mon-profil.php');
    }
}

function can_manage_event(array $event, ?array $user = null): bool
{
    $user ??= current_user();

    if ($user === null) {
        return false;
    }

    if ($user['role'] === 'administrateur') {
        return true;
    }

    return $user['role'] === 'organisateur'
        && organizer_is_approved($user)
        && (int) $event['organisateur_id'] === (int) $user['id'];
}

function event_reserved_places(array $event, PDO $pdo): int
{
    if (isset($event['places_reservees'])) {
        return (int) $event['places_reservees'];
    }

    if (!isset($event['id'])) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM reservations
         WHERE evenement_id = :event_id
           AND statut = "reserve"'
    );
    $stmt->execute(['event_id' => (int) $event['id']]);

    return (int) $stmt->fetchColumn();
}

function event_waitlist_count(PDO $pdo, int $eventId): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM listes_attente
         WHERE evenement_id = :event_id
           AND statut = "en_attente"'
    );
    $stmt->execute(['event_id' => $eventId]);

    return (int) $stmt->fetchColumn();
}

function fetch_waitlist_entry(PDO $pdo, int $eventId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT *
         FROM listes_attente
         WHERE evenement_id = :event_id
           AND user_id = :user_id
           AND statut = "en_attente"
         LIMIT 1'
    );
    $stmt->execute([
        'event_id' => $eventId,
        'user_id' => $userId,
    ]);
    $entry = $stmt->fetch();

    return $entry ?: null;
}

function event_badge(string $status, int $reserved, int $capacity): array
{
    if ($status === 'annule') {
        return ['bg-slate-200 text-slate-700', 'Annulé'];
    }

    if ($reserved >= $capacity) {
        return ['bg-red-100 text-red-700', 'Complet'];
    }

    if ($reserved >= max(1, (int) ceil($capacity * 0.8))) {
        return ['bg-amber-100 text-amber-700', 'Presque complet'];
    }

    return ['bg-emerald-100 text-emerald-700', 'Places disponibles'];
}

function reservation_reference(int $reservationId, int $eventId): string
{
    return sprintf('OMNES-%04d-%04d', $eventId, $reservationId);
}

function generate_qr_token(): string
{
    return strtoupper(bin2hex(random_bytes(16)));
}

function ensure_reservation_qr_token(PDO $pdo, array $reservation): string
{
    if (!empty($reservation['qr_token'])) {
        return (string) $reservation['qr_token'];
    }

    $token = generate_qr_token();
    $stmt = $pdo->prepare('UPDATE reservations SET qr_token = :qr_token WHERE id = :id');
    $stmt->execute([
        'qr_token' => $token,
        'id' => (int) $reservation['id'],
    ]);

    return $token;
}

function ticket_qr_payload(string $token): string
{
    return absolute_url('pages/verification-billet.php?token=' . urlencode($token));
}

function ticket_qr_image_url(string $token): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode(ticket_qr_payload($token));
}

function event_price(array $event): float
{
    return round((float) ($event['prix'] ?? 0), 2);
}

function event_is_paid(array $event): bool
{
    return event_price($event) > 0;
}

function format_price(float $price): string
{
    if ($price <= 0) {
        return 'Gratuit';
    }

    return number_format($price, 2, ',', ' ') . ' EUR';
}

function event_map_query(array $event): string
{
    $query = trim((string) ($event['adresse_complete'] ?? ''));

    if ($query !== '') {
        return $query;
    }

    return trim((string) ($event['lieu'] ?? ''));
}

function event_map_link(array $event): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(event_map_query($event));
}

function event_map_embed_url(array $event): string
{
    return 'https://maps.google.com/maps?q=' . rawurlencode(event_map_query($event)) . '&t=&z=14&ie=UTF8&iwloc=&output=embed';
}

function promote_waitlist(PDO $pdo, int $eventId): ?array
{
    $eventStmt = $pdo->prepare(
        'SELECT *
         FROM evenements
         WHERE id = :id
         LIMIT 1
         FOR UPDATE'
    );
    $eventStmt->execute(['id' => $eventId]);
    $event = $eventStmt->fetch();

    if ($event === false || $event['statut'] === 'annule' || (int) $event['places_reservees'] >= (int) $event['capacite_max']) {
        return null;
    }

    $waitlistStmt = $pdo->prepare(
        'SELECT *
         FROM listes_attente
         WHERE evenement_id = :event_id
           AND statut = "en_attente"
         ORDER BY position_attente ASC, created_at ASC
         LIMIT 1
         FOR UPDATE'
    );
    $waitlistStmt->execute(['event_id' => $eventId]);
    $waitingUser = $waitlistStmt->fetch();

    if ($waitingUser === false) {
        return null;
    }

    $capacityStmt = $pdo->prepare(
        'UPDATE evenements
         SET places_reservees = places_reservees + 1
         WHERE id = :id
           AND statut = "actif"
           AND places_reservees < capacite_max'
    );
    $capacityStmt->execute(['id' => $eventId]);

    if ($capacityStmt->rowCount() !== 1) {
        return null;
    }

    $reservationStmt = $pdo->prepare(
        'SELECT *
         FROM reservations
         WHERE user_id = :user_id
           AND evenement_id = :event_id
         LIMIT 1
         FOR UPDATE'
    );
    $reservationStmt->execute([
        'user_id' => (int) $waitingUser['user_id'],
        'event_id' => $eventId,
    ]);
    $reservation = $reservationStmt->fetch();

    $paymentStatus = event_is_paid($event) ? 'paye' : 'non_requis';
    $paymentReference = event_is_paid($event) ? 'AUTO-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12)) : null;
    $qrToken = generate_qr_token();

    if ($reservation !== false) {
        $updateReservation = $pdo->prepare(
            'UPDATE reservations
             SET statut = "reserve",
                 presence_validee = 0,
                 created_at = NOW(),
                 qr_token = :qr_token,
                 payment_status = :payment_status,
                 payment_reference = :payment_reference
             WHERE id = :id'
        );
        $updateReservation->execute([
            'qr_token' => $qrToken,
            'payment_status' => $paymentStatus,
            'payment_reference' => $paymentReference,
            'id' => (int) $reservation['id'],
        ]);
        $reservationId = (int) $reservation['id'];
    } else {
        $insertReservation = $pdo->prepare(
            'INSERT INTO reservations (
                user_id,
                evenement_id,
                statut,
                presence_validee,
                qr_token,
                payment_status,
                payment_reference
             ) VALUES (
                :user_id,
                :event_id,
                "reserve",
                0,
                :qr_token,
                :payment_status,
                :payment_reference
             )'
        );
        $insertReservation->execute([
            'user_id' => (int) $waitingUser['user_id'],
            'event_id' => $eventId,
            'qr_token' => $qrToken,
            'payment_status' => $paymentStatus,
            'payment_reference' => $paymentReference,
        ]);
        $reservationId = (int) $pdo->lastInsertId();
    }

    $updateWaitlist = $pdo->prepare(
        'UPDATE listes_attente
         SET statut = "convertie"
         WHERE id = :id'
    );
    $updateWaitlist->execute(['id' => (int) $waitingUser['id']]);

    return [
        'reservation_id' => $reservationId,
        'user_id' => (int) $waitingUser['user_id'],
        'event_id' => $eventId,
        'qr_token' => $qrToken,
    ];
}

function fetch_user_by_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetch_user_by_id(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function fetch_event_by_id(PDO $pdo, int $eventId): ?array
{
    $sql = 'SELECT e.*,
                   CONCAT(u.prenom, " ", u.nom) AS organisateur_nom,
                   u.email AS organisateur_email
            FROM evenements e
            LEFT JOIN users u ON u.id = e.organisateur_id
            WHERE e.id = :id
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch();

    return $event ?: null;
}

function upload_event_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement de l’image a échoué.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('L’image dépasse la taille maximale de 5 Mo.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Le format d’image n’est pas autorisé.');
    }

    $directory = ROOT_PATH . '/uploads/events';

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de créer le dossier des images.');
    }

    $extension = $allowedTypes[$mimeType];
    $filename = uniqid('event_', true) . '.' . $extension;
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible d’enregistrer l’image envoyée.');
    }

    return 'uploads/events/' . $filename;
}
