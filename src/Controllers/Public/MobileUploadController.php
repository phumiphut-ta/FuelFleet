<?php
namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Database;
use Exception;

class MobileUploadController {
    
    public function showUploadForm(Request $request, Response $response) {
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        $isValid = false;
        $dbError = null;
        
        if (!empty($token)) {
            try {
                $db = Database::getConnection();
                $now = date('Y-m-d H:i:s');
                $stmt = $db->prepare("SELECT * FROM temporary_tokens WHERE token = :token AND expires_at > :now");
                $stmt->execute(['token' => $token, 'now' => $now]);
                $tokenRecord = $stmt->fetch();
                
                if ($tokenRecord) {
                    $isValid = true;
                }
            } catch (Exception $e) {
                $dbError = $e->getMessage();
            }
        }
        
        $router = new Router($request, $response);
        return $router->renderView('public/mobile_upload', [
            'token' => $token,
            'isValid' => $isValid,
            'dbError' => $dbError
        ]);
    }
    
    public function checkToken(Request $request, Response $response) {
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        
        if (empty($token)) {
            return $response->json(['success' => false, 'message' => 'Missing token']);
        }
        
        try {
            $db = Database::getConnection();
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("SELECT * FROM temporary_tokens WHERE token = :token AND expires_at > :now");
            $stmt->execute(['token' => $token, 'now' => $now]);
            $tokenRecord = $stmt->fetch();
            
            if (!$tokenRecord) {
                return $response->json(['success' => false, 'message' => 'Token has expired or is invalid']);
            }
            
            if (!empty($tokenRecord['uploaded_file'])) {
                return $response->json([
                    'success' => true,
                    'uploaded' => true,
                    'filename' => $tokenRecord['uploaded_file']
                ]);
            } else {
                return $response->json([
                    'success' => true,
                    'uploaded' => false
                ]);
            }
        } catch (Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function saveUpload(Request $request, Response $response) {
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        $files = $request->getFiles();
        
        if (empty($token)) {
            return $response->json(['success' => false, 'message' => 'Missing token']);
        }
        
        try {
            $db = Database::getConnection();
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("SELECT * FROM temporary_tokens WHERE token = :token AND expires_at > :now");
            $stmt->execute(['token' => $token, 'now' => $now]);
            $tokenRecord = $stmt->fetch();
            
            if (!$tokenRecord) {
                return $response->json(['success' => false, 'message' => 'Token has expired or is invalid']);
            }
            
            if (!isset($files['doc_file']) || $files['doc_file']['error'] !== UPLOAD_ERR_OK) {
                return $response->json(['success' => false, 'message' => 'No file uploaded or upload error occurred']);
            }
            
            $file = $files['doc_file'];
            $originalName = $file['name'];
            $fileTmpPath = $file['tmp_name'];
            $fileSize = $file['size'];
            
            // Limit to 10MB
            if ($fileSize > 10 * 1024 * 1024) {
                return $response->json(['success' => false, 'message' => 'File size exceeds 10MB limit']);
            }
            
            $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return $response->json(['success' => false, 'message' => 'Only PDF and image files (JPG, PNG, WEBP) are allowed']);
            }
            
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $newFilename = 'mobile_receipt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $stmtUpdate = $db->prepare("UPDATE temporary_tokens SET uploaded_file = :filename WHERE token = :token");
                $stmtUpdate->execute(['filename' => $newFilename, 'token' => $token]);
                
                return $response->json([
                    'success' => true,
                    'filename' => $newFilename
                ]);
            } else {
                return $response->json(['success' => false, 'message' => 'Failed to save uploaded file']);
            }
        } catch (Exception $e) {
            return $response->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
