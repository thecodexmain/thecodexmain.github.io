<?php
$role     = $_SESSION['role'] ?? '';
$settings = getSettings();
$modules  = $settings['modules'] ?? [];
$baseUrl  = getBaseUrl();
$self     = $_SERVER['PHP_SELF'];

function sidebarLink($url, $icon, $label, $self) {
    $active = (strpos($self, basename($url, '.php')) !== false) ? 'active' : '';
    echo "<li class='nav-item'><a class='nav-link text-white $active' href='$url'><i class='bi bi-$icon'></i> $label</a></li>\n";
}
?>
<div class="sidebar bg-dark text-white" style="min-width:220px;min-height:calc(100vh - 56px)">
    <nav class="pt-2">
        <ul class="nav flex-column">

            <?php if (in_array($role, ['super_admin', 'admin'])): ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'dashboard')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <?php if ($modules['students']  ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'student')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/students.php"><i class="bi bi-people"></i> Students</a></li><?php endif; ?>
            <?php if ($modules['teachers']  ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'teacher')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/teachers.php"><i class="bi bi-person-badge"></i> Teachers</a></li><?php endif; ?>
            <?php if ($modules['classes']   ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'classes')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/classes.php"><i class="bi bi-building"></i> Classes</a></li><?php endif; ?>
            <?php if ($modules['attendance']?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'attendance')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/attendance.php"><i class="bi bi-calendar-check"></i> Attendance</a></li><?php endif; ?>
            <?php if ($modules['exams']     ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'exams')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/exams.php"><i class="bi bi-journal-text"></i> Exams</a></li><?php endif; ?>
            <?php if ($modules['fees']      ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'fees')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/fees.php"><i class="bi bi-cash-stack"></i> Fees</a></li><?php endif; ?>
            <?php if ($modules['timetable'] ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'timetable')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/timetable.php"><i class="bi bi-clock"></i> Timetable</a></li><?php endif; ?>
            <?php if ($modules['assignments']??true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'assignments')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/assignments.php"><i class="bi bi-file-earmark-text"></i> Assignments</a></li><?php endif; ?>
            <?php if ($modules['library']   ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'library')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/library.php"><i class="bi bi-book"></i> Library</a></li><?php endif; ?>
            <?php if ($modules['notices']   ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'notices')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/notices.php"><i class="bi bi-megaphone"></i> Notices</a></li><?php endif; ?>
            <?php if ($modules['messages']  ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'messages')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/messages.php"><i class="bi bi-chat-dots"></i> Messages</a></li><?php endif; ?>
            <?php if ($modules['events']    ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'events')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/events.php"><i class="bi bi-calendar-event"></i> Events</a></li><?php endif; ?>
            <?php if ($modules['documents'] ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'documents')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/documents.php"><i class="bi bi-folder"></i> Documents</a></li><?php endif; ?>
            <?php if ($modules['reports']   ?? true): ?><li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'reports')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/reports.php"><i class="bi bi-bar-chart"></i> Reports</a></li><?php endif; ?>
            <li class="nav-item mt-2 border-top border-secondary pt-2">
                <a class="nav-link text-white <?php echo strpos($self,'settings')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/admin/settings.php"><i class="bi bi-gear"></i> Settings</a>
            </li>
            <?php endif; ?>

            <?php if ($role === 'teacher'): ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'dashboard')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/teacher/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'attendance')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/teacher/attendance.php"><i class="bi bi-calendar-check"></i> Take Attendance</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'assignments')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/teacher/assignments.php"><i class="bi bi-file-earmark-text"></i> Assignments</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'timetable')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/teacher/timetable.php"><i class="bi bi-clock"></i> My Timetable</a></li>
            <?php endif; ?>

            <?php if ($role === 'student'): ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'dashboard')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'attendance')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/attendance.php"><i class="bi bi-calendar-check"></i> My Attendance</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'results')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/results.php"><i class="bi bi-award"></i> My Results</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'fees')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/fees.php"><i class="bi bi-cash-stack"></i> My Fees</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'timetable')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/timetable.php"><i class="bi bi-clock"></i> Timetable</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'assignments')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/student/assignments.php"><i class="bi bi-file-earmark-text"></i> Assignments</a></li>
            <?php endif; ?>

            <?php if ($role === 'parent'): ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'dashboard')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/parent/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <?php endif; ?>

            <?php if ($role === 'accountant'): ?>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'dashboard')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/accountant/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white <?php echo strpos($self,'fees')!==false?'active':''; ?>" href="<?php echo $baseUrl; ?>/accountant/fees.php"><i class="bi bi-cash-stack"></i> Fee Management</a></li>
            <?php endif; ?>

        </ul>
    </nav>
</div>
