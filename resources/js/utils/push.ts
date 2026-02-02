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

export async function initPush() {
  console.debug("initPush");

  // Hard guards
  if (!("serviceWorker" in navigator)) {
    return { ok: false, reason: "no_sw" };
  }
  if (!("PushManager" in window)) {
    return { ok: false, reason: "no_push_support" };
  }

  // Register SW
  await navigator.serviceWorker.register("/service-worker.js", { scope: "/" });
  const reg = await navigator.serviceWorker.ready;

  // Permission handling
  if (Notification.permission === "denied") {
    return { ok: false, reason: "permission_denied" };
  }

  if (Notification.permission === "default") {
    const permission = await Notification.requestPermission();
    if (permission !== "granted") {
      return { ok: false, reason: permission };
    }
  }

  // Reuse existing subscription if present
  let sub = await reg.pushManager.getSubscription();
  if (sub) {
    await axios.post("/push/subscribe", sub.toJSON());
    return { ok: true, reused: true };
  }

  // Create new subscription
  try {
    const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;

    sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });

    await axios.post("/push/subscribe", sub.toJSON());
    return { ok: true, reused: false };

  } catch (error) {
    console.error("Push subscribe failed:", {
        name: error?.name,
        message: error?.message,
        stack: error?.stack,
    });

    return { ok: false, reason: "subscribe_failed", error };
  }

}

