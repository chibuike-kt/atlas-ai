# Atlas — Futuristic Voice AI Dashboard (Jarvis-style)

Atlas is a futuristic, voice-enabled AI assistant interface built for the web.
It combines a reactive 3D “waveform orb” (Three.js + shaders) with real-time chat, external text-to-speech (OpenAI TTS via PHP), and a compact dashboard chat UI.

## Highlights

* **Gold waveform orb** rendered with **Three.js** + custom **GLSL shaders**
* **Speech output** with **OpenAI TTS** (served via `tts.php`)
* **Intelligent chat responses** with **OpenAI** (served via `chat.php`)
* **Compact chat panel** that stays fixed and scrolls internally
* Designed to feel like speaking to a real assistant (Jarvis-inspired UX)

## Tech Stack

* **Frontend:** HTML, CSS (Tailwind-style classes), JavaScript (ES Modules)
* **3D / FX:** Three.js, EffectComposer, UnrealBloomPass
* **Backend:** PHP endpoints for OpenAI requests
* **Audio:** Web Audio API (analysis + playback), OpenAI TTS (external voice)

## Project Structure (example)

```
atlas-ai/
  dashboard/
    index.php
  config/
    chat.php
    tts.php
  public/
    app.js
    styles.css
  orb.js
  includes/
    header.php
    footer.php
    functions.php
```

## Requirements

* PHP server (XAMPP / Apache / Nginx)
* A modern browser (Chrome recommended for best audio support)
* OpenAI API key

## Environment Setup

Set your OpenAI API key on your server (recommended):

* **Apache (XAMPP):** set in `httpd.conf` or `.htaccess`
* Or define it in your PHP config (not recommended for production)

Example (Apache env):

```
SetEnv OPENAI_API_KEY "YOUR_KEY_HERE"
```

## Running Locally

1. Put the project inside your web server directory (e.g. XAMPP `htdocs/atlas-ai`)
2. Start Apache
3. Visit:

```
http://localhost/atlas-ai/dashboard/
```

## Configuration

Atlas reads these variables from your page:

* `window.ATLAS_USER_NAME` — used for greeting
* `window.ATLAS_TTS_URL` — optional override for TTS endpoint URL
* `window.ATLAS_CHAT_URL` — optional override for chat endpoint URL

Example:

```html
<script>
  window.ATLAS_USER_NAME = "Binge";
  window.ATLAS_TTS_URL = "../config/tts.php";
  window.ATLAS_CHAT_URL = "../config/chat.php";
</script>
```

## How It Works

### 1) Orb / Animation (`orb.js`)

* Creates a 3D orb made of many line strands
* Uses shader noise and time to create wave motion
* Reacts to “speaking” via:

  * `window.setAtlasSpeaking(true/false)`
  * pitch animation from TTS audio analysis

Exposed helpers:

* `window.atlasSpeak(text, opts)` — plays OpenAI TTS audio and animates orb
* `window.atlasStop()` — stops current voice (barge-in support)

### 2) Chat UI (`app.js`)

* Sends user messages to `chat.php`
* Displays assistant reply
* Calls `atlasSpeak()` to speak responses for full interactivity

### 3) Backend endpoints

* `chat.php` calls OpenAI chat and returns `{ reply: "..." }`
* `tts.php` calls OpenAI TTS and returns audio bytes (mp3)

## Security Notes

* **Never expose your API key** in the frontend.
* Validate/limit message size server-side.
* Consider adding rate limits + auth checks on `chat.php` and `tts.php`.

## Roadmap / Ideas

* True Speech-to-Text (STT) voice input
* Wake word mode (“Hey Atlas”)
* Streaming chat responses (token-by-token)
* Conversation memory per user (DB storage)
* Voice interruption + continuous listening mode

## License

MIT (or your preferred license)

---

Built as “Atlas”: a futuristic assistant experience for dashboards and products.
