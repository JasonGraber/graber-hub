<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Graber Hub</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --bg: #0a0a0f;
      --surface: #14141f;
      --surface-2: #1e1e2e;
      --border: #2a2a3a;
      --text: #f0f0f5;
      --text-muted: #8888aa;
      --accent: #03a9f4;
      --pink: #ec5281;
      --green: #4ade80;
      --yellow: #fbbf24;
      --orange: #fb923c;
      --red: #f87171;
      --purple: #a78bfa;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      height: 100vh;
      width: 100vw;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* ============ HEADER ============ */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 28px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }

    .header-left { display: flex; align-items: center; gap: 16px; }

    /* ============ DISPLAY MODE INDICATOR ============ */
    .mode-indicator {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 14px;
      border-radius: 20px;
      background: rgba(3, 169, 244, 0.08);
      border: 1px solid rgba(3, 169, 244, 0.2);
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    .mode-indicator::before {
      content: '';
      position: absolute;
      top: 0; left: -100%; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(3, 169, 244, 0.1), transparent);
      animation: modeShimmer 3s ease-in-out infinite;
    }
    @keyframes modeShimmer {
      0% { left: -100%; }
      50% { left: 100%; }
      100% { left: 100%; }
    }
    .mode-indicator.mode-photos {
      background: rgba(236, 82, 129, 0.08);
      border-color: rgba(236, 82, 129, 0.25);
    }
    .mode-indicator.mode-photos::before {
      background: linear-gradient(90deg, transparent, rgba(236, 82, 129, 0.1), transparent);
    }
    .mode-indicator.mode-photos .mode-dot {
      background: var(--pink);
      box-shadow: 0 0 8px rgba(236, 82, 129, 0.6);
    }
    .mode-indicator.mode-auto {
      background: rgba(74, 222, 128, 0.08);
      border-color: rgba(74, 222, 128, 0.25);
    }
    .mode-indicator.mode-auto::before {
      background: linear-gradient(90deg, transparent, rgba(74, 222, 128, 0.1), transparent);
    }
    .mode-indicator.mode-auto .mode-dot {
      background: var(--green);
      box-shadow: 0 0 8px rgba(74, 222, 128, 0.6);
    }
    .mode-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent);
      box-shadow: 0 0 8px rgba(3, 169, 244, 0.6);
      animation: modePulse 2s ease-in-out infinite;
      flex-shrink: 0;
    }
    @keyframes modePulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.6; transform: scale(0.8); }
    }
    .mode-icon {
      font-size: 14px;
      line-height: 1;
    }
    .mode-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      transition: color 0.3s;
    }
    .mode-indicator.mode-photos .mode-label { color: var(--pink); }
    .mode-indicator.mode-auto .mode-label { color: var(--green); }
    .mode-indicator.mode-dashboard .mode-label { color: var(--accent); }

    .weather-mini { display: flex; align-items: center; gap: 10px; }
    .weather-mini .weather-icon { font-size: 24px; }
    .weather-mini .temp { font-size: 22px; font-weight: 700; }
    .weather-mini .weather-detail { font-size: 12px; color: var(--text-muted); line-height: 1.4; }

    .header-center { flex: 1; text-align: center; padding: 0 24px; }
    .verse { font-size: 18px; font-style: italic; color: var(--text-muted); line-height: 1.5; }
    .verse .ref { color: var(--accent); font-weight: 600; font-style: normal; }

    .datetime { text-align: right; }
    .time { font-size: 32px; font-weight: 700; letter-spacing: -1px; }
    .date { font-size: 13px; color: var(--text-muted); font-weight: 500; }

    /* ============ MAIN GRID ============ */
    /*
      Layout:
      [schedule] [countdown] [timer]     <- top half
      [schedule] [kid1][kid2][kid3][kid4] <- bottom half
    */
    .main-grid {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 3fr;
      grid-template-rows: 1fr 1fr;
      gap: 14px;
      padding: 14px 28px;
      min-height: 0;
    }

    .schedule-col {
      grid-row: 1 / 3;
      background: var(--surface);
      border-radius: 16px;
      border: 1px solid var(--border);
      padding: 16px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .top-right {
      display: flex;
      gap: 14px;
      min-height: 0;
    }

    .kids-row {
      display: flex;
      gap: 14px;
      min-height: 0;
    }

    /* ============ CARD SHARED ============ */
    .card-header {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 10px; flex-shrink: 0;
    }
    .card-header h2 {
      font-size: 12px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted);
    }

    .card-body {
      flex: 1; overflow-y: auto; min-height: 0;
    }
    .card-body::-webkit-scrollbar { width: 3px; }
    .card-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

    /* ============ SCHEDULE PROGRESS ============ */
    .schedule-progress {
      flex-shrink: 0;
      margin-bottom: 12px;
    }

    .progress-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .progress-label {
      font-size: 10px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .progress-pct {
      font-size: 12px;
      font-weight: 800;
      color: var(--accent);
    }

    .progress-bar-bg {
      width: 100%;
      height: 6px;
      background: var(--surface-2);
      border-radius: 3px;
      overflow: hidden;
    }

    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--accent), var(--green));
      border-radius: 3px;
      transition: width 0.5s ease;
    }

    .progress-current {
      font-size: 10px;
      color: var(--accent);
      margin-top: 4px;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ============ SCHEDULE ============ */
    .schedule-block {
      padding: 8px 10px; border-radius: 8px; margin-bottom: 4px;
      border-left: 3px solid var(--accent); background: var(--surface-2);
      transition: opacity 0.3s, background 0.3s;
      position: relative;
      overflow: hidden;
    }
    .schedule-block.active {
      background: rgba(3, 169, 244, 0.1);
      box-shadow: 0 0 16px rgba(3, 169, 244, 0.1);
    }
    .schedule-block.past { opacity: 0.35; }
    .schedule-time {
      font-size: 11px; font-weight: 700; color: var(--accent);
      text-transform: uppercase; letter-spacing: 0.5px;
    }
    .schedule-title { font-size: 15px; font-weight: 600; margin-top: 2px; }

    .schedule-top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .block-pct {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      flex-shrink: 0;
      min-width: 32px;
      text-align: right;
    }
    .block-pct.active { color: var(--accent); }
    .block-pct.done { color: var(--green); }

    .block-progress-bg {
      width: 100%;
      height: 3px;
      background: var(--border);
      border-radius: 2px;
      margin-top: 4px;
      overflow: hidden;
    }
    .block-progress-fill {
      height: 100%;
      border-radius: 2px;
      transition: width 1s linear;
    }

    /* ============ KID CARDS ============ */
    .kid-card {
      flex: 1;
      background: var(--surface);
      border-radius: 14px;
      border: 1px solid var(--border);
      padding: 14px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      min-height: 0;
    }

    .kid-card-header {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 10px; flex-shrink: 0;
    }

    .kid-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 800; color: white; flex-shrink: 0;
    }
    .kid-name { font-size: 18px; font-weight: 700; }
    .kid-score { margin-left: auto; font-size: 14px; font-weight: 700; color: var(--green); }

    .kid-chores { flex: 1; overflow-y: auto; min-height: 0; }
    .kid-chores::-webkit-scrollbar { width: 2px; }
    .kid-chores::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

    .chore-item {
      display: flex; align-items: center; gap: 8px;
      padding: 5px 0; border-bottom: 1px solid var(--border);
    }
    .chore-item:last-child { border-bottom: none; }

    .chore-checkbox {
      width: 20px; height: 20px; border-radius: 4px;
      border: 2px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; font-size: 11px;
    }
    .chore-checkbox.done { background: var(--green); border-color: var(--green); color: white; }
    .chore-text { font-size: 15px; font-weight: 500; }
    .chore-text.done { text-decoration: line-through; color: var(--text-muted); }
    .chore-emoji { font-size: 16px; flex-shrink: 0; }

    .empty-chores {
      color: var(--text-muted); font-size: 12px; font-style: italic;
      text-align: center; padding: 12px 0;
    }

    .all-done-badge { text-align: center; padding: 6px; font-size: 22px; }

    /* ============ COUNTDOWN + TIMER PANELS ============ */
    .hero-panel {
      flex: 1;
      background: var(--surface);
      border-radius: 16px;
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      min-height: 0;
    }

    .hero-emoji {
      font-size: 50px;
      margin-bottom: 8px;
      animation: float 3s ease-in-out infinite;
    }

    .hero-label {
      font-size: 16px; font-weight: 700; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 3px; margin-bottom: 4px;
    }

    .hero-number {
      font-size: 80px; font-weight: 900; letter-spacing: -4px;
      background: linear-gradient(135deg, var(--accent), var(--pink));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      line-height: 1;
    }

    .hero-unit {
      font-size: 20px; font-weight: 600; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 6px; margin-top: 2px;
    }

    .hero-sub {
      font-size: 12px; color: var(--text-muted); margin-top: 8px; font-weight: 500;
    }

    .countdown-ring {
      position: absolute; border-radius: 50%; border: 2px solid var(--border);
      opacity: 0.2; animation: ringPulse 4s ease-in-out infinite;
    }
    .countdown-ring:nth-child(1) { width: 200px; height: 200px; }
    .countdown-ring:nth-child(2) { width: 260px; height: 260px; animation-delay: 0.5s; opacity: 0.1; }
    .countdown-ring:nth-child(3) { width: 320px; height: 320px; animation-delay: 1s; opacity: 0.05; }

    .hero-dots {
      position: absolute; width: 100%; height: 100%; overflow: hidden;
    }

    .dot {
      position: absolute; width: 4px; height: 4px; border-radius: 50%;
      background: var(--accent); opacity: 0.3; animation: drift 8s linear infinite;
    }

    /* Timer specific */
    .timer-display {
      font-size: 80px; font-weight: 900; letter-spacing: -2px;
      font-variant-numeric: tabular-nums;
      color: var(--text);
      line-height: 1;
    }

    .timer-display.running {
      background: linear-gradient(135deg, var(--green), var(--accent));
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }

    .timer-display.done-flash {
      animation: timerFlash 0.5s ease-in-out infinite;
    }

    .timer-idle-msg {
      font-size: 14px;
      color: var(--text-muted);
      font-style: italic;
      margin-top: 8px;
    }

    @keyframes timerFlash {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }
    @keyframes ringPulse {
      0%, 100% { transform: scale(1); opacity: 0.2; }
      50% { transform: scale(1.05); opacity: 0.1; }
    }
    @keyframes drift {
      0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
      10% { opacity: 0.3; }
      90% { opacity: 0.3; }
      100% { transform: translateY(-20px) rotate(360deg); opacity: 0; }
    }

    /* ============ PHOTO FRAME MODE ============ */
    .photo-frame {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: #000;
      z-index: 500;
      display: none;
      overflow: hidden;
    }

    .photo-frame.active { display: block; }

    .photo-slide {
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      image-rendering: auto;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0;
      transition: opacity 2s ease-in-out;
    }

    .photo-slide.visible { opacity: 1; }

    .photo-slide.ken-burns {
      animation: kenBurns 30s ease-in-out forwards;
    }

    @keyframes kenBurns {
      0% { transform: scale(1.0) translate(0, 0); }
      100% { transform: scale(1.15) translate(var(--kb-x), var(--kb-y)); }
    }

    .photo-overlay {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      background: linear-gradient(transparent, rgba(0,0,0,0.3) 30%, rgba(0,0,0,0.7));
      padding: 40px 50px 30px;
      z-index: 2;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }

    .photo-clock {
      font-size: 72px;
      font-weight: 200;
      letter-spacing: -2px;
      line-height: 1;
      color: #fff;
      text-shadow: 0 2px 20px rgba(0,0,0,0.5);
    }

    .photo-date {
      font-size: 20px;
      font-weight: 400;
      color: rgba(255,255,255,0.8);
      margin-top: 4px;
      text-shadow: 0 1px 10px rgba(0,0,0,0.5);
    }

    .photo-weather {
      text-align: right;
      color: #fff;
      text-shadow: 0 2px 20px rgba(0,0,0,0.5);
    }

    .photo-weather-temp {
      font-size: 48px;
      font-weight: 300;
      line-height: 1;
    }

    .photo-weather-icon {
      font-size: 36px;
      margin-right: 8px;
    }

    .photo-weather-desc {
      font-size: 16px;
      color: rgba(255,255,255,0.7);
      margin-top: 4px;
    }

    .photo-verse-overlay {
      position: absolute;
      top: 30px;
      left: 50px;
      right: 50px;
      z-index: 2;
      text-align: center;
      opacity: 0;
      transition: opacity 3s ease-in-out;
    }

    .photo-verse-overlay.visible { opacity: 1; }

    .photo-verse-text {
      font-size: 22px;
      font-weight: 300;
      font-style: italic;
      color: rgba(255,255,255,0.85);
      text-shadow: 0 2px 15px rgba(0,0,0,0.7);
      line-height: 1.5;
    }

    .photo-verse-ref {
      font-size: 16px;
      font-weight: 600;
      color: rgba(255,255,255,0.6);
      margin-top: 6px;
      font-style: normal;
      text-shadow: 0 1px 10px rgba(0,0,0,0.5);
    }

    /* ============ FOOTER ============ */
    .footer {
      display: flex; justify-content: center; align-items: center;
      padding: 8px 28px; border-top: 1px solid var(--border); gap: 40px; flex-shrink: 0;
    }
    .footer-item {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--text-muted);
      cursor: pointer; padding: 4px 12px; border-radius: 8px; transition: background 0.2s;
    }
    .footer-item:hover, .footer-item.active { background: var(--surface-2); }
    .footer-item .countdown-num { font-weight: 800; font-size: 15px; color: var(--accent); }

    /* ============ CELEBRATION POPUP ============ */
    .popup-overlay {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.7);
      display: flex; align-items: center; justify-content: center;
      z-index: 1000; opacity: 0; pointer-events: none; transition: opacity 0.3s;
    }
    .popup-overlay.show { opacity: 1; pointer-events: auto; }

    .popup-card {
      background: var(--surface); border-radius: 24px; padding: 40px 60px;
      text-align: center; transform: scale(0.8);
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      border: 2px solid var(--accent); box-shadow: 0 0 60px rgba(3, 169, 244, 0.3);
    }
    .popup-overlay.show .popup-card { transform: scale(1); }
    .popup-emoji { font-size: 64px; margin-bottom: 16px; }
    .popup-name { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
    .popup-message { font-size: 20px; color: var(--text-muted); font-weight: 500; }

    /* ============ CONFETTI ============ */
    .confetti-container {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      pointer-events: none; z-index: 2000; overflow: hidden;
    }
    .confetti-piece {
      position: absolute; width: 10px; height: 10px; top: -10px;
      animation: confettiFall 3s ease-out forwards;
    }
    @keyframes confettiFall {
      0% { transform: translateY(0) rotate(0deg); opacity: 1; }
      100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
    }
  </style>
</head>
<body>
  <!-- PHOTO FRAME MODE -->
  <div class="photo-frame" id="photo-frame">
    <div class="photo-slide" id="photo-slide-a"></div>
    <div class="photo-slide" id="photo-slide-b"></div>
    <div class="photo-verse-overlay" id="photo-verse">
      <div class="photo-verse-text" id="photo-verse-text"></div>
      <div class="photo-verse-ref" id="photo-verse-ref"></div>
    </div>
    <div class="photo-overlay">
      <div>
        <div class="photo-clock" id="photo-clock">--:--</div>
        <div class="photo-date" id="photo-date"></div>
      </div>
      <div class="photo-weather">
        <div>
          <span class="photo-weather-icon" id="photo-w-icon"></span>
          <span class="photo-weather-temp" id="photo-w-temp">--</span>
        </div>
        <div class="photo-weather-desc" id="photo-w-desc"></div>
      </div>
    </div>
  </div>

  <div class="popup-overlay" id="popup">
    <div class="popup-card">
      <div class="popup-emoji" id="popup-emoji"></div>
      <div class="popup-name" id="popup-name">Great job!</div>
      <div class="popup-message" id="popup-message">Keep it up!</div>
    </div>
  </div>
  <div class="confetti-container" id="confetti"></div>

  <div class="header">
    <div class="header-left">
      <div class="weather-mini">
        <span class="weather-icon" id="w-icon"></span>
        <span class="temp" id="w-temp">--</span>
        <div class="weather-detail">
          <span id="w-location">Sarasota, FL</span><br>
          <span id="w-desc">Loading...</span>
        </div>
      </div>
      <div class="mode-indicator mode-dashboard" id="mode-indicator">
        <span class="mode-dot"></span>
        <span class="mode-icon" id="mode-icon">🖥️</span>
        <span class="mode-label" id="mode-label">Dashboard</span>
      </div>
    </div>
    <div class="header-center">
      <div class="verse">
        "<span id="verse-text">Loading...</span>"
        <span class="ref" id="verse-ref"></span>
      </div>
    </div>
    <div class="datetime">
      <div class="time" id="clock">--:--</div>
      <div class="date" id="date">Loading...</div>
    </div>
  </div>

  <div class="main-grid">
    <!-- Schedule: left column, full height -->
    <div class="schedule-col">
      <div class="card-header">
        <h2>Schedule</h2>
      </div>
      <div class="schedule-progress" id="schedule-progress">
        <div class="progress-info">
          <span class="progress-label">Day Progress</span>
          <span class="progress-pct" id="progress-pct">0%</span>
        </div>
        <div class="progress-bar-bg">
          <div class="progress-bar-fill" id="progress-fill" style="width: 0%"></div>
        </div>
        <div class="progress-current" id="progress-current"></div>
      </div>
      <div class="card-body" id="schedule-list"></div>
    </div>

    <!-- Top right: Countdown + Timer side by side -->
    <div class="top-right">
      <div class="hero-panel" id="countdown-hero">
        <div class="hero-dots" id="countdown-dots"></div>
        <div class="countdown-ring"></div>
        <div class="countdown-ring"></div>
        <div class="countdown-ring"></div>
        <div class="hero-emoji" id="cd-emoji"></div>
        <div class="hero-label" id="cd-label">Loading</div>
        <div class="hero-number" id="cd-number">--</div>
        <div class="hero-unit">DAYS</div>
        <div class="hero-sub" id="cd-date"></div>
      </div>

      <div class="hero-panel" id="timer-panel">
        <div class="hero-dots" id="timer-dots"></div>
        <div class="countdown-ring" style="border-color: var(--green);"></div>
        <div class="countdown-ring" style="border-color: var(--green);"></div>
        <div class="countdown-ring" style="border-color: var(--green);"></div>
        <div class="hero-emoji" id="timer-emoji">\u23F1</div>
        <div class="hero-label">Timer</div>
        <div class="timer-display" id="timer-display">00:00</div>
        <div class="hero-unit" id="timer-unit">READY</div>
        <div class="timer-idle-msg" id="timer-idle">Set from the app</div>
      </div>
    </div>

    <!-- Bottom right: Kids -->
    <div class="kids-row" id="kids-row"></div>
  </div>

  <div class="footer" id="countdown-bar"></div>

  <script>
    var API = "/api.php";
    var BORDER_COLORS = ["#03a9f4", "#ec5281", "#4ade80", "#a78bfa", "#fbbf24", "#fb923c", "#f87171"];

    var ENCOURAGEMENTS = [
      { emoji: "\u2B50", msgs: ["You are a rockstar!", "Nailed it!", "Way to go!", "So proud of you!"] },
      { emoji: "\uD83D\uDCAA", msgs: ["Strong work!", "Crushing it!", "Beast mode!", "Unstoppable!"] },
      { emoji: "\uD83D\uDD25", msgs: ["On fire!", "Keep blazing!", "Amazing!", "Wow!"] },
      { emoji: "\uD83C\uDFAF", msgs: ["Bullseye!", "Right on target!", "Perfect!", "Spot on!"] },
      { emoji: "\uD83C\uDFC6", msgs: ["Champion!", "Winner!", "Gold medal effort!", "Top tier!"] },
      { emoji: "\uD83D\uDC4F", msgs: ["Bravo!", "Fantastic work!", "Round of applause!", "Incredible!"] }
    ];

    var ALL_DONE_MESSAGES = [
      "\uD83C\uDF89 ALL CHORES DONE! You are AMAZING!",
      "\uD83C\uDFC6 EVERY SINGLE ONE! Champion!",
      "\u2B50 PERFECT SCORE! Mom and Dad are so proud!",
      "\uD83C\uDF8A CRUSHED IT! Officially awesome!",
      "\uD83D\uDC51 FINISHED! You rule!"
    ];

    var currentCountdownIndex = 0;
    var allCountdowns = [];
    var previousChoreStates = {};
    var timerEndTime = null;
    var timerLabel = "";
    var timerInterval = null;
    var timerAlarmPlaying = false;
    var timerAlarmCtx = null;
    var timerAlarmOsc = null;
    var timerAlarmTimeout = null;

    // ============ CLOCK ============
    function updateClock() {
      var now = new Date();
      document.getElementById("clock").textContent =
        now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true });
      document.getElementById("date").textContent =
        now.toLocaleDateString("en-US", { weekday: "long", month: "long", day: "numeric", year: "numeric" });
    }

    // ============ WEATHER ============
    function fetchWeather() {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "https://wttr.in/Sarasota,FL?format=j1");
      xhr.onload = function() {
        try {
          var data = JSON.parse(xhr.responseText);
          var c = data.current_condition[0];
          document.getElementById("w-temp").textContent = c.temp_F + "\u00B0";
          document.getElementById("w-desc").textContent = c.weatherDesc[0].value;
          var code = parseInt(c.weatherCode);
          var icon = "\u2600\uFE0F";
          if (code > 113 && code <= 116) icon = "\u26C5";
          if (code >= 119 && code <= 122) icon = "\u2601\uFE0F";
          if (code >= 176) icon = "\uD83C\uDF27\uFE0F";
          document.getElementById("w-icon").textContent = icon;
        } catch(e) {
          document.getElementById("w-desc").textContent = "Unavailable";
        }
      };
      xhr.onerror = function() { document.getElementById("w-desc").textContent = "Unavailable"; };
      xhr.send();
    }

    // ============ CELEBRATIONS ============
    function showEncouragement(kidName, kidColor) {
      var pick = ENCOURAGEMENTS[Math.floor(Math.random() * ENCOURAGEMENTS.length)];
      var msg = pick.msgs[Math.floor(Math.random() * pick.msgs.length)];
      var popup = document.getElementById("popup");
      document.getElementById("popup-emoji").textContent = pick.emoji;
      document.getElementById("popup-name").textContent = kidName + "!";
      document.getElementById("popup-message").textContent = msg;
      popup.querySelector(".popup-card").style.borderColor = kidColor;
      popup.querySelector(".popup-card").style.boxShadow = "0 0 60px " + kidColor + "50";
      popup.classList.add("show");
      setTimeout(function() { popup.classList.remove("show"); }, 2500);
    }

    function showAllDoneConfetti(kidName, kidColor) {
      var msg = ALL_DONE_MESSAGES[Math.floor(Math.random() * ALL_DONE_MESSAGES.length)];
      var popup = document.getElementById("popup");
      document.getElementById("popup-emoji").textContent = "\uD83C\uDF8A";
      document.getElementById("popup-name").textContent = kidName + "!";
      document.getElementById("popup-message").textContent = msg;
      popup.querySelector(".popup-card").style.borderColor = kidColor;
      popup.querySelector(".popup-card").style.boxShadow = "0 0 80px " + kidColor + "60";
      popup.classList.add("show");
      launchConfetti();
      setTimeout(function() { popup.classList.remove("show"); }, 4000);
    }

    function launchConfetti() {
      var container = document.getElementById("confetti");
      container.innerHTML = "";
      var colors = ["#03a9f4", "#ec5281", "#4ade80", "#a78bfa", "#fbbf24", "#fb923c", "#f87171", "#ffffff"];
      for (var i = 0; i < 150; i++) {
        var piece = document.createElement("div");
        piece.className = "confetti-piece";
        piece.style.left = Math.random() * 100 + "%";
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.width = (Math.random() * 8 + 4) + "px";
        piece.style.height = (Math.random() * 8 + 4) + "px";
        piece.style.borderRadius = Math.random() > 0.5 ? "50%" : "2px";
        piece.style.animationDuration = (Math.random() * 2 + 2) + "s";
        piece.style.animationDelay = (Math.random() * 0.8) + "s";
        container.appendChild(piece);
      }
      setTimeout(function() { container.innerHTML = ""; }, 5000);
    }

    // ============ KIDS ============
    function renderKids(kids) {
      var container = document.getElementById("kids-row");
      var html = "";
      for (var k = 0; k < kids.length; k++) {
        var kid = kids[k];
        var allDone = kid.total > 0 && kid.done === kid.total;
        var choreHtml = "";
        if (kid.chores.length > 0) {
          for (var c = 0; c < kid.chores.length; c++) {
            var ch = kid.chores[c];
            var emojiSpan = ch.emoji ? '<span class="chore-emoji">' + ch.emoji + '</span>' : '';
            choreHtml += '<div class="chore-item">' +
              '<div class="chore-checkbox ' + (ch.done ? "done" : "") + '">' + (ch.done ? "\u2713" : "") + '</div>' +
              emojiSpan +
              '<div class="chore-text ' + (ch.done ? "done" : "") + '">' + ch.title + '</div></div>';
          }
          if (allDone) choreHtml += '<div class="all-done-badge">\uD83C\uDFC6\u2728</div>';
        } else {
          choreHtml = '<div class="empty-chores">No chores today \uD83C\uDF89</div>';
        }

        html += '<div class="kid-card" style="border-top: 3px solid ' + kid.color + '">' +
          '<div class="kid-card-header">' +
            '<div class="kid-avatar" style="background:' + kid.color + '">' + kid.initial + '</div>' +
            '<div class="kid-name">' + kid.name + '</div>' +
            '<div class="kid-score">' + kid.done + '/' + kid.total + '</div>' +
          '</div>' +
          '<div class="kid-chores">' + choreHtml + '</div></div>';

        for (var c2 = 0; c2 < kid.chores.length; c2++) {
          var chore = kid.chores[c2];
          var key = kid.id + "-" + chore.id;
          var wasDone = previousChoreStates[key];
          if (chore.done && wasDone === false) {
            if (allDone) {
              showAllDoneConfetti(kid.name, kid.color);
            } else {
              showEncouragement(kid.name, kid.color);
            }
          }
          previousChoreStates[key] = chore.done;
        }
      }
      container.innerHTML = html;
    }

    // ============ SCHEDULE + PROGRESS ============
    function renderSchedule(schedule) {
      var container = document.getElementById("schedule-list");
      if (!schedule || schedule.length === 0) {
        container.innerHTML = '<div style="color:var(--text-muted);font-size:13px;font-style:italic;text-align:center;padding:20px">No schedule set</div>';
        updateScheduleProgress([], 0);
        return;
      }
      var now = new Date();
      var currentMinutes = now.getHours() * 60 + now.getMinutes();
      var html = "";
      var completedBlocks = 0;
      var activeBlock = null;

      for (var i = 0; i < schedule.length; i++) {
        var block = schedule[i];
        var borderColor = BORDER_COLORS[i % BORDER_COLORS.length];
        var startMin = parseTime(block.time_start);
        var endMin = block.time_end ? parseTime(block.time_end) : startMin + 30;
        var cls = "";
        var blockPct = 0;
        if (currentMinutes >= startMin && currentMinutes < endMin) {
          cls = "active";
          activeBlock = block;
          blockPct = Math.round(((currentMinutes - startMin) / (endMin - startMin)) * 100);
        } else if (currentMinutes >= endMin) {
          cls = "past";
          completedBlocks++;
          blockPct = 100;
        }
        var timeLabel = block.time_end ? formatTime12(block.time_start) + " \u2013 " + formatTime12(block.time_end) : formatTime12(block.time_start);
        var progressHtml = "";
        var pctHtml = "";
        if (cls === "active") {
          progressHtml = '<div class="block-progress-bg"><div class="block-progress-fill" style="width:' + blockPct + '%;background:linear-gradient(90deg,' + borderColor + ',var(--green))"></div></div>';
          pctHtml = '<span class="block-pct active">' + blockPct + '%</span>';
        } else if (cls === "past") {
          progressHtml = '<div class="block-progress-bg"><div class="block-progress-fill" style="width:100%;background:var(--green);opacity:0.4"></div></div>';
          pctHtml = '<span class="block-pct done">\u2713</span>';
        } else {
          progressHtml = '<div class="block-progress-bg"><div class="block-progress-fill" style="width:0%"></div></div>';
          pctHtml = '<span class="block-pct">0%</span>';
        }
        html += '<div class="schedule-block ' + cls + '" style="border-left-color:' + borderColor + '">' +
          '<div class="schedule-top-row"><div><div class="schedule-time">' + timeLabel + '</div>' +
          '<div class="schedule-title">' + (block.emoji ? block.emoji + " " : "") + block.title + '</div></div>' +
          pctHtml + '</div>' +
          progressHtml + '</div>';
      }
      container.innerHTML = html;
      updateScheduleProgress(schedule, completedBlocks, activeBlock, currentMinutes);
    }

    function updateScheduleProgress(schedule, completedBlocks, activeBlock, currentMinutes) {
      var total = schedule.length;
      if (total === 0) {
        document.getElementById("progress-pct").textContent = "0%";
        document.getElementById("progress-fill").style.width = "0%";
        document.getElementById("progress-current").textContent = "";
        return;
      }

      var pct = Math.round((completedBlocks / total) * 100);
      if (activeBlock) {
        var aStart = parseTime(activeBlock.time_start);
        var aEnd = activeBlock.time_end ? parseTime(activeBlock.time_end) : aStart + 30;
        var blockProgress = (currentMinutes - aStart) / (aEnd - aStart);
        pct = Math.round(((completedBlocks + blockProgress) / total) * 100);
      }
      pct = Math.min(100, Math.max(0, pct));

      document.getElementById("progress-pct").textContent = pct + "%";
      document.getElementById("progress-fill").style.width = pct + "%";

      if (activeBlock) {
        document.getElementById("progress-current").textContent = "Now: " + (activeBlock.emoji ? activeBlock.emoji + " " : "") + activeBlock.title;
      } else if (completedBlocks >= total) {
        document.getElementById("progress-current").textContent = "\u2705 Day complete!";
      } else {
        document.getElementById("progress-current").textContent = "";
      }
    }

    function formatTime12(str) {
      if (!str) return str;
      str = str.trim();
      // If already has AM/PM, return as-is
      if (/[AaPp][Mm]/.test(str)) return str;
      var match = str.match(/^(\d{1,2}):(\d{2})$/);
      if (!match) return str;
      var h = parseInt(match[1]);
      var m = match[2];
      var ampm = h >= 12 ? "PM" : "AM";
      if (h === 0) h = 12;
      else if (h > 12) h = h - 12;
      return h + ":" + m + " " + ampm;
    }

    function parseTime(str) {
      if (!str) return 0;
      str = str.trim().toUpperCase();
      var match = str.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/);
      if (!match) return 0;
      var h = parseInt(match[1]), m = parseInt(match[2]), ampm = match[3];
      if (ampm === "PM" && h !== 12) h += 12;
      if (ampm === "AM" && h === 12) h = 0;
      return h * 60 + m;
    }

    // ============ COUNTDOWN ============
    function renderCountdownHero(countdowns) {
      allCountdowns = countdowns || [];
      if (allCountdowns.length === 0) {
        document.getElementById("cd-emoji").textContent = "\u2728";
        document.getElementById("cd-label").textContent = "NO COUNTDOWNS";
        document.getElementById("cd-number").textContent = "\u2014";
        document.getElementById("cd-date").textContent = "";
        return;
      }
      if (currentCountdownIndex >= allCountdowns.length) currentCountdownIndex = 0;
      var cd = allCountdowns[currentCountdownIndex];
      var now = new Date();
      var target = new Date(cd.target_date + "T00:00:00");
      var days = Math.max(0, Math.ceil((target - now) / 86400000));
      document.getElementById("cd-emoji").textContent = cd.emoji || "\u23F3";
      document.getElementById("cd-label").textContent = cd.label;
      document.getElementById("cd-number").textContent = days;
      document.getElementById("cd-date").textContent = target.toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" });
    }

    function renderCountdownBar(countdowns) {
      var bar = document.getElementById("countdown-bar");
      if (!countdowns || countdowns.length === 0) { bar.innerHTML = ""; return; }
      var now = new Date();
      var html = "";
      for (var i = 0; i < countdowns.length; i++) {
        var cd = countdowns[i];
        var target = new Date(cd.target_date + "T00:00:00");
        var days = Math.max(0, Math.ceil((target - now) / 86400000));
        var active = i === currentCountdownIndex ? " active" : "";
        html += '<div class="footer-item' + active + '" onclick="selectCountdown(' + i + ')">' +
          cd.emoji + " " + cd.label + ': <span class="countdown-num">' + days + '</span> days</div>';
      }
      bar.innerHTML = html;
    }

    function selectCountdown(index) {
      currentCountdownIndex = index;
      renderCountdownHero(allCountdowns);
      renderCountdownBar(allCountdowns);
    }

    setInterval(function() {
      if (allCountdowns.length > 1) {
        currentCountdownIndex = (currentCountdownIndex + 1) % allCountdowns.length;
        renderCountdownHero(allCountdowns);
        renderCountdownBar(allCountdowns);
      }
    }, 30000);

    // ============ TIMER ============
    function fetchTimer() {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", API + "/timer");
      xhr.onload = function() {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.active && data.end_time) {
            timerEndTime = new Date(data.end_time).getTime();
            timerLabel = data.label || "Timer";
            if (!timerInterval) {
              timerInterval = setInterval(updateTimerDisplay, 100);
            }
          } else {
            timerEndTime = null;
            timerLabel = "";
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
            stopAlarm();
            document.getElementById("timer-display").textContent = "00:00";
            document.getElementById("timer-display").className = "timer-display";
            document.getElementById("timer-unit").textContent = "READY";
            document.getElementById("timer-idle").textContent = "Set from the app";
            document.getElementById("timer-idle").style.display = "";
            document.getElementById("timer-emoji").textContent = "\u23F1";
          }
        } catch(e) {}
      };
      xhr.send();
    }

    function updateTimerDisplay() {
      if (!timerEndTime) return;
      var now = Date.now();
      var remaining = timerEndTime - now;
      var display = document.getElementById("timer-display");
      var unit = document.getElementById("timer-unit");
      var idle = document.getElementById("timer-idle");
      var emoji = document.getElementById("timer-emoji");

      if (remaining <= 0) {
        display.textContent = "00:00";
        display.className = "timer-display done-flash";
        unit.textContent = "TIME IS UP!";
        idle.style.display = "none";
        emoji.textContent = "\uD83D\uDD14";
        if (!timerAlarmPlaying) {
          timerAlarmPlaying = true;
          playAlarm();
        }
        return;
      }

      display.className = "timer-display running";
      idle.style.display = "none";
      emoji.textContent = "\u23F1";

      var totalSec = Math.ceil(remaining / 1000);
      var hrs = Math.floor(totalSec / 3600);
      var mins = Math.floor((totalSec % 3600) / 60);
      var secs = totalSec % 60;

      if (hrs > 0) {
        display.textContent = pad(hrs) + ":" + pad(mins) + ":" + pad(secs);
        unit.textContent = timerLabel || "COUNTING DOWN";
      } else {
        display.textContent = pad(mins) + ":" + pad(secs);
        unit.textContent = timerLabel || "COUNTING DOWN";
      }
    }

    function pad(n) { return n < 10 ? "0" + n : "" + n; }

    // ============ ALARM SOUND ============
    function playAlarm() {
      try {
        timerAlarmCtx = new (window.AudioContext || window.webkitAudioContext)();
        var startTime = timerAlarmCtx.currentTime;
        // Play a repeating chime pattern for 15 seconds
        for (var i = 0; i < 15; i++) {
          // Two-tone chime: high then low
          var osc1 = timerAlarmCtx.createOscillator();
          var gain1 = timerAlarmCtx.createGain();
          osc1.type = "sine";
          osc1.frequency.value = 880; // A5
          gain1.gain.setValueAtTime(0.3, startTime + i);
          gain1.gain.exponentialRampToValueAtTime(0.01, startTime + i + 0.4);
          osc1.connect(gain1);
          gain1.connect(timerAlarmCtx.destination);
          osc1.start(startTime + i);
          osc1.stop(startTime + i + 0.4);

          var osc2 = timerAlarmCtx.createOscillator();
          var gain2 = timerAlarmCtx.createGain();
          osc2.type = "sine";
          osc2.frequency.value = 659; // E5
          gain2.gain.setValueAtTime(0.3, startTime + i + 0.5);
          gain2.gain.exponentialRampToValueAtTime(0.01, startTime + i + 0.9);
          osc2.connect(gain2);
          gain2.connect(timerAlarmCtx.destination);
          osc2.start(startTime + i + 0.5);
          osc2.stop(startTime + i + 0.9);
        }
        timerAlarmTimeout = setTimeout(function() {
          timerAlarmPlaying = false;
          if (timerAlarmCtx) timerAlarmCtx.close();
          timerAlarmCtx = null;
        }, 15000);
      } catch(e) {
        console.error("Alarm error:", e);
      }
    }

    function stopAlarm() {
      timerAlarmPlaying = false;
      if (timerAlarmTimeout) { clearTimeout(timerAlarmTimeout); timerAlarmTimeout = null; }
      if (timerAlarmCtx) { timerAlarmCtx.close(); timerAlarmCtx = null; }
    }

    // ============ VERSE ============
    function renderVerse(verse) {
      if (!verse) return;
      document.getElementById("verse-text").textContent = verse.text;
      document.getElementById("verse-ref").textContent = "\u2014 " + verse.ref;
    }

    // ============ DOTS ============
    function initDots(containerId) {
      var container = document.getElementById(containerId);
      for (var i = 0; i < 15; i++) {
        var dot = document.createElement("div");
        dot.className = "dot";
        dot.style.left = Math.random() * 100 + "%";
        dot.style.animationDuration = (Math.random() * 6 + 5) + "s";
        dot.style.animationDelay = (Math.random() * 8) + "s";
        dot.style.opacity = Math.random() * 0.3 + 0.1;
        dot.style.width = (Math.random() * 4 + 2) + "px";
        dot.style.height = dot.style.width;
        container.appendChild(dot);
      }
    }

    // ============ MAIN FETCH ============
    function fetchDashboard() {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", API + "/dashboard");
      xhr.onload = function() {
        try {
          var data = JSON.parse(xhr.responseText);
          renderKids(data.kids);
          renderSchedule(data.schedule);
          renderCountdownHero(data.countdowns);
          renderCountdownBar(data.countdowns);
          renderVerse(data.verse);
        } catch(e) {
          console.error("Parse error:", e);
        }
      };
      xhr.send();
    }

    // ============ PHOTO FRAME MODE ============
    var photoFrameActive = false;
    var photoList = [];
    var photoIndex = 0;
    var photoCurrentSlide = "a"; // alternates between a and b for crossfade
    var photoTransitionSec = 30;
    var photoInterval = null;
    var photoVerseData = null;
    var photoVerseVisible = false;

    var KB_OFFSETS = [
      { x: "-2%", y: "-1%" },
      { x: "2%", y: "1%" },
      { x: "-1%", y: "2%" },
      { x: "1%", y: "-2%" },
      { x: "0%", y: "-2%" },
      { x: "-2%", y: "0%" },
      { x: "2%", y: "-1%" },
      { x: "-1%", y: "-1%" },
    ];

    var modeLabels = { dashboard: "Dashboard", photos: "Photos", auto: "Auto" };
    var modeIcons = { dashboard: "\uD83D\uDDA5\uFE0F", photos: "\uD83D\uDCF7", auto: "\u2728" };
    var currentDisplayMode = "dashboard";

    function updateModeIndicator(mode) {
      var el = document.getElementById("mode-indicator");
      var iconEl = document.getElementById("mode-icon");
      var labelEl = document.getElementById("mode-label");
      if (!el) return;
      el.className = "mode-indicator mode-" + mode;
      iconEl.textContent = modeIcons[mode] || modeIcons.dashboard;
      labelEl.textContent = modeLabels[mode] || "Dashboard";
      currentDisplayMode = mode;
    }

    function checkPhotoMode() {
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/photos.php/current-mode");
      xhr.onload = function() {
        try {
          var data = JSON.parse(xhr.responseText);
          // Update mode indicator with raw setting (before auto resolution)
          var rawMode = (data.settings && data.settings.mode) ? data.settings.mode : "dashboard";
          updateModeIndicator(rawMode);
          if (data.mode === "photos" && !photoFrameActive) {
            startPhotoFrame(data.settings);
          } else if (data.mode === "dashboard" && photoFrameActive) {
            stopPhotoFrame();
          }
          if (data.settings && data.settings.transition_seconds) {
            photoTransitionSec = data.settings.transition_seconds;
          }
        } catch(e) {}
      };
      xhr.send();
    }

    function startPhotoFrame(settings) {
      // Fetch photo list first
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "/photos.php/list");
      xhr.onload = function() {
        try {
          var data = JSON.parse(xhr.responseText);
          if (data.photos && data.photos.length > 0) {
            photoList = shuffleArray(data.photos);
            photoIndex = 0;
            photoFrameActive = true;
            document.getElementById("photo-frame").classList.add("active");
            showNextPhoto();
            if (photoInterval) clearInterval(photoInterval);
            photoInterval = setInterval(showNextPhoto, photoTransitionSec * 1000);
            updatePhotoOverlays();
          }
        } catch(e) {}
      };
      xhr.send();
    }

    function stopPhotoFrame() {
      photoFrameActive = false;
      document.getElementById("photo-frame").classList.remove("active");
      if (photoInterval) { clearInterval(photoInterval); photoInterval = null; }
    }

    function showNextPhoto() {
      if (photoList.length === 0) return;
      
      var url = photoList[photoIndex];
      // Ensure high-res
      if (url.indexOf("=w") === -1) url = url + "=w3840-h2160-no";
      photoIndex = (photoIndex + 1) % photoList.length;
      
      // Re-shuffle when we've gone through all
      if (photoIndex === 0 && photoList.length > 1) {
        photoList = shuffleArray(photoList);
      }
      
      var slideA = document.getElementById("photo-slide-a");
      var slideB = document.getElementById("photo-slide-b");
      
      var incoming, outgoing;
      if (photoCurrentSlide === "a") {
        incoming = slideB;
        outgoing = slideA;
        photoCurrentSlide = "b";
      } else {
        incoming = slideA;
        outgoing = slideB;
        photoCurrentSlide = "a";
      }
      
      // Set Ken Burns random direction
      var kb = KB_OFFSETS[Math.floor(Math.random() * KB_OFFSETS.length)];
      incoming.style.setProperty("--kb-x", kb.x);
      incoming.style.setProperty("--kb-y", kb.y);
      
      // Preload image
      var img = new Image();
      img.onload = function() {
        incoming.style.backgroundImage = 'url("' + url + '")';
        incoming.className = "photo-slide visible ken-burns";
        incoming.style.animationDuration = photoTransitionSec + "s";
        
        // Fade out old slide after crossfade
        setTimeout(function() {
          outgoing.classList.remove("visible");
        }, 2000);
        
        // Reset animation on outgoing for next use
        setTimeout(function() {
          outgoing.className = "photo-slide";
          outgoing.style.backgroundImage = "";
        }, 2500);
      };
      img.onerror = function() {
        // Skip bad photos
        if (photoList.length > 1) showNextPhoto();
      };
      img.src = url;
      
      // Toggle verse visibility (show verse every 3rd photo)
      if (photoIndex % 3 === 0 && photoVerseData) {
        document.getElementById("photo-verse-text").textContent = "\u201C" + photoVerseData.text + "\u201D";
        document.getElementById("photo-verse-ref").textContent = "\u2014 " + photoVerseData.ref;
        document.getElementById("photo-verse").classList.add("visible");
        setTimeout(function() {
          document.getElementById("photo-verse").classList.remove("visible");
        }, (photoTransitionSec - 5) * 1000);
      }
    }

    function updatePhotoOverlays() {
      if (!photoFrameActive) return;
      
      var now = new Date();
      document.getElementById("photo-clock").textContent =
        now.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit", hour12: true });
      document.getElementById("photo-date").textContent =
        now.toLocaleDateString("en-US", { weekday: "long", month: "long", day: "numeric" });
      
      // Copy weather from main dashboard
      var mainTemp = document.getElementById("w-temp").textContent;
      var mainIcon = document.getElementById("w-icon").textContent;
      var mainDesc = document.getElementById("w-desc").textContent;
      document.getElementById("photo-w-temp").textContent = mainTemp;
      document.getElementById("photo-w-icon").textContent = mainIcon;
      document.getElementById("photo-w-desc").textContent = mainDesc;
    }

    function shuffleArray(arr) {
      var shuffled = arr.slice();
      for (var i = shuffled.length - 1; i > 0; i--) {
        var j = Math.floor(Math.random() * (i + 1));
        var temp = shuffled[i];
        shuffled[i] = shuffled[j];
        shuffled[j] = temp;
      }
      return shuffled;
    }

    // Store verse data for photo overlay
    var origRenderVerse = renderVerse;
    renderVerse = function(verse) {
      photoVerseData = verse;
      origRenderVerse(verse);
    };

    // Init
    updateClock();
    fetchWeather();
    fetchDashboard();
    fetchTimer();
    initDots("countdown-dots");
    initDots("timer-dots");
    checkPhotoMode();

    setInterval(updateClock, 1000);
    setInterval(fetchDashboard, 15000);
    setInterval(fetchWeather, 600000);
    setInterval(fetchTimer, 5000);
    setInterval(checkPhotoMode, 5000); // Check mode every 5s for responsive switching
    setInterval(updatePhotoOverlays, 1000); // Update photo clock every second
  </script>
</body>
</html>
