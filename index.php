<?php
require_once 'config.php';

// Handle all actions
$action = $_GET['action'] ?? 'home';

// Handle User Registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)")
             ->execute([$username, $password, $email]);
        $_SESSION['message'] = 'Registration successful! Please login.';
        redirect('?action=login');
    } catch (PDOException $e) {
        $error = 'Username or email already exists!';
    }
}

// Handle User Login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        redirect('?action=dashboard');
    } else {
        $error = 'Invalid username or password!';
    }
}

// Handle Logout
if ($action == 'logout') {
    session_destroy();
    redirect('./');
}

// Handle Video Upload
if (isset($_POST['upload_video']) && isLoggedIn()) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $tags = $_POST['tags'];
    $videoFile = uploadFile($_FILES['video_file'], 'video');
    $thumbnailFile = uploadFile($_FILES['thumbnail'], 'image');
    if ($videoFile) {
        $stmt = $pdo->prepare("INSERT INTO videos (title, description, filename, thumbnail, category, tags, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $videoFile, $thumbnailFile, $category, $tags, $_SESSION['user_id']]);
        $_SESSION['message'] = 'Video uploaded successfully!';
    }
}

// Handle Video Update
if (isset($_POST['update_video']) && isLoggedIn()) {
    $id = $_POST['video_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $tags = $_POST['tags'];
    $stmt = $pdo->prepare("UPDATE videos SET title = ?, description = ?, category = ?, tags = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$title, $description, $category, $tags, $id, $_SESSION['user_id']]);
    $_SESSION['message'] = 'Video updated successfully!';
}

// Handle Video Delete
if (isset($_POST['delete_video']) && isLoggedIn()) {
    $id = $_POST['video_id'];
    $stmt = $pdo->prepare("SELECT filename, thumbnail FROM videos WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $video = $stmt->fetch();
    if ($video) {
        @unlink('uploads/' . $video['filename']);
        @unlink('thumbnails/' . $video['thumbnail']);
        $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
        $_SESSION['message'] = 'Video deleted successfully!';
    }
}

// JSON endpoint for video edit
if ($action == 'get_video' && isset($_GET['id']) && isLoggedIn()) {
    header('Content-Type: application/json');
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

// Get videos with search and filter
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$where = "WHERE category = 'IT Education' OR category LIKE '%IT%' OR category LIKE '%Programming%' OR category LIKE '%Web%' OR category LIKE '%Database%' OR category LIKE '%Mobile Development%'";
$params = [];
if ($search) {
    $where .= " AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($category) {
    $where .= " AND category = ?";
    $params[] = $category;
}
$orderBy = "created_at DESC";
switch ($sort) {
    case 'oldest': $orderBy = "created_at ASC"; break;
    case 'popular': $orderBy = "views DESC"; break;
}
$stmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id $where ORDER BY $orderBy");
$stmt->execute($params);
$videos = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("SELECT DISTINCT category FROM videos ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Get all users (admin)
if (isAdmin()) {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

// Handle User Management (Admin only) - Tambah, Edit, Hapus User
if ($action == 'manage_users' && isAdmin()) {
    // Tambah user baru
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
                 ->execute([$username, $email, $password, $role]);
            $_SESSION['message'] = 'User added successfully!';
            redirect('?action=manage_users');
        } catch (PDOException $e) {
            $error = 'Username atau email sudah digunakan!';
        }
    }

    // Edit user
    if (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        try {
            $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?")
                 ->execute([$username, $email, $role, $id]);
            $_SESSION['message'] = 'User updated successfully!';
            redirect('?action=manage_users');
        } catch (PDOException $e) {
            $error = 'Username atau email sudah digunakan!';
        }
    }

    // Hapus user
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $_SESSION['message'] = 'User deleted!';
        redirect('?action=manage_users');
    }

    // Ambil ulang data user setelah perubahan
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NaysVideo - Platform Edukasi IT</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #a78bfa;
            --secondary-color: #93c5fd;
            --accent-color: #86efac;
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
        }
        body { background-color: var(--bg-light); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }
        .navbar { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
        .navbar-brand { font-weight: bold; color: white !important; }
        .hero-section { background: linear-gradient(135deg, var(--secondary-color), var(--accent-color)); color: white; padding: 60px 0; }
        .video-card { transition: transform 0.3s ease, box-shadow 0.3s ease; border: none; border-radius: 15px; overflow: hidden; }
        .video-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .video-thumbnail { position: relative; overflow: hidden; aspect-ratio: 16/9; }
        .video-thumbnail img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .video-card:hover .video-thumbnail img { transform: scale(1.05); }
        .footer { background: var(--text-dark); color: white; padding: 40px 0; margin-top: 60px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="./"><i class="fas fa-play-circle me-2"></i>NaysVideo</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="?action=home">Home</a></li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="?action=dashboard">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="?action=manage_users">Manage Users</a></li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><i class="fas fa-user"></i> <?= $_SESSION['username'] ?></a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="?action=profile">Profile</a></li>
                        <li><a class="dropdown-item" href="?action=logout">Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="?action=login">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=register">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); endif; ?>

    <?php switch ($action):
        case 'login': ?>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header text-center"><h3><i class="fas fa-sign-in-alt"></i> Login</h3></div>
                            <div class="card-body">
                                <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                                <form method="post">
                                    <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                                    <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                                </form>
                                <p class="text-center mt-3">Don't have an account? <a href="?action=register">Register here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php break; ?>

        <?php case 'register': ?>
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header text-center"><h3><i class="fas fa-user-plus"></i> Register</h3></div>
                            <div class="card-body">
                                <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                                <form method="post">
                                    <div class="mb-3"><label>Username</label><input type="text" name="username" class="form-control" required></div>
                                    <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                                    <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                                    <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                                </form>
                                <p class="text-center mt-3">Already have an account? <a href="?action=login">Login here</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php break; ?>

        <?php case 'dashboard': ?>
            <div class="container mt-4">
                <h2>My Dashboard</h2>
                <div class="card p-4 mb-4">
                    <h4><i class="fas fa-upload"></i> Upload New Video</h4>
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Video Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="IT Education">IT Education</option>
                                    <option value="Programming">Programming</option>
                                    <option value="Web Development">Web Development</option>
                                    <option value="Database">Database</option>
                                    <option value="Mobile Development">Mobile Development</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        <div class="mb-3"><label>Tags</label><input type="text" name="tags" class="form-control" placeholder="php, web, tutorial"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Video File</label>&nbsp;<input type="file" name="video_file" accept="video/*" required></div>
                            <div class="col-md-6 mb-3"><label>Thumbnail</label>&nbsp;<input type="file" name="thumbnail" accept="image/*"></div>
                        </div>
                        <button type="submit" name="upload_video" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Video</button>
                    </form>
                </div>

                <h4>My Videos</h4>
                <div class="row">
                    <?php
                    $myVideos = $pdo->prepare("SELECT * FROM videos WHERE user_id = ? ORDER BY created_at DESC");
                    $myVideos->execute([$_SESSION['user_id']]);
                    $myVideos = $myVideos->fetchAll();
                    if (empty($myVideos)): ?>
                        <div class="col-12"><div class="alert alert-info">No videos uploaded yet.</div></div>
                    <?php else: ?>
                        <?php foreach ($myVideos as $video): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card video-card">
                                <a href="video.php?id=<?= $video['id'] ?>" class="text-decoration-none">
                                    <div class="video-thumbnail">
                                        <img src="<?= $video['thumbnail'] ? 'thumbnails/' . $video['thumbnail'] : 'https://via.placeholder.com/300x200?text=No+Thumbnail' ?>" alt="<?= $video['title'] ?>">
                                    </div>
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($video['title']) ?></h6>
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-sm btn-warning" onclick="editVideo(<?= $video['id'] ?>)"><i class="fas fa-edit"></i> Edit</button>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                            <button type="submit" name="delete_video" class="btn btn-sm btn-danger" onclick="return confirm('Delete this video?')"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php break; ?>

        <?php case 'manage_users': ?>
            <?php if (!isAdmin()) redirect('./'); ?>

            <?php
            // Tambah user baru
            if (isset($_POST['add_user'])) {
                $username = $_POST['username'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                try {
                    $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)")
                         ->execute([$username, $email, $password, $role]);
                    $_SESSION['message'] = 'User added successfully!';
                    redirect('?action=manage_users');
                } catch (PDOException $e) {
                    $error = 'Username atau email sudah digunakan!';
                }
            }

            // Edit user
            if (isset($_POST['edit_user'])) {
                $id = $_POST['user_id'];
                $username = $_POST['username'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                try {
                    $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?")
                         ->execute([$username, $email, $role, $id]);
                    $_SESSION['message'] = 'User updated successfully!';
                    redirect('?action=manage_users');
                } catch (PDOException $e) {
                    $error = 'Username atau email sudah digunakan!';
                }
            }

            // Hapus user
            if (isset($_POST['delete_user'])) {
                $id = $_POST['user_id'];
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $_SESSION['message'] = 'User deleted!';
                redirect('?action=manage_users');
            }

            // Ambil semua user
            $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
            ?>

            <div class="container mt-4">
                <h2><i class="fas fa-users"></i> Manage Users</h2>

                <!-- Tambah User Baru -->
                <div class="card mb-4 p-3">
                    <h5>Tambah User Baru</h5>
                    <form method="post">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <input type="text" name="username" class="form-control" placeholder="Username" required>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <select name="role" class="form-select">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="submit" name="add_user" class="btn btn-success w-100"><i class="fas fa-plus"></i> Tambah</button>
                            </div>
                        </div>
                    </form>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mt-2"><?= $error ?></div>
                    <?php endif; ?>
                </div>

                <!-- Daftar User -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <form method="post">
                                    <td><?= $user['id'] ?><input type="hidden" name="user_id" value="<?= $user['id'] ?>"></td>
                                    <td><input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="form-control form-control-sm"></td>
                                    <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control form-control-sm"></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <button type="submit" name="edit_user" class="btn btn-sm btn-warning"><i class="fas fa-save"></i> Simpan</button>
                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php break; ?>

        <?php default: ?>
            <div class="hero-section">
                <div class="container text-center">
                    <h1 class="display-4 mb-3">NaysVideo</h1>
                    <p class="lead mb-4">Platform Edukasi IT dengan Konten Video</p>
                    <p class="mb-0"></p>
                </div>
            </div>

            <div class="container mt-4">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="get" class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search videos..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" onchange="location.href='?category='+this.value+'&search=<?= urlencode($search) ?>'">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $category == $cat ? 'selected' : '' ?>><?= $cat  ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <?php if (empty($videos)): ?>
                        <div class="col-12 text-center"><h3>No videos found</h3></div>
                    <?php else: ?>
                        <?php foreach ($videos as $video): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <a href="video.php?id=<?= $video['id'] ?>" class="text-decoration-none">
                                <div class="card video-card">
                                    <div class="video-thumbnail">
                                        <img src="<?= $video['thumbnail'] ? 'thumbnails/' . $video['thumbnail'] : 'https://via.placeholder.com/300x200?text=No+Thumbnail' ?>" alt="<?= $video['title'] ?>">
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($video['title']) ?></h6>
                                        <p class="text-muted small">
                                            <i class="fas fa-user"></i> <?= $video['username'] ?><br>
                                            <i class="fas fa-eye"></i> <?= $video['views'] ?> views<br>
                                            <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($video['created_at'])) ?>
                                        </p>
                                        <span class="badge bg-primary"><?= $video['category'] ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php break; ?>
    <?php endswitch; ?>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="video_id" id="editVideoId">
                    <div class="mb-3"><label>Title</label><input type="text" name="title" id="editTitle" class="form-control" required></div>
                    <div class="mb-3"><label>Description</label><textarea name="description" id="editDescription" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3"><label>Category</label>
                        <select name="category" id="editCategory" class="form-control">
                            <option value="IT Education">IT Education</option>
                            <option value="Programming">Programming</option>
                            <option value="Web Development">Web Development</option>
                            <option value="Database">Database</option>
                            <option value="Mobile Development">Mobile Development</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Tags</label><input type="text" name="tags" id="editTags" class="form-control" placeholder="php, web, tutorial"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_video" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="footer mt-5">
    <div class="container text-center">
        <h5>NaysVideo</h5>
        <p>&copy; 2025 NaysVideo. Created by Asmaul Asni Subegi, S.Kom</p>
        <p>
            <i class="fas fa-envelope"></i> admin@naysvideo.com |
            <i class="fab fa-facebook"></i> NaysVideo |
            <i class="fab fa-twitter"></i> @naysvideo |
            <i class="fab fa-instagram"></i> @naysvideo
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editVideo(id) {
    fetch('?action=get_video&id=' + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById('editVideoId').value = data.id;
            document.getElementById('editTitle').value = data.title;
            document.getElementById('editDescription').value = data.description;
            document.getElementById('editCategory').value = data.category;
            document.getElementById('editTags').value = data.tags;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });
}
</script>
</body>
</html>