import { io } from "socket.io-client";
import { ref, computed } from "vue";

type ConnectionState = "connected" | "connecting" | "disconnected" | "error";

const RECONNECTION_ATTEMPTS = 5;

export const socketState = {
  connected: ref(false),
  status: ref<ConnectionState>("connecting"),
  lastError: ref<string | null>(null),
  id: ref<string | null>(null),
  reconnectAttemptsLeft: ref<number>(RECONNECTION_ATTEMPTS),
};

export const isSocketReady = computed(() => socketState.connected.value);

export const instance = io(import.meta.env.VITE_SOCKET_URL, {
  auth: { token: import.meta.env.VITE_SOCKET_TOKEN },
  withCredentials: true,
  transports: ["websocket"],
  reconnectionAttempts: RECONNECTION_ATTEMPTS,
});

instance.on("connect", () => {
  socketState.connected.value = true;
  socketState.status.value = "connected";
  socketState.lastError.value = null;
  socketState.id.value = instance.id ?? null;
  socketState.reconnectAttemptsLeft.value = RECONNECTION_ATTEMPTS;

  console.log("Connected to socket Successfully");
});

instance.on("disconnect", () => {
  socketState.connected.value = false;
  socketState.status.value = "disconnected";
  socketState.id.value = null;
});

instance.on("connect_error", (err: any) => {
  socketState.connected.value = false;
  socketState.status.value = "error";
  socketState.lastError.value = err?.message ?? String(err);
});

// socket.io-client manager events (reconnect attempts)
instance.io.on("reconnect_attempt", (attempt) => {
  socketState.status.value = "connecting";
  socketState.reconnectAttemptsLeft.value = Math.max(
    0,
    RECONNECTION_ATTEMPTS - attempt
  );
});

instance.io.on("reconnect_failed", () => {
  socketState.status.value = "error";
  socketState.reconnectAttemptsLeft.value = 0;
});
