/* =========================================================
   Atlas App.js — Jarvis-like Voice + Chat + UI
   - Wake word: "hey atlas", "atlas" (optional "hey")
   - STT: SpeechRecognition
   - Chat: OpenAI via chat.php
   - TTS: external via window.atlasSpeak (from orb.js)
   ========================================================= */

// ===============================
// Menu Toggle
// ===============================
function toggleMenu() {
  const menu = document.getElementById("menuPanel");
  menu?.classList.toggle("-translate-x-full");
}
window.toggleMenu = toggleMenu;

// ===============================
// Helpers
// ===============================
function $(id) {
  return document.getElementById(id);
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}

function getTimestamp() {
  const now = new Date();
  return now.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: false
  });
}

function setStatus(status) {
  const dot = $("statusDot");
  const text = $("statusText");
  if (!dot || !text) return;

  dot.className = "w-2 h-2 rounded-full";

  switch (status) {
    case "idle":
      dot.classList.add("status-idle");
      text.textContent = "Idle";
      break;
    case "listening":
      dot.classList.add("status-listening");
      text.textContent = "Listening";
      break;
    case "thinking":
      dot.classList.add("status-listening");
      text.textContent = "Thinking";
      break;
    case "speaking":
      dot.classList.add("status-speaking");
      text.textContent = "Speaking";
      break;
    default:
      dot.classList.add("status-idle");
      text.textContent = "Idle";
      break;
  }
}

function scrollToBottom() {
  const c = $("messagesContainer");
  if (!c) return;
  c.scrollTop = c.scrollHeight;
}

// ===============================
// Typing indicator
// ===============================
function showTypingIndicator() {
  const container = $("messagesContainer");
  if (!container) return;

  removeTypingIndicator();

  const typingDiv = document.createElement("div");
  typingDiv.className = "flex justify-start fade-in";
  typingDiv.id = "typingIndicator";
  typingDiv.innerHTML = `
    <div class="chat-bubble-ai max-w-[70%] px-4 py-3">
      <div class="flex gap-1">
        <span class="w-2 h-2 bg-cyan-electric rounded-full animate-bounce" style="animation-delay: 0ms"></span>
        <span class="w-2 h-2 bg-cyan-electric rounded-full animate-bounce" style="animation-delay: 150ms"></span>
        <span class="w-2 h-2 bg-cyan-electric rounded-full animate-bounce" style="animation-delay: 300ms"></span>
      </div>
    </div>
  `;
  container.appendChild(typingDiv);
  scrollToBottom();
}

function removeTypingIndicator() {
  const indicator = $("typingIndicator");
  if (indicator) indicator.remove();
}

// ===============================
// Message UI
// ===============================
function appendUserMessage(message) {
  const container = $("messagesContainer");
  if (!container) return;

  const userMsg = document.createElement("div");
  userMsg.className = "flex justify-end fade-in";
  userMsg.innerHTML = `
    <div class="chat-bubble-user max-w-[70%] px-4 py-3">
      <p class="text-[15px] text-frost leading-relaxed">${escapeHtml(message)}</p>
      <span class="text-[10px] text-violet-pulse/60 font-mono mt-1 block text-right">
        <i class="far fa-clock mr-1"></i>${getTimestamp()}
      </span>
    </div>
  `;
  container.appendChild(userMsg);
  scrollToBottom();
}

function appendAIMessage(message) {
  const container = $("messagesContainer");
  if (!container) return;

  const aiMsg = document.createElement("div");
  aiMsg.className = "flex justify-start fade-in";
  aiMsg.innerHTML = `
    <div class="chat-bubble-ai max-w-[70%] px-4 py-3">
      <p class="text-[15px] text-frost leading-relaxed">${escapeHtml(message)}</p>
      <span class="text-[10px] text-whisper font-mono mt-1 block">
        <i class="far fa-clock mr-1"></i>${getTimestamp()}
      </span>
    </div>
  `;
  container.appendChild(aiMsg);
  scrollToBottom();
}

// Seed welcome message (compact panel stays fixed)
function seedWelcome() {
  const container = $("messagesContainer");
  if (!container) return;

  const name =
    window.userData?.name ||
    window.ATLAS_USER_NAME ||
    "there";

  container.innerHTML = `
    <div class="flex justify-start fade-in">
      <div class="chat-bubble-ai max-w-[85%] md:max-w-[70%] px-4 py-2.5">
        <p class="text-[14px] text-frost leading-relaxed">
          Hello <strong>${escapeHtml(name)}</strong>. I’m Atlas. Say “Hey Atlas” when you’re ready.
        </p>
        <span class="text-[10px] text-whisper font-mono mt-1 block">
          <i class="far fa-clock mr-1"></i><span class="timestamp">${getTimestamp()}</span>
        </span>
      </div>
    </div>
  `;
  scrollToBottom();
}

// ===============================
// OpenAI chat (via your PHP proxy)
// ===============================
const ATLAS_CHAT_URL = window.ATLAS_CHAT_URL || "../config/chat.php";

// Keep short context so it feels continuous
const userName =
  window.userData?.name ||
  window.ATLAS_USER_NAME ||
  "there";

const chatHistory = [
  {
    role: "system",
    content:
      `You are Atlas, a refined, calm, highly capable AI assistant with a futuristic but natural tone. ` +
      `The user's first name is "${userName}". ` +
      `Keep responses concise by default, but expand when asked. ` +
      `Be proactive, ask short follow-ups only when necessary. ` +
      `Avoid excessive emojis.`
  }
];

async function fetchAtlasReply(userMessage) {
  chatHistory.push({ role: "user", content: userMessage });

  // memory: system + last 18 turns
  const trimmed = [chatHistory[0], ...chatHistory.slice(-18)];

  const res = await fetch(ATLAS_CHAT_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      user: window.userData || { name: userName },
      messages: trimmed
    })
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || "Chat request failed");

  const reply = (data.reply || "").trim();
  if (!reply) throw new Error("Empty reply");

  chatHistory.push({ role: "assistant", content: reply });
  return reply;
}

// ===============================
// TTS + Orb animation hooks
// ===============================
const VOICE = window.ATLAS_VOICE || "nova"; // try: "nova", "onyx", "alloy"
const TTS_MODEL = window.ATLAS_TTS_MODEL || "tts-1-hd";

function orbSpeaking(on) {
  try {
    if (typeof window.setAtlasSpeaking === "function") {
      window.setAtlasSpeaking(!!on);
    }
  } catch {}
}

function orbPitch(v01) {
  try {
    if (typeof window.setAtlasPitch === "function") {
      window.setAtlasPitch(v01);
    }
  } catch {}
}

// Optional stop hook (recommended):
// In orb.js, expose: window.atlasStop = () => { if(currentSource) currentSource.stop(); }
function stopAtlasVoiceIfPossible() {
  try {
    if (typeof window.atlasStop === "function") window.atlasStop();
  } catch {}
}

async function speakAtlas(text) {
  if (!text) return;

  // if orb.js not ready, just skip
  if (typeof window.atlasSpeak !== "function") return;

  try {
    setStatus("speaking");
    orbSpeaking(true);

    await window.atlasSpeak(text, {
      voice: VOICE,
      model: TTS_MODEL
    });

  } catch (e) {
    console.warn("TTS failed:", e);
  } finally {
    orbSpeaking(false);
    setStatus("idle");
  }
}

// ===============================
// Voice Engine (STT + Wake + Dictation)
// ===============================
const Voice = {
  enabled: true,
  mode: "wake", // "wake" | "dictation" | "off"
  allowHeyOnly: false, // you CAN enable, but it triggers accidentally a lot
  listening: false,
  capturing: false,
  lastHeardAt: 0,
  cooldownMs: 900,
  lastWakeAt: 0,
  finalBuffer: "",
  interimBuffer: "",
  silenceMs: 1000,
  maxDictationMs: 8000,
  timerSilence: null,
  timerMax: null,
  recog: null
};

function normalizeText(s) {
  return (s || "")
    .toLowerCase()
    .replace(/[^\w\s]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function matchesWake(text) {
  const t = normalizeText(text);
  if (!t) return null;

  if (t.includes("hey atlas")) return "hey atlas";
  if (/(^|\s)atlas(\s|$)/.test(t)) return "atlas";

  if (Voice.allowHeyOnly) {
    const words = t.split(" ").filter(Boolean);
    if (/(^|\s)hey(\s|$)/.test(t) && words.length <= 2) return "hey";
  }

  return null;
}

function beep() {
  // small UX signal; if audio context blocked, it just does nothing
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;

    const ctx = new AudioCtx();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = "sine";
    o.frequency.value = 880;

    g.gain.value = 0.0001;
    o.connect(g);
    g.connect(ctx.destination);

    const now = ctx.currentTime;
    g.gain.exponentialRampToValueAtTime(0.06, now + 0.01);
    g.gain.exponentialRampToValueAtTime(0.0001, now + 0.12);

    o.start(now);
    o.stop(now + 0.13);

    setTimeout(() => ctx.close().catch(() => {}), 250);
  } catch {}
}

function setupSpeechRecognition() {
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SR) {
    console.warn("SpeechRecognition not supported in this browser.");
    Voice.enabled = false;
    return null;
  }

  const r = new SR();
  r.continuous = true;
  r.interimResults = true;
  r.lang = "en-US";

  r.onstart = () => {
    Voice.listening = true;
    setStatus("listening");
    setMicUI(true);
    console.log("🎙️ Voice listening");
  };

  r.onresult = (event) => {
    let combinedInterim = "";
    let combinedFinal = "";

    for (let i = event.resultIndex; i < event.results.length; i++) {
      const res = event.results[i];
      const text = (res[0]?.transcript || "").trim();
      if (!text) continue;

      if (res.isFinal) combinedFinal += (combinedFinal ? " " : "") + text;
      else combinedInterim += (combinedInterim ? " " : "") + text;
    }

    Voice.lastHeardAt = performance.now();

    // Jarvis-like: if Atlas is speaking and you start talking -> barge in
    if (App.isSpeaking) {
      App.isSpeaking = false;
      stopAtlasVoiceIfPossible();
      orbSpeaking(false);
    }

    if (Voice.mode === "wake") {
      // We only need enough transcript to detect wake
      const hit = matchesWake(combinedFinal || combinedInterim);
      if (!hit) return;

      const now = performance.now();
      if (now - Voice.lastWakeAt < Voice.cooldownMs) return;
      Voice.lastWakeAt = now;

      console.log("✅ Wake:", hit);
      beep();

      // Switch into dictation mode (capture next phrase)
      startDictationMode();
      return;
    }

    if (Voice.mode === "dictation") {
      // Fill input live
      const input = $("messageInput");
      if (input) input.value = combinedInterim || combinedFinal || input.value;

      if (combinedFinal) {
        Voice.finalBuffer = (Voice.finalBuffer ? Voice.finalBuffer + " " : "") + combinedFinal;
      }

      // reset silence timer on any result
      resetSilenceTimer();
    }
  };

  r.onerror = (e) => {
    // "no-speech" is common; don't hard-fail
    if (e.error !== "no-speech") console.warn("Voice error:", e.error, e);

    Voice.listening = false;
    setMicUI(false);
    setStatus("idle");

    // if still enabled, restart automatically
    if (App.voiceOn) {
      setTimeout(() => safeStartRecognition(), 250);
    }
  };

  r.onend = () => {
    Voice.listening = false;
    setMicUI(false);
    if (App.voiceOn) {
      setTimeout(() => safeStartRecognition(), 250);
    } else {
      setStatus("idle");
    }
  };

  Voice.recog = r;
  return r;
}

function safeStartRecognition() {
  if (!Voice.enabled) return;
  if (!Voice.recog) setupSpeechRecognition();
  if (!Voice.recog) return;

  try {
    Voice.recog.start();
  } catch {
    // start called twice -> ignore
  }
}

function safeStopRecognition() {
  try {
    Voice.recog && Voice.recog.stop();
  } catch {}
}

function resetSilenceTimer() {
  clearTimeout(Voice.timerSilence);
  Voice.timerSilence = setTimeout(() => {
    finishDictation();
  }, Voice.silenceMs);
}

function startDictationMode() {
  Voice.mode = "dictation";
  Voice.capturing = true;
  Voice.finalBuffer = "";
  Voice.interimBuffer = "";

  // visual: orb “attentive”
  orbPitch(0.45);

  // Start timers
  clearTimeout(Voice.timerMax);
  Voice.timerMax = setTimeout(() => finishDictation(), Voice.maxDictationMs);

  resetSilenceTimer();
}

async function finishDictation() {
  if (!Voice.capturing) return;

  Voice.capturing = false;
  clearTimeout(Voice.timerSilence);
  clearTimeout(Voice.timerMax);

  const input = $("messageInput");
  const text = normalizeText(Voice.finalBuffer) ? Voice.finalBuffer.trim() : (input?.value || "").trim();

  Voice.mode = "wake";

  if (!text) {
    // nothing captured; return to wake quietly
    orbPitch(0.25);
    return;
  }

  // Send as a normal message (voice query)
  if (input) input.value = text;
  await sendMessage({ fromVoice: true });
}

// ===============================
// Mic Button UI
// ===============================
let micBtn = null;

function setMicUI(listening) {
  if (!micBtn) return;

  micBtn.classList.toggle("ring-2", !!listening);
  micBtn.classList.toggle("ring-cyan-electric", !!listening);
  micBtn.setAttribute("aria-pressed", listening ? "true" : "false");

  micBtn.innerHTML = listening
    ? `<i class="fas fa-microphone-slash"></i>`
    : `<i class="fas fa-microphone"></i>`;
}

function injectMicButton() {
  const form = document.querySelector('form[onsubmit*="sendMessage"]');
  if (!form) return;
  if (micBtn) return;

  micBtn = document.createElement("button");
  micBtn.type = "button";
  micBtn.className =
    "px-4 py-2.5 rounded-lg bg-abyss/80 backdrop-blur-sm border border-whisper text-frost " +
    "hover:border-cyan-electric transition-all text-sm md:text-base";
  micBtn.title = "Voice mode (Wake word)";
  micBtn.setAttribute("aria-label", "Toggle voice mode");
  micBtn.setAttribute("aria-pressed", "false");
  micBtn.innerHTML = `<i class="fas fa-microphone"></i>`;

  micBtn.addEventListener("click", async () => {
    // user gesture is required for SR & audio on some browsers
    toggleVoiceMode();
  });

  const sendBtn = form.querySelector('button[type="submit"]');
  if (sendBtn) form.insertBefore(micBtn, sendBtn);
  else form.appendChild(micBtn);
}

// ===============================
// Main Chat
// ===============================
const App = {
  busy: false,
  voiceOn: false,
  isSpeaking: false
};

async function sendMessage(opts = {}) {
  if (App.busy) return;

  const input = $("messageInput");
  const msg = (input?.value || "").trim();
  if (!msg) return;

  App.busy = true;

  appendUserMessage(msg);
  if (input) input.value = "";

  setStatus("thinking");
  showTypingIndicator();

  try {
    const reply = await fetchAtlasReply(msg);

    removeTypingIndicator();
    appendAIMessage(reply);

    // Speak reply
    App.isSpeaking = true;
    await speakAtlas(reply);
    App.isSpeaking = false;

    // If voice mode is ON, return to wake mode automatically
    if (App.voiceOn) {
      Voice.mode = "wake";
      orbPitch(0.25);
    }

  } catch (e) {
    console.error(e);
    removeTypingIndicator();
    appendAIMessage("I hit an error while responding. Try again.");
  } finally {
    setStatus("idle");
    App.busy = false;
  }
}

// Expose for form usage
window.sendMessage = sendMessage;

// ===============================
// Voice Mode Controls (Wake Word)
// ===============================
function toggleVoiceMode() {
  if (!Voice.enabled) {
    appendAIMessage("Voice input isn’t supported in this browser. Try Chrome or Edge.");
    return;
  }

  App.voiceOn = !App.voiceOn;

  if (App.voiceOn) {
    Voice.mode = "wake";
    safeStartRecognition();
    appendAIMessage("Voice mode enabled. Say “Hey Atlas”.");
    // Optional: spoken confirmation
    speakAtlas("Voice mode enabled. Say Hey Atlas.");
  } else {
    safeStopRecognition();
    Voice.mode = "off";
    appendAIMessage("Voice mode disabled.");
  }
}

// ===============================
// Close menu when clicking outside
// ===============================
document.addEventListener("click", (e) => {
  const menu = $("menuPanel");
  const menuBtn = document.querySelector("nav button");

  if (menu && !menu.contains(e.target) && !menuBtn?.contains(e.target)) {
    menu.classList.add("-translate-x-full");
  }
});
$("menuPanel")?.addEventListener("click", (e) => e.stopPropagation());

// ===============================
// Keyboard shortcuts
// ===============================
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    const menu = $("menuPanel");
    if (menu && !menu.classList.contains("-translate-x-full")) toggleMenu();
  }

  // Ctrl/Cmd + K focuses input
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
    e.preventDefault();
    $("messageInput")?.focus();
  }

  // Ctrl/Cmd + M toggles voice mode
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "m") {
    e.preventDefault();
    toggleVoiceMode();
  }
});

// ===============================
// Init
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  injectMicButton();
  seedWelcome();
  setupSpeechRecognition();

  const input = $("messageInput");
  if (input) {
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  // greet by name (spoken) on load
  const name = userName || "there";
  // slight delay so orb.js is ready
  setTimeout(() => {
    speakAtlas(`Welcome back, ${name}. How can I help?`);
  }, 450);

  setStatus("idle");
});
// End of app.jsake Word)
// ===============================
function toggleVoiceMode() {
  if (!Voice.enabled) {
    appendAIMessage("Voice input isn’t supported in this browser. Try Chrome or Edge.");
    return;
  }

  App.voiceOn = !App.voiceOn;

  if (App.voiceOn) {
    Voice.mode = "wake";
    safeStartRecognition();
    appendAIMessage("Voice mode enabled. Say “Hey Atlas”.");
    // Optional: spoken confirmation
    speakAtlas("Voice mode enabled. Say Hey Atlas.");
  } else {
    safeStopRecognition();
    Voice.mode = "off";
    appendAIMessage("Voice mode disabled.");
  }
}

// ===============================
// Close menu when clicking outside
// ===============================
document.addEventListener("click", (e) => {
  const menu = $("menuPanel");
  const menuBtn = document.querySelector("nav button");

  if (menu && !menu.contains(e.target) && !menuBtn?.contains(e.target)) {
    menu.classList.add("-translate-x-full");
  }
});
$("menuPanel")?.addEventListener("click", (e) => e.stopPropagation());

// ===============================
// Keyboard shortcuts
// ===============================
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    const menu = $("menuPanel");
    if (menu && !menu.classList.contains("-translate-x-full")) toggleMenu();
  }

  // Ctrl/Cmd + K focuses input
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") {
    e.preventDefault();
    $("messageInput")?.focus();
  }

  // Ctrl/Cmd + M toggles voice mode
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "m") {
    e.preventDefault();
    toggleVoiceMode();
  }
});

// ===============================
// Init
// ===============================
document.addEventListener("DOMContentLoaded", () => {
  injectMicButton();
  seedWelcome();
  setupSpeechRecognition();

  const input = $("messageInput");
  if (input) {
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  // greet by name (spoken) on load
  const name = userName || "there";
  // slight delay so orb.js is ready
  setTimeout(() => {
    speakAtlas(`Welcome back, ${name}. How can I help?`);
  }, 450);

  setStatus("idle");
});
// End of app.js
