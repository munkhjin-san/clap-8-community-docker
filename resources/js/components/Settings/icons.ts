// Inline line-icon set for the settings screen. Each value is raw SVG markup
// themed via `currentColor`, rendered with v-html.
const svg = (paths: string) =>
    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`

export const icons = {
    lock: svg('<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>'),
    shield: svg('<path d="M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6l7-3z"/><path d="M9.2 12l2 2 3.6-4"/>'),
    mail: svg('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>'),
    key: svg('<circle cx="8.5" cy="9" r="4.5"/><path d="M12 12l8 8M16.5 16.5l2-2M19 19l1.5-1.5"/>'),
    palette: svg('<path d="M12 3a9 9 0 1 0 0 18c1.4 0 2-1 2-2 0-1.4 1-2 2-2h2a3 3 0 0 0 3-3c0-4.9-4-9-9-9z"/><circle cx="7.5" cy="11" r="1"/><circle cx="12" cy="7.5" r="1"/><circle cx="16.5" cy="11" r="1"/>'),
    theme: svg('<circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6L19 19M19 5l-1.4 1.4M6.4 17.6L5 19"/>'),
    pen: svg('<path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/>'),
    calendar: svg('<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 9h16M8 3v4M16 3v4"/>'),
    bell: svg('<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6z"/><path d="M10 19a2 2 0 0 0 4 0"/>'),
    info: svg('<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5h.01"/>'),
    layout: svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 15h18"/>'),
    logout: svg('<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 12h10M16.5 8.5L20 12l-3.5 3.5"/>'),
    pc: svg('<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/>'),
    android: svg('<rect x="6" y="3" width="12" height="18" rx="2"/><path d="M10 18h4"/>'),
    apple: svg('<path d="M16 13c0 3 2 4 2 4-.5 1.5-1.6 3-3 3-1 0-1.4-.6-2.5-.6S10.2 20 9.2 20c-1.6 0-3.5-2.5-3.5-6 0-3.4 2.2-5 4-5 1.1 0 2 .7 2.7.7.7 0 1.7-.8 3-.7 1.4.1 2.4.7 3 1.7-2.4 1.4-2.1 4.3.4 4z"/><path d="M13.5 6c.7-.9.6-2-.1-2.7"/>'),
    sun: svg('<circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.4 1.4M17.6 17.6L19 19M19 5l-1.4 1.4M6.4 17.6L5 19"/>'),
    moon: svg('<path d="M20 13a7.5 7.5 0 0 1-9-9 7.5 7.5 0 1 0 9 9z"/>'),
    auto: svg('<circle cx="12" cy="12" r="9"/><path d="M12 3v18"/><path d="M12 3a9 9 0 0 0 0 18z" fill="currentColor" stroke="none"/>'),
    gear: svg('<circle cx="12" cy="12" r="3"/><path d="M19.4 13a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.9.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0-1.2-2.9H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.4-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H10a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V10a1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'),
}

export type IconName = keyof typeof icons
