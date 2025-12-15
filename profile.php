<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/audit_helper.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user (for demo, using session or default)
$currentUserId = $_SESSION['user_id'] ?? 1;

// Handle AJAX/POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_profile':
                // Update user profile information
                $stmt = $pdo->prepare("
                    UPDATE user_profiles 
                    SET full_name = ?, email = ?, phone = ?, address = ?, 
                        bio = ?, job_title = ?, department = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                
                $stmt->execute([
                    $_POST['full_name'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['phone'] ?? '',
                    $_POST['address'] ?? '',
                    $_POST['bio'] ?? '',
                    $_POST['job_title'] ?? '',
                    $_POST['department'] ?? '',
                    $currentUserId
                ]);
                
                // Log the activity
                log_audit($pdo, $_POST['full_name'] ?? 'User', 'Update Record', 'Profile', 'Success', 'Updated profile information');
                
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
                exit;
                
            case 'upload_image':
                // Handle image upload
                if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('No file uploaded or upload error');
                }
                
                $file = $_FILES['profile_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed');
                }
                
                // Validate file size
                if ($file['size'] > $maxSize) {
                    throw new Exception('File too large. Maximum size is 5MB');
                }
                
                // Create uploads directory if it doesn't exist
                $uploadDir = __DIR__ . '/uploads/profiles/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $currentUserId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                // Get old image to delete
                $stmt = $pdo->prepare("SELECT profile_image FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$currentUserId]);
                $oldImage = $stmt->fetchColumn();
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Update database
                    $stmt = $pdo->prepare("
                        UPDATE user_profiles 
                        SET profile_image = ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$filename, $currentUserId]);
                    
                    // Delete old image if exists
                    if ($oldImage && file_exists($uploadDir . $oldImage)) {
                        unlink($uploadDir . $oldImage);
                    }
                    
                    // Log the activity
                    $stmt = $pdo->prepare("SELECT full_name FROM user_profiles WHERE user_id = ?");
                    $stmt->execute([$currentUserId]);
                    $userName = $stmt->fetchColumn();
                    log_audit($pdo, $userName, 'Update Record', 'Profile', 'Success', 'Uploaded profile image');
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Profile image uploaded successfully',
                        'image_url' => 'uploads/profiles/' . $filename
                    ]);
                } else {
                    throw new Exception('Failed to move uploaded file');
                }
                exit;
                
            case 'change_password':
                // Change password
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    throw new Exception('All password fields are required');
                }
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New passwords do not match');
                }
                
                if (strlen($newPassword) < 6) {
                    throw new Exception('Password must be at least 6 characters long');
                }
                
                // Update password (in a real app, verify current password first)
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE user_profiles 
                    SET password_hash = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$hashedPassword, $currentUserId]);
                
                // Log the activity
                $stmt = $pdo->prepare("SELECT full_name FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$currentUserId]);
                $userName = $stmt->fetchColumn();
                log_audit($pdo, $userName, 'Change Settings', 'Profile', 'Success', 'Changed password');
                
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get user profile data
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$currentUserId]);
$profile = $stmt->fetch();

// If no profile exists, create default one
if (!$profile) {
    $stmt = $pdo->prepare("
        INSERT INTO user_profiles (user_id, full_name, email, created_at, updated_at)
        VALUES (?, 'Demo User', 'demo@example.com', NOW(), NOW())
    ");
    $stmt->execute([$currentUserId]);
    
    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$currentUserId]);
    $profile = $stmt->fetch();
}

require_once __DIR__ . '/inc/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">User Profile</h2>
        </div>
    </div>
    
    <div class="row">
        <!-- Profile Image Section -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Profile Picture</h5>
                    <div class="mb-3">
                        <?php if ($profile['profile_image']): ?>
                            <img src="uploads/profiles/<?= esc($profile['profile_image']) ?>" 
                                 id="profileImagePreview" 
                                 class="rounded-circle img-thumbnail" 
                                 style="width: 200px; height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23ddd' width='200' height='200'/%3E%3Ctext fill='%23999' font-family='sans-serif' font-size='50' x='50%25' y='50%25' text-anchor='middle' dy='.3em'%3ENo Image%3C/text%3E%3C/svg%3E" 
                                 id="profileImagePreview"
                                 class="rounded-circle img-thumbnail" 
                                 style="width: 200px; height: 200px; object-fit: cover;">
                        <?php endif; ?>
                    </div>
                    
                    <form id="imageUploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_image">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="profileImageInput" name="profile_image" accept="image/*" required>
                            <small class="text-muted">Max size: 5MB. Formats: JPG, PNG, GIF, WebP</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </form>
                </div>
            </div>
            
            <!-- Account Info -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Account Information</h5>
                    <p class="mb-1"><strong>User ID:</strong> <?= esc($profile['user_id']) ?></p>
                    <p class="mb-1"><strong>Member Since:</strong> <?= date('M d, Y', strtotime($profile['created_at'])) ?></p>
                    <p class="mb-0"><strong>Last Updated:</strong> <?= date('M d, Y H:i', strtotime($profile['updated_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Profile Details Section -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Personal Information</h5>
                    
                    <form id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= esc($profile['full_name']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= esc($profile['email']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= esc($profile['phone']) ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="job_title" class="form-label">Job Title</label>
                                <input type="text" class="form-control" id="job_title" name="job_title" 
                                       value="<?= esc($profile['job_title']) ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-control" id="department" name="department">
                                    <option value="">Select Department</option>
                                    <option value="Management" <?= $profile['department'] === 'Management' ? 'selected' : '' ?>>Management</option>
                                    <option value="Operations" <?= $profile['department'] === 'Operations' ? 'selected' : '' ?>>Operations</option>
                                    <option value="Sales" <?= $profile['department'] === 'Sales' ? 'selected' : '' ?>>Sales</option>
                                    <option value="Finance" <?= $profile['department'] === 'Finance' ? 'selected' : '' ?>>Finance</option>
                                    <option value="IT" <?= $profile['department'] === 'IT' ? 'selected' : '' ?>>IT</option>
                                    <option value="HR" <?= $profile['department'] === 'HR' ? 'selected' : '' ?>>HR</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?= esc($profile['address']) ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?= esc($profile['bio']) ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Section -->
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Change Password</h5>
                    
                    <form id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Min 6 characters</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Profile Form Submission
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error updating profile: ' + error);
        });
    });
    
    // Image Upload Form Submission
    document.getElementById('imageUploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const fileInput = document.getElementById('profileImageInput');
        
        if (!fileInput.files[0]) {
            alert('Please select an image');
            return;
        }
        
        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Update image preview
                document.getElementById('profileImagePreview').src = data.image_url + '?' + new Date().getTime();
                // Clear file input
                fileInput.value = '';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error uploading image: ' + error);
        });
    });
    
    // Image Preview on Selection
    document.getElementById('profileImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Password Form Submission
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('confirm_password');
        
        if (newPassword !== confirmPassword) {
            alert('New passwords do not match!');
            return;
        }
        
        fetch('profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                this.reset();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error changing password: ' + error);
        });
    });
});
</script>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
