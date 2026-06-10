<?php
require_once 'config.php';

// الحصول على معرف القناة
$ch_id = $_GET['id'] ?? $_GET['ch'] ?? '';
$channels = get_json_data(CHANNELS_FILE);
$channel = null;

foreach ($channels as $c) {
    if ($c['id'] == $ch_id) {
        $channel = $c;
        break;
    }
}

if (!$channel) {
    die("<div style='color:#f43f5e; font-family:sans-serif; text-align:center; margin-top:50px; font-size:1.2rem;'>❌ القناة غير موجودة أو تم حذفها.</div>");
}

// استخراج السيرفرات المتوفرة للقناة
$servers = [];
if (!empty($channel['servers']) && is_array($channel['servers'])) {
    $servers = $channel['servers'];
} else {
    // دعم القنوات القديمة التي لا تحتوي على مصفوفة سيرفرات
    $servers[] = [
        'name'      => 'سيرفر أساسي',
        'url'       => $channel['url'] ?? '',
        'useragent' => $channel['useragent'] ?? '',
        'referer'   => $channel['referer'] ?? '',
        'origin'    => $channel['origin'] ?? '',
        'drmkey'    => $channel['drmkey'] ?? '',
        'cookie'    => $channel['cookie'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشغل البث — <?= htmlspecialchars($channel['name']) ?></title>
    
    <!-- Video.js Styles -->
    <link href="https://cdn.jsdelivr.net/npm/video.js@8.10.0/dist/video-js.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --accent: #ff0000; /* مؤشر أحمر */
            --bg: #03030c;
            --panel-bg: rgba(15, 15, 35, 0.85);
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg);
            color: #fff;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }

        .player-wrapper {
            position: relative;
            width: 100%;
            max-width: 960px;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8), 0 0 30px rgba(255, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        /* حاوية الفيديو */
        .video-js {
            width: 100%;
            height: 100%;
            font-family: inherit;
        }

        /* شريط اختيار السيرفرات من الأعلى داخل المشغل */
        .server-selector-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10;
            background: linear-gradient(to bottom, rgba(3, 3, 12, 0.95) 20%, rgba(3, 3, 12, 0.7) 60%, rgba(3, 3, 12, 0));
            padding: 18px 20px 35px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform: translateY(0);
            opacity: 1;
        }

        /* إخفاء شريط السيرفرات تلقائياً عند اختفاء أدوات التحكم بالمشغل */
        .video-js.vjs-user-inactive .server-selector-overlay {
            transform: translateY(-100%);
            opacity: 0;
        }

        .server-selector-overlay h3 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
        }

        .server-selector-overlay h3 i {
            color: var(--accent);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .servers-list {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
            padding-bottom: 2px;
            width: 100%;
        }

        .servers-list::-webkit-scrollbar {
            display: none; /* Chrome/Safari */
        }

        .server-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e2f0;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(10px);
        }

        .server-btn:hover {
            background: rgba(255, 0, 0, 0.15);
            border-color: rgba(255, 0, 0, 0.3);
            color: #fff;
        }

        .server-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 0 12px rgba(255, 0, 0, 0.4);
            font-weight: 600;
        }

        .server-btn.active::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #fff;
            border-radius: 50%;
            display: inline-block;
        }

        /* ── تعديل ثيم المشغل ليكون المؤشر بأحمر ── */
        .video-js .vjs-big-play-button {
            background-color: rgba(255, 0, 0, 0.8) !important;
            border: 2px solid var(--accent) !important;
            border-radius: 50% !important;
            width: 70px !important;
            height: 70px !important;
            line-height: 66px !important;
            margin-left: -35px !important;
            margin-top: -35px !important;
            font-size: 2.2rem !important;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.4) !important;
            transition: all 0.2s !important;
        }

        .video-js:hover .vjs-big-play-button {
            background-color: var(--accent) !important;
            transform: scale(1.1);
        }

        /* شريط التمرير والمؤشر (Progress Bar) باللون الأحمر */
        .video-js .vjs-play-progress {
            background-color: var(--accent) !important;
        }

        .video-js .vjs-play-progress:before {
            color: #fff !important;
            text-shadow: 0 0 8px rgba(255, 0, 0, 1) !important;
        }

        .video-js .vjs-slider {
            background-color: rgba(255, 255, 255, 0.2) !important;
        }

        .video-js .vjs-load-progress {
            background-color: rgba(255, 255, 255, 0.15) !important;
        }

        .video-js .vjs-volume-level {
            background-color: var(--accent) !important;
        }

        .video-js .vjs-control-bar {
            background-color: rgba(3, 3, 12, 0.85) !important;
            backdrop-filter: blur(10px);
            height: 48px;
        }

        /* زر الرجوع للوحة التحكم */
        .back-btn-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7a7a9a;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s;
            background: rgba(255,255,255,0.03);
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .back-btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>

    <div class="player-wrapper">
        
        <!-- قائمة اختيار السيرفرات في الأعلى بداخل المشغل -->
        <div class="server-selector-overlay">
            <h3><i class="bi bi-broadcast"></i> السيرفرات:</h3>
            <div class="servers-list">
                <?php foreach ($servers as $index => $srv): ?>
                    <button class="server-btn" onclick="selectServer(<?= $index ?>)">
                        <?= htmlspecialchars($srv['name'] ?: "سيرفر " . ($index + 1)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- مشغل الفيديو -->
        <video id="tv-player" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto">
            <p class="vjs-no-js">
                لتشغيل هذا الفيديو يرجى تفعيل الجافا سكربت، أو استخدام متصفح حديث يدعم HTML5.
            </p>
        </video>

    </div>

    <div class="back-btn-container">
        <a href="admin/index.php" class="back-btn">
            <i class="bi bi-arrow-right"></i> العودة للوحة التحكم
        </a>
    </div>

    <!-- Video.js and DASH/DRM dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/video.js@8.10.0/dist/video.min.js"></script>
    
    <!-- Dash.js for DASH streams support (.mpd) -->
    <script src="https://cdn.jsdelivr.net/npm/dashjs@4.7.4/dist/dash.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/videojs-contrib-dash@5.1.1/dist/videojs-contrib-dash.min.js"></script>

    <!-- videojs-contrib-eme for DRM support (ClearKey / Widevine) -->
    <script src="https://cdn.jsdelivr.net/npm/videojs-contrib-eme@5.1.1/dist/videojs-contrib-eme.min.js"></script>

    <script>
        // تعريف بيانات السيرفرات القادمة من PHP
        const servers = <?php echo json_encode($servers); ?>;
        let player = null;

        document.addEventListener('DOMContentLoaded', () => {
            // تهيئة مشغل الفيديو
            player = videojs('tv-player', {
                autoplay: true,
                controls: true,
                fluid: false,
                preload: 'auto',
                liveui: true,
                controlBar: {
                    pictureInPictureToggle: false
                }
            });

            // تفعيل دعم الـ DRM (Encrypted Media Extensions)
            if (typeof player.eme === 'function') {
                player.eme();
            }

            // تشغيل السيرفر الأول افتراضياً
            selectServer(0);
        });

        function selectServer(index) {
            if (!servers[index]) return;
            
            const server = servers[index];
            const url = server.url;
            
            // تحديث الأزرار النشطة
            const buttons = document.querySelectorAll('.server-btn');
            buttons.forEach((btn, i) => {
                if (i === index) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // تحديد نوع البث بناءً على الرابط
            let type = 'application/x-mpegURL'; // الافتراضي HLS (.m3u8)
            if (url.includes('.mpd')) {
                type = 'application/dash+xml'; // باقة DASH (.mpd)
            } else if (url.includes('.mp4')) {
                type = 'video/mp4';
            }

            // خيارات البث الافتراضية
            let srcOptions = {
                src: url,
                type: type
            };

            // معالجة مفاتيح التشفير DRM وحماية ClearKey أو Widevine
            if (server.drmkey) {
                const keyStr = server.drmkey.trim();
                
                if (keyStr.startsWith('http')) {
                    // إذا كان رابط ترخيص Widevine
                    srcOptions.keySystems = {
                        'com.widevine.alpha': keyStr
                    };
                } else {
                    // إذا كانت حماية ClearKey (بصيغة KID:KEY)
                    try {
                        let clearkeysData = {};
                        if (keyStr.includes(':')) {
                            const parts = keyStr.split(':');
                            if (parts.length === 2) {
                                clearkeysData[parts[0].trim()] = parts[1].trim();
                            }
                        } else {
                            // إذا كانت بصيغة JSON مباشرة
                            clearkeysData = JSON.parse(keyStr);
                        }
                        
                        srcOptions.keySystems = {
                            'org.w3.clearkey': {
                                'clearkeys': clearkeysData
                            }
                        };
                    } catch (e) {
                        console.error('خطأ في معالجة مفتاح ClearKey:', e);
                    }
                }
            }

            // تحميل الرابط في المشغل وتحديث البث
            player.src(srcOptions);
            player.load();
            
            // محاولة التشغيل التلقائي
            player.ready(() => {
                player.play().catch(err => {
                    console.log('فشل التشغيل التلقائي بسبب حماية المتصفح، يرجى النقر على تشغيل:', err);
                });
            });
        }
    </script>
</body>
</html>
