<?php
/**
 * Atlas AI Dashboard
 * Main interface with enhanced fluid waveform
 */

// Page configuration
$pageTitle = 'Dashboard';
$loadChatJS = true;

// Security check and user data
require_once '../includes/functions.php';
requireLogin();

// Get current user information
$user = getUserById($_SESSION['user_id']);
$userName = $user['full_name'];
$userEmail = $user['email'];
$firstName = explode(' ', $userName)[0];
$userInitial = strtoupper(substr($firstName, 0, 1));
?>

<?php require_once '../includes/header.php'; ?>

<style>
/* Adjust main content padding */
.main-content-adjusted {
    padding-bottom: 300px;
}

@media (max-width: 768px) {
    .main-content-adjusted {
        padding-bottom: 280px;
    }
}
</style>

<!-- Navigation Bar -->
<nav class="absolute top-0 left-0 p-6 z-20">
    <div class="flex items-center gap-4">
        <button onclick="toggleMenu()" class="w-10 h-10 rounded-lg bg-slate border border-whisper flex items-center justify-center hover:border-cyan-electric transition-colors focus:outline-none focus:ring-2 focus:ring-cyan-electric" aria-label="Toggle menu">
            <i class="fas fa-bars text-mist"></i>
        </button>
    </div>
</nav>

<!-- Sidebar Menu Panel -->
<div id="menuPanel" class="fixed top-0 left-0 h-full w-64 bg-abyss border-r border-whisper z-30 transform -translate-x-full transition-transform duration-300 shadow-xl">
    <div class="p-6 flex flex-col h-full">
        
        <!-- Menu Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="font-sans text-lg font-semibold text-frost">Menu</h3>
            <button onclick="toggleMenu()" class="text-mist hover:text-frost transition-colors focus:outline-none" aria-label="Close menu">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- User Profile Section -->
        <div class="mb-6 pb-6 border-b border-whisper">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-base to-violet-pulse flex items-center justify-center text-white font-bold text-lg shadow-lg">
                    <?php echo $userInitial; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-frost truncate" title="<?php echo htmlspecialchars($userName); ?>">
                        <?php echo htmlspecialchars($firstName); ?>
                    </p>
                    <p class="text-xs text-mist truncate" title="<?php echo htmlspecialchars($userEmail); ?>">
                        <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <nav class="space-y-2 flex-1">
            <a href="#" class="flex items-center w-full text-left px-4 py-3 rounded-lg bg-slate text-frost transition-colors">
                <i class="fas fa-home mr-3 w-5"></i>
                <span>Home</span>
            </a>
            <a href="#" class="flex items-center w-full text-left px-4 py-3 rounded-lg hover:bg-slate transition-colors text-mist hover:text-frost">
                <i class="fas fa-history mr-3 w-5"></i>
                <span>History</span>
            </a>
            <a href="#" class="flex items-center w-full text-left px-4 py-3 rounded-lg hover:bg-slate transition-colors text-mist hover:text-frost">
                <i class="fas fa-bookmark mr-3 w-5"></i>
                <span>Saved</span>
            </a>
            <a href="#" class="flex items-center w-full text-left px-4 py-3 rounded-lg hover:bg-slate transition-colors text-mist hover:text-frost">
                <i class="fas fa-cog mr-3 w-5"></i>
                <span>Settings</span>
            </a>
        </nav>
        
        <!-- Logout Button -->
        <div class="pt-4 border-t border-whisper">
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="flex items-center w-full text-left px-4 py-3 rounded-lg hover:bg-red-900/20 transition-colors text-mist hover:text-red-400">
                <i class="fas fa-sign-out-alt mr-3 w-5"></i>
                <span>Logout</span>
            </a>
        </div>
        
    </div>
</div>

<!-- Status Indicator -->
<div class="absolute top-6 right-6 z-20">
    <div class="flex items-center gap-2 px-3 py-2 rounded-full bg-slate/80 backdrop-blur-md border border-whisper shadow-lg">
        <div id="statusDot" class="w-2 h-2 rounded-full status-idle"></div>
        <span id="statusText" class="text-xs font-sans text-mist font-medium">Idle</span>
    </div>
</div>

  <script>
    // expose name to orb.js
    window.ATLAS_USER_NAME = <?= json_encode($firstName) ?>;
    // optional, if tts.php is not in same folder:
    // window.ATLAS_TTS_URL = "http://localhost:8080/atlas-ai/config/tts.php";
  </script>

  <script type="module" src="../orb.js"></script>

 <!-- Compact Chat Panel -->
<section class="glass-chat fixed bottom-0 left-0 right-0 p-4 md:p-6 chat-panel-compact">
    <div class="max-w-4xl mx-auto h-full flex flex-col">
        
        <!-- Messages Container -->
        <div id="messagesContainer" class="flex-1 overflow-y-auto mb-3 space-y-3 custom-scrollbar pr-2">
            <!-- Messages will be dynamically appended here -->
        </div>

        <!-- Input Area -->
        <form onsubmit="sendMessage(); return false;" class="flex gap-2 md:gap-3">
            <input
                id="messageInput"
                type="text"
                placeholder="Ask Atlas anything..."
                autocomplete="off"
                class="flex-1 px-4 py-2.5 rounded-lg bg-abyss/80 backdrop-blur-sm border border-whisper text-frost placeholder-whisper focus:outline-none focus:border-cyan-electric focus:shadow-[0_0_0_3px_rgba(0,229,255,0.15)] transition-all text-sm md:text-base"
            />
            <button type="submit" class="px-4 md:px-6 py-2.5 rounded-lg btn-primary text-white font-sans font-medium text-sm whitespace-nowrap">
                <i class="fas fa-paper-plane mr-0 md:mr-2"></i>
                <span class="hidden md:inline">Send</span>
            </button>
        </form>

    </div>
</section>

<!-- Pass User Data to JavaScript -->
<script>
    // Make user data available to JavaScript
    window.userData = {
        id: <?php echo json_encode($_SESSION['user_id']); ?>,
        name: <?php echo json_encode($firstName); ?>,
        fullName: <?php echo json_encode($userName); ?>,
        email: <?php echo json_encode($userEmail); ?>
    };
</script>

<?php require_once '../includes/footer.php'; ?>