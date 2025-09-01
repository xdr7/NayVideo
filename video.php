<?php
require_once 'config.php';

$video_id = $_GET['id'] ?? 0;

// Ambil data video
$stmt = $pdo->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if (!$video) {
    die("Video not found.");
}

// Tambah view
$pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - NaysVideo</title>
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
        
        body {
            background-color: var(--bg-light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--secondary-color), var(--accent-color));
            color: white;
            padding: 60px 0;
        }
        
        .video-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }
        
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .video-thumbnail {
            position: relative;
            overflow: hidden;
            aspect-ratio: 16/9;
        }
        
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .video-card:hover .video-thumbnail img {
            transform: scale(1.05);
        }
        
        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .video-card:hover .play-button {
            opacity: 1;
        }
        
        .search-box {
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-box input {
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid var(--secondary-color);
            width: 100%;
            font-size: 16px;
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 50%;
            width: 45px;
            height: 45px;
            border: none;
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        .duration-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner-border {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="./"><i class="fas fa-play-circle me-2"></i> NaysVideo</a>
        <a href="./" class="btn btn-outline-light btn-sm">Kembali ke Home</a>
    </div>
</nav>

<div class="container mt-4">
    <h2><?= htmlspecialchars($video['title']) ?></h2>
    <p class="text-muted">
        Uploaded by <strong><?= htmlspecialchars($video['username']) ?></strong> on <?= date('d M Y', strtotime($video['created_at'])) ?>
    </p>

    <video controls width="100%" style="max-height: 500px;">
        <source src="uploads/<?= $video['filename'] ?>" type="video/mp4">
        Browser Anda tidak mendukung pemutar video.
    </video>

    <div class="mt-4">
        <h5>Deskripsi</h5>
        <p><?= nl2br(htmlspecialchars($video['description'])) ?></p>

        <p><strong>Kategori:</strong> <?= htmlspecialchars($video['category']) ?></p>
        <p><strong>Tags:</strong> <?= htmlspecialchars($video['tags']) ?></p>
        <p><strong>Dilihat:</strong> <?= $video['views'] ?> kali</p>
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
</body>
</html>