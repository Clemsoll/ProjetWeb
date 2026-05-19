<?php
declare(strict_types=1);

function e(null|string|int $value): string
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
        set_flash('error', 'Vous n\'avez pas accès à cette page.');
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

function category_slug(?string $category): string
{
    return match (normalize_category($category)) {
        'Soirée' => 'soiree',
        'Sport' => 'sport',
        'Culture' => 'culture',
        default => 'autre',
    };
}

function normalize_category(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $normalized = trim(mb_strtolower($value, 'UTF-8'));
    $normalized = str_replace(['é', 'è', 'ê'], 'e', $normalized);

    return match ($normalized) {
        'soiree' => 'Soirée',
        'sport' => 'Sport',
        'culture' => 'Culture',
        default => null,
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
        'Soirée' => url('images/Soiree.webp'),
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
        throw new RuntimeException('Le téléversement de l\'image a échoué.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('L\'image dépasse la taille maximale de 5 Mo.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Le format d\'image n\'est pas autorisé.');
    }

    $directory = ROOT_PATH . '/uploads/events';

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossible de créer le dossier des images.');
    }

    $extension = $allowedTypes[$mimeType];
    $filename = uniqid('event_', true) . '.' . $extension;
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image envoyée.');
    }

    return 'uploads/events/' . $filename;
}
