<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";

$message = '';
$error = '';
$postedTitle = '';
$postedDescription = '';
$postedTarget = 'all';

$createTableSql = "CREATE TABLE IF NOT EXISTS announcements ("
    . "id INT AUTO_INCREMENT PRIMARY KEY, "
    . "title VARCHAR(255) NOT NULL, "
    . "description TEXT NOT NULL, "
    . "target ENUM('student','instructor','all') NOT NULL DEFAULT 'all', "
    . "files TEXT NULL, "
    . "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
    . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createTableSql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['deleteAnnouncementId'])) {
        $deleteId = intval($_POST['deleteAnnouncementId']);
        $stmt = $conn->prepare("SELECT files FROM announcements WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            if ($stmt->execute()) {
                $stmt->bind_result($filesJson);
                if ($stmt->fetch()) {
                    $files = json_decode($filesJson, true) ?: [];
                    foreach ($files as $file) {
                        if ($file) {
                            $filePath = __DIR__ . '/../' . $file;
                            if (is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                    }
                }
            }
            $stmt->close();
        }

        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            if ($stmt->execute()) {
                $message = 'Announcement deleted successfully.';
            } else {
                $error = 'Unable to delete announcement. Please try again.';
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again later.';
        }
    } elseif (!empty($_POST['editAnnouncementId'])) {
        $editId = intval($_POST['editAnnouncementId']);
        $postedTitle = trim($_POST['announcementTitle'] ?? '');
        $postedDescription = strip_tags(trim($_POST['announcementDescription'] ?? ''), '<b><i><u><br><p><ul><ol><li><strong><em><a>');
        $postedTarget = $_POST['announcementTarget'] ?? 'all';
        $allowedTargets = ['student', 'instructor', 'all'];
        if (!in_array($postedTarget, $allowedTargets, true)) {
            $postedTarget = 'all';
        }

        if ($postedTitle === '' && trim(strip_tags($postedDescription)) === '') {
            $error = 'Please add a title or description.';
        } else {
            $stmt = $conn->prepare("UPDATE announcements SET title = ?, description = ?, target = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('sssi', $postedTitle, $postedDescription, $postedTarget, $editId);
                if ($stmt->execute()) {
                    $message = 'Announcement updated successfully.';
                } else {
                    $error = 'Unable to update announcement. Please try again.';
                }
                $stmt->close();
            } else {
                $error = 'Database error. Please try again later.';
            }
        }
    } else {
        $postedTitle = trim($_POST['announcementTitle'] ?? '');
        $postedDescription = strip_tags(trim($_POST['announcementDescription'] ?? ''), '<b><i><u><br><p><ul><ol><li><strong><em><a>');
        $postedTarget = $_POST['announcementTarget'] ?? 'all';
        $allowedTargets = ['student', 'instructor', 'all'];
        if (!in_array($postedTarget, $allowedTargets, true)) {
            $postedTarget = 'all';
        }

        if ($postedTitle === '' && trim(strip_tags($postedDescription)) === '' && empty($_FILES['announcementFiles']['name'][0])) {
            $error = 'Please add a title, description, or image.';
        } else {
            $uploadDir = __DIR__ . '/../assets/uploads/announcements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $storedFiles = [];

            if (!empty($_FILES['announcementFiles']['name']) && is_array($_FILES['announcementFiles']['name'])) {
                $fileCount = count($_FILES['announcementFiles']['name']);
                for ($i = 0; $i < $fileCount && count($storedFiles) < 2; $i++) {
                    if ($_FILES['announcementFiles']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $tmpName = $_FILES['announcementFiles']['tmp_name'][$i];
                    if (!is_uploaded_file($tmpName)) {
                        continue;
                    }
                    $mimeType = mime_content_type($tmpName);
                    if (!in_array($mimeType, $allowedMimeTypes, true)) {
                        continue;
                    }
                    $originalName = basename($_FILES['announcementFiles']['name'][$i]);
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                        continue;
                    }
                    $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalName);
                    $saveName = time() . '_' . uniqid() . '_' . $safeName;
                    $destination = $uploadDir . $saveName;
                    if (move_uploaded_file($tmpName, $destination)) {
                        $storedFiles[] = 'assets/uploads/announcements/' . $saveName;
                    }
                }
            }

            $filesJson = json_encode($storedFiles);
            $stmt = $conn->prepare("INSERT INTO announcements (title, description, target, files, created_at) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $createdAt = date('Y-m-d H:i:s');
                $stmt->bind_param('sssss', $postedTitle, $postedDescription, $postedTarget, $filesJson, $createdAt);
                if ($stmt->execute()) {
                    $message = 'Announcement posted successfully.';
                    $postedTitle = '';
                    $postedDescription = '';
                    $postedTarget = 'all';
                } else {
                    $error = 'Unable to save announcement. Please try again.';
                }
                $stmt->close();
            } else {
                $error = 'Database error. Please try again later.';
            }
        }
    }
}

$announcements = [];
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['files'] = json_decode($row['files'], true) ?: [];
        $announcements[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>MUCAHUB Announcements</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            background: #f9f9f9;
            font-family: Arial, sans-serif;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .main {
            flex: 1;
            padding: 20px;
            box-sizing: border-box;
        }
        .announcement-container {
            margin-top: 20px;
        }
        .post-box {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            background: #fdfdfd;
        }
        .editor-toolbar {
            margin-bottom: 5px;
        }
        .editor-toolbar select,
        .editor-toolbar button,
        .editor-toolbar input[type="color"] {
            margin-right: 5px;
            padding: 3px 5px;
        }
        .editor {
            width: 100%;
            min-height: 120px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            font-size: 14px;
            overflow: auto;
            background: white;
        }
        .post-box input[type="file"] {
            margin-top: 10px;
        }
        .post-box button {
            margin-top: 10px;
            background: olive;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
        }
        .post-box button:hover {
            opacity: 0.95;
        }
        #fileNamesDisplay {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .file-chip {
            position: relative;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 6px 20px 6px 8px;
            font-size: 12px;
            background: #fafafa;
        }
        .file-remove {
            position: absolute;
            top: -6px;
            right: -6px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        #charCount {
            font-size: 12px;
            color: #555;
            margin-top: 3px;
        }
        .notice-box {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 10px;
        }
        .success-box {
            background: #e6f4ea;
            border: 1px solid #b4d8b3;
            color: #2f5d32;
        }
        .error-box {
            background: #ffe8e8;
            border: 1px solid #e0b4b4;
            color: #8a2d2d;
        }
        .posts-wall {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 30px;
        }
        .announcement-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #ddd;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .announcement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }
        
        /* Beat animation for announcement navigation */
        .announcement-card.beat-active {
            animation: beat-pulse 0.6s ease-in-out;
        }
        
        @keyframes beat-pulse {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.05); }
            50% { transform: scale(0.98); }
            75% { transform: scale(1.05); }
        }
        
        .card-title {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .card-image {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .card-time,
        .card-target {
            font-size: 13px;
            color: #666;
            margin-top: 4px;
        }
        .post-popup {
            display: none;
            position: fixed;
            z-index: 1100;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .post-popup-content {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 740px;
            max-width: 740px;
            min-width: 740px;
            max-height: 85vh;
            overflow-y: auto;
            overflow-wrap: break-word;
            word-wrap: break-word;
            hyphens: auto;
            position: relative;
        }
        .popup-close {
            position: absolute;
            right: 18px;
            top: 12px;
            font-size: 24px;
            cursor: pointer;
        }
        .popup-image {
            width: 100%;
            border-radius: 10px;
            margin-top: 16px;
        }
        .popup-meta {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
        }
        .popup-body {
            margin-top: 18px;
            line-height: 1.7;
        }
        .popup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }
        .popup-button {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: white;
            font-weight: 600;
        }
        .popup-button.delete {
            background: #d9534f;
        }
        .popup-button.edit {
            background: #0275d8;
        }
        .popup-button.save {
            background: #28a745;
        }
        .popup-button.cancel {
            background: #6c757d;
        }
        .popup-form label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
        }
        .popup-form input[type="text"],
        .popup-form select,
        .popup-form .editor {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            background: white;
        }
        .popup-form .editor {
            min-height: 120px;
        }
        @media (max-width: 768px) {
            .posts-wall {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include "../includes/sidebar_admin.php"; ?>
    <div class="main">
        <h2>Hi, Admin</h2>
        <div class="announcement-container">
            <?php if ($message): ?>
                <div class="notice-box success-box"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="notice-box error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="post-box">
                <form id="announcementForm" method="post" enctype="multipart/form-data" action="announcements.php">
                    <input type="text" name="announcementTitle" id="announcementTitle" placeholder="Post Title"
                        style="width:100%;padding:10px;margin-bottom:10px;font-weight:bold;font-size:16px;"
                        value="<?php echo htmlspecialchars($postedTitle); ?>">

                    <div style="margin-bottom:12px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                        <label style="font-weight:bold;">Target:</label>
                        <select name="announcementTarget" id="announcementTarget" style="padding:6px 10px;">
                            <option value="student" <?php echo $postedTarget === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="instructor" <?php echo $postedTarget === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                            <option value="all" <?php echo $postedTarget === 'all' ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>

                    <div class="editor-toolbar">
                        <button onclick="format('bold')" type="button"><b>B</b></button>
                        <button onclick="format('italic')" type="button"><i>I</i></button>
                        <button onclick="format('underline')" type="button"><u>U</u></button>
                        <select onchange="format('fontName', this.value)">
                            <option value="">Font</option>
                            <option value="Arial">Arial</option>
                            <option value="Tahoma">Tahoma</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Georgia">Georgia</option>
                        </select>
                        <select onchange="format('fontSize', this.value)">
                            <option value="">Size</option>
                            <option value="1">10px</option>
                            <option value="2">12px</option>
                            <option value="3">14px</option>
                            <option value="4">16px</option>
                            <option value="5">18px</option>
                            <option value="6">20px</option>
                            <option value="7">24px</option>
                        </select>
                        <input type="color" onchange="format('foreColor', this.value)">
                    </div>

                    <div id="announcementText" class="editor" contenteditable="true"><?php echo $postedDescription; ?></div>
                    <input type="hidden" name="announcementDescription" id="announcementDescription" value="<?php echo htmlspecialchars($postedDescription); ?>">
                    <div id="charCount"><?php echo mb_strlen(strip_tags($postedDescription)); ?> characters</div>

                    <input type="file" name="announcementFiles[]" id="announcementFiles" multiple accept="image/*">
                    <div id="fileNamesDisplay"></div>
                    <button type="submit" onclick="prepareSubmit(event)">Post</button>
                </form>
            </div>

            <div class="posts-wall" id="postsWall">
                <?php if (empty($announcements)): ?>
                    <p style="color:#555;">No announcements posted yet.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $index => $post): ?>
                        <?php $firstImage = !empty($post['files']) ? $post['files'][0] : ''; ?>
                        <div class="announcement-card" data-announcement-id="<?php echo intval($post['id']); ?>" id="announcement-<?php echo intval($post['id']); ?>" onclick="openPostPopup(<?php echo $index; ?>)">
                            <?php if ($firstImage): ?>
                                <img src="../<?php echo htmlspecialchars($firstImage); ?>" class="card-image" alt="Announcement image">
                            <?php endif; ?>
                            <div class="card-title"><?php echo htmlspecialchars($post['title'] ?: 'Announcement'); ?></div>
                            <div class="card-time"><?php echo date('F j, Y g:i A', strtotime($post['created_at'])); ?></div>
                            <div class="card-target">Target: <?php echo htmlspecialchars(ucfirst($post['target'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "../includes/float.php"; ?>
    <?php include "../includes/footer.php"; ?>
    <?php include "../includes/back_to_top.php"; ?>

    <div id="postPopup" class="post-popup">
        <div class="post-popup-content" id="popupContent"></div>
    </div>

    <script>
        const announcementData = <?php echo json_encode($announcements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const editor = document.getElementById('announcementText');
        const charCount = document.getElementById('charCount');
        const fileNamesDisplay = document.getElementById('fileNamesDisplay');
        let filesToPost = [];

        editor.addEventListener('input', () => {
            const textLength = editor.innerText.length;
            charCount.textContent = textLength + ' characters';
        });

        document.getElementById('announcementFiles').addEventListener('change', function () {
            const selected = Array.from(this.files);
            if (selected.length > 2) {
                alert('Only 2 images are allowed. The first two selected files will be uploaded.');
            }
            filesToPost = selected.slice(0, 2);
            updateInputFiles();
            renderSelectedFiles();
        });

        function updateInputFiles() {
            const input = document.getElementById('announcementFiles');
            const dt = new DataTransfer();
            filesToPost.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }

        function renderSelectedFiles() {
            fileNamesDisplay.innerHTML = '';
            filesToPost.forEach((file, index) => {
                const chip = document.createElement('div');
                chip.className = 'file-chip';
                chip.innerHTML = `${file.name}<div class="file-remove" onclick="removeFile(${index})">×</div>`;
                fileNamesDisplay.appendChild(chip);
            });
        }

        function removeFile(index) {
            filesToPost.splice(index, 1);
            updateInputFiles();
            renderSelectedFiles();
        }

        function format(command, value = null) {
            document.execCommand(command, false, value);
        }

        function prepareSubmit(event) {
            const hiddenInput = document.getElementById('announcementDescription');
            hiddenInput.value = editor.innerHTML.trim();
            if (filesToPost.length > 2) {
                alert('Only 2 images are allowed. Extra files will be ignored.');
            }
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function linkifyHtml(html) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const urlRegex = /((https?:\/\/|www\.)[^\s<]+)/gi;

            function walk(node) {
                if (node.nodeType === Node.TEXT_NODE) {
                    const text = node.nodeValue;
                    if (!text || !urlRegex.test(text)) {
                        return;
                    }
                    const frag = document.createDocumentFragment();
                    let lastIndex = 0;
                    text.replace(urlRegex, (match, url, prefix, offset) => {
                        const before = text.slice(lastIndex, offset);
                        if (before) {
                            frag.appendChild(document.createTextNode(before));
                        }
                        const href = prefix.toLowerCase().startsWith('http') ? match : 'http://' + match;
                        const a = document.createElement('a');
                        a.href = href;
                        a.target = '_blank';
                        a.rel = 'noopener noreferrer';
                        a.textContent = match;
                        frag.appendChild(a);
                        lastIndex = offset + match.length;
                    });
                    const after = text.slice(lastIndex);
                    if (after) {
                        frag.appendChild(document.createTextNode(after));
                    }
                    node.replaceWith(frag);
                } else if (node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'A' && node.tagName !== 'SCRIPT' && node.tagName !== 'STYLE') {
                    Array.from(node.childNodes).forEach(child => walk(child));
                }
            }

            walk(wrapper);
            return wrapper.innerHTML;
        }

        function openPostPopup(index) {
            const post = announcementData[index];
            if (!post) return;
            const files = Array.isArray(post.files) ? post.files : [];
            let filesHTML = '';
            files.forEach(file => {
                if (file && file.match(/\.(jpe?g|png|gif|webp)$/i)) {
                    filesHTML += `<img src="../${file}" class="popup-image" alt="Announcement image">`;
                }
            });
            document.getElementById('popupContent').innerHTML = `
                <span class="popup-close" onclick="closePostPopup()">×</span>
                <h2>${escapeHtml(post.title || 'Announcement')}</h2>
                <div class="popup-meta">Target: ${post.target ? post.target.charAt(0).toUpperCase() + post.target.slice(1) : 'All'}</div>
                <div class="popup-meta">Posted: ${new Date(post.created_at).toLocaleString()}</div>
                <div class="popup-body">${linkifyHtml(post.description)}</div>
                ${filesHTML}
                <div class="popup-actions">
                    <button type="button" class="popup-button delete" onclick="confirmDelete(${post.id})">Delete</button>
                </div>
            `;
            document.getElementById('postPopup').style.display = 'flex';
        }

        function confirmDelete(id) {
            if (!confirm('Are you sure you want to delete this announcement?')) {
                return;
            }
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'announcements.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'deleteAnnouncementId';
            input.value = id;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function closePostPopup() {
            document.getElementById('postPopup').style.display = 'none';
        }

        document.getElementById('postPopup').onclick = function (event) {
            if (event.target.id === 'postPopup') {
                closePostPopup();
            }
        };

        /* Handle announcement navigation from dashboard with beat animation */
        document.addEventListener('DOMContentLoaded', function() {
            // Check if we have an announcement ID in the URL hash
            const hash = window.location.hash;
            if (hash.startsWith('#announcement-')) {
                const announcementId = hash.substring('#announcement-'.length);
                const card = document.querySelector(`[data-announcement-id="${announcementId}"]`);
                if (card) {
                    // Apply beat animation
                    card.classList.add('beat-active');
                    
                    // Scroll into view
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Remove animation class after animation completes
                    setTimeout(() => {
                        card.classList.remove('beat-active');
                    }, 600);
                }
            }
        });
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
