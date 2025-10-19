<?php
/**
 * Create/Delete trading list items
 * POST fields: name (required), category (optional), description (optional), meetup_location (optional)
 * DELETE: item_id (required) - deletes trading item (owner or admin only)
 */
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';

$me = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$me) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'not_authenticated']); exit; }

// Check if user is admin
$isAdmin = false;
$userStmt = mysqli_prepare($conn, "SELECT usertype FROM accounts WHERE UserID = ? LIMIT 1");
mysqli_stmt_bind_param($userStmt, 'i', $me);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
if ($userRow = mysqli_fetch_assoc($userRes)) {
    if (isset($userRow['usertype']) && strtolower($userRow['usertype']) === 'admin') {
        $isAdmin = true;
    }
}

// Handle PUT request (Update item)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($input['item_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $category = trim($input['category'] ?? '');
    $meetupLocation = trim($input['meetup_location'] ?? '');
    
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    if ($name === '' || $description === '' || $meetupLocation === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Name, description, and meetup location are required']);
        exit;
    }
    
    // Check ownership (admins can edit any item)
    if (!$isAdmin) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($res);
        
        if (!$item || intval($item['UserID']) !== intval($me)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Not authorized']);
            exit;
        }
    }
    
    // Update the item
    $stmt = mysqli_prepare($conn, "UPDATE tradinglist SET Name = ?, Description = ?, Category = ?, MeetupLocation = ? WHERE ItemID = ?");
    mysqli_stmt_bind_param($stmt, 'ssssi', $name, $description, $category, $meetupLocation, $itemId);
    $ok = mysqli_stmt_execute($stmt);
    
    if ($ok) {
        echo json_encode(['ok' => true, 'message' => 'Item updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to update item']);
    }
    mysqli_stmt_close($stmt);
    exit;
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($input['item_id'] ?? 0);
    
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    // Check ownership (admins can delete any item)
    if (!$isAdmin) {
        $stmt = mysqli_prepare($conn, "SELECT UserID FROM tradinglist WHERE ItemID = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $itemId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $item = mysqli_fetch_assoc($res);
        
        if (!$item || intval($item['UserID']) !== intval($me)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Not authorized']);
            exit;
        }
    }
    
    // Delete images from disk and DB
    $imgRes = mysqli_query($conn, "SELECT path FROM trading_images WHERE ItemID = " . $itemId);
    if ($imgRes) {
        while ($img = mysqli_fetch_assoc($imgRes)) {
            $filePath = __DIR__ . '/../' . ltrim($img['path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        mysqli_query($conn, "DELETE FROM trading_images WHERE ItemID = " . $itemId);
    }
    
    // Delete trading requests and the item
    mysqli_query($conn, "DELETE FROM tradingrequests WHERE ItemID = " . $itemId);
    mysqli_query($conn, "DELETE FROM tradinglist WHERE ItemID = " . $itemId);
    
    echo json_encode(['ok' => true]);
    exit;
}

// do not create table; use existing `tradinglist` table from DB dump

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$desc = trim($_POST['description'] ?? '');
$meetup = trim($_POST['meetup_location'] ?? '');
if ($name === '') { echo json_encode(['ok'=>false,'error'=>'name_required']); exit; }

// Insert into `tradinglist`, prefer extended columns when available
$id = 0;
$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID,Name,Category,Description,MeetupLocation) VALUES (?,?,?,?,?)");
if ($stmt) {
	mysqli_stmt_bind_param($stmt,'issss',$me,$name,$category,$desc,$meetup);
	if (!mysqli_stmt_execute($stmt)) {
		// fall back to (UserID,Name,Category,Description)
		mysqli_stmt_close($stmt);
		$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID,Name,Category,Description) VALUES (?,?,?,?)");
		if ($stmt) {
			mysqli_stmt_bind_param($stmt,'isss',$me,$name,$category,$desc);
			if (!mysqli_stmt_execute($stmt)) {
				mysqli_stmt_close($stmt);
				// fall back to legacy minimal columns
				$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID,Name,Category) VALUES (?,?,?)");
				mysqli_stmt_bind_param($stmt,'iss',$me,$name,$category);
				mysqli_stmt_execute($stmt);
			}
		}
	}
} else {
	// prepare failed for extended; use minimal
	$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID,Name,Category) VALUES (?,?,?)");
	mysqli_stmt_bind_param($stmt,'iss',$me,$name,$category);
	mysqli_stmt_execute($stmt);
}
$id = mysqli_insert_id($conn);
if ($stmt) mysqli_stmt_close($stmt);

echo json_encode(['ok'=>true,'item_id'=>$id]);

// handle uploaded images (multiple) and save paths to trading_images
$conn->query("CREATE TABLE IF NOT EXISTS trading_images (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	ItemID INT NOT NULL,
	path VARCHAR(255) NOT NULL,
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	INDEX (ItemID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// accept multiple uploaded files under input name 'image' or 'image[]'
$uploaded = [];
if (!empty($_FILES)) {
	// normalize possible names
	if (isset($_FILES['image'])) $files = $_FILES['image'];
	elseif (isset($_FILES['image[]'])) $files = $_FILES['image[]'];
	else $files = null;
	if ($files) {
		$names = is_array($files['name']) ? $files['name'] : [$files['name']];
		$tmps = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
		for ($i=0;$i<count($names);$i++) {
			$namef = $names[$i]; $tmp = $tmps[$i];
			if (!is_uploaded_file($tmp)) continue;
			$ext = pathinfo($namef, PATHINFO_EXTENSION);
			$fn = 'uploads/trading/item_' . intval($id) . '_' . uniqid() . '.' . $ext;
			$dest = __DIR__ . '/../' . $fn;
			if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
			if (move_uploaded_file($tmp, $dest)) {
				$pstmt = mysqli_prepare($conn, "INSERT INTO trading_images (ItemID,path) VALUES (?,?)");
				if ($pstmt) { mysqli_stmt_bind_param($pstmt,'is',$id,$fn); mysqli_stmt_execute($pstmt); mysqli_stmt_close($pstmt); }
				$uploaded[] = $fn;
			}
		}
	}
}

?>
