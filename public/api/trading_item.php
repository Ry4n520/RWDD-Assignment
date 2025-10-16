<?php
/**
 * Simple API to create trading list items
 * POST fields: name, category, description (optional)
 */
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../src/db.php';

$me = $_SESSION['user_id'] ?? $_SESSION['UserID'] ?? null;
if (!$me) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'not_authenticated']); exit; }

// do not create table; use existing `tradinglist` table from DB dump

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$desc = trim($_POST['description'] ?? '');
if ($name === '') { echo json_encode(['ok'=>false,'error'=>'name_required']); exit; }

// match existing table name `tradinglist`
$stmt = mysqli_prepare($conn, "INSERT INTO tradinglist (UserID,Name,Category) VALUES (?,?,?)");
mysqli_stmt_bind_param($stmt,'iss',$me,$name,$category);
mysqli_stmt_execute($stmt);
$id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

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
