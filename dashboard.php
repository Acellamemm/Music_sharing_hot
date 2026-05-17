<?php
session_start();
include("connection.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$dj_alias = $_SESSION['dj_alias'] ?? 'DJ';
$display_name = $dj_alias !== 'DJ' ? $dj_alias : ($_SESSION['fname'] ?? 'Artist');

// My Mixes
$my_mix_stmt = $conn->prepare("SELECT title, description, soundcloud_link, uploaded_at, genre, listens FROM mixes WHERE user_id = ? ORDER BY uploaded_at DESC");
$my_mix_stmt->bind_param("i", $user_id);
$my_mix_stmt->execute();
$my_mixes = $my_mix_stmt->get_result();

// Discover - newest mixes
$discover_stmt = $conn->query("SELECT m.title, m.description, m.soundcloud_link, m.uploaded_at, u.dj_alias, m.genre, m.listens 
                              FROM mixes m JOIN users u ON m.user_id = u.id 
                              ORDER BY m.uploaded_at DESC LIMIT 12");

// Community Feed
$feed_stmt = $conn->query("SELECT m.title, m.description, m.soundcloud_link, m.uploaded_at, u.dj_alias, m.genre, m.listens 
                          FROM mixes m JOIN users u ON m.user_id = u.id 
                          ORDER BY m.uploaded_at DESC LIMIT 20");

// Top DJs - ranked by number of mixes
$top_djs_stmt = $conn->query("SELECT u.id, u.dj_alias, COUNT(m.id) as mix_count 
                             FROM users u 
                             LEFT JOIN mixes m ON u.id = m.user_id 
                             GROUP BY u.id 
                             ORDER BY mix_count DESC LIMIT 10");

// All distinct genres for the dropdown
$genres_stmt = $conn->query("SELECT DISTINCT genre FROM mixes WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");

// Genre filter
$selected_genre = $_GET['genre'] ?? '';
$genre_filter_sql = $selected_genre ? "WHERE m.genre = '" . $conn->real_escape_string($selected_genre) . "'" : '';
$genre_mixes_stmt = $conn->query("SELECT m.title, m.description, m.soundcloud_link, m.uploaded_at, u.dj_alias, m.genre, m.listens 
                                  FROM mixes m JOIN users u ON m.user_id = u.id 
                                  $genre_filter_sql 
                                  ORDER BY m.uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FusionMix</title>
    <link rel="stylesheet" href="style.css">
    <style>
       <style>
    /* Background Control */
    .bg-wrapper {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        z-index: -2;
        overflow: hidden;
    }
    .bg-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: blur(4px);
    }
    .bg-overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(15, 12, 41, 0.88), rgba(48, 43, 99, 0.82));
        z-index: 1;
    }

    /* Top Nav - Fixed at top */
    .top-nav {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: rgba(15, 12, 41, 0.92);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 10px 30px;
        height: 62px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 1000;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
    }
    .logo-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.4rem;
        font-weight: 800;
        color: white;
    }
    .logo-nav .badge { font-size: 1.8rem; }
    .nav-links {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .left-links, .right-links {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .nav-link {
        color: white;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 6px 16px;
        border-radius: 30px;
        transition: all 0.3s;
        cursor: pointer;
    }
    .nav-link:hover, .nav-link.active {
        background: linear-gradient(90deg, var(--accent1), var(--accent2));
    }
    .logout-btn {
        padding: 6px 18px;
        background: linear-gradient(90deg, #ff4b2b, #ff416c);
        color: white;
        text-decoration: none;
        border-radius: 30px;
        font-weight: bold;
        font-size: 0.9rem;
    }

    /* Search Bar */
    .search-bar {
        position: relative;
        width: 350px;
        max-width: 100%;
    }
    .search-bar input {
        width: 100%;
        padding: 10px 40px 10px 16px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        color: white;
        font-size: 1rem;
        outline: none;
    }
    .search-bar input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    .search-bar input:focus {
        border-color: var(--accent1);
        box-shadow: 0 0 15px rgba(255, 65, 108, 0.3);
    }
    .search-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.4rem;
        color: rgba(255, 255, 255, 0.7);
        pointer-events: none;
    }
    .soundcloud-embed iframe {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    }
    .mix-card {
        transition: transform 0.2s;
    }
    .mix-card:hover {
        transform: translateY(-5px);
    }

    /* Main Content */
    .content-wrapper {
        position: relative;
        z-index: 2;
        max-width: 1300px;
        width: 95%;
        margin: 90px auto 50px;
        padding: 40px;
        background: rgba(15, 12, 41, 0.93);
        backdrop-filter: blur(18px);
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .welcome-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .welcome {
        font-size: 2.8rem;
        background: linear-gradient(90deg, var(--accent1), var(--accent2));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }

    .tabs {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }

    .mix-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
    }
    .mix-card {
        background: rgba(20, 20, 50, 0.8);
        backdrop-filter: blur(8px);
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .mix-title {
        font-size: 1.5rem;
        margin-bottom: 8px;
        color: var(--accent1);
    }
    .mix-meta {
        font-size: 0.95rem;
        opacity: 0.8;
        margin-bottom: 15px;
    }
    .no-mixes {
        grid-column: 1 / -1;
        text-align: center;
        padding: 80px 20px;
        font-size: 1.4rem;
        opacity: 0.7;
    }
    .soundcloud-embed {
        margin-top: 20px;
        border-radius: 12px;
        overflow: hidden;
    }

    /* Top DJs */
    .dj-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 25px;
    }
    .dj-card {
        background: rgba(30, 30, 60, 0.7);
        padding: 25px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .dj-rank {
        font-size: 2.2rem;
        opacity: 0.5;
        margin-bottom: 10px;
    }
    .dj-name {
        font-size: 1.4rem;
        font-weight: bold;
        color: var(--accent1);
    }

    /* Genre Filter */
    .genre-filter {
        text-align: center;
        margin-bottom: 40px;
    }
    .genre-select {
        padding: 12px 24px;
        background: rgba(30, 30, 60, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        color: white;
        font-size: 1.1rem;
        min-width: 200px;
    }
    select option {
        background: #0f0c29;
        color: white;
    }

    /* Upload Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        backdrop-filter: blur(8px);
        overflow-y: auto;
    }
    .modal-content {
        background: rgba(15, 12, 41, 0.95);
        backdrop-filter: blur(15px);
        margin: 5% auto;
        padding: 40px 30px;
        border-radius: 20px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        position: relative;
    }
    .close {
        position: absolute;
        top: 15px;
        right: 25px;
        font-size: 2rem;
        cursor: pointer;
        color: #aaa;
    }
    .close:hover { color: white; }
    .modal h2 {
        text-align: center;
        margin-bottom: 30px;
        color: var(--accent1);
    }
    .modal label {
        display: block;
        margin: 20px 0 8px;
        font-weight: bold;
    }
    .modal input,
    .modal select,
    .modal textarea {
        width: 100%;
        padding: 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px;
        color: white;
    }

    /* Responsive */
    @media (max-width: 1000px) {
        .top-nav {
            flex-wrap: wrap;
            height: auto;
            padding: 10px 20px;
        }
        .search-bar {
            order: 3;
            width: 100%;
            margin: 10px 0;
        }
        .content-wrapper {
            margin: 80px auto 40px;
            padding: 30px 20px;
        }
        .mix-grid {
            grid-template-columns: 1fr;
        }
    }
    /* Fix unreadable inactive tab buttons */
        .tabs .nav-link,
        .top-nav .nav-link,
        .top-nav button.nav-link {
            background: rgba(255, 255, 255, 0.12) !important; /* Slight dark tint */
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
        }

        .tabs .nav-link:hover,
        .top-nav .nav-link:hover,
        .top-nav button.nav-link:hover {
            background: rgba(255, 255, 255, 0.22) !important;
        }

        /* Active tab gets the gradient */
        .tabs .nav-link.active,
        .top-nav .nav-link.active {
            background: linear-gradient(90deg, var(--accent1), var(--accent2)) !important;
            color: white !important;
            border: none !important;
        }
</style>
    </style>
    <script src="https://w.soundcloud.com/player/api.js"></script>
</head>
<body>

    <!-- Background -->
    <div class="bg-wrapper">
        <img src="https://images.pexels.com/photos/1570651/pexels-photo-1570651.jpeg?auto=compress&cs=tinysrgb&w=1920" alt="Moody rave crowd" class="bg-image">
        <div class="bg-overlay"></div>
    </div>

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="logo-nav">
            <div class="badge">🎧</div>
            <div>FusionMix</div>
        </div>

        <div class="nav-links left-links">
            <a href="#" class="nav-link" onclick="openTab('my-mixes'); return false;">My Mixes</a>
            <a href="#" class="nav-link" onclick="openTab('discover'); return false;">Discover</a>
            <a href="#" class="nav-link" onclick="openTab('top-djs'); return false;">Top DJs</a>
            <a href="#" class="nav-link" onclick="openTab('genres'); return false;">Genres</a>
        </div>

        <!-- Search Bar in the Center -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search mixes, DJs, genres..." onkeyup="performSearch()">
            <span class="search-icon">🔍</span>
        </div>

        <div class="nav-links right-links">
            <a href="#" class="nav-link" onclick="openTab('community'); return false;">Community Feed</a>
            <button class="nav-link" onclick="openUploadModal()">+ Upload Mix</button>
            <a href="logout.php" class="logout-btn">Log Out</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="welcome-header">
            <h1 class="welcome">Welcome back, <?= htmlspecialchars($display_name) ?> 🎧</h1>
            <p>Drop heat. Discover vibes. Own the decks.</p>
        </div>

        <div class="tabs">
            <button class="nav-link active" onclick="openTab('my-mixes')">My Mixes</button>
            <button class="nav-link" onclick="openTab('discover')">Discover</button>
            <button class="nav-link" onclick="openTab('top-djs')">Top DJs</button>
            <button class="nav-link" onclick="openTab('genres')">Genres</button>
            <button class="nav-link" onclick="openTab('community')">Community Feed</button>
        </div>
        <!-- My Mixes -->
<div id="my-mixes" class="tab-content active">
    <h2 style="text-align:center;margin-bottom:30px;">Your Mixes</h2>
    <div class="mix-grid">
        <?php if ($my_mixes->num_rows > 0): ?>
            <?php while ($mix = $my_mixes->fetch_assoc()): ?>
                <div class="mix-card">
                    <div class="mix-title"><?= htmlspecialchars($mix['title']) ?></div>
                    <div class="mix-meta">
                        <?= htmlspecialchars($mix['genre'] ?? 'Various') ?> • 
                        <?= date('M j, Y', strtotime($mix['uploaded_at'])) ?>
                    </div>

                    <?php if (!empty($mix['description'])): ?>
                        <p class="mix-description" style="margin: 12px 0; opacity: 0.9;">
                            <?= nl2br(htmlspecialchars($mix['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($mix['soundcloud_link'])): ?>
                        <div class="soundcloud-embed" style="margin: 20px 0; border-radius: 12px; overflow: hidden;">
                            <iframe 
                                width="100%" 
                                height="300" 
                                scrolling="no" 
                                frameborder="no" 
                                allow="autoplay; encrypted-media" 
                                src="https://w.soundcloud.com/player/?url=<?= urlencode($mix['soundcloud_link']) ?>&color=%23ff416c&visual=true&auto_play=false&show_comments=true&show_user=true&show_reposts=false&show_teaser=true">
                            </iframe>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; opacity: 0.7; margin: 30px 0; font-style: italic;">
                            No SoundCloud link yet — add one to preview!
                        </p>
                    <?php endif; ?>

                    <!-- Your existing engagement section (likes, listens, comments) -->
                    <div class="engagement-section" style="margin-top:30px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.1);">
                        <!-- Likes & Listens -->
                        <div style="display:flex;align-items:center;gap:20px;margin-bottom:25px;">
                            <?php
                            $like_check = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND mix_id = ?");
                            $like_check->bind_param("ii", $user_id, $mix['id']);
                            $like_check->execute();
                            $liked = $like_check->get_result()->num_rows > 0;

                            $like_count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM likes WHERE mix_id = ?");
                            $like_count_stmt->bind_param("i", $mix['id']);
                            $like_count_stmt->execute();
                            $like_count = $like_count_stmt->get_result()->fetch_assoc()['c'];
                            ?>

                            <button onclick="toggleLike(<?= $mix['id'] ?>)" style="background:none;border:none;cursor:pointer;font-size:1.6rem;">
                                <span id="heart-<?= $mix['id'] ?>"><?= $liked ? '❤️' : '🤍' ?></span>
                            </button>
                            <span id="like-count-<?= $mix['id'] ?>" style="color:#ff416c;font-weight:bold;"><?= $like_count ?></span> Likes

                            <span class="listen-count" style="color:#aaa;margin-left:auto;">
                                👀 <?= $mix['listens'] ?? 0 ?> Listens
                            </span>
                        </div>

                        <!-- Comments -->
                        <div>
                            <h4 style="color:var(--accent1);margin:0 0 15px 0;">Comments</h4>

                            <?php
                            $comments_stmt = $conn->prepare("SELECT c.comment, c.created_at, u.dj_alias 
                                                             FROM comments c 
                                                             JOIN users u ON c.user_id = u.id 
                                                             WHERE c.mix_id = ? 
                                                             ORDER BY c.created_at DESC");
                            $comments_stmt->bind_param("i", $mix['id']);
                            $comments_stmt->execute();
                            $comments_res = $comments_stmt->get_result();
                            ?>

                            <?php if ($comments_res->num_rows > 0): ?>
                                <?php while ($c = $comments_res->fetch_assoc()): ?>
                                    <div style="background:rgba(255,255,255,0.05);padding:12px;border-radius:10px;margin-bottom:10px;">
                                        <strong style="color:var(--accent1);"><?= htmlspecialchars($c['dj_alias']) ?></strong>
                                        <small style="opacity:0.7;"> • <?= date('M j', strtotime($c['created_at'])) ?></small>
                                        <p style="margin:6px 0 0;"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="opacity:0.6;font-style:italic;margin:10px 0;">No comments yet — be the first!</p>
                            <?php endif; ?>

                            <form action="add_comment.php" method="post" style="margin-top:20px;">
                                <input type="hidden" name="mix_id" value="<?= $mix['id'] ?>">
                                <textarea name="comment" rows="3" placeholder="Drop your thoughts..." required 
                                          style="width:100%;padding:10px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.15);border-radius:10px;color:white;"></textarea>
                                <button type="submit" style="margin-top:10px;padding:8px 20px;background:linear-gradient(90deg,var(--accent1),var(--accent2));border:none;border-radius:30px;color:white;">
                                    Post
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-mixes">
                <p>You haven't uploaded any mixes yet.</p>
                <p>Time to share your sound with the world! 🔥</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    

    <!-- Upload Modal (inline) -->
    <div id="uploadModal" class="modal" aria-hidden="true">
        <div class="modal-content">
            <span class="close" onclick="closeUploadModal()">&times;</span>
            <h2>Upload Mix</h2>
            <form action="upload_mix.php" method="post">
                <label for="title">Title</label>
                <input id="title" name="title" type="text" required placeholder="Mix title">

                <label for="genre">Genre</label>
                <select id="genre" name="genre" required>
                    <option value="">Select a genre</option>
                    <option>House</option>
                    <option>Techno</option>
                    <option>Drum & Bass</option>
                    <option>Hip-Hop</option>
                    <option>Various</option>
                </select>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Add optional details..."></textarea>

                <label for="soundcloud_link">SoundCloud Link</label>
                <input id="soundcloud_link" name="soundcloud_link" type="url" required placeholder="https://soundcloud.com/artist/track">

                <div style="text-align:center;margin-top:20px;">
                    <button type="submit" style="padding:10px 22px;border-radius:30px;background:linear-gradient(90deg,var(--accent1),var(--accent2));color:white;border:none;">Upload Mix</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openUploadModal(){
        var m = document.getElementById('uploadModal');
        if(!m) return;
        m.style.display = 'block';
        m.setAttribute('aria-hidden','false');
    }
    function closeUploadModal(){
        var m = document.getElementById('uploadModal');
        if(!m) return;
        m.style.display = 'none';
        m.setAttribute('aria-hidden','true');
    }
    window.addEventListener('click', function(e){
        var m = document.getElementById('uploadModal');
        if(!m) return;
        if(e.target === m) closeUploadModal();
    });
    function toggleLike(mixId) {
    const heart = document.getElementById(`heart-${mixId}`);
    const countSpan = document.getElementById(`like-count-${mixId}`);
    
    // AJAX to server
    fetch('toggle_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `mix_id=${mixId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            heart.innerHTML = data.liked ? '❤️' : '🤍';
            countSpan.innerHTML = data.like_count;
        } else {
            alert('Error toggling like: ' + data.error);
        }
    })
    .catch(error => console.error('Like error:', error));
    }
        document.querySelectorAll('.soundcloud-embed iframe').forEach(iframe => {
        const widget = SC.Widget(iframe);
        widget.bind(SC.Widget.Events.PLAY, function() {
            const mixId = iframe.closest('.mix-card').querySelector('input[name="mix_id"]').value;  // Get mix_id from hidden input
            
            fetch('increment_listen.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `mix_id=${mixId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optional: Update displayed count if you want real-time
                    const listenSpan = iframe.closest('.mix-card').querySelector('.listen-count');  // Add class="listen-count" to your listens span
                    if (listenSpan) listenSpan.innerHTML = data.new_count;
                }
            })
            .catch(error => console.error('Listen error:', error));
        });
    });
    function toggleLike(mixId) {
        const heart = document.getElementById(`heart-${mixId}`);
        const countSpan = document.getElementById(`like-count-${mixId}`);
        
        if (!heart || !countSpan) {
            console.log("Heart or count element not found for mix " + mixId);
            return;
        }

        fetch('toggle_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `mix_id=${mixId}`
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                heart.innerHTML = data.liked ? '❤️' : '🤍';
                countSpan.textContent = data.like_count;
            } else {
                alert('Like failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Like toggle error:', error);
            alert('Could not connect — check console (F12)');
        });
    }
    </script>

    </body>
    </html>

