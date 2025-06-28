<?php
session_start();

// --- CONFIGURATION ---
$users = [
    'User' => 'password',
    'user' => 'password',
    'user' => 'password',
    'user' => 'password',
];
$ollama_endpoint = "http://localhost:11434/api/generate";

// --- MUSIC LIBRARY FUNCTION ---
function getMusicLibrary($dir) {
    $library = [];
    $rootPath = realpath($dir);
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $audioExtensions = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];

        foreach ($iterator as $file) {
            if (in_array(strtolower($file->getExtension()), $audioExtensions)) {
                $displayName = basename($file->getPath());
                
                $artist = '';
                $songTitle = $displayName;
                if (strpos($displayName, ' - ') !== false) {
                    list($artist, $songTitle) = array_map('trim', explode(' - ', $displayName, 2));
                }
                
                $simplifiedKey = strtolower($displayName);
                $simplifiedKey = preg_replace('/[_\-\[\]\(\)]+/', ' ', $simplifiedKey);
                $simplifiedKey = preg_replace('/[^a-z0-9\s]/', '', $simplifiedKey);
                $simplifiedKey = trim(preg_replace('/\s+/', ' ', $simplifiedKey));
                
                $fullFilePath = $file->getRealPath();
                $relativePath = substr($fullFilePath, strlen($rootPath) + 1);
                $webPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                if (!empty($simplifiedKey) && !isset($library[$simplifiedKey])) {
                    $library[$simplifiedKey] = [
                        'path' => $webPath,
                        'displayName' => $displayName,
                        'artist' => $artist,
                        'song' => $songTitle
                    ];
                }
            }
        }
    } catch (Exception $e) {
        return ['error' => 'Could not scan music directory: ' . $e->getMessage()];
    }
    ksort($library);
    return $library;
}

// --- AUTHENTICATION LOGIC ---
$error = '';
if (isset($_POST['username'], $_POST['password'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];
    if (isset($users[$u]) && $users[$u] === $p) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $u;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// --- LOGIN PAGE RENDER ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Vista Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --frutiger-sky-light: #80d5ff;
            --frutiger-sky-dark: #0077c2;
            --frutiger-grass-light: #76d752;
            --frutiger-grass-dark: #3a912c;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-color: #0f3c5b;
            --gloss-gradient: linear-gradient(to bottom, rgba(255,255,255,0.5), rgba(255,255,255,0.1));
        }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to bottom, var(--frutiger-sky-light) 0%, var(--frutiger-sky-dark) 50%, var(--frutiger-grass-dark) 100%);
            color: var(--text-color);
            padding: 20px;
            box-sizing: border-box;
        }
        .login-container {
            width: 100%;
            max-width: 380px;
            padding: 40px;
            background: var(--glass-bg);
            border-top: 2px solid var(--glass-border);
            border-left: 1px solid var(--glass-border);
            border-right: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255,255,255,0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            text-align: center;
        }
        .login-container h2 {
            margin-top: 0;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 28px;
            color: #fff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }
        .error {
            color: #fff;
            background: rgba(220, 53, 69, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
            text-shadow: 0 1px 1px rgba(255,255,255,0.3);
        }
        .input-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            font-size: 16px;
            color: #fff;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        .input-group input::placeholder { color: rgba(255,255,255,0.6); }
        .input-group input:focus {
            outline: none;
            border-color: var(--frutiger-sky-light);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 0 0 3px rgba(128, 213, 255, 0.5);
        }
        .login-button {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--frutiger-grass-dark);
            border-radius: 10px;
            background: linear-gradient(to bottom, var(--frutiger-grass-light), var(--frutiger-grass-dark));
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            text-shadow: 0 -1px 1px rgba(0,0,0,0.3);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.4);
        }
        .login-button:hover {
            background: linear-gradient(to bottom, #87e063, #4bab3d);
            box-shadow: 0 7px 15px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        .login-button:active {
            transform: translateY(1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Welcome</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">Login</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// --- BACKEND LOGIC ---
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$realDir = realpath($currentDir);
$root = realpath('.');

if ($realDir === false || strpos($realDir, $root) !== 0) {
    die("Access denied.");
}

// --- Ollama Proxy ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    $user_prompt = $_POST['prompt'];
    $model = $_POST['model'] ?? 'qwen2.5:0.5b';

    // UPDATED AI INSTRUCTION
    $system_instruction = "If the user wants to play one or more songs, respond with {\"action\": \"play_music\", \"song_name\": \"the full list of songs they mentioned\"}. For 'list songs', respond with {\"action\": \"list_songs\"}. For 'play random', respond with {\"action\": \"play_random\"}. Otherwise, answer normally. Only respond with JSON for a command.";
    $final_prompt = $system_instruction . "\n\nUser: " . $user_prompt;

    $ch = curl_init($ollama_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['model' => $model, 'prompt' => $final_prompt, 'stream' => false]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch) || $http_code >= 400) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Ollama API Error',
            'details' => [
                'curl_error' => curl_error($ch),
                'http_code' => $http_code,
                'response_body' => $response_body
            ]
        ]);
    } else {
        header("Content-Type: application/json");
        echo $response_body;
    }
    curl_close($ch);
    exit;
}

// --- Other Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $uploadPath = $realDir . DIRECTORY_SEPARATOR . basename($file['name']);
    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        move_uploaded_file($file['tmp_name'], $uploadPath);
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($currentDir));
    exit;
}

function handleFileRequest($param, $disposition) {
    global $realDir, $root, $currentDir;
    if (isset($_GET[$param])) {
        $filePath = realpath($realDir . DIRECTORY_SEPARATOR . $_GET[$param]);
        if ($filePath && is_file($filePath) && strpos($filePath, $root) === 0) {
            header("Content-Type: " . mime_content_type($filePath));
            if ($disposition === 'attachment') {
                header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
            }
            readfile($filePath);
            exit;
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?dir=' . urlencode($currentDir));
            exit;
        }
    }
}
handleFileRequest('view', 'inline');
handleFileRequest('download', 'attachment');

$items = scandir($realDir);
$musicLibrary = getMusicLibrary($root);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultimate Panel + Ollama</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --frutiger-sky-light: #80d5ff;
            --frutiger-sky-dark: #0077c2;
            --frutiger-grass-light: #76d752;
            --frutiger-grass-dark: #3a912c;
            --glass-bg: rgba(255, 255, 255, 0.2);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-dark: #0f3c5b;
            --text-light: #3e627a;
            --shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            --gloss-gradient: linear-gradient(to bottom, rgba(255,255,255,0.4), rgba(255,255,255,0.05));
        }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(to bottom, var(--frutiger-sky-light) 0%, var(--frutiger-sky-dark) 70%, var(--frutiger-grass-dark) 100%);
            background-attachment: fixed;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }
        .main-container {
            width: 100%;
            max-width: 1200px;
            height: 90vh;
            max-height: 850px;
            display: flex;
            gap: 20px;
        }
        .panel {
            background: var(--glass-bg);
            border-top: 2px solid var(--glass-border);
            border-left: 1px solid var(--glass-border);
            border-right: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow), inset 0 1px 0 rgba(255,255,255,0.4);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .file-manager { flex: 0 0 400px; }
        .chat-interface { flex: 1; }
        .panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            background: var(--gloss-gradient);
        }
        .panel-body { padding: 10px; overflow-y: auto; flex: 1; }
        .current-dir {
            font-size: 14px;
            word-break: break-all;
            background: rgba(0,0,0,0.1);
            padding: 10px 15px;
            border-radius: 10px;
            margin: 10px;
            color: var(--text-dark);
            font-weight: 500;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .file-list { list-style: none; padding: 0 10px; margin: 0; }
        .file-list li { display: flex; align-items: center; padding: 4px 10px; border-radius: 8px; transition: all 0.2s ease; }
        .file-list li:hover { background: rgba(255, 255, 255, 0.4); transform: translateX(5px); }
        .file-list a { color: var(--text-dark); text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 10px; flex: 1; padding: 8px 0; }
        .file-icon { width: 20px; text-align: center; color: var(--frutiger-sky-dark); font-size: 1.2em; }
        .file-actions { display: flex; gap: 8px; }
        .file-actions a { font-size: 12px; color: #fff; background: var(--frutiger-sky-dark); padding: 4px 10px; border-radius: 15px; font-weight: 600; transition: all 0.2s ease; }
        .file-actions a:hover { background: var(--frutiger-sky-light); color: var(--text-dark); }
        .upload-form { margin: 10px; padding: 10px; border-top: 1px solid var(--glass-border); background: rgba(0,0,0,0.05); border-radius: 0 0 16px 16px; }
        .upload-label { display: block; text-align: center; padding: 20px; background: rgba(255,255,255,0.2); border: 2px dashed rgba(255,255,255,0.4); border-radius: 10px; cursor: pointer; transition: all 0.2s ease; font-weight: 500; color: var(--text-dark); }
        .upload-label:hover { background: rgba(255,255,255,0.7); border-color: var(--frutiger-sky-light); }
        #chat-box { flex: 1; overflow-y: auto; padding: 20px; }
        #chat-box .song-list-item { display: flex; justify-content: space-between; align-items: center; }
        #chat-box .song-list-item button { background: none; border: none; font-size: 18px; color: var(--frutiger-sky-dark); cursor: pointer; padding: 5px; }
        .message { display: flex; margin-bottom: 20px; max-width: 85%; }
        .message-content { padding: 12px 18px; border-radius: 18px; line-height: 1.5; box-shadow: 0 4px 8px rgba(0,0,0,0.1); word-wrap: break-word; }
        .me { margin-left: auto; flex-direction: row-reverse; }
        .me .message-content { background: linear-gradient(to top right, #c8e6c9, #a5d6a7); border-bottom-right-radius: 4px; color: #1b5e20; }
        .ai { margin-right: auto; }
        .ai .message-content { background: #fff; border-bottom-left-radius: 4px; color: #333; }
        .message-sender { font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .chat-input-area { padding: 15px; border-top: 1px solid var(--glass-border); display: flex; gap: 10px; align-items: center; background: rgba(0,0,0,0.1); border-radius: 0 0 18px 18px; }
        #prompt { flex: 1; padding: 14px; border: 1px solid rgba(0,0,0,0.1); border-radius: 12px; background: rgba(255, 255, 255, 0.5); font-size: 15px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        #prompt:focus { outline: none; border-color: var(--frutiger-sky-light); }
        #model-select { padding: 12px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background: rgba(255, 255, 255, 0.5); }
        .chat-btn { border: 1px solid var(--frutiger-sky-dark); color: white; width: 48px; height: 48px; border-radius: 50%; cursor: pointer; font-size: 18px; transition: all 0.2s ease; text-shadow: 0 -1px 1px rgba(0,0,0,0.2); box-shadow: 0 4px 8px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.5); }
        .chat-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.6); }
        .chat-btn:active { transform: translateY(1px); box-shadow: 0 2px 4px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.6); }
        #send-button { background: linear-gradient(to bottom, var(--frutiger-sky-light), var(--frutiger-sky-dark)); }
        #mic-button { background: linear-gradient(to bottom, #9e9e9e, #616161); }
        #mic-button.is-listening { background: linear-gradient(to bottom, #ff8a80, #d50000); animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(213, 0, 0, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(213, 0, 0, 0); } 100% { box-shadow: 0 0 0 0 rgba(213, 0, 0, 0); } }

        #layout-toggle-button { position: fixed; top: 20px; right: 20px; z-index: 1000; width: 50px; height: 50px; background: linear-gradient(to bottom, var(--frutiger-sky-light), var(--frutiger-sky-dark)); color: white; border: 2px solid var(--glass-border); border-radius: 50%; cursor: pointer; font-size: 22px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.5); transition: all 0.3s ease; }
        #layout-toggle-button:hover { transform: scale(1.1); }
        .mobile-view { padding: 0 !important; align-items: flex-start !important; }
        .mobile-view .main-container { flex-direction: column; height: 100vh; max-height: none; gap: 0; border-radius: 0; }
        .mobile-view .panel { border-radius: 0; border-left: none; border-right: none; box-shadow: none; flex-grow: 1; min-height: 0; }
        .mobile-view .file-manager { flex-basis: 45%; }
        .mobile-view .chat-interface { flex-basis: 55%; border-top: 1px solid var(--glass-border); }
        .mobile-view #layout-toggle-button { background: linear-gradient(to bottom, var(--frutiger-grass-light), var(--frutiger-grass-dark)); }
        
        #audio-player-bar { position: fixed; bottom: -150px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 600px; background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); border-radius: 15px; box-shadow: 0 -4px 20px rgba(0,0,0,0.3); padding: 15px 20px; transition: bottom 0.5s ease-in-out; z-index: 2000; display: flex; align-items: center; gap: 15px; }
        #audio-player-bar.visible { bottom: 20px; }

        .album-art-frame { min-width: 60px; height: 60px; background: linear-gradient(145deg, rgba(255,255,255,0.3), rgba(255,255,255,0.05)); border-radius: 10px; padding: 5px; box-shadow: inset 0 0 1px 1px rgba(255,255,255,0.4), 0 2px 4px rgba(0,0,0,0.2); }
        #album-art-img, #album-art-placeholder { width: 100%; height: 100%; border-radius: 6px; object-fit: cover; }
        #album-art-placeholder { background-color: rgba(0,0,0,0.2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        #album-art-img { transition: opacity 0.5s ease; }
        .player-controls { flex-grow: 1; display: flex; flex-direction: column; justify-content: center; }
        .player-top-row { display: flex; align-items: center; gap: 15px; color: #fff; }
        .player-ctrl-btn { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 0 8px; }
        #play-pause-btn { font-size: 24px; padding: 0 8px 0 0; }
        #now-playing-text { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex-grow: 1; }
        #time-display { font-size: 14px; font-weight: 400; }
        .progress-container { width: 100%; margin-top: 10px; }
        #progress-bar { width: 100%; cursor: pointer; -webkit-appearance: none; appearance: none; height: 8px; background: rgba(255,255,255,0.2); border-radius: 4px; outline: none; }
        #progress-bar::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 16px; height: 16px; background: #fff; border-radius: 50%; cursor: pointer; }
        #progress-bar::-moz-range-thumb { width: 16px; height: 16px; background: #fff; border-radius: 50%; cursor: pointer; border: none; }
    </style>
</head>
<body>

<button id="layout-toggle-button" title="Toggle Mobile Layout">
    <i class="fa-solid fa-mobile-screen-button"></i>
</button>

<div class="main-container">
    <div class="panel file-manager">
        <div class="panel-header"><i class="fa-solid fa-folder-open"></i> File Manager</div>
        <div class="panel-body">
            <div class="current-dir"><strong>Current:</strong> <?= htmlspecialchars($currentDir) ?></div>
            <ul class="file-list">
                <?php
                if ($realDir !== $root) { echo "<li><a href='?dir=" . urlencode(dirname($currentDir)) . "'><i class='file-icon fa-solid fa-arrow-turn-up'></i> Go Up</a></li>"; }
                $dirs = []; $files = [];
                foreach ($items as $item) { if ($item === '.' || $item === '..') continue; if (is_dir($realDir . DIRECTORY_SEPARATOR . $item)) { $dirs[] = $item; } else { $files[] = $item; } }
                foreach ($dirs as $item) { echo "<li><a href='?dir=" . urlencode($currentDir . DIRECTORY_SEPARATOR . $item) . "'><i class='file-icon fa-solid fa-folder'></i> " . htmlspecialchars($item) . "</a></li>"; }
                foreach ($files as $item) {
                    $encodedItem = urlencode($item); $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                    $icon = 'fa-solid fa-file';
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $icon = 'fa-solid fa-file-image'; elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'flac', 'm4a'])) $icon = 'fa-solid fa-file-audio'; elseif (in_array($ext, ['mp4', 'webm', 'mov'])) $icon = 'fa-solid fa-file-video'; elseif (in_array($ext, ['zip', 'rar', '7z'])) $icon = 'fa-solid fa-file-zipper'; elseif (in_array($ext, ['pdf'])) $icon = 'fa-solid fa-file-pdf';
                    echo "<li><a href='?dir=" . urlencode($currentDir) . "&view=$encodedItem' target='_blank'><i class='file-icon $icon'></i> " . htmlspecialchars($item) . "</a><div class='file-actions'><a href='?dir=" . urlencode($currentDir) . "&download=$encodedItem'>DL</a></div></li>";
                }
                ?>
            </ul>
        </div>
        <form class="upload-form" method="post" enctype="multipart/form-data" id="uploadForm">
            <label for="file-upload" class="upload-label"><i class="fa-solid fa-cloud-arrow-up"></i> Choose file...</label>
            <input id="file-upload" type="file" name="file" style="display:none;">
        </form>
    </div>

    <div class="panel chat-interface">
        <div class="panel-header"><i class="fa-solid fa-robot"></i> Chat with Ollama</div>
        <div class="panel-body" id="chat-box"></div>
        <div class="chat-input-area">
            <select id="model-select">
                <option value="qwen2.5:0.5b">qwen2.5:0.5b</option>
                <option value="smollm:135m">deepseek-r1:1.5b</option>
                <option value="smollm2:135m">smollm2:135m</option>
            </select>
            <input id="prompt" placeholder="Type or click the mic to talk..." autocomplete="off">
            <button id="mic-button" class="chat-btn" title="Use Voice"><i class="fa-solid fa-microphone"></i></button>
            <button id="send-button" class="chat-btn"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<audio id="audio-player"></audio>
<div id="audio-player-bar">
    <div class="album-art-frame">
        <div id="album-art-placeholder"><i class="fas fa-music"></i></div>
        <img src="" id="album-art-img" alt="Album Art" style="display: none;">
    </div>
    <div class="player-controls">
        <div class="player-top-row">
            <button id="prev-btn" class="player-ctrl-btn"><i class="fas fa-backward-step"></i></button>
            <button id="play-pause-btn" class="player-ctrl-btn"><i class="fas fa-play"></i></button>
            <button id="next-btn" class="player-ctrl-btn"><i class="fas fa-forward-step"></i></button>
            <div id="now-playing-text">No song selected</div>
            <div id="time-display">0:00 / 0:00</div>
        </div>
        <div class="progress-container">
            <input type="range" id="progress-bar" value="0" step="1" style="width: 100%;">
        </div>
    </div>
</div>


<script>
const musicLibrary = <?= json_encode($musicLibrary) ?>;
const albumArtCache = {};

document.addEventListener('DOMContentLoaded', function() {
    // --- Element Definitions ---
    const chatBox = document.getElementById('chat-box');
    const promptInput = document.getElementById('prompt');
    const sendButton = document.getElementById('send-button');
    const micButton = document.getElementById('mic-button');
    const modelSelect = document.getElementById('model-select');
    
    const audioPlayer = document.getElementById('audio-player');
    const playerBar = document.getElementById('audio-player-bar');
    const playPauseBtn = document.getElementById('play-pause-btn');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const nowPlayingText = document.getElementById('now-playing-text');
    const timeDisplay = document.getElementById('time-display');
    const progressBar = document.getElementById('progress-bar');
    const albumArtImg = document.getElementById('album-art-img');
    const albumArtPlaceholder = document.getElementById('album-art-placeholder');

    // --- Player State ---
    let playbackQueue = [];
    let currentQueueIndex = -1;

    // --- Voice Recognition ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.lang = 'en-US';
        recognition.interimResults = true;

        micButton.addEventListener('click', () => {
            if (micButton.classList.contains('is-listening')) {
                recognition.stop();
            } else {
                try { recognition.start(); } catch(e) { console.error("Could not start recognition", e); }
            }
        });

        recognition.onstart = () => micButton.classList.add('is-listening');
        recognition.onend = () => micButton.classList.remove('is-listening');
        recognition.onerror = (event) => { micButton.classList.remove('is-listening'); };
        recognition.onresult = (event) => {
            let finalTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript;
                } else {
                    promptInput.value = event.results[i][0].transcript;
                }
            }
            if (finalTranscript) {
                promptInput.value = finalTranscript.trim();
                sendButton.click();
            }
        };
    } else {
        micButton.style.display = 'none';
    }

    // --- Core Logic Functions ---
    function simplifyString(str) {
        return str.toLowerCase().replace(/[_\-\[\]\(\)]+/g, ' ').replace(/[^a-z0-9\s]/g, '').trim().replace(/\s+/g, ' ');
    }
    
    function findSong(query) {
        const queryTokens = new Set(simplifyString(query).split(' '));
        if (queryTokens.size === 0) return null;
        let bestMatch = null;
        let highestScore = 0;
        for (const key in musicLibrary) {
            const titleTokens = new Set(key.split(' '));
            let score = 0;
            for (const token of queryTokens) {
                if (titleTokens.has(token)) { score++; }
            }
            const normalizedScore = score / titleTokens.size; 
            if (normalizedScore > highestScore) {
                highestScore = normalizedScore;
                bestMatch = musicLibrary[key];
            }
        }
        return highestScore > 0.5 ? bestMatch : null;
    }
    
    function playFromQueue(index) {
        if (index >= 0 && index < playbackQueue.length) {
            currentQueueIndex = index;
            const songData = playbackQueue[currentQueueIndex];

            nowPlayingText.textContent = songData.displayName;
            const encodedPath = songData.path.split('/').map(part => encodeURIComponent(part)).join('/');
            audioPlayer.src = encodedPath;
            playerBar.classList.add('visible');

            fetchAlbumArt(songData);

            const playPromise = audioPlayer.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                });
            }
        } else {
            console.log("Queue finished.");
        }
    }

    function playNext() {
        playFromQueue(currentQueueIndex + 1);
    }

    function playPrev() {
        playFromQueue(currentQueueIndex - 1);
    }
    
    async function fetchAlbumArt(songData) {
        albumArtImg.style.display = 'none';
        albumArtPlaceholder.style.display = 'flex';

        if (albumArtCache[songData.displayName]) {
            if (albumArtCache[songData.displayName] !== 'not_found') {
                albumArtImg.src = albumArtCache[songData.displayName];
                albumArtImg.style.display = 'block';
                albumArtPlaceholder.style.display = 'none';
            }
            return;
        }

        const searchStrategies = [
            `${songData.artist} ${songData.song}`,
            songData.displayName,
            songData.song
        ];

        for (const term of searchStrategies) {
            if (!term) continue;
            const url = `https://itunes.apple.com/search?term=${encodeURIComponent(term)}&entity=song&limit=1`;
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.resultCount > 0 && data.results[0].artworkUrl100) {
                    const highResUrl = data.results[0].artworkUrl100.replace('100x100bb.jpg', '600x600bb.jpg');
                    albumArtImg.src = highResUrl;
                    albumArtImg.style.display = 'block';
                    albumArtPlaceholder.style.display = 'none';
                    albumArtCache[songData.displayName] = highResUrl;
                    return;
                }
            } catch (error) {
                console.error(`Search failed for term "${term}":`, error);
            }
        }
        
        albumArtCache[songData.displayName] = 'not_found';
    }

    // --- Event Listeners for Player ---
    prevBtn.addEventListener('click', playPrev);
    nextBtn.addEventListener('click', playNext);
    playPauseBtn.addEventListener('click', () => {
        if (!audioPlayer.src || audioPlayer.src === window.location.href) return;
        if (audioPlayer.paused) {
            audioPlayer.play();
        } else {
            audioPlayer.pause();
        }
    });
    audioPlayer.addEventListener('pause', () => { playPauseBtn.innerHTML = '<i class="fas fa-play"></i>'; });
    audioPlayer.addEventListener('play', () => { playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>'; });
    audioPlayer.addEventListener('ended', playNext); // Play next song when current one ends
    audioPlayer.addEventListener('error', (e) => { addMessage(`<strong>Error:</strong> Could not load audio file. Check file path/permissions.`, 'ai'); });

    function formatTime(seconds) {
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return `${min}:${sec < 10 ? '0' : ''}${sec}`;
    }
    audioPlayer.addEventListener('loadedmetadata', () => {
        progressBar.max = audioPlayer.duration;
        timeDisplay.textContent = `${formatTime(0)} / ${formatTime(audioPlayer.duration)}`;
    });
    audioPlayer.addEventListener('timeupdate', () => {
        progressBar.value = audioPlayer.currentTime;
        timeDisplay.textContent = `${formatTime(audioPlayer.currentTime)} / ${formatTime(audioPlayer.duration)}`;
    });
    progressBar.addEventListener('input', () => {
        audioPlayer.currentTime = progressBar.value;
    });

    // --- Chat & AI Logic ---
    function handleAiResponse(content) {
        const actionMatch = content.match(/"action"\s*:\s*"([^"]+)"/);
        
        if (!actionMatch) {
            addMessage(content, 'ai');
            return;
        }

        const action = actionMatch[1];

        if (action === 'play_music') {
            const songNameMatch = content.match(/"song_name"\s*:\s*"([^"]+)"/);
            if(songNameMatch && songNameMatch[1]) {
                const songRequests = songNameMatch[1].split(/, | and | then /i);
                playbackQueue = []; // Clear previous queue
                let songsFound = 0;
                songRequests.forEach(request => {
                    const songData = findSong(request);
                    if(songData) {
                        playbackQueue.push(songData);
                        songsFound++;
                    }
                });
                if(songsFound > 0) {
                    playFromQueue(0);
                } else {
                    addMessage("Sorry, I couldn't find any of the requested songs in your library.", 'ai');
                }
            }
        } else if (action === 'list_songs') {
            listSongs();
        } else if (action === 'play_random') {
            playRandomSong();
        } else {
            addMessage(content, 'ai');
        }
    }

    function playRandomSong() {
        const songs = Object.values(musicLibrary);
        if (songs.length > 0) {
            const randomSong = songs[Math.floor(Math.random() * songs.length)];
            playbackQueue = [randomSong];
            playFromQueue(0);
        }
    }

    function addMessage(content, sender) {
        const senderName = sender === 'me' ? 'You' : 'Ollama';
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        const sanitizedContent = content.replace(/</g, "&lt;").replace(/>/g, "&gt;");
        messageDiv.innerHTML = `<div class="message-content"><div class="message-sender">${senderName}</div>${sanitizedContent}</div>`;
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    function listSongs() {
        let songListHtml = "<strong>Available Songs:</strong><ul>";
        if (Object.keys(musicLibrary).length > 0) {
            for (const key in musicLibrary) {
                const songData = musicLibrary[key];
                songListHtml += `<li class="song-list-item"><span>${songData.displayName}</span> <button class="direct-play-btn" data-song-key="${key}"><i class="fas fa-play-circle"></i></button></li>`;
            }
        } else {
            songListHtml += "<li>No music found in the library.</li>";
        }
        songListHtml += "</ul>";
        
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message ai';
        messageDiv.innerHTML = `<div class="message-content"><div class="message-sender">System</div>${songListHtml}</div>`;
        chatBox.appendChild(messageDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    chatBox.addEventListener('click', function(event) {
        const target = event.target.closest('.direct-play-btn');
        if (target) {
            const songKey = target.dataset.songKey;
            if (songKey && musicLibrary[songKey]) {
                playbackQueue = [musicLibrary[songKey]];
                playFromQueue(0);
            }
        }
    });

    function sendPrompt() {
        const promptText = promptInput.value.trim();
        if (!promptText) return;

        const command = promptText.toLowerCase();
        if (['list songs', 'debug library', 'playrandom'].includes(command)) {
            addMessage(promptText, 'me');
            if (command === 'list songs') listSongs();
            if (command === 'debug library') {
                const debugContent = `<pre style="white-space: pre-wrap; word-break: break-all;">${JSON.stringify(musicLibrary, null, 2)}</pre>`;
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message ai';
                messageDiv.innerHTML = `<div class="message-content"><div class="message-sender">System</div>${debugContent}</div>`;
                chatBox.appendChild(messageDiv);
                chatBox.scrollTop = chatBox.scrollHeight;
            }
            if (command === 'playrandom') playRandomSong();
            promptInput.value = "";
            return;
        }

        addMessage(promptText, 'me');
        promptInput.value = "";
        promptInput.disabled = true;
        sendButton.disabled = true;

        fetch("", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ prompt: promptText, model: modelSelect.value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                let errorMsg = `<strong>Error:</strong> ${data.error}`;
                if (data.details) {
                    errorMsg += `<br><small>Status: ${data.details.http_code}. See console for full response.</small>`;
                    console.error("Ollama API Error Details:", data.details);
                }
                addMessage(errorMsg, 'ai');
            } else {
                const responseText = data.response || "No response from model.";
                handleAiResponse(responseText);
            }
        })
        .catch(err => {
            addMessage(`<strong>JavaScript Error:</strong> ${err.message}. Check browser console.`, 'ai');
            console.error(err);
        })
        .finally(() => {
            promptInput.disabled = false;
            sendButton.disabled = false;
            promptInput.focus();
        });
    }

    sendButton.addEventListener('click', sendPrompt);
    promptInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendPrompt(); }
    });
});
</script>

</body>
</html>
