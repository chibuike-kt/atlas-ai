import * as THREE from "three";

import { EffectComposer } from "three/addons/postprocessing/EffectComposer.js";
import { RenderPass } from "three/addons/postprocessing/RenderPass.js";
import { UnrealBloomPass } from "three/addons/postprocessing/UnrealBloomPass.js";

(() => {
  // =========================================================
  //  CONFIG
  // =========================================================
  const TTS_URL = window.ATLAS_TTS_URL || "../config/tts.php";

  const USER_NAME_RAW = (window.ATLAS_USER_NAME || window.userData?.name || "")
    .toString()
    .trim();
  const USER_NAME = USER_NAME_RAW ? USER_NAME_RAW.split(" ")[0] : "";

  // Visual tuning (safe defaults)
  const VIS = {
    bg: 0x02040a,
    radius: 1.25,
    lineCount: 1400,
    pointsPerLine: 90,

    // Bloom clamp (prevents “too white”)
    bloomStrengthBase: 0.25,
    bloomStrengthSpeak: 0.35,
    bloomStrengthSurge: 0.45,
    bloomRadiusBase: 0.34,

    // Core/shell
    coreOpacity: 0.58,
    shellOpacityBase: 0.03,

    // Mic reactive (very subtle, not the aggressive scatter)
    micReactMax: 0.22,
    micPitchBias: 0.38
  };

  // =========================================================
  //  MOUNT TARGET
  // =========================================================
  const mount = document.getElementById("atlas-orb") || document.body;

  // =========================================================
  //  THREE / ORB SETUP
  // =========================================================
  const scene = new THREE.Scene();

  const camera = new THREE.PerspectiveCamera(
    60,
    window.innerWidth / window.innerHeight,
    0.1,
    100
  );
  camera.position.set(0, 0, 4);

  const renderer = new THREE.WebGLRenderer({
    antialias: true,
    alpha: true,
    powerPreference: "high-performance"
  });

  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setClearColor(VIS.bg, 1);

  // Remove ONLY our previous canvas if we re-init
  mount.querySelectorAll("canvas[data-atlas-orb='1']").forEach((c) => c.remove());

  renderer.domElement.dataset.atlasOrb = "1";
  renderer.domElement.style.position = "fixed";
  renderer.domElement.style.inset = "0";
  renderer.domElement.style.zIndex = "0";
  renderer.domElement.style.pointerEvents = "none";

  mount.appendChild(renderer.domElement);

  const composer = new EffectComposer(renderer);
  composer.addPass(new RenderPass(scene, camera));

  const bloom = new UnrealBloomPass(
    new THREE.Vector2(window.innerWidth, window.innerHeight),
    0.85,
    0.55,
    0.22
  );
  composer.addPass(bloom);

  // warm ambient (gold vibe)
  scene.add(new THREE.AmbientLight(0xffcc88, 0.16));

  // =========================================================
  //  SHADERS
  // =========================================================
  const vertexShader = `
    uniform float uTime;
    uniform float uSurge;
    uniform float uRadius;

    uniform float uSpeak;
    uniform vec3  uFocusDir;

    uniform float uPitch;
    uniform float uSeedOffset;
    uniform float uSettle;

    attribute float aSeed;

    varying float vDepth;
    varying float vAlpha;

    float hash(vec3 p) {
      return fract(sin(dot(p, vec3(127.1, 311.7, 74.7))) * 43758.5453123);
    }

    float noise(vec3 p) {
      vec3 i = floor(p);
      vec3 f = fract(p);
      f = f * f * (3.0 - 2.0 * f);

      float n000 = hash(i + vec3(0.0, 0.0, 0.0));
      float n100 = hash(i + vec3(1.0, 0.0, 0.0));
      float n010 = hash(i + vec3(0.0, 1.0, 0.0));
      float n110 = hash(i + vec3(1.0, 1.0, 0.0));
      float n001 = hash(i + vec3(0.0, 0.0, 1.0));
      float n101 = hash(i + vec3(1.0, 0.0, 1.0));
      float n011 = hash(i + vec3(0.0, 1.0, 1.0));
      float n111 = hash(i + vec3(1.0, 1.0, 1.0));

      float nx00 = mix(n000, n100, f.x);
      float nx10 = mix(n010, n110, f.x);
      float nx01 = mix(n001, n101, f.x);
      float nx11 = mix(n011, n111, f.x);

      float nxy0 = mix(nx00, nx10, f.y);
      float nxy1 = mix(nx01, nx11, f.y);

      return mix(nxy0, nxy1, f.z);
    }

    void main() {
      vec3 p = position;

      float seed = aSeed + uSeedOffset;
      vec3 dir = normalize(p);

      p = dir * uRadius;

      float t = uTime * 0.22;

      float n1 = noise(dir * 3.1 + vec3(t, t * 0.8, t * 0.35) + seed);
      float n2 = noise(dir * 6.8 + vec3(-t * 0.6, t * 0.9, t * 0.2) + seed * 1.7);

      float w = sin((n1 * 6.283) + (uTime * 1.15) + seed) * 0.65
              + sin((n2 * 6.283) + (uTime * 0.85)) * 0.35;

      vec3 axis = normalize(vec3(0.15, 1.0, 0.35));
      vec3 orbital = normalize(cross(axis, dir));
      vec3 radial = dir;

      vec3 flow = normalize(mix(orbital, radial, uSurge));

      float calmAmp = 0.08;
      float surgeAmp = 0.23;
      float amp = calmAmp + surgeAmp * uSurge;

      vec3 focusDir = normalize(uFocusDir);
      float align = dot(dir, focusDir);
      float focusMask = smoothstep(0.05, 0.95, align);
      float speakAmount = uSpeak * focusMask;

      flow = normalize(mix(flow, focusDir, speakAmount * 0.85));
      amp *= (1.0 + 0.85 * speakAmount);

      // Pull forward slightly while speaking (Jarvis "attention")
      p += focusDir * (0.20 * speakAmount);

      // Subtle tightening to the axis (prevents chaotic scatter)
      vec3 perp = dir - focusDir * dot(dir, focusDir);
      p -= perp * (0.12 * speakAmount);

      float variance = 0.65 + 0.35 * sin(seed * 6.283);

      p += flow * w * amp * variance;
      p += dir * sin((uTime * 1.6) + seed + n2 * 6.283) * 0.02;

      float pitchFactor = 0.35 + 0.55 * uPitch;
      float settleAmp = uSettle * pitchFactor * 0.10;
      float settleWave = sin(uTime * (1.2 + 1.4 * pitchFactor) + seed * 8.0);
      p += orbital * settleWave * settleAmp;

      vDepth = dir.z;
      float front = smoothstep(-0.35, 0.85, vDepth);

      vAlpha = (0.10 + 0.42 * front) * (0.48 + 0.45 * uSurge);
      vAlpha *= (1.0 + 0.55 * speakAmount);
      vAlpha *= (1.0 + 0.12 * uSettle);

      gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
    }
  `;

  const fragmentShader = `
    varying float vDepth;
    varying float vAlpha;

    void main() {
      float front = smoothstep(-0.35, 0.85, vDepth);

      // Gold palette (stronger gold, less white)
      vec3 gold = vec3(0.98, 0.73, 0.32);
      vec3 pale = vec3(1.00, 0.86, 0.56);

      vec3 color = mix(gold, pale, front * 0.18);
      color *= (0.62 + 0.34 * front);

      gl_FragColor = vec4(color * 0.90, vAlpha);
    }
  `;

  const group = new THREE.Group();
  scene.add(group);

  const core = new THREE.Mesh(
    new THREE.SphereGeometry(0.17, 32, 32),
    new THREE.MeshBasicMaterial({
      color: 0xffe2a8,
      transparent: true,
      opacity: VIS.coreOpacity,
      blending: THREE.AdditiveBlending
    })
  );
  group.add(core);

  const shell = new THREE.Mesh(
    new THREE.SphereGeometry(1.12, 48, 48),
    new THREE.MeshBasicMaterial({
      color: 0xffb25a,
      transparent: true,
      opacity: VIS.shellOpacityBase,
      blending: THREE.AdditiveBlending
    })
  );
  group.add(shell);

  const LINE_COUNT = VIS.lineCount;
  const POINTS_PER_LINE = VIS.pointsPerLine;
  const RADIUS = VIS.radius;

  const sharedUniforms = {
    uTime: { value: 0 },
    uSurge: { value: 0 },
    uRadius: { value: RADIUS },

    uSpeak: { value: 0 },
    uFocusDir: { value: new THREE.Vector3(0, 0, 1) },

    uPitch: { value: 0.35 },
    uSeedOffset: { value: 0 },
    uSettle: { value: 0 }
  };

  const lines = [];
  for (let i = 0; i < LINE_COUNT; i++) {
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.acos(2 * Math.random() - 1);

    const positions = new Float32Array(POINTS_PER_LINE * 3);
    const seeds = new Float32Array(POINTS_PER_LINE);

    for (let j = 0; j < POINTS_PER_LINE; j++) {
      const tt = j / (POINTS_PER_LINE - 1);
      const a = theta + (tt - 0.5) * 1.25;
      const b = phi + (tt - 0.5) * 0.40;

      const x = Math.sin(b) * Math.cos(a);
      const y = Math.cos(b);
      const z = Math.sin(b) * Math.sin(a);

      positions[j * 3 + 0] = x * RADIUS;
      positions[j * 3 + 1] = y * RADIUS;
      positions[j * 3 + 2] = z * RADIUS;

      seeds[j] = (i / LINE_COUNT) + (Math.random() * 0.02);
    }

    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute("position", new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute("aSeed", new THREE.BufferAttribute(seeds, 1));

    const material = new THREE.ShaderMaterial({
      vertexShader,
      fragmentShader,
      uniforms: sharedUniforms,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending
    });

    const line = new THREE.Line(geometry, material);
    line.userData.drift = 0.00012 + Math.random() * 0.00035;
    line.userData.tilt = (Math.random() - 0.5) * 0.4;

    lines.push(line);
    group.add(line);
  }

  // =========================================================
  //  SPEAKING / PITCH STATE (exposed to app.js)
  // =========================================================
  let speakTarget = 0;
  let speakValue = 0;

  let pitchValue = sharedUniforms.uPitch.value;

  let seedOffset = 0;
  let seedOffsetTarget = 0;

  let settleValue = 0;
  let reseedTimer = 0;

  function triggerReseed() {
    seedOffsetTarget = Math.random() * 1000;
    settleValue = 1.0;
    reseedTimer = 0;
  }

  window.setAtlasPitch = (v) => {
    pitchValue = Math.max(0, Math.min(1, Number(v) || 0));
  };

  window.setAtlasSpeaking = (isSpeaking) => {
    speakTarget = isSpeaking ? 1 : 0;
    if (isSpeaking) triggerReseed();
  };

  // =========================================================
  //  AUDIO CONTEXT
  // =========================================================
  const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

  // TTS analyser (pitch animation during Atlas speech)
  const ttsAnalyser = audioCtx.createAnalyser();
  ttsAnalyser.fftSize = 2048;

  const gainNode = audioCtx.createGain();
  const compressor = audioCtx.createDynamicsCompressor();

  gainNode.gain.value = 1.55;
  compressor.threshold.value = -28;
  compressor.knee.value = 24;
  compressor.ratio.value = 4;
  compressor.attack.value = 0.01;
  compressor.release.value = 0.18;

  const ttsTimeData = new Float32Array(ttsAnalyser.fftSize);
  let currentSource = null;
  let isPlaying = false;

  function hzTo01(hz) {
    const minHz = 85;
    const maxHz = 210;
    const x = (hz - minHz) / (maxHz - minHz);
    return Math.max(0, Math.min(1, x));
  }

  function estimatePitchAutocorr(buf, sampleRate) {
    const SIZE = buf.length;

    let mean = 0;
    for (let i = 0; i < SIZE; i++) mean += buf[i];
    mean /= SIZE;

    let rms = 0;
    for (let i = 0; i < SIZE; i++) {
      const v = buf[i] - mean;
      rms += v * v;
    }
    rms = Math.sqrt(rms / SIZE);
    if (rms < 0.008) return null;

    let bestLag = -1;
    let bestCorr = 0;

    const minFreq = 70;
    const maxFreq = 350;
    const minLag = Math.floor(sampleRate / maxFreq);
    const maxLag = Math.floor(sampleRate / minFreq);

    for (let lag = minLag; lag <= maxLag; lag++) {
      let corr = 0;
      for (let i = 0; i < SIZE - lag; i++) corr += buf[i] * buf[i + lag];
      corr = corr / (SIZE - lag);

      if (corr > bestCorr) {
        bestCorr = corr;
        bestLag = lag;
      }
    }

    if (bestLag === -1 || bestCorr < 0.12) return null;
    return sampleRate / bestLag;
  }

  function updatePitchFromTTS() {
    if (!isPlaying) return;

    ttsAnalyser.getFloatTimeDomainData(ttsTimeData);
    const hz = estimatePitchAutocorr(ttsTimeData, audioCtx.sampleRate);

    const p01 = hz ? hzTo01(hz) : 0.25;
    pitchValue = THREE.MathUtils.lerp(pitchValue, p01, 0.15);
  }

  // =========================================================
  //  MIC CAPTURE + SUBTLE REACTIVITY
  // =========================================================
  let micStream = null;
  let micSource = null;

  const micAnalyser = audioCtx.createAnalyser();
  micAnalyser.fftSize = 2048;

  const micByte = new Uint8Array(micAnalyser.fftSize);
  let micRms = 0;

  function computeRMSFromByte(data) {
    let sum = 0;
    for (let i = 0; i < data.length; i++) {
      const v = (data[i] - 128) / 128;
      sum += v * v;
    }
    return Math.sqrt(sum / data.length);
  }

  async function enableMic() {
    try {
      if (audioCtx.state === "suspended") await audioCtx.resume();
      if (micStream) return true;

      micStream = await navigator.mediaDevices.getUserMedia({
        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true }
      });

      micSource = audioCtx.createMediaStreamSource(micStream);
      micSource.connect(micAnalyser);
      return true;
    } catch (e) {
      console.warn("enableMic failed:", e);
      return false;
    }
  }

  function updateMicMeter() {
    if (!micStream) return;
    micAnalyser.getByteTimeDomainData(micByte);
    micRms = computeRMSFromByte(micByte);
  }

  // Expose for app.js if you want to call it directly
  window.enableAtlasAudio = async () => {
    if (audioCtx.state === "suspended") {
      try { await audioCtx.resume(); } catch {}
    }
    await enableMic();
    return true;
  };

  // =========================================================
  //  OPENAI TTS
  // =========================================================
  async function atlasSpeak(text, opts = {}) {
    if (!text) return;

    if (audioCtx.state === "suspended") await audioCtx.resume();

    // stop current if speaking
    try {
      if (currentSource) currentSource.stop();
    } catch {}
    currentSource = null;

    window.setAtlasSpeaking(true);

    const voice = opts.voice || "sage";
    const model = opts.model || "tts-1-hd";

    const res = await fetch(TTS_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ text, voice, model, format: "mp3" })
    });

    if (!res.ok) {
      const errText = await res.text();
      console.error("TTS error:", errText);
      window.setAtlasSpeaking(false);
      return;
    }

    const arrayBuf = await res.arrayBuffer();
    const audioBuf = await audioCtx.decodeAudioData(arrayBuf);

    const src = audioCtx.createBufferSource();
    src.buffer = audioBuf;

    src.connect(gainNode);
    gainNode.connect(compressor);
    compressor.connect(ttsAnalyser);
    ttsAnalyser.connect(audioCtx.destination);

    currentSource = src;
    isPlaying = true;

    src.onended = () => {
      isPlaying = false;
      currentSource = null;
      window.setAtlasSpeaking(false);
      window.setAtlasPitch(0.25); // return to calm pitch
    };

    src.start(0);
  }

  // Expose TTS
  window.atlasSpeak = atlasSpeak;

  // IMPORTANT: barge-in support for app.js
  window.atlasStop = () => {
    try { currentSource && currentSource.stop(); } catch {}
    currentSource = null;
    isPlaying = false;
    window.setAtlasSpeaking(false);
  };

  // =========================================================
  //  GREETING (once on first user gesture) — NO "T" key
  // =========================================================
  let greeted = false;

  function buildGreeting() {
    if (USER_NAME) return `Welcome back, ${USER_NAME}.`;
    return "Welcome. I am Atlas.";
  }

  async function greetOnce() {
    if (greeted) return;
    greeted = true;

    if (audioCtx.state === "suspended") {
      try { await audioCtx.resume(); } catch {}
    }

    // prime mic for later (optional but helps)
    await enableMic();

    // speak greeting
    atlasSpeak(buildGreeting(), { voice: "sage" });
  }

  // This is the only gesture-based unlock we keep
  window.addEventListener(
    "click",
    () => {
      greetOnce();
    },
    { once: true }
  );

  // =========================================================
  //  ANIMATION LOOP
  // =========================================================
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);

    const dt = clock.getDelta();
    const t = clock.elapsedTime;

    updateMicMeter();

    // Gentle surge pulse (rare)
    const raw = Math.max(0, Math.sin(t * 0.33));
    const surge = Math.pow(raw, 8.0);

    // Speak ramp
    const speakUp = 14.0;
    const speakDown = 5.0;

    if (speakTarget > speakValue) speakValue = Math.min(1, speakValue + speakUp * dt);
    else speakValue = Math.max(0, speakValue - speakDown * dt);

    // Pitch update from TTS
    updatePitchFromTTS();

    // Mic-driven subtle energy when NOT speaking (Jarvis "alive" feel)
    // This avoids the aggressive scatter and only adds life.
    if (micStream && speakValue < 0.05) {
      const micEnergy = Math.min(1, micRms * 12.0);
      const micBoost = micEnergy * VIS.micReactMax;

      // small breathing motion
      sharedUniforms.uSettle.value = Math.max(sharedUniforms.uSettle.value, micBoost * 0.6);

      // bias pitch slightly from mic energy (visual only)
      const targetPitch = VIS.micPitchBias + micEnergy * 0.35;
      pitchValue = THREE.MathUtils.lerp(pitchValue, targetPitch, 0.05);

      // very gentle reseed if user is speaking loud
      if (micEnergy > 0.55 && Math.random() < 0.015) triggerReseed();
    }

    // Occasional reseed while speaking; pitch controls interval (restrained)
    if (speakValue > 0.02) {
      reseedTimer += dt;
      const interval = THREE.MathUtils.lerp(2.2, 1.1, pitchValue);
      if (reseedTimer > interval && speakValue > 0.35) triggerReseed();
    } else {
      reseedTimer = 0;
    }

    // Seed follows target smoothly
    const seedFollow = THREE.MathUtils.lerp(6.0, 10.0, pitchValue);
    seedOffset = THREE.MathUtils.lerp(
      seedOffset,
      seedOffsetTarget,
      1 - Math.exp(-seedFollow * dt)
    );

    // Settle decay
    settleValue = Math.max(0, settleValue - dt * (2.6 + 1.4 * pitchValue));

    // Uniforms
    sharedUniforms.uTime.value = t;
    sharedUniforms.uSurge.value = surge;
    sharedUniforms.uSpeak.value = speakValue;
    sharedUniforms.uFocusDir.value.set(0, 0, 1);

    sharedUniforms.uPitch.value = pitchValue;
    sharedUniforms.uSeedOffset.value = seedOffset;
    sharedUniforms.uSettle.value = Math.max(settleValue, sharedUniforms.uSettle.value * 0.92);

    // Drift
    group.rotation.y += 0.00025;
    group.rotation.x += 0.00012;

    for (let i = 0; i < lines.length; i++) {
      const l = lines[i];
      l.rotation.y += l.userData.drift;
      l.rotation.x += l.userData.drift * 0.6;
      l.rotation.z = l.userData.tilt * (0.35 + surge);
    }

    core.scale.setScalar(1 + surge * 0.16 + speakValue * 0.12);
    shell.scale.setScalar(1 + surge * 0.10 + speakValue * 0.05);

    shell.material.opacity =
      VIS.shellOpacityBase + surge * 0.05 + speakValue * 0.02 + sharedUniforms.uSettle.value * 0.02;

    // Bloom clamp (prevents blown-out whites)
    const bloomStrength =
      VIS.bloomStrengthBase +
      surge * VIS.bloomStrengthSurge +
      speakValue * VIS.bloomStrengthSpeak +
      sharedUniforms.uSettle.value * 0.10;

    bloom.strength = Math.min(0.75, bloomStrength);
    bloom.radius = VIS.bloomRadiusBase + surge * 0.10 + speakValue * 0.05;

    composer.render();

    // decay the mic visual settle gently
    sharedUniforms.uSettle.value *= 0.94;
  }

  animate();

  // =========================================================
  //  Resize
  // =========================================================
  window.addEventListener("resize", () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();

    renderer.setSize(window.innerWidth, window.innerHeight);
    composer.setSize(window.innerWidth, window.innerHeight);
    bloom.setSize(window.innerWidth, window.innerHeight);
  });
})();
