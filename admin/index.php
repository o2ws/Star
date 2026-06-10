<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$action = isset($_GET['op']) ? $_GET['op'] : 'list';
$success_msg = '';
$error_msg = '';

// ─── حذف تصنيف ───
if ($action == 'del_cat') {
    $id = $_GET['id'];
    $cats = get_json_data(CATEGORIES_FILE);
    $cats = array_values(array_filter($cats, fn($c) => $c['id'] != $id));
    save_json_data(CATEGORIES_FILE, $cats);
    header('Location: index.php?msg=cat_deleted'); exit;
}

// ─── حذف قناة ───
if ($action == 'del_ch') {
    $id = $_GET['id'];
    $chs = get_json_data(CHANNELS_FILE);
    $chs = array_values(array_filter($chs, fn($c) => $c['id'] != $id));
    save_json_data(CHANNELS_FILE, $chs);
    header('Location: index.php?msg=ch_deleted'); exit;
}

// ─── تحميل بيانات التعديل ───
$edit_cat = null;
$edit_ch  = null;
$edit_servers = [];
if ($action == 'edit_cat' && isset($_GET['id'])) {
    foreach (get_json_data(CATEGORIES_FILE) as $c) {
        if ($c['id'] == $_GET['id']) { $edit_cat = $c; break; }
    }
}
if ($action == 'edit_ch' && isset($_GET['id'])) {
    foreach (get_json_data(CHANNELS_FILE) as $c) {
        if ($c['id'] == $_GET['id']) {
            $edit_ch = $c;
            if (!empty($c['servers']) && is_array($c['servers'])) {
                $edit_servers = $c['servers'];
            } elseif (!empty($c['url'])) {
                $edit_servers[] = [
                    'name'      => 'سيرفر 1',
                    'url'       => $c['url'],
                    'useragent' => $c['useragent'] ?? '',
                    'referer'   => $c['referer'] ?? '',
                    'origin'    => $c['origin'] ?? '',
                    'cookie'    => $c['cookie'] ?? '',
                    'drmkey'    => $c['drmkey'] ?? '',
                ];
            }
            break;
        }
    }
}
if (empty($edit_servers)) {
    $edit_servers[] = [
        'name'      => 'سيرفر 1',
        'url'       => '',
        'useragent' => '',
        'referer'   => '',
        'origin'    => '',
        'cookie'    => '',
        'drmkey'    => '',
    ];
}

// ─── حفظ تصنيف جديد ───
if (isset($_POST['save_cat'])) {
    $cats = get_json_data(CATEGORIES_FILE);
    $sort_order = isset($_POST['sort_order']) && $_POST['sort_order'] !== '' ? intval($_POST['sort_order']) : 0;
    $cats[] = [
        'id' => time(),
        'name' => trim($_POST['name']),
        'image' => trim($_POST['image']),
        'sort_order' => $sort_order
    ];
    save_json_data(CATEGORIES_FILE, $cats);
    header('Location: index.php?msg=cat_added'); exit;
}

// ─── تحديث تصنيف موجود ───
if (isset($_POST['update_cat'])) {
    $cats = get_json_data(CATEGORIES_FILE);
    $sort_order = isset($_POST['sort_order']) && $_POST['sort_order'] !== '' ? intval($_POST['sort_order']) : 0;
    foreach ($cats as &$c) {
        if ($c['id'] == $_POST['edit_id']) {
            $c['name']  = trim($_POST['name']);
            $c['image'] = trim($_POST['image']);
            $c['sort_order'] = $sort_order;
            break;
        }
    }
    save_json_data(CATEGORIES_FILE, $cats);
    header('Location: index.php?msg=cat_updated'); exit;
}

// ─── حفظ قناة جديدة ───
if (isset($_POST['save_ch'])) {
    $chs = get_json_data(CHANNELS_FILE);
    
    $servers = [];
    if (isset($_POST['server_url']) && is_array($_POST['server_url'])) {
        for ($i = 0; $i < count($_POST['server_url']); $i++) {
            $s_url = trim($_POST['server_url'][$i]);
            if (empty($s_url)) continue;
            
            $servers[] = [
                'name'      => trim($_POST['server_name'][$i] ?? ''),
                'url'       => $s_url,
                'useragent' => trim($_POST['server_useragent'][$i] ?? ''),
                'referer'   => trim($_POST['server_referer'][$i] ?? ''),
                'origin'    => trim($_POST['server_origin'][$i] ?? ''),
                'cookie'    => trim($_POST['server_cookie'][$i] ?? ''),
                'drmkey'    => trim($_POST['server_drmkey'][$i] ?? ''),
            ];
        }
    }
    
    // Default server values for compatibility
    $first_server = $servers[0] ?? [
        'name' => '', 'url' => '', 'useragent' => '', 'referer' => '', 'origin' => '', 'cookie' => '', 'drmkey' => ''
    ];

    $chs[] = [
        'id'        => time(),
        'cat_id'    => $_POST['cat_id'],
        'name'      => trim($_POST['name']),
        'image'     => trim($_POST['image']),
        'url'       => $first_server['url'],
        'useragent' => $first_server['useragent'],
        'referer'   => $first_server['referer'],
        'origin'    => $first_server['origin'],
        'drmkey'    => $first_server['drmkey'],
        'cookie'    => $first_server['cookie'],
        'servers'   => $servers,
    ];
    save_json_data(CHANNELS_FILE, $chs);
    header('Location: index.php?msg=ch_added'); exit;
}

// ─── تحديث قناة موجودة ───
if (isset($_POST['update_ch'])) {
    $chs = get_json_data(CHANNELS_FILE);
    
    $servers = [];
    if (isset($_POST['server_url']) && is_array($_POST['server_url'])) {
        for ($i = 0; $i < count($_POST['server_url']); $i++) {
            $s_url = trim($_POST['server_url'][$i]);
            if (empty($s_url)) continue;
            
            $servers[] = [
                'name'      => trim($_POST['server_name'][$i] ?? ''),
                'url'       => $s_url,
                'useragent' => trim($_POST['server_useragent'][$i] ?? ''),
                'referer'   => trim($_POST['server_referer'][$i] ?? ''),
                'origin'    => trim($_POST['server_origin'][$i] ?? ''),
                'cookie'    => trim($_POST['server_cookie'][$i] ?? ''),
                'drmkey'    => trim($_POST['server_drmkey'][$i] ?? ''),
            ];
        }
    }
    
    // Default server values for compatibility
    $first_server = $servers[0] ?? [
        'name' => '', 'url' => '', 'useragent' => '', 'referer' => '', 'origin' => '', 'cookie' => '', 'drmkey' => ''
    ];

    foreach ($chs as &$c) {
        if ($c['id'] == $_POST['edit_id']) {
            $c['cat_id']    = $_POST['cat_id'];
            $c['name']      = trim($_POST['name']);
            $c['image']     = trim($_POST['image']);
            $c['url']       = $first_server['url'];
            $c['useragent'] = $first_server['useragent'];
            $c['referer']   = $first_server['referer'];
            $c['origin']    = $first_server['origin'];
            $c['drmkey']    = $first_server['drmkey'];
            $c['cookie']    = $first_server['cookie'];
            $c['servers']   = $servers;
            break;
        }
    }
    save_json_data(CHANNELS_FILE, $chs);
    header('Location: index.php?msg=ch_updated'); exit;
}

// ─── حفظ رسالة التحديث ───
if (isset($_POST['save_update_msg'])) {
    $msg_data = [
        'enabled'  => isset($_POST['msg_enabled']) ? true : false,
        'title'    => trim($_POST['msg_title']),
        'message'  => trim($_POST['msg_body']),
        'url'      => trim($_POST['msg_url']),
        'btn_text' => trim($_POST['msg_btn']),
        'version'  => trim($_POST['app_version']),
        'force'    => isset($_POST['force_update']) ? true : false,
    ];
    save_json_data(UPDATE_MSG_FILE, $msg_data);
    header('Location: index.php?msg=update_saved'); exit;
}

// ─── حفظ إعدادات الصيانة ───
if (isset($_POST['save_maintenance'])) {
    $maint_data = [
        'enabled' => isset($_POST['maint_enabled']) ? true : false,
        'title'   => trim($_POST['maint_title']),
        'message' => trim($_POST['maint_body']),
        'image'   => trim($_POST['maint_image']),
        'contact' => trim($_POST['maint_contact']),
    ];
    save_json_data(MAINTENANCE_FILE, $maint_data);
    header('Location: index.php?msg=maint_saved'); exit;
}

// ─── تحميل البيانات ───
$cats       = get_json_data(CATEGORIES_FILE);
$chs        = get_json_data(CHANNELS_FILE);
$update_cfg = file_exists(UPDATE_MSG_FILE)  ? get_json_data(UPDATE_MSG_FILE)  : [];
$maint_cfg  = file_exists(MAINTENANCE_FILE) ? get_json_data(MAINTENANCE_FILE) : [];

// ─── تحميل بيانات المستخدمين (للقسم الجديد) ───
$users_file = defined('USERS_FILE') ? USERS_FILE : __DIR__ . '/../data/users.json';
$users      = file_exists($users_file) ? get_json_data($users_file) : [];

// إحصائيات المستخدمين
$total_users = count($users);
$today = date('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));
$active_today = 0;
$active_week  = 0;
$platforms = ['android' => 0, 'ios' => 0, 'other' => 0];
foreach ($users as $u) {
    if (substr($u['last_seen'], 0, 10) === $today) $active_today++;
    if (substr($u['last_seen'], 0, 10) >= $week_ago) $active_week++;
    $p = strtolower($u['platform'] ?? 'android');
    if ($p === 'ios') $platforms['ios']++;
    elseif ($p === 'android') $platforms['android']++;
    else $platforms['other']++;
}

// رسائل النجاح
$msgs = [
    'cat_added'    => '✅ تم إضافة التصنيف بنجاح',
    'cat_updated'  => '✅ تم تحديث التصنيف بنجاح',
    'cat_deleted'  => '🗑️ تم حذف التصنيف',
    'ch_added'     => '✅ تم إضافة القناة بنجاح',
    'ch_updated'   => '✅ تم تحديث القناة بنجاح',
    'ch_deleted'   => '🗑️ تم حذف القناة',
    'update_saved' => '✅ تم حفظ رسالة التحديث',
    'maint_saved'  => '✅ تم حفظ إعدادات الصيانة',
];
$flash = isset($_GET['msg']) && isset($msgs[$_GET['msg']]) ? $msgs[$_GET['msg']] : '';

// ─── دالة الحصول على اسم التصنيف ───
function getCatName($cats, $cat_id) {
    foreach ($cats as $c) { if ($c['id'] == $cat_id) return $c['name']; }
    return 'غير مصنف';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nova TV — لوحة التحكم</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
:root {
    --bg:       #070714;
    --surface:  #0f0f28;
    --surface2: #161638;
    --border:   #2a2a55;
    --accent:   #7c3aed;
    --cyan:     #06d6f5;
    --green:    #22d3a6;
    --red:      #f43f5e;
    --yellow:   #fbbf24;
    --text:     #e2e2f0;
    --muted:    #7a7a9a;
}
* { box-sizing: border-box; }
body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
}

/* ── Sidebar ── */
.sidebar {
    width: 240px; min-height: 100vh;
    background: var(--surface);
    border-left: 1px solid var(--border);
    position: fixed; top: 0; right: 0;
    display: flex; flex-direction: column;
    z-index: 100;
    transition: transform .3s;
}
.sidebar-brand {
    padding: 24px 20px 16px;
    border-bottom: 1px solid var(--border);
}
.sidebar-brand h2 { font-size: 1.3rem; margin: 0; color: var(--cyan); }
.sidebar-brand small { color: var(--muted); font-size: .75rem; }
.nav-section { padding: 12px 16px 4px; font-size: .7rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; }
.nav-item { padding: 0; }
.nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 20px; color: var(--muted);
    border-radius: 0; transition: all .2s;
    font-size: .9rem; cursor: pointer; border: none; background: none; width: 100%; text-align: right;
}
.nav-link:hover, .nav-link.active {
    color: var(--text); background: var(--surface2);
    border-right: 3px solid var(--accent);
}
.nav-link i { font-size: 1rem; }
.sidebar-footer { margin-top: auto; padding: 16px; border-top: 1px solid var(--border); }

/* ── Main ── */
.main-content {
    margin-right: 240px;
    padding: 28px 32px;
    min-height: 100vh;
}
.top-bar {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 28px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}
.top-bar h1 { font-size: 1.5rem; margin: 0; }

/* ── Cards ── */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    margin-bottom: 20px;
}
.card-header-custom {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
    font-weight: 600; font-size: 1rem;
}
.card-header-custom .icon-circle {
    width: 34px; height: 34px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem;
}
.card-body-custom { padding: 20px; }

/* ── Forms ── */
.form-control, .form-select {
    background: var(--surface2) !important;
    border: 1px solid var(--border) !important;
    color: var(--text) !important;
    border-radius: 8px;
}
.form-control:focus, .form-select:focus {
    border-color: var(--accent) !important;
    box-shadow: 0 0 0 3px rgba(124,58,237,.2) !important;
}
.form-label { font-size: .85rem; color: var(--muted); margin-bottom: 4px; }
.input-group-text { background: var(--border); border-color: var(--border); color: var(--muted); }
.advanced-section {
    background: rgba(255,255,255,.03);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 14px;
    margin: 12px 0;
}
.advanced-section summary { color: var(--cyan); cursor: pointer; font-size: .85rem; }

/* ── Buttons ── */
.btn-accent   { background: var(--accent); color: #fff; border: none; }
.btn-accent:hover { background: #6d28d9; color: #fff; }
.btn-cyan     { background: var(--cyan); color: #000; border: none; }
.btn-cyan:hover { background: #04bcd4; color: #000; }
.btn-danger   { background: var(--red); border: none; }
.btn-warning-custom { background: var(--yellow); color: #000; border: none; }
.btn-warning-custom:hover { background: #f59e0b; }
.btn-green    { background: var(--green); color: #000; border: none; }
.btn-green:hover { background: #16a38a; }

/* ── Table ── */
.table-dark-custom { color: var(--text) !important; }
.table-dark-custom th { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; border-color: var(--border) !important; font-weight: 500; }
.table-dark-custom td { border-color: var(--border) !important; vertical-align: middle; font-size: .88rem; }
.table-dark-custom tr:hover td { background: var(--surface2); }
.ch-logo { width: 38px; height: 38px; border-radius: 8px; object-fit: cover; background: var(--surface2); }
.badge-cat { background: rgba(6,214,245,.12); color: var(--cyan); border: 1px solid rgba(6,214,245,.25); padding: 3px 8px; border-radius: 6px; font-size: .75rem; }
.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-left: 6px; }
.status-on  { background: var(--green); box-shadow: 0 0 6px var(--green); }
.status-off { background: var(--muted); }

/* ── Tab Panels ── */
.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* ── Toggle Switch ── */
.toggle-wrap { display: flex; align-items: center; gap: 10px; }
.form-switch .form-check-input { width: 2.5em; height: 1.3em; background-color: var(--border); border-color: var(--border); cursor: pointer; }
.form-switch .form-check-input:checked { background-color: var(--green); border-color: var(--green); }

/* ── Maintenance Banner Preview ── */
.maint-preview {
    background: linear-gradient(135deg, #1a1a3a, #0f0f28);
    border: 2px dashed var(--border);
    border-radius: 12px; padding: 28px; text-align: center; margin-top: 16px;
}
.maint-preview h3 { color: var(--yellow); }
.maint-preview p  { color: var(--muted); }

/* ── Flash ── */
.flash { background: rgba(34,211,166,.12); border: 1px solid var(--green); color: var(--green); border-radius: 10px; padding: 12px 18px; margin-bottom: 20px; }

/* ── Stats Bar ── */
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 22px;
    display: flex; align-items: center; gap: 14px;
}
.stat-icon { font-size: 1.8rem; }
.stat-num  { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.stat-lbl  { font-size: .78rem; color: var(--muted); }

@media (max-width: 768px) {
    .sidebar { transform: translateX(100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-right: 0; padding: 16px; }
}

/* ── Servers Styling ── */
.server-card {
    background: rgba(255, 255, 255, 0.02) !important;
    border: 1px solid var(--border) !important;
    border-radius: 10px;
    padding: 16px;
    margin-top: 12px;
    margin-bottom: 12px;
    position: relative;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: border-color 0.2s, background-color 0.2s;
}
.server-card:hover {
    border-color: rgba(6, 214, 245, 0.4) !important;
    background: rgba(255, 255, 255, 0.03) !important;
}
.server-title {
    font-size: 0.9rem;
    color: var(--cyan);
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.server-title i {
    font-size: 1rem;
}
.remove-server-btn {
    position: absolute;
    top: 14px;
    left: 14px;
    color: var(--red) !important;
    background: rgba(244, 63, 94, 0.1) !important;
    border: 1px solid rgba(244, 63, 94, 0.2) !important;
    border-radius: 6px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}
.remove-server-btn:hover {
    background: var(--red) !important;
    color: #fff !important;
}
.btn-outline-cyan {
    color: var(--cyan) !important;
    border: 1px solid var(--cyan) !important;
    background: transparent !important;
    transition: all 0.2s;
}
.btn-outline-cyan:hover {
    background: var(--cyan) !important;
    color: #000 !important;
}
</style>
</head>
<body>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h2><i class="bi bi-tv-fill me-2"></i>Nova TV</h2>
        <small>لوحة التحكم الرئيسية</small>
    </div>

    <div class="nav-section">المحتوى</div>
    <div class="nav-item">
        <button class="nav-link active" onclick="showTab('tab-channels')">
            <i class="bi bi-collection-play"></i> القنوات والأقسام
        </button>
    </div>

    <div class="nav-section">التطبيق</div>
    <div class="nav-item">
        <button class="nav-link" onclick="showTab('tab-update')">
            <i class="bi bi-cloud-arrow-up"></i> رسالة التحديث
        </button>
    </div>
    <div class="nav-item">
        <button class="nav-link" onclick="showTab('tab-maintenance')">
            <i class="bi bi-tools"></i> وضع الصيانة
        </button>
    </div>

    <div class="nav-section">المستخدمون</div>
    <div class="nav-item">
        <button class="nav-link" onclick="showTab('tab-users')">
            <i class="bi bi-people"></i> إحصائيات المستخدمين
        </button>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-sm btn-outline-danger w-100">
            <i class="bi bi-box-arrow-left me-1"></i> تسجيل الخروج
        </a>
    </div>
</aside>

<!-- ═══════════════ MAIN ═══════════════ -->
<main class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <h1 id="page-title"><i class="bi bi-collection-play me-2" style="color:var(--cyan)"></i>القنوات والأقسام</h1>
        <div class="d-flex gap-2 align-items-center">
            <?php if($maint_cfg['enabled'] ?? false): ?>
                <span class="badge" style="background:rgba(251,191,36,.15);color:var(--yellow);border:1px solid rgba(251,191,36,.3)">
                    <span class="status-dot status-on" style="background:var(--yellow)"></span> وضع الصيانة نشط
                </span>
            <?php endif; ?>
            <?php if($update_cfg['enabled'] ?? false): ?>
                <span class="badge" style="background:rgba(34,211,166,.12);color:var(--green);border:1px solid rgba(34,211,166,.25)">
                    <span class="status-dot status-on"></span> رسالة التحديث نشطة
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if($flash): ?><div class="flash"><?= $flash ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card">
                <span class="stat-icon" style="color:var(--cyan)">📺</span>
                <div>
                    <div class="stat-num"><?= count($chs) ?></div>
                    <div class="stat-lbl">إجمالي القنوات</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <span class="stat-icon" style="color:var(--accent)">🗂️</span>
                <div>
                    <div class="stat-num"><?= count($cats) ?></div>
                    <div class="stat-lbl">إجمالي الأقسام</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <span class="stat-icon" style="color:var(--green)">⚙️</span>
                <div>
                    <div class="stat-num" style="font-size:1rem;padding-top:4px">
                        <?= ($maint_cfg['enabled'] ?? false) ? '<span style="color:var(--yellow)">صيانة</span>' : '<span style="color:var(--green)">مشغّل</span>' ?>
                    </div>
                    <div class="stat-lbl">حالة التطبيق</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════ TAB: CHANNELS ════════════════ -->
    <div id="tab-channels" class="tab-panel active">
        <div class="row g-4">

            <!-- ── Add / Edit Category ── -->
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(124,58,237,.2)"><i class="bi bi-folder-plus" style="color:var(--accent)"></i></div>
                        <?= $edit_cat ? 'تعديل التصنيف' : 'إضافة تصنيف جديد' ?>
                        <?php if($edit_cat): ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:.75rem">+ جديد</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body-custom">
                        <form method="POST">
                            <?php if($edit_cat): ?>
                                <input type="hidden" name="edit_id" value="<?= $edit_cat['id'] ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">اسم التصنيف</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($edit_cat['name'] ?? '') ?>"
                                    placeholder="مثال: قنوات رياضية" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">رابط الصورة (اختياري)</label>
                                <input type="text" name="image" class="form-control"
                                    value="<?= htmlspecialchars($edit_cat['image'] ?? '') ?>"
                                    placeholder="https://...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ترتيب العرض (رقم أصغر يظهر أولاً)</label>
                                <input type="number" name="sort_order" class="form-control"
                                    value="<?= htmlspecialchars($edit_cat['sort_order'] ?? '0') ?>"
                                    placeholder="مثال: 1" min="0">
                            </div>
                            <?php if($edit_cat): ?>
                                <button name="update_cat" class="btn btn-warning-custom w-100">
                                    <i class="bi bi-pencil-square me-1"></i> حفظ التعديلات
                                </button>
                            <?php else: ?>
                                <button name="save_cat" class="btn btn-accent w-100">
                                    <i class="bi bi-plus-circle me-1"></i> إضافة التصنيف
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(6,214,245,.15)"><i class="bi bi-grid" style="color:var(--cyan)"></i></div>
                        الأقسام الحالية
                    </div>
                    <div class="card-body-custom p-2">
                        <?php if(empty($cats)): ?>
                            <p class="text-center" style="color:var(--muted);padding:20px">لا توجد أقسام بعد</p>
                        <?php else: ?>
                        <table class="table table-dark-custom mb-0">
                            <thead><tr><th>الترتيب</th><th>الاسم</th><th>القنوات</th><th>إجراءات</th></tr></thead>
                            <tbody>
                            <?php foreach($cats as $cat):
                                $count = count(array_filter($chs, fn($c)=>$c['cat_id']==$cat['id']));
                            ?>
                            <tr>
                                <td>
                                    <span class="badge" style="background-color: var(--accent); color: #fff;">
                                        <?= htmlspecialchars($cat['sort_order'] ?? '0') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if(!empty($cat['image'])): ?>
                                        <img src="<?= htmlspecialchars($cat['image']) ?>" class="ch-logo me-2" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </td>
                                <td><span class="badge-cat"><?= $count ?></span></td>
                                <td>
                                    <a href="?op=edit_cat&id=<?= $cat['id'] ?>#form-cat" class="btn btn-sm" style="background:rgba(251,191,36,.15);color:var(--yellow);border:1px solid rgba(251,191,36,.3)">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?op=del_cat&id=<?= $cat['id'] ?>" class="btn btn-sm btn-danger ms-1"
                                       onclick="return confirm('حذف التصنيف نهائياً؟')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Add / Edit Channel ── -->
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(34,211,166,.15)"><i class="bi bi-plus-square" style="color:var(--green)"></i></div>
                        <?= $edit_ch ? 'تعديل القناة: '.htmlspecialchars($edit_ch['name']) : 'إضافة قناة جديدة' ?>
                        <?php if($edit_ch): ?>
                            <a href="index.php" class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:.75rem">+ جديدة</a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body-custom">
                        <form method="POST">
                            <?php if($edit_ch): ?>
                                <input type="hidden" name="edit_id" value="<?= $edit_ch['id'] ?>">
                            <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">التصنيف</label>
                                    <select name="cat_id" class="form-select">
                                        <?php foreach($cats as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"
                                                <?= (($edit_ch['cat_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">اسم القناة</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?= htmlspecialchars($edit_ch['name'] ?? '') ?>"
                                        placeholder="مثال: beIN Sports 1" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">رابط الشعار</label>
                                    <input type="text" name="image" class="form-control"
                                        value="<?= htmlspecialchars($edit_ch['image'] ?? '') ?>"
                                        placeholder="https://...">
                                </div>
                            </div>

                            <!-- Servers List Section -->
                            <div class="mt-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-bold text-light mb-0" style="font-size: 0.95rem;">
                                        <i class="bi bi-hdd-stack me-1 text-cyan"></i> سيرفرات البث للقناة
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-cyan" id="add-server-btn" style="font-size:0.8rem;">
                                        <i class="bi bi-plus-circle me-1"></i> إضافة سيرفر جديد
                                    </button>
                                </div>
                                <div id="servers-container">
                                    <!-- Dynamic Server Cards will be loaded here -->
                                </div>
                            </div>

                            <?php if($edit_ch): ?>
                                <button name="update_ch" class="btn btn-warning-custom w-100 mt-3">
                                    <i class="bi bi-pencil-square me-1"></i> حفظ تعديلات القناة
                                </button>
                            <?php else: ?>
                                <button name="save_ch" class="btn btn-green w-100 mt-3">
                                    <i class="bi bi-plus-circle me-1"></i> إضافة القناة
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Channels List -->
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(34,211,166,.15)"><i class="bi bi-collection-play" style="color:var(--green)"></i></div>
                        قائمة القنوات
                        <span class="badge ms-auto" style="background:var(--surface2);color:var(--muted)"><?= count($chs) ?> قناة</span>
                    </div>
                    <div class="card-body-custom p-0">
                        <?php if(empty($chs)): ?>
                            <p class="text-center" style="color:var(--muted);padding:28px">لا توجد قنوات بعد</p>
                        <?php else: ?>
                        <div style="max-height:420px;overflow-y:auto">
                        <table class="table table-dark-custom mb-0">
                            <thead style="position:sticky;top:0;background:var(--surface)">
                                <tr>
                                    <th style="width:46px"></th>
                                    <th>الاسم</th>
                                    <th>التصنيف</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($chs as $ch): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($ch['image'] ?? '') ?>"
                                         class="ch-logo"
                                         onerror="this.src='../logo.png'">
                                </td>
                                <td>
                                    <span class="fw-500"><?= htmlspecialchars($ch['name']) ?></span>
                                    <?php if(!empty($ch['drmkey'])): ?>
                                        <span class="badge ms-1" style="background:rgba(244,63,94,.15);color:var(--red);border:1px solid rgba(244,63,94,.25);font-size:.65rem">DRM</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge-cat"><?= htmlspecialchars(getCatName($cats, $ch['cat_id'])) ?></span></td>
                                <td>
                                    <a href="../player.php?id=<?= $ch['id'] ?>" target="_blank" class="btn btn-sm me-1"
                                       style="background:rgba(34,211,166,.15);color:var(--green);border:1px solid rgba(34,211,166,.3)" title="تشغيل القناة">
                                        <i class="bi bi-play-fill"></i>
                                    </a>
                                    <a href="?op=edit_ch&id=<?= $ch['id'] ?>" class="btn btn-sm"
                                       style="background:rgba(251,191,36,.15);color:var(--yellow);border:1px solid rgba(251,191,36,.3)">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?op=del_ch&id=<?= $ch['id'] ?>" class="btn btn-sm btn-danger ms-1"
                                       onclick="return confirm('حذف القناة نهائياً؟')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- end tab-channels -->


    <!-- ════════════════ TAB: UPDATE MESSAGE ════════════════ -->
    <div id="tab-update" class="tab-panel">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(34,211,166,.15)"><i class="bi bi-cloud-arrow-up" style="color:var(--green)"></i></div>
                        إعدادات رسالة التحديث
                    </div>
                    <div class="card-body-custom">
                        <form method="POST">
                            <!-- Enable Toggle -->
                            <div class="toggle-wrap mb-4 p-3" style="background:var(--surface2);border-radius:10px;border:1px solid var(--border)">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="msg_enabled" id="msgToggle"
                                           <?= ($update_cfg['enabled'] ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="msgToggle">
                                        <strong>تفعيل رسالة التحديث</strong>
                                        <small class="d-block" style="color:var(--muted)">ستظهر للمستخدمين عند فتح التطبيق</small>
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">عنوان الرسالة</label>
                                    <input type="text" name="msg_title" class="form-control"
                                        value="<?= htmlspecialchars($update_cfg['title'] ?? 'يتوفر تحديث جديد!') ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">إصدار التطبيق الجديد</label>
                                    <div class="input-group">
                                        <span class="input-group-text">v</span>
                                        <input type="text" name="app_version" class="form-control"
                                            value="<?= htmlspecialchars($update_cfg['version'] ?? '1.0.0') ?>"
                                            placeholder="2.0.0">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">نص الرسالة</label>
                                    <textarea name="msg_body" class="form-control" rows="3"
                                        placeholder="تم إضافة مميزات جديدة وإصلاح مشاكل..."><?= htmlspecialchars($update_cfg['message'] ?? '') ?></textarea>
                                </div>
                                <div class="col-sm-8">
                                    <label class="form-label">رابط التحديث (متجر / APK)</label>
                                    <input type="text" name="msg_url" class="form-control"
                                        value="<?= htmlspecialchars($update_cfg['url'] ?? '') ?>"
                                        placeholder="https://play.google.com/...">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label">نص زر التحديث</label>
                                    <input type="text" name="msg_btn" class="form-control"
                                        value="<?= htmlspecialchars($update_cfg['btn_text'] ?? 'تحديث الآن') ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="force_update" id="forceToggle"
                                               <?= ($update_cfg['force'] ?? false) ? 'checked' : '' ?>>
                                        <label class="form-check-label ms-2" for="forceToggle">
                                            <strong>تحديث إجباري</strong>
                                            <small class="d-block" style="color:var(--muted)">لا يمكن للمستخدم تجاهل الرسالة</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="maint-preview mt-4">
                                <div style="font-size:2.5rem">🚀</div>
                                <h3 id="prev-title"><?= htmlspecialchars($update_cfg['title'] ?? 'يتوفر تحديث جديد!') ?></h3>
                                <p id="prev-body"><?= htmlspecialchars($update_cfg['message'] ?? 'نص رسالة التحديث يظهر هنا...') ?></p>
                                <button class="btn btn-green" type="button" id="prev-btn">
                                    <?= htmlspecialchars($update_cfg['btn_text'] ?? 'تحديث الآن') ?>
                                </button>
                            </div>

                            <button name="save_update_msg" class="btn btn-green w-100 mt-4" style="height:46px">
                                <i class="bi bi-cloud-check me-2"></i> حفظ إعدادات التحديث
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end tab-update -->


    <!-- ════════════════ TAB: MAINTENANCE ════════════════ -->
    <div id="tab-maintenance" class="tab-panel">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(251,191,36,.15)"><i class="bi bi-tools" style="color:var(--yellow)"></i></div>
                        إعدادات وضع الصيانة
                    </div>
                    <div class="card-body-custom">

                        <!-- Warning -->
                        <?php if($maint_cfg['enabled'] ?? false): ?>
                        <div class="alert mb-4" style="background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:var(--red);border-radius:10px">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>تحذير:</strong> التطبيق حالياً في وضع الصيانة. المستخدمون لا يستطيعون استخدام التطبيق.
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Enable Toggle -->
                            <div class="toggle-wrap mb-4 p-3" style="background:var(--surface2);border-radius:10px;border:1px solid <?= ($maint_cfg['enabled'] ?? false) ? 'rgba(244,63,94,.4)' : 'var(--border)' ?>">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maint_enabled" id="maintToggle"
                                           <?= ($maint_cfg['enabled'] ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label ms-2" for="maintToggle">
                                        <strong>تفعيل وضع الصيانة</strong>
                                        <small class="d-block" style="color:var(--muted)">إيقاف التطبيق مؤقتاً للمستخدمين</small>
                                    </label>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">عنوان صفحة الصيانة</label>
                                    <input type="text" name="maint_title" class="form-control"
                                        value="<?= htmlspecialchars($maint_cfg['title'] ?? 'نحن في وضع الصيانة') ?>"
                                        placeholder="التطبيق تحت الصيانة">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">رسالة الصيانة</label>
                                    <textarea name="maint_body" class="form-control" rows="3"
                                        placeholder="نعمل على تحسين التطبيق وسنعود قريباً..."><?= htmlspecialchars($maint_cfg['message'] ?? '') ?></textarea>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">رابط صورة الصيانة (اختياري)</label>
                                    <input type="text" name="maint_image" class="form-control"
                                        value="<?= htmlspecialchars($maint_cfg['image'] ?? '') ?>"
                                        placeholder="https://...">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">معلومات التواصل (اختياري)</label>
                                    <input type="text" name="maint_contact" class="form-control"
                                        value="<?= htmlspecialchars($maint_cfg['contact'] ?? '') ?>"
                                        placeholder="@username أو رقم هاتف">
                                </div>
                            </div>

                            <!-- Preview -->
                            <div class="maint-preview mt-4">
                                <div style="font-size:3rem">🔧</div>
                                <h3 id="prev-maint-title"><?= htmlspecialchars($maint_cfg['title'] ?? 'نحن في وضع الصيانة') ?></h3>
                                <p id="prev-maint-body" style="max-width:400px;margin:0 auto">
                                    <?= htmlspecialchars($maint_cfg['message'] ?? 'نعمل على تحسين التطبيق وسنعود قريباً...') ?>
                                </p>
                                <?php if(!empty($maint_cfg['contact'])): ?>
                                    <small style="color:var(--muted)">للتواصل: <?= htmlspecialchars($maint_cfg['contact']) ?></small>
                                <?php endif; ?>
                            </div>

                            <button name="save_maintenance" class="btn mt-4 w-100"
                                style="background:var(--yellow);color:#000;height:46px;font-weight:600">
                                <i class="bi bi-tools me-2"></i> حفظ إعدادات الصيانة
                            </button>
                        </form>
                    </div>
                </div>

                <!-- API Endpoint Info -->
                <div class="card">
                    <div class="card-header-custom">
                        <div class="icon-circle" style="background:rgba(6,214,245,.1)"><i class="bi bi-code-slash" style="color:var(--cyan)"></i></div>
                        نقاط API للتطبيق
                    </div>
                    <div class="card-body-custom">
                        <p style="color:var(--muted);font-size:.85rem">استخدم هذه الروابط في تطبيقك للتحقق من الحالة:</p>
                        <div class="p-3 rounded mb-2" style="background:#0a0a1a;border:1px solid var(--border);font-family:monospace;font-size:.8rem">
                            <span style="color:var(--cyan)">GET</span> <span style="color:var(--green)">/api/status.php</span>
                            <small class="d-block" style="color:var(--muted)">← يرجع حالة الصيانة ورسالة التحديث بصيغة JSON</small>
                        </div>
                        <div class="p-3 rounded mb-2" style="background:#0a0a1a;border:1px solid var(--border);font-family:monospace;font-size:.8rem">
                            <span style="color:var(--cyan)">GET</span> <span style="color:var(--green)">/api/channels.php</span>
                            <small class="d-block" style="color:var(--muted)">← يرجع قائمة الأقسام والقنوات</small>
                        </div>
                        <div class="p-3 rounded" style="background:#0a0a1a;border:1px solid var(--border);font-family:monospace;font-size:.8rem">
                            <span style="color:var(--cyan)">POST</span> <span style="color:var(--green)">/api/user.php</span>
                            <small class="d-block" style="color:var(--muted)">← تسجيل / تحديث بيانات المستخدم (device_id, platform, app_version)</small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div><!-- end tab-maintenance -->


    <!-- ════════════════ TAB: USERS ════════════════ -->
    <div id="tab-users" class="tab-panel">
        <div class="row g-3 mb-4">
            <div class="col-sm-3">
                <div class="stat-card">
                    <span class="stat-icon" style="color:var(--cyan)">👥</span>
                    <div>
                        <div class="stat-num"><?= $total_users ?></div>
                        <div class="stat-lbl">إجمالي المستخدمين</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-card">
                    <span class="stat-icon" style="color:var(--green)">📅</span>
                    <div>
                        <div class="stat-num"><?= $active_today ?></div>
                        <div class="stat-lbl">نشط اليوم</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-card">
                    <span class="stat-icon" style="color:var(--accent)">📊</span>
                    <div>
                        <div class="stat-num"><?= $active_week ?></div>
                        <div class="stat-lbl">نشط آخر 7 أيام</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="stat-card">
                    <span class="stat-icon" style="color:var(--yellow)">📱</span>
                    <div>
                        <div class="stat-num" style="font-size:0.9rem;">
                            <?= $platforms['android'] ?> | <?= $platforms['ios'] ?>
                        </div>
                        <div class="stat-lbl">أندرويد | iOS</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header-custom">
                <div class="icon-circle" style="background:rgba(6,214,245,.15)"><i class="bi bi-people-fill" style="color:var(--cyan)"></i></div>
                قائمة المستخدمين
                <span class="badge ms-auto" style="background:var(--surface2);color:var(--muted)"><?= $total_users ?> مستخدم</span>
            </div>
            <div class="card-body-custom p-0">
                <?php if (empty($users)): ?>
                    <p class="text-center" style="color:var(--muted);padding:28px">لا يوجد مستخدمون بعد</p>
                <?php else: ?>
                <div style="max-height:500px;overflow-y:auto">
                <table class="table table-dark-custom mb-0">
                    <thead style="position:sticky;top:0;background:var(--surface)">
                        <tr>
                            <th>الجهاز</th>
                            <th>النظام</th>
                            <th>الإصدار</th>
                            <th>أول ظهور</th>
                            <th>آخر ظهور</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($users) as $u): ?>
                    <tr>
                        <td><code style="color:var(--cyan); font-size:0.8rem;"><?= htmlspecialchars($u['device_id']) ?></code></td>
                        <td><span class="badge-cat"><?= htmlspecialchars($u['platform'] ?? 'غير معروف') ?></span></td>
                        <td><?= htmlspecialchars($u['app_ver'] ?? '') ?></td>
                        <td style="font-size:.8rem"><?= htmlspecialchars($u['first_seen'] ?? '') ?></td>
                        <td style="font-size:.8rem"><?= htmlspecialchars($u['last_seen'] ?? '') ?></td>
                        <td style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($u['ip'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- end tab-users -->

</main>

<script>
// ─── Tab Switching ───
const tabMap = {
    'tab-channels':    { title: '<i class="bi bi-collection-play me-2" style="color:var(--cyan)"></i>القنوات والأقسام' },
    'tab-update':      { title: '<i class="bi bi-cloud-arrow-up me-2" style="color:var(--green)"></i>رسالة التحديث' },
    'tab-maintenance': { title: '<i class="bi bi-tools me-2" style="color:var(--yellow)"></i>وضع الصيانة' },
    'tab-users':       { title: '<i class="bi bi-people me-2" style="color:var(--cyan)"></i>إحصائيات المستخدمين' }
};

function showTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('.nav-link').forEach(l => {
        if (l.getAttribute('onclick') && l.getAttribute('onclick').includes(id)) l.classList.add('active');
    });
    document.getElementById('page-title').innerHTML = tabMap[id].title;
}

// ─── Live Preview for Update Message ───
function livePreview(inputSel, previewSel) {
    const el = document.querySelector(inputSel);
    if (!el) return;
    el.addEventListener('input', () => {
        const prev = document.querySelector(previewSel);
        if (prev) prev.textContent = el.value || el.placeholder;
    });
}
livePreview('[name="msg_title"]', '#prev-title');
livePreview('[name="msg_body"]', '#prev-body');
livePreview('[name="msg_btn"]', '#prev-btn');
livePreview('[name="maint_title"]', '#prev-maint-title');
livePreview('[name="maint_body"]', '#prev-maint-body');

// ─── Auto-open edit tab if editing ───
<?php if($edit_cat || $edit_ch): ?>
showTab('tab-channels');
<?php endif; ?>

// ─── Restore tab from hash ───
const savedTab = localStorage.getItem('novaAdminTab');
if (savedTab && document.getElementById(savedTab)) showTab(savedTab);
document.querySelectorAll('.nav-link').forEach(l => {
    l.addEventListener('click', () => {
        const m = l.getAttribute('onclick').match(/'([^']+)'/);
        if (m) localStorage.setItem('novaAdminTab', m[1]);
    });
});

// ─── Dynamic Server Management ───
const initialServers = <?php echo json_encode($edit_servers); ?>;
let serverCount = 0;

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function addServer(data = {}) {
    serverCount++;
    const container = document.getElementById('servers-container');
    if (!container) return;
    
    const card = document.createElement('div');
    card.className = 'server-card';
    card.id = `server-card-${serverCount}`;
    
    const name = data.name || `سيرفر ${serverCount}`;
    const url = data.url || '';
    const useragent = data.useragent || '';
    const referer = data.referer || '';
    const origin = data.origin || '';
    const cookie = data.cookie || '';
    const drmkey = data.drmkey || '';

    card.innerHTML = `
        <button type="button" class="remove-server-btn" onclick="removeServer(${serverCount})">
            <i class="bi bi-trash"></i> حذف
        </button>
        <div class="server-title">
            <i class="bi bi-hdd-network"></i>
            <span>السيرفر #${serverCount}</span>
        </div>
        <div class="row g-3">
            <div class="col-sm-4">
                <label class="form-label" style="font-size:0.8rem;">اسم السيرفر</label>
                <input type="text" name="server_name[]" class="form-control form-control-sm" 
                    value="${escapeHtml(name)}" placeholder="مثال: سيرفر 1" required>
            </div>
            <div class="col-sm-8">
                <label class="form-label" style="font-size:0.8rem;">رابط البث</label>
                <input type="text" name="server_url[]" class="form-control form-control-sm" 
                    value="${escapeHtml(url)}" placeholder="https://..." required>
            </div>
        </div>
        
        <details class="advanced-section mt-2 mb-0">
            <summary style="font-size:0.75rem; color:var(--cyan);"><i class="bi bi-sliders me-1"></i>إعدادات متقدمة للسيرفر</summary>
            <div class="row g-2 mt-1">
                <div class="col-sm-6">
                    <label class="form-label" style="font-size:0.75rem;margin-bottom:2px;">User-Agent</label>
                    <input type="text" name="server_useragent[]" class="form-control form-control-sm" value="${escapeHtml(useragent)}">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" style="font-size:0.75rem;margin-bottom:2px;">Referer</label>
                    <input type="text" name="server_referer[]" class="form-control form-control-sm" value="${escapeHtml(referer)}">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" style="font-size:0.75rem;margin-bottom:2px;">Origin</label>
                    <input type="text" name="server_origin[]" class="form-control form-control-sm" value="${escapeHtml(origin)}">
                </div>
                <div class="col-sm-6">
                    <label class="form-label" style="font-size:0.75rem;margin-bottom:2px;">Cookie</label>
                    <input type="text" name="server_cookie[]" class="form-control form-control-sm" value="${escapeHtml(cookie)}">
                </div>
                <div class="col-12">
                    <label class="form-label" style="font-size:0.75rem;margin-bottom:2px;">DRM Key (License URL)</label>
                    <input type="text" name="server_drmkey[]" class="form-control form-control-sm" value="${escapeHtml(drmkey)}" placeholder="https://license.server.com/...">
                </div>
            </div>
        </details>
    `;
    container.appendChild(card);
    updateServerTitles();
}

function removeServer(id) {
    const card = document.getElementById(`server-card-${id}`);
    if (card) {
        card.remove();
        updateServerTitles();
    }
}

function updateServerTitles() {
    const cards = document.querySelectorAll('#servers-container .server-card');
    cards.forEach((card, index) => {
        const titleSpan = card.querySelector('.server-title span');
        if (titleSpan) {
            titleSpan.innerText = `السيرفر #${index + 1}`;
        }
    });
}

// Load initial servers on page load
document.addEventListener('DOMContentLoaded', () => {
    if (Array.isArray(initialServers) && initialServers.length > 0) {
        initialServers.forEach(s => addServer(s));
    }
    
    const addBtn = document.getElementById('add-server-btn');
    if (addBtn) {
        addBtn.addEventListener('click', () => addServer());
    }
});
</script>
</body>
</html>