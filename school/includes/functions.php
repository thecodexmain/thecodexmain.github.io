<?php
function loadData($file) {
    $path = __DIR__ . '/../data/' . $file . '.json';
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    return json_decode($json, true) ?? [];
}

function saveData($file, $data) {
    $path = __DIR__ . '/../data/' . $file . '.json';
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function generateId($prefix = '') {
    return $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}

function getSettings() {
    $path = __DIR__ . '/../data/settings.json';
    $defaults = [
        "school_name" => "Amrit Public School",
        "tagline" => "Excellence in Education",
        "logo" => "",
        "email" => "school@amritpublic.edu",
        "phone" => "+91-9876543210",
        "address" => "123, Education Street, Knowledge City",
        "theme_color" => "#0d6efd",
        "modules" => [
            "students" => true, "teachers" => true, "classes" => true,
            "attendance" => true, "exams" => true, "fees" => true,
            "timetable" => true, "assignments" => true, "library" => true,
            "notices" => true, "messages" => true, "events" => true,
            "documents" => true, "reports" => true
        ]
    ];
    if (!file_exists($path)) {
        file_put_contents($path, json_encode($defaults, JSON_PRETTY_PRINT));
        return $defaults;
    }
    $data = json_decode(file_get_contents($path), true);
    return $data ?: $defaults;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getStudentById($id) {
    foreach (loadData('students') as $s) {
        if ($s['id'] === $id) return $s;
    }
    return null;
}

function getTeacherById($id) {
    foreach (loadData('teachers') as $t) {
        if ($t['id'] === $id) return $t;
    }
    return null;
}

function getUserByUsername($username) {
    foreach (loadData('users') as $u) {
        if ($u['username'] === $username) return $u;
    }
    return null;
}

function getClassLabel($class, $section) {
    return "Class $class - Section $section";
}

function isModuleEnabled($module) {
    $settings = getSettings();
    return isset($settings['modules'][$module]) ? (bool)$settings['modules'][$module] : true;
}

function formatDate($date) {
    if (empty($date)) return '-';
    return date('d M Y', strtotime($date));
}

function getGrade($marks, $maxMarks) {
    if ($maxMarks == 0) return 'N/A';
    $pct = ($marks / $maxMarks) * 100;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 35) return 'D';
    return 'F';
}

function paginate($array, $page, $perPage = 10) {
    $total = count($array);
    $offset = ($page - 1) * $perPage;
    return [
        'data'    => array_slice($array, $offset, $perPage),
        'total'   => $total,
        'pages'   => max(1, ceil($total / $perPage)),
        'current' => $page
    ];
}

function renderFlash() {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']);
    $msg  = htmlspecialchars($flash['message']);
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>{$msg}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
}

function getClassesForSelect() {
    $classes = loadData('classes');
    $opts = [];
    foreach ($classes as $c) {
        $opts[] = ['value' => $c['class'] . '|' . $c['section'], 'label' => 'Class ' . $c['class'] . ' - ' . $c['section']];
    }
    return $opts;
}
