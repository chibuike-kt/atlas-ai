<?php
/**
 * Atlas AI - User Registration Page
 */

$pageTitle = 'Register';
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

        <!-- Registration Form -->
        <div class="glass-panel rounded-2xl p-8">
            <h2 class="font-sans text-xl font-semibold mb-6">Create Account</h2>
            
            <form action="process/register_process.php" method="POST" class="space-y-5" novalidate>
                
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-mist mb-2">
                        <i class="fas fa-user mr-1"></i> Full Name
                    </label>
                    <input
                        id="full_name"
                        name="full_name"
                        type="text"
                        placeholder="John Doe"
                        required
                        class="input-field w-full px-4 py-3 rounded-lg text-frost placeholder-whisper"
                        value="<?php echo isset($_SESSION['old_input']['full_name']) ? htmlspecialchars($_SESSION['old_input']['full_name']) : ''; ?>"
                    />
                </div>

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
                        value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>"
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
                        minlength="8"
                        class="input-field w-full px-4 py-3 rounded-lg text-frost placeholder-whisper"
                    />
                    <p class="text-xs text-mist mt-1">Minimum 8 characters</p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-mist mb-2">
                        <i class="fas fa-lock mr-1"></i> Confirm Password
                    </label>
                    <input
                        id="confirm_password"
                        name="confirm_password"
                        type="password"
                        placeholder="••••••••"
                        required
                        minlength="8"
                        class="input-field w-full px-4 py-3 rounded-lg text-frost placeholder-whisper"
                    />
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="btn-primary w-full py-3 px-6 rounded-lg text-white font-sans font-medium text-sm tracking-wide"
                >
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center text-sm text-mist">
                Already have an account?
                <a href="login.php" class="text-cyan-electric hover:underline ml-1 font-medium">Sign in</a>
            </div>
        </div>

    </div>
</div>

<?php 
// Clear old input
unset($_SESSION['old_input']);
?>

<?php require_once '../includes/footer.php'; ?>