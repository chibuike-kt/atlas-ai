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
/* Enhanced Waveform Styles */
.waveform-container-enhanced {
    position: relative;
    width: 500px;
    height: 500px;
}

@media (max-width: 768px) {
    .waveform-container-enhanced {
        width: 320px;
        height: 320px;
    }
}

/* Fluid Blob Animation */
@keyframes morph {
    0%, 100% {
        d: path("M150,50 Q100,25 80,80 Q60,135 80,180 Q100,225 150,200 Q200,175 220,120 Q240,65 200,50 Q175,35 150,50 Z");
    }
    25% {
        d: path("M150,45 Q105,30 75,75 Q45,120 70,175 Q95,230 150,210 Q205,190 235,135 Q265,80 220,55 Q185,30 150,45 Z");
    }
    50% {
        d: path("M150,40 Q90,20 65,85 Q40,150 75,200 Q110,250 165,220 Q220,190 245,125 Q270,60 210,45 Q180,30 150,40 Z");
    }
    75% {
        d: path("M150,48 Q98,28 72,78 Q46,128 78,188 Q110,248 158,218 Q206,188 238,128 Q270,68 218,48 Q184,28 150,48 Z");
    }
}

@keyframes rotate-glow {
    0% {
        filter: hue-rotate(0deg) drop-shadow(0 0 30px rgba(0, 229, 255, 0.6));
    }
    33% {
        filter: hue-rotate(120deg) drop-shadow(0 0 35px rgba(168, 85, 247, 0.7));
    }
    66% {
        filter: hue-rotate(240deg) drop-shadow(0 0 40px rgba(45, 27, 105, 0.6));
    }
    100% {
        filter: hue-rotate(360deg) drop-shadow(0 0 30px rgba(0, 229, 255, 0.6));
    }
}

@keyframes float-gentle {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    33% {
        transform: translateY(-10px) rotate(2deg);
    }
    66% {
        transform: translateY(5px) rotate(-2deg);
    }
}

.fluid-blob {
    animation: morph 8s ease-in-out infinite, 
               rotate-glow 10s linear infinite,
               float-gentle 6s ease-in-out infinite;
}

/* Compact Chat Panel */
.chat-panel-compact {
    height: 280px !important;
    max-height: 280px;
}

@media (max-width: 768px) {
    .chat-panel-compact {
        height: 260px !important;
        max-height: 260px;
    }
}

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

<!-- Main Content Area -->
<main class="h-screen flex flex-col items-center justify-center main-content-adjusted px-4">
    
    <!-- Ultra-Complex Futuristic Holographic Orb -->
    <div class="waveform-container-enhanced mb-8">
        <svg viewBox="0 0 300 300" class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Advanced Gradients -->
                <radialGradient id="core-gradient">
                    <stop offset="0%" style="stop-color:#00E5FF;stop-opacity:1"/>
                    <stop offset="50%" style="stop-color:#A855F7;stop-opacity:0.8"/>
                    <stop offset="100%" style="stop-color:#2D1B69;stop-opacity:0.3"/>
                </radialGradient>

                <linearGradient id="tech-gradient-1">
                    <stop offset="0%" style="stop-color:#00E5FF;stop-opacity:1">
                        <animate attributeName="stop-color" values="#00E5FF;#A855F7;#2D1B69;#00E5FF" dur="6s" repeatCount="indefinite"/>
                    </stop>
                    <stop offset="100%" style="stop-color:#A855F7;stop-opacity:1">
                        <animate attributeName="stop-color" values="#A855F7;#2D1B69;#00E5FF;#A855F7" dur="6s" repeatCount="indefinite"/>
                    </stop>
                </linearGradient>

                <linearGradient id="tech-gradient-2">
                    <stop offset="0%" style="stop-color:#A855F7;stop-opacity:0.9"/>
                    <stop offset="100%" style="stop-color:#00E5FF;stop-opacity:0.9"/>
                </linearGradient>

                <!-- Intense Glow Filters -->
                <filter id="intense-glow" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="6" result="blur1"/>
                    <feGaussianBlur stdDeviation="12" result="blur2"/>
                    <feGaussianBlur stdDeviation="20" result="blur3"/>
                    <feMerge>
                        <feMergeNode in="blur3"/>
                        <feMergeNode in="blur2"/>
                        <feMergeNode in="blur1"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>

                <filter id="sharp-glow" x="-50%" y="-50%" width="200%" height="200%">
                    <feGaussianBlur stdDeviation="3" result="blur"/>
                    <feMerge>
                        <feMergeNode in="blur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            </defs>

            <!-- Outer Energy Sphere -->
            <circle cx="150" cy="150" r="140" fill="none" stroke="url(#tech-gradient-1)" stroke-width="0.5" opacity="0.2" filter="url(#intense-glow)">
                <animate attributeName="r" values="140;145;140" dur="4s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.2;0.4;0.2" dur="4s" repeatCount="indefinite"/>
            </circle>

            <!-- Main Architectural Rings Layer 1 -->
            <g opacity="0.9" filter="url(#sharp-glow)">
                <!-- Primary Ring Structure -->
                <circle cx="150" cy="150" r="120" fill="none" stroke="url(#tech-gradient-1)" stroke-width="1.5" opacity="0.7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="30s" repeatCount="indefinite"/>
                </circle>
                
                <!-- Secondary Segmented Ring -->
                <circle cx="150" cy="150" r="120" fill="none" stroke="url(#tech-gradient-2)" stroke-width="1" stroke-dasharray="15 10" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="25s" repeatCount="indefinite"/>
                </circle>

                <!-- Tertiary Data Ring -->
                <circle cx="150" cy="150" r="118" fill="none" stroke="#00E5FF" stroke-width="0.5" stroke-dasharray="5 8" opacity="0.6">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="20s" repeatCount="indefinite"/>
                </circle>
            </g>

            <!-- Complex Middle Layer -->
            <g opacity="0.85" filter="url(#sharp-glow)">
                <!-- Mid Ring 1 -->
                <circle cx="150" cy="150" r="100" fill="none" stroke="url(#tech-gradient-1)" stroke-width="1.2" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="22s" repeatCount="indefinite"/>
                </circle>

                <!-- Hexagonal Pattern Ring -->
                <circle cx="150" cy="150" r="100" fill="none" stroke="#A855F7" stroke-width="0.8" stroke-dasharray="10 15" opacity="0.7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="18s" repeatCount="indefinite"/>
                </circle>

                <!-- Data Stream Ring -->
                <circle cx="150" cy="150" r="98" fill="none" stroke="#00E5FF" stroke-width="0.5" stroke-dasharray="3 5" opacity="0.6">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="15s" repeatCount="indefinite"/>
                    <animate attributeName="stroke-dashoffset" from="0" to="100" dur="3s" repeatCount="indefinite"/>
                </circle>
            </g>

            <!-- Inner Technical Rings -->
            <g opacity="0.9" filter="url(#sharp-glow)">
                <!-- Inner Ring System 1 -->
                <circle cx="150" cy="150" r="80" fill="none" stroke="url(#tech-gradient-2)" stroke-width="1.5" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="16s" repeatCount="indefinite"/>
                </circle>

                <!-- Segmented Inner Ring -->
                <circle cx="150" cy="150" r="80" fill="none" stroke="#00E5FF" stroke-width="1" stroke-dasharray="8 12" opacity="0.7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="14s" repeatCount="indefinite"/>
                </circle>

                <!-- Inner Ring System 2 -->
                <circle cx="150" cy="150" r="65" fill="none" stroke="url(#tech-gradient-1)" stroke-width="1" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="12s" repeatCount="indefinite"/>
                </circle>

                <!-- Technical Detail Ring -->
                <circle cx="150" cy="150" r="65" fill="none" stroke="#A855F7" stroke-width="0.5" stroke-dasharray="5 10" opacity="0.6">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="10s" repeatCount="indefinite"/>
                </circle>
            </g>

            <!-- Orbiting Data Nodes -->
            <g filter="url(#intense-glow)">
                <!-- Outer Orbit Nodes -->
                <circle cx="150" cy="30" r="2.5" fill="#00E5FF" opacity="0.9">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="12s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;1;0.5" dur="2s" repeatCount="indefinite"/>
                </circle>
                <circle cx="270" cy="150" r="2.5" fill="#A855F7" opacity="0.9">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="14s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;1;0.5" dur="2.3s" repeatCount="indefinite"/>
                </circle>
                <circle cx="150" cy="270" r="2.5" fill="#00E5FF" opacity="0.9">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="16s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;1;0.5" dur="2.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="30" cy="150" r="2.5" fill="#A855F7" opacity="0.9">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="13s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.5;1;0.5" dur="2.2s" repeatCount="indefinite"/>
                </circle>

                <!-- Mid Orbit Nodes -->
                <circle cx="210" cy="90" r="2" fill="#00E5FF" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="10s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.4;1;0.4" dur="1.8s" repeatCount="indefinite"/>
                </circle>
                <circle cx="90" cy="90" r="2" fill="#A855F7" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="11s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.4;1;0.4" dur="2.1s" repeatCount="indefinite"/>
                </circle>
                <circle cx="210" cy="210" r="2" fill="#00E5FF" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="9s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.4;1;0.4" dur="1.9s" repeatCount="indefinite"/>
                </circle>
                <circle cx="90" cy="210" r="2" fill="#A855F7" opacity="0.8">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="10.5s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.4;1;0.4" dur="2s" repeatCount="indefinite"/>
                </circle>
            </g>

            <!-- Connecting Lines (Technical Grid) -->
            <g opacity="0.3" stroke="#00E5FF" stroke-width="0.5">
                <line x1="150" y1="30" x2="150" y2="270">
                    <animate attributeName="opacity" values="0.2;0.5;0.2" dur="3s" repeatCount="indefinite"/>
                </line>
                <line x1="30" y1="150" x2="270" y2="150">
                    <animate attributeName="opacity" values="0.2;0.5;0.2" dur="3.5s" repeatCount="indefinite"/>
                </line>
                <line x1="75" y1="75" x2="225" y2="225">
                    <animate attributeName="opacity" values="0.2;0.5;0.2" dur="4s" repeatCount="indefinite"/>
                </line>
                <line x1="225" y1="75" x2="75" y2="225">
                    <animate attributeName="opacity" values="0.2;0.5;0.2" dur="4.5s" repeatCount="indefinite"/>
                </line>
            </g>

            <!-- Core Complex Structure -->
            <g filter="url(#intense-glow)">
                <!-- Outer Core Ring -->
                <circle cx="150" cy="150" r="45" fill="none" stroke="url(#tech-gradient-1)" stroke-width="1.5" opacity="0.9">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="8s" repeatCount="indefinite"/>
                </circle>

                <!-- Core Data Ring -->
                <circle cx="150" cy="150" r="45" fill="none" stroke="#00E5FF" stroke-width="1" stroke-dasharray="6 8" opacity="0.7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="6s" repeatCount="indefinite"/>
                </circle>

                <!-- Inner Core Ring -->
                <circle cx="150" cy="150" r="35" fill="url(#core-gradient)" stroke="url(#tech-gradient-2)" stroke-width="1" opacity="0.8">
                    <animate attributeName="r" values="35;38;35" dur="3s" repeatCount="indefinite"/>
                </circle>

                <!-- Central Energy Core -->
                <circle cx="150" cy="150" r="20" fill="#00E5FF" opacity="0.6">
                    <animate attributeName="r" values="20;25;20" dur="2.5s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.6;0.9;0.6" dur="2.5s" repeatCount="indefinite"/>
                </circle>

                <!-- Core Nucleus -->
                <circle cx="150" cy="150" r="12" fill="url(#core-gradient)" opacity="1">
                    <animate attributeName="r" values="12;15;12" dur="2s" repeatCount="indefinite"/>
                </circle>
            </g>

            <!-- Floating Particles System -->
            <g opacity="0.7" filter="url(#sharp-glow)">
                <circle cx="180" cy="120" r="1.5" fill="#00E5FF">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="7s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="120" cy="180" r="1.5" fill="#A855F7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="8s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1.7s" repeatCount="indefinite"/>
                </circle>
                <circle cx="180" cy="180" r="1.5" fill="#00E5FF">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="360 150 150" dur="9s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1.6s" repeatCount="indefinite"/>
                </circle>
                <circle cx="120" cy="120" r="1.5" fill="#A855F7">
                    <animateTransform attributeName="transform" type="rotate" from="0 150 150" to="-360 150 150" dur="7.5s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.3;1;0.3" dur="1.8s" repeatCount="indefinite"/>
                </circle>
            </g>
        </svg>
    </div>

    <!-- Welcome Section -->
    <div class="text-center">
        <p class="text-cyan-electric text-sm mb-2 font-medium animate-pulse">
            <i class="fas fa-circle text-[6px] mr-1"></i>
            Welcome back, <?php echo htmlspecialchars($firstName); ?>!
        </p>
        <h1 class="font-sans text-4xl md:text-5xl font-bold mb-3 tracking-tight">
            Atlas
        </h1>
        <div class="role-badge inline-block px-4 py-1.5 rounded-full">
            <span class="text-white text-xs font-sans font-medium tracking-wide">
                <i class="fas fa-robot mr-1"></i> AI Assistant
            </span>
        </div>
    </div>

</main>

<!-- Compact Chat Panel -->
<section class="glass-chat fixed bottom-0 left-0 right-0 p-4 md:p-6 chat-panel-compact">
    <div class="max-w-4xl mx-auto h-full flex flex-col">
        
        <!-- Messages Container -->
        <div id="messagesContainer" class="flex-1 overflow-y-auto mb-3 space-y-3 custom-scrollbar pr-2">
            <!-- Initial AI message -->
            <div class="flex justify-start fade-in">
                <div class="chat-bubble-ai max-w-[85%] md:max-w-[70%] px-4 py-2.5">
                    <p class="text-[14px] text-frost leading-relaxed">
                        Hello <strong><?php echo htmlspecialchars($firstName); ?></strong>! I'm Atlas, your AI assistant. How can I help you today?
                    </p>
                    <span class="text-[10px] text-whisper font-mono mt-1 block">
                        <i class="far fa-clock mr-1"></i><span class="timestamp"></span>
                    </span>
                </div>
            </div>
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