// /composables/useSSE.ts
import { ref, onUnmounted } from 'vue';

export type SSEHandler = (payload: string, ev: MessageEvent) => void;

export type UseSSEOptions = {
  // defaults you can set once
  event?: string;                           // default "message"
  endSignal?: string;                       // e.g. "</stream>"
  glue?: string;                            // joiner for text chunking
  autoReconnect?: boolean;                  // default true
  reconnectDelayMs?: number;                // base backoff
  maxReconnectDelayMs?: number;             // cap
  withCredentials?: boolean;                // default true
  handlers?: Record<string, SSEHandler>;    // initial handlers, "*" allowed
};

export type StartOverrides = {
  event?: string;
  endSignal?: string;
  glue?: string;
  autoReconnect?: boolean;
  withCredentials?: boolean;
};

export function useSSE(opts: UseSSEOptions = {}) {
  // state refs
  const data = ref('');
  const isOpen = ref(false);
  const isComplete = ref(false);
  const error = ref<Event | null>(null);
  const lastEvent = ref<MessageEvent | null>(null);
  const lastEventId = ref<string | null>(null);
  const currentUrl = ref<string | null>(null);

  // runtime-configurable
  let es: EventSource | null = null;
  let reconnectTimer: number | null = null;
  let currentDelay = opts.reconnectDelayMs ?? 800;
  let allowReconnect = opts.autoReconnect ?? true;
  let started = false;

  // mutable “current” connection config
  let currentPath: string | null = null;
  let currentParams: Record<string, string | number | boolean> | null = null;
  let currentEvent = opts.event ?? 'message';
  let currentEndSignal = opts.endSignal ?? '</stream>';
  let currentGlue = opts.glue ?? '';
  let currentWithCredentials = opts.withCredentials ?? true;

  // handler registry (supports "*" catch-all)
  const handlers: Record<string, SSEHandler> = { ...(opts.handlers ?? {}) };
  const on = (evt: string, handler: SSEHandler) => { handlers[evt] = handler; };
  const off = (evt: string) => { delete handlers[evt]; };

  const buildUrl = () => {
    if (!currentPath) throw new Error('useSSE.start(path, ...) was not called with a valid path');
    const url = new URL(currentPath, window.location.origin);
    if (currentParams) for (const [k, v] of Object.entries(currentParams)) url.searchParams.set(k, String(v));
    currentUrl.value = url.toString();
    return currentUrl.value;
  };

  const cleanup = () => {
    if (reconnectTimer) { window.clearTimeout(reconnectTimer); reconnectTimer = null; }
    if (es) { es.close(); es = null; }
  };

  const stop = () => {
    allowReconnect = false; // kill future retries
    cleanup();
    isOpen.value = false;
    started = false;
  };

  const callHandlers = (evtName: string, payload: string, ev: MessageEvent) => {
    handlers[evtName]?.(payload, ev);
    handlers['*']?.(payload, ev);
  };

  const dispatch = (ev: MessageEvent) => {
    lastEvent.value = ev;
    if (ev.lastEventId) lastEventId.value = ev.lastEventId;

    const raw = String(ev.data ?? '');
    if (currentEndSignal && raw.includes(currentEndSignal)) {
      const trimmed = raw.replace(currentEndSignal, '');
      data.value = currentGlue ? (data.value ? data.value + currentGlue + trimmed : trimmed) : (trimmed || data.value);
      console.log('SSE received end signal, closing connection.', ev.type);
      callHandlers(ev.type, trimmed, ev);
      callHandlers('complete', trimmed, ev);
      isComplete.value = true;
      stop();
      return;
    }

    data.value = currentGlue ? (data.value ? data.value + currentGlue + raw : raw) : raw;
    callHandlers(ev.type, raw, ev);
  };

  const attachKnownEventListeners = () => {
    if (!es) return;
    // always listen to generic "message"
    es.addEventListener('message', dispatch);
    // default custom event
    if (currentEvent && currentEvent !== 'message') es.addEventListener(currentEvent, dispatch);
    // plus any registered handler keys (except "*" which is virtual)
    Object.keys(handlers).forEach(k => {
      if (k !== '*' && k !== 'message') es?.addEventListener(k, dispatch);
    });
  };

  const start = (
    path?: string,
    params?: Record<string, string | number | boolean>,
    overrides?: StartOverrides
  ) => {
    // update runtime config if provided
    if (path) currentPath = path;
    if (params) currentParams = params;
    if (overrides) {
      if (overrides.event !== undefined) currentEvent = overrides.event;
      if (overrides.endSignal !== undefined) currentEndSignal = overrides.endSignal;
      if (overrides.glue !== undefined) currentGlue = overrides.glue;
      if (overrides.autoReconnect !== undefined) allowReconnect = overrides.autoReconnect;
      if (overrides.withCredentials !== undefined) currentWithCredentials = overrides.withCredentials;
    }

    if (!currentPath) throw new Error('Missing path. Call start("/your/stream", ...)');

    if (started) {
      // restart with new params/config
      cleanup();
      started = false;
    }

    isComplete.value = false;
    error.value = null;
    data.value = data.value; // keep accumulated unless you want to reset here

    const url = buildUrl();
    es = new EventSource(url, { withCredentials: currentWithCredentials });
    started = true;

    es.addEventListener('open', () => {
      isOpen.value = true;
      currentDelay = opts.reconnectDelayMs ?? 800;
    });

    attachKnownEventListeners();

    es.addEventListener('error', (ev) => {
      error.value = ev;
      isOpen.value = false;
      if (!allowReconnect || isComplete.value) {
        cleanup();
        started = false;
        return;
      }
      cleanup();
      reconnectTimer = window.setTimeout(() => {
        started = false;
        start(); // reuse currentPath/params/config
      }, currentDelay);
      currentDelay = Math.min(currentDelay * 2, (opts.maxReconnectDelayMs ?? 8000));
    });
  };

  // utilities if you want to tweak after starting
  const updateParams = (next: Record<string, string | number | boolean>, restart = true) => {
    currentParams = next;
    if (started && restart) start(); // reconnect with new query
  };
  const updatePath = (nextPath: string, restart = true) => {
    currentPath = nextPath;
    if (started && restart) start();
  };

  onUnmounted(stop);

  return {
    // lifecycle
    start, stop,
    // runtime tweaks
    updateParams, updatePath,
    on, off,
    // state
    isOpen, isComplete, data, lastEvent, lastEventId, error, currentUrl
  };
}
