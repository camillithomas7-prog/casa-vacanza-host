<?php
// Frontend bootstrap for Web Push: registers the service worker
// and exposes a global window.cvPush API for the toggle button.
?>
<script>
(function(){
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

  const u8 = (b64) => {
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    const b = atob((b64 + pad).replace(/-/g,'+').replace(/_/g,'/'));
    const arr = new Uint8Array(b.length);
    for (let i=0;i<b.length;i++) arr[i] = b.charCodeAt(i);
    return arr;
  };

  let swReg = null;
  let vapidKey = null;

  async function ensureSW() {
    if (swReg) return swReg;
    swReg = await navigator.serviceWorker.register('/sw.js');
    return swReg;
  }

  async function getVapid() {
    if (vapidKey) return vapidKey;
    const r = await fetch('/api/push-vapid.php', { credentials: 'same-origin' });
    const d = await r.json();
    vapidKey = d.publicKey;
    return vapidKey;
  }

  async function getSubscription() {
    const r = await ensureSW();
    return await r.pushManager.getSubscription();
  }

  async function subscribe() {
    const reg = await ensureSW();
    const perm = await Notification.requestPermission();
    if (perm !== 'granted') throw new Error('permission_denied');
    const vp = await getVapid();
    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: u8(vp),
    });
    const r = await fetch('/api/push-subscribe.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sub.toJSON()),
    });
    if (!r.ok) throw new Error('save_failed');
    return sub;
  }

  async function unsubscribe() {
    const sub = await getSubscription();
    if (!sub) return;
    await fetch('/api/push-unsubscribe.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ endpoint: sub.endpoint }),
    });
    await sub.unsubscribe();
  }

  async function sendTest() {
    const r = await fetch('/api/push-test.php', { method: 'POST', credentials: 'same-origin' });
    return await r.json();
  }

  async function status() {
    if (!('Notification' in window)) return { supported: false };
    const perm = Notification.permission;
    let active = false;
    try {
      const sub = await getSubscription();
      active = !!sub;
    } catch(e) {}
    return { supported: true, permission: perm, active };
  }

  window.cvPush = { subscribe, unsubscribe, sendTest, status };

  // Auto-register the SW on page load (without asking for permission)
  ensureSW().catch(()=>{});
})();
</script>
