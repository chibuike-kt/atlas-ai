<?php
/**
 * Atlas AI - User Login Page
 */

$pageTitle = 'Login';
require_once '../includes/functions.php';

// Redirect if already logged in
redirectIfLoggedIn();

// Get flash messages
$flash = getFlashMessage();
?>

<?php require_once '../includes/header.php'; ?>

<div class="waveform-bg"></div>

<div class="relative min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md z-10">
        
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <h1 class="font-sans text-4xl font-bold tracking-tight text-frost mb-2">
                Atlas
            </h1>
            <p class="text-mist text-sm">
                Your intelligent assistant
            </p>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
        <div class="mb-6 p-4 rounded-lg glass-panel border-l-4 <?php echo $flash['type'] === 'error' ? 'border-red-500' : 'border-green-500'; ?>">
            <div class="flex items-start">
                <i class="fas <?php echo $flash['type'] === 'error' ? 'fa-exclamation-circle text-red-400' : 'fa-check-circle text-green-400'; ?> mr-3 mt-0.5"></i>
                <p class="text-sm <?php echo $flash['type'] === 'error' ? 'text-red-300' : 'text-green-300'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="glass-panel rounded-2xl p-8">
            <h2 class="font-sans text-xl font-semibold mb-6">Sign In</h2>
            
            <form action="process/login_process.php" method="POST" class="space-y-5" novalidate>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-mist mb-2">
                        <i class="fas fa-envelope mr-1"></i> Email
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        placeholder="you@example.com"
                        required
                        class="input-field w-full px-4 py-3 rounded-lg text-frost placeholder-whisper"
                        autofocus
                    />
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-mist mb-2">
                        <i class="fas fa-lock mr-1"></i> Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="••••••••"
                        required
                        class="input-field w-full px-4 py-3 rounded-lg text-frost placeholder-whisper"
                    />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center text-mist cursor-pointer hover:text-frost transition-colors">
                        <input type="checkbox" name="remember" class="mr-2 rounded border-whisper accent-cyan-electric cursor-pointer">
                        Remember me
                    </label>
                    <a href="#" class="text-cyan-electric hover:underline font-medium">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="btn-primary w-full py-3 px-6 rounded-lg text-white font-sans font-medium text-sm tracking-wide"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center text-sm text-mist">
                Don't have an account?
                <a href="register.php" class="text-cyan-electric hover:underline ml-1 font-medium">Sign up</a>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>