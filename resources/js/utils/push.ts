import axios from "axios";

function urlBase64ToUint8Array(base64String: string) {
  const cleaned = (base64String || "").trim();
  const padding = "=".repeat((4 - (cleaned.length % 4)) % 4);
  const base64 = (cleaned + padding).replace(/-/g, "+").replace(/_/g, "/");

  const raw = atob(base64);
  const out = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
  return out;
}
async function sha256Base64(input: string) {
  const data = new TextEncoder().encode(input);
  const hash = await crypto.subtle.digest("SHA-256", data);
  const bytes = new Uint8Array(hash);
  let bin = "";
  for (const b of bytes) bin += String.fromCharCode(b);
  return btoa(bin);
}

export async function initPush() {
  console.debug("initPush");

  if (!("serviceWorker" in navigator)) return { ok: false, reason: "no_sw" };
  if (!("PushManager" in window)) return { ok: false, reason: "no_push_support" };

  await navigator.serviceWorker.register("/service-worker.js", { scope: "/" });
  const reg = await navigator.serviceWorker.ready;

  if (Notification.permission === "denied") return { ok: false, reason: "permission_denied" };

  if (Notification.permission === "default") {
    const permission = await Notification.requestPermission();
    if (permission !== "granted") return { ok: false, reason: permission };
  }

  const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
  const vapidHash = await sha256Base64(vapidKey);

  let sub = await reg.pushManager.getSubscription();

  // Ask server if this subscription is acceptable for current VAPID
  if (sub) {
    const res = await axios.post("/push/subscribe", {
      subscription: sub.toJSON(),
      vapid_public_hash: vapidHash,
      origin: location.origin,
    });

    // If server says it doesn't match, unsubscribe + resubscribe
    if (res.data?.needs_resubscribe) {
      await sub.unsubscribe();
      sub = null;
    } else {
      return { ok: true, reused: true };
    }
  }

  try {
    sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });

    await axios.post("/push/subscribe", {
      subscription: sub.toJSON(),
      vapid_public_hash: vapidHash,
      origin: location.origin,
    });

    return { ok: true, reused: false };
  } catch (error: any) {
    console.error("Push subscribe failed:", {
      name: error?.name,
      message: error?.message,
      stack: error?.stack,
    });
    return { ok: false, reason: "subscribe_failed", error };
  }
}


