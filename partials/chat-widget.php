<?php
// Widget chat "Sofia" — incluso dal site-footer su tutte le pagine pubbliche
$chatEnabled = (bool) setting('openai_api_key', '');
if (!$chatEnabled) return;
$chatName = setting('chat_assistant_name', 'Sofia');
$chatPhone = setting('contact_phone', cfg('site.phone'));
$chatLang = function_exists('currentLang') ? currentLang() : 'it';

$L = [
    'it' => [
        'status' => 'In linea · Risponde di solito subito',
        'placeholder' => 'Scrivi un messaggio...',
        'aria_open' => 'Apri chat',
        'aria_close' => 'Chiudi',
        'greet_morning' => 'Buongiorno',
        'greet_day' => 'Ciao',
        'greet_evening' => 'Buonasera',
        'greet_intro' => "! Sono {NAME}, lavoro con Patrizia 😊\nDimmi due cose veloci e ti propongo subito 2-3 appartamenti giusti per te:\n• **Quando** vorresti venire a Sharm?\n• **In quanti** siete?",
        'q1_label' => '📅 Giugno · 2 persone', 'q1_msg' => 'Cosa avete a giugno per 2 persone?',
        'q2_label' => '🌊 Vista mare', 'q2_msg' => 'Avete un appartamento vista mare sotto i 400€ a settimana?',
        'q3_label' => '👨‍👩‍👧 Famiglia', 'q3_msg' => 'Cerco qualcosa per famiglia con piscina',
        'q4_label' => '💰 Economici', 'q4_msg' => 'Quali sono i 3 più economici?',
        'err_conn' => 'Mi spiace, ho avuto un problema di connessione. Riprova tra poco o scrivimi su WhatsApp al ',
    ],
    'en' => [
        'status' => 'Online · Usually replies right away',
        'placeholder' => 'Type a message...',
        'aria_open' => 'Open chat',
        'aria_close' => 'Close',
        'greet_morning' => 'Good morning',
        'greet_day' => 'Hi',
        'greet_evening' => 'Good evening',
        'greet_intro' => "! I'm {NAME}, I work with Patrizia 😊\nTell me two quick things and I'll suggest 2-3 great apartments for you:\n• **When** would you like to come to Sharm?\n• **How many** people are you?",
        'q1_label' => '📅 June · 2 people', 'q1_msg' => 'What do you have available in June for 2 people?',
        'q2_label' => '🌊 Sea view', 'q2_msg' => 'Do you have a sea view apartment under €400 per week?',
        'q3_label' => '👨‍👩‍👧 Family', 'q3_msg' => 'I am looking for a family apartment with a pool',
        'q4_label' => '💰 Cheapest', 'q4_msg' => 'What are your 3 cheapest apartments?',
        'err_conn' => "Sorry, I'm having a connection issue. Try again shortly or message me on WhatsApp at ",
    ],
    'de' => [
        'status' => 'Online · Antwortet meist sofort',
        'placeholder' => 'Nachricht schreiben...',
        'aria_open' => 'Chat öffnen',
        'aria_close' => 'Schließen',
        'greet_morning' => 'Guten Morgen',
        'greet_day' => 'Hallo',
        'greet_evening' => 'Guten Abend',
        'greet_intro' => "! Ich bin {NAME}, ich arbeite mit Patrizia 😊\nSag mir kurz zwei Dinge und ich schlage dir 2-3 passende Wohnungen vor:\n• **Wann** möchtest du nach Sharm kommen?\n• **Wie viele** Personen seid ihr?",
        'q1_label' => '📅 Juni · 2 Personen', 'q1_msg' => 'Was habt ihr im Juni für 2 Personen verfügbar?',
        'q2_label' => '🌊 Meerblick', 'q2_msg' => 'Habt ihr eine Wohnung mit Meerblick unter 400€ pro Woche?',
        'q3_label' => '👨‍👩‍👧 Familie', 'q3_msg' => 'Ich suche etwas für die Familie mit Pool',
        'q4_label' => '💰 Günstig', 'q4_msg' => 'Was sind eure 3 günstigsten Wohnungen?',
        'err_conn' => 'Entschuldigung, ich habe ein Verbindungsproblem. Versuch es gleich noch einmal oder schreib mir auf WhatsApp unter ',
    ],
    'es' => [
        'status' => 'En línea · Suele responder enseguida',
        'placeholder' => 'Escribe un mensaje...',
        'aria_open' => 'Abrir chat',
        'aria_close' => 'Cerrar',
        'greet_morning' => 'Buenos días',
        'greet_day' => 'Hola',
        'greet_evening' => 'Buenas tardes',
        'greet_intro' => "! Soy {NAME}, trabajo con Patrizia 😊\nDime dos cosas rápidas y te propongo 2-3 apartamentos ideales:\n• **¿Cuándo** te gustaría venir a Sharm?\n• **¿Cuántas** personas sois?",
        'q1_label' => '📅 Junio · 2 personas', 'q1_msg' => '¿Qué tenéis disponible en junio para 2 personas?',
        'q2_label' => '🌊 Vista mar', 'q2_msg' => '¿Tenéis un apartamento con vistas al mar por menos de 400€ semana?',
        'q3_label' => '👨‍👩‍👧 Familia', 'q3_msg' => 'Busco algo para familia con piscina',
        'q4_label' => '💰 Económicos', 'q4_msg' => '¿Cuáles son los 3 más económicos?',
        'err_conn' => 'Lo siento, tengo un problema de conexión. Inténtalo de nuevo o escríbeme por WhatsApp al ',
    ],
    'ru' => [
        'status' => 'В сети · Обычно отвечает сразу',
        'placeholder' => 'Напишите сообщение...',
        'aria_open' => 'Открыть чат',
        'aria_close' => 'Закрыть',
        'greet_morning' => 'Доброе утро',
        'greet_day' => 'Привет',
        'greet_evening' => 'Добрый вечер',
        'greet_intro' => "! Я {NAME}, работаю с Патрицией 😊\nСкажите быстро две вещи, и я предложу 2-3 подходящие квартиры:\n• **Когда** хотите приехать в Шарм?\n• **Сколько** вас человек?",
        'q1_label' => '📅 Июнь · 2 чел.', 'q1_msg' => 'Что есть в июне на 2 человек?',
        'q2_label' => '🌊 Вид на море', 'q2_msg' => 'Есть квартира с видом на море до 400€ в неделю?',
        'q3_label' => '👨‍👩‍👧 Семья', 'q3_msg' => 'Ищу что-то для семьи с бассейном',
        'q4_label' => '💰 Бюджетные', 'q4_msg' => 'Какие 3 самые недорогие?',
        'err_conn' => 'Извините, проблема со связью. Попробуйте снова или напишите мне в WhatsApp: ',
    ],
];
$t = $L[$chatLang] ?? $L['it'];
?>
<style>
.cv-chat-btn{position:fixed;bottom:20px;right:20px;z-index:60;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#f97316,#fb923c);box-shadow:0 10px 30px -8px rgba(249,115,22,.55);display:flex;align-items:center;justify-content:center;color:#fff;cursor:pointer;border:none;transition:transform .2s}
.cv-chat-btn:hover{transform:scale(1.08)}
.cv-chat-btn .cv-pulse{position:absolute;inset:-4px;border-radius:50%;background:#f97316;opacity:.35;animation:cv-pulse 2s ease-out infinite}
@keyframes cv-pulse{0%{transform:scale(1);opacity:.35}100%{transform:scale(1.4);opacity:0}}
.cv-chat-dot{position:absolute;top:6px;right:6px;width:12px;height:12px;border-radius:50%;background:#10b981;border:2px solid #fff}
.cv-chat-panel{position:fixed;bottom:90px;right:20px;z-index:60;width:380px;max-width:calc(100vw - 28px);height:560px;max-height:calc(100vh - 120px);background:#fff;border-radius:20px;box-shadow:0 25px 60px -15px rgba(0,0,0,.3),0 0 0 1px rgba(0,0,0,.05);display:none;flex-direction:column;overflow:hidden;font-family:inherit}
.cv-chat-panel.open{display:flex;animation:cv-slide .25s cubic-bezier(.16,1,.3,1)}
@keyframes cv-slide{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.cv-chat-head{padding:18px 18px 14px;background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;display:flex;align-items:center;gap:12px}
.cv-chat-avatar{width:44px;height:44px;border-radius:50%;background:#fff;color:#f97316;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:18px;flex-shrink:0;position:relative;font-family:'Cormorant Garamond',Georgia,serif}
.cv-chat-avatar::after{content:"";position:absolute;bottom:-1px;right:-1px;width:13px;height:13px;border-radius:50%;background:#10b981;border:2px solid #fff}
.cv-chat-head-name{font-weight:700;font-size:15px;line-height:1.2}
.cv-chat-head-sub{font-size:11px;opacity:.85;margin-top:2px}
.cv-chat-close{margin-left:auto;background:rgba(255,255,255,.18);border:none;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:20px;line-height:1}
.cv-chat-close:hover{background:rgba(255,255,255,.3)}
.cv-chat-body{flex:1;overflow-y:auto;padding:14px 14px 8px;background:#fafafa;display:flex;flex-direction:column;gap:8px}
.cv-msg{max-width:80%;padding:10px 14px;border-radius:18px;font-size:14px;line-height:1.45;animation:cv-msg .2s ease-out;word-wrap:break-word}
@keyframes cv-msg{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.cv-msg.bot{background:#fff;color:#1f2937;border:1px solid #e5e7eb;border-bottom-left-radius:6px;align-self:flex-start}
.cv-msg.user{background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;border-bottom-right-radius:6px;align-self:flex-end}
.cv-msg a{color:inherit;text-decoration:underline}
.cv-typing{align-self:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:18px;border-bottom-left-radius:6px;padding:12px 16px;display:flex;gap:4px}
.cv-typing span{width:7px;height:7px;border-radius:50%;background:#94a3b8;animation:cv-bounce 1s infinite}
.cv-typing span:nth-child(2){animation-delay:.15s}
.cv-typing span:nth-child(3){animation-delay:.3s}
@keyframes cv-bounce{0%,80%,100%{transform:scale(.7);opacity:.5}40%{transform:scale(1);opacity:1}}
.cv-chat-input{padding:12px 14px;background:#fff;border-top:1px solid #e5e7eb;display:flex;gap:8px;align-items:flex-end}
.cv-chat-input textarea{flex:1;border:1px solid #e5e7eb;border-radius:14px;padding:10px 14px;resize:none;font:inherit;font-size:14px;max-height:100px;outline:none;color:#1f2937;background:#fff}
.cv-chat-input textarea:focus{border-color:#f97316}
.cv-chat-send{width:42px;height:42px;border-radius:50%;border:none;background:linear-gradient(135deg,#f97316,#fb923c);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .15s}
.cv-chat-send:hover{transform:scale(1.06)}
.cv-chat-send:disabled{opacity:.5;cursor:not-allowed;transform:none}
.cv-chat-quick{padding:0 14px 10px;display:flex;gap:6px;flex-wrap:wrap;background:#fafafa}
.cv-quick-btn{font-size:12px;padding:6px 10px;border-radius:12px;background:#fff;border:1px solid #e5e7eb;color:#475569;cursor:pointer;transition:all .15s}
.cv-quick-btn:hover{border-color:#f97316;color:#f97316}
.cv-card{margin-top:6px;border-radius:14px;border:1px solid #e5e7eb;overflow:hidden;background:#fff;display:block;text-decoration:none;color:inherit}
.cv-card img{width:100%;height:90px;object-fit:cover;display:block}
.cv-card-body{padding:8px 10px}
.cv-card-title{font-weight:600;font-size:13px;color:#1f2937;line-height:1.3}
.cv-card-meta{font-size:11px;color:#64748b;margin-top:2px}
@media (max-width:480px){.cv-chat-panel{bottom:0;right:0;width:100vw;height:100vh;max-height:100vh;border-radius:0}}
</style>

<button id="cv-chat-btn" class="cv-chat-btn" aria-label="<?= e($t['aria_open']) ?>">
  <span class="cv-pulse"></span>
  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="position:relative"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
</button>

<div id="cv-chat-panel" class="cv-chat-panel" role="dialog" aria-label="Chat">
  <div class="cv-chat-head">
    <div class="cv-chat-avatar"><?= e(mb_substr($chatName, 0, 1)) ?></div>
    <div>
      <div class="cv-chat-head-name"><?= e($chatName) ?></div>
      <div class="cv-chat-head-sub"><?= e($t['status']) ?></div>
    </div>
    <button class="cv-chat-close" id="cv-chat-close" aria-label="<?= e($t['aria_close']) ?>">×</button>
  </div>
  <div class="cv-chat-body" id="cv-chat-body"></div>
  <div class="cv-chat-quick" id="cv-chat-quick">
    <button class="cv-quick-btn" data-msg="<?= e($t['q1_msg']) ?>"><?= e($t['q1_label']) ?></button>
    <button class="cv-quick-btn" data-msg="<?= e($t['q2_msg']) ?>"><?= e($t['q2_label']) ?></button>
    <button class="cv-quick-btn" data-msg="<?= e($t['q3_msg']) ?>"><?= e($t['q3_label']) ?></button>
    <button class="cv-quick-btn" data-msg="<?= e($t['q4_msg']) ?>"><?= e($t['q4_label']) ?></button>
  </div>
  <form class="cv-chat-input" id="cv-chat-form">
    <textarea id="cv-chat-text" placeholder="<?= e($t['placeholder']) ?>" rows="1"></textarea>
    <button type="submit" class="cv-chat-send" id="cv-chat-send" aria-label="Invia">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
    </button>
  </form>
</div>

<script>
(function(){
  const btn = document.getElementById('cv-chat-btn');
  const panel = document.getElementById('cv-chat-panel');
  const closeBtn = document.getElementById('cv-chat-close');
  const body = document.getElementById('cv-chat-body');
  const form = document.getElementById('cv-chat-form');
  const text = document.getElementById('cv-chat-text');
  const sendBtn = document.getElementById('cv-chat-send');
  const quickBox = document.getElementById('cv-chat-quick');
  const NAME = <?= json_encode($chatName) ?>;
  const LANG = <?= json_encode($chatLang) ?>;
  const T = <?= json_encode([
      'greet_morning' => $t['greet_morning'],
      'greet_day' => $t['greet_day'],
      'greet_evening' => $t['greet_evening'],
      'greet_intro' => $t['greet_intro'],
      'err_conn' => $t['err_conn'],
  ], JSON_UNESCAPED_UNICODE) ?>;
  const STORAGE_KEY = 'cv_chat_v1_' + LANG;
  const SESSION_KEY = 'cv_chat_session';
  function genSid(){ return 'sid-' + Date.now().toString(36) + '-' + Math.random().toString(36).substr(2,12); }
  let SID = localStorage.getItem(SESSION_KEY);
  if (!SID || !/^sid-[a-z0-9\-]{10,}$/.test(SID)) { SID = genSid(); localStorage.setItem(SESSION_KEY, SID); }

  function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function md(s){
    s = esc(s);
    s = s.replace(/\*\*(.+?)\*\*/g,'<b>$1</b>');
    s = s.replace(/\*(.+?)\*/g,'<i>$1</i>');
    s = s.replace(/\[([^\]]+)\]\(([^)]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>');
    s = s.replace(/\n/g,'<br>');
    return s;
  }

  let history = [];
  try { history = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]'); } catch(e){}

  function save(){ try{sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history));}catch(e){} }
  function scrollDown(){ body.scrollTop = body.scrollHeight; }

  function addMsg(role, content, cards){
    const d = document.createElement('div');
    d.className = 'cv-msg ' + (role === 'user' ? 'user' : 'bot');
    d.innerHTML = md(content);
    if (cards && cards.length){
      cards.forEach(c => {
        const a = document.createElement('a');
        a.className = 'cv-card';
        a.href = c.url;
        a.target = '_blank';
        a.rel = 'noopener';
        a.innerHTML = (c.image ? `<img src="${esc(c.image)}" alt="">` : '') + `<div class="cv-card-body"><div class="cv-card-title">${esc(c.title)}</div><div class="cv-card-meta">${esc(c.meta||'')}</div></div>`;
        d.appendChild(a);
      });
    }
    body.appendChild(d);
    scrollDown();
  }

  function renderHistory(){
    body.innerHTML = '';
    if (!history.length) {
      const hour = new Date().getHours();
      const greet = hour < 12 ? T.greet_morning : (hour < 19 ? T.greet_day : T.greet_evening);
      addMsg('assistant', greet + T.greet_intro.replace('{NAME}', NAME));
    } else {
      history.forEach(m => addMsg(m.role, m.content, m.cards));
    }
  }

  function showTyping(){
    const t = document.createElement('div');
    t.className = 'cv-typing';
    t.id = 'cv-typing';
    t.innerHTML = '<span></span><span></span><span></span>';
    body.appendChild(t);
    scrollDown();
  }
  function hideTyping(){ const t = document.getElementById('cv-typing'); if(t) t.remove(); }

  async function send(msg){
    if (!msg.trim()) return;
    addMsg('user', msg);
    history.push({role:'user', content: msg});
    save();
    text.value = '';
    text.style.height = 'auto';
    sendBtn.disabled = true;
    showTyping();
    try {
      const r = await fetch('/api/chat.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({history: history.slice(-12), session_id: SID, lang: document.documentElement.lang || 'it'})
      });
      const d = await r.json();
      hideTyping();
      if (d.error){ addMsg('assistant', d.error); }
      else {
        addMsg('assistant', d.reply, d.cards);
        history.push({role:'assistant', content: d.reply, cards: d.cards || null});
        save();
      }
    } catch(e){
      hideTyping();
      addMsg('assistant', T.err_conn + '<?= e($chatPhone) ?>.');
    } finally {
      sendBtn.disabled = false;
      text.focus();
    }
  }

  btn.addEventListener('click', () => {
    panel.classList.toggle('open');
    if (panel.classList.contains('open')){ renderHistory(); setTimeout(()=>text.focus(),100); }
  });
  closeBtn.addEventListener('click', () => panel.classList.remove('open'));
  form.addEventListener('submit', e => { e.preventDefault(); send(text.value); });
  text.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); send(text.value); } });
  text.addEventListener('input', () => { text.style.height = 'auto'; text.style.height = Math.min(100, text.scrollHeight) + 'px'; });
  quickBox.addEventListener('click', e => {
    const b = e.target.closest('.cv-quick-btn');
    if (b) send(b.dataset.msg);
  });
})();
</script>
