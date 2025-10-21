<?php
// src/api_helpers.php - Helper functions for API endpoints

/**
 * Get the current logged-in user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) || isset($_SESSION['UserID']) 
        ? (int)($_SESSION['user_id'] ?? $_SESSION['UserID']) 
        : null;
}

/**
 * Check if the current user is an admin
 * @param mysqli $conn Database connection
 * @param int|null $userId User ID to check (defaults to current user)
 * @return bool True if user is admin, false otherwise
 */
function isAdmin(mysqli $conn, ?int $userId = null): bool {
    $userId = $userId ?? getCurrentUserId();
    if (!$userId) {
        return false;
    }
    
    $stmt = mysqli_prepare($conn, "SELECT usertype FROM accounts WHERE UserID = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $isAdmin = false;
    
    if ($row = mysqli_fetch_assoc($result)) {
        $isAdmin = isset($row['usertype']) && strtolower($row['usertype']) === 'admin';
    }
    
    mysqli_stmt_close($stmt);
    return $isAdmin;
}

/**
 * Require authentication, exit with 401 if not logged in
 * @return int User ID
 */
function requireAuth(): int {
    $userId = getCurrentUserId();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
        exit;
    }
    return $userId;
}

/**
 * Require admin access, exit with 403 if not admin
 * @param mysqli $conn Database connection
 * @return int User ID
 */
function requireAdmin(mysqli $conn): int {
    $userId = requireAuth();
    
    if (!isAdmin($conn, $userId)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Admin access required']);
        exit;
    }
    
    return $userId;
}

/**
 * Send JSON response and exit
 * @param int $code HTTP status code
 * @param array $data Response data
 */
function jsonResponse(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
