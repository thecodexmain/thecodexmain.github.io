<?php
/**
 * HTML Footer Template
 */
$settings = getSettings();
?>
    <!-- Main JS -->
    <script src="/panel/assets/js/main.js"></script>
    
    <!-- PWA Service Worker -->
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/panel/sw.js').catch(() => {});
    }
    </script>
</body>
</html>
