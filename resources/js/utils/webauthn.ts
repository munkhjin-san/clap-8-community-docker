/**
 * Minimal WebAuthn ceremony helpers for laravel/passkeys (Sanctum migration Phase 8).
 *
 * The server (web-auth/webauthn-lib) speaks the standard WebAuthn JSON format: options come down
 * with base64url-encoded binary fields, and credentials must go back up the same way. These helpers
 * convert between that JSON shape and the ArrayBuffers that navigator.credentials expects.
 * See docs/sanctum_migration_footprint.md.
 */

export function passkeysSupported(): boolean {
    return typeof window !== 'undefined'
        && typeof window.PublicKeyCredential !== 'undefined'
        && !!navigator.credentials
}

function base64urlToBuffer(value: string): ArrayBuffer {
    const padding = '='.repeat((4 - (value.length % 4)) % 4)
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/')
    const binary = atob(base64)
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i)
    return bytes.buffer
}

function bufferToBase64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer)
    let binary = ''
    for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i])
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

/** Run the registration ceremony and return the credential as server-ready JSON. */
export async function createPasskey(options: any): Promise<any> {
    const publicKey: any = { ...options }
    publicKey.challenge = base64urlToBuffer(options.challenge)
    publicKey.user = { ...options.user, id: base64urlToBuffer(options.user.id) }
    if (Array.isArray(options.excludeCredentials)) {
        publicKey.excludeCredentials = options.excludeCredentials.map((c: any) => ({ ...c, id: base64urlToBuffer(c.id) }))
    }

    const credential = (await navigator.credentials.create({ publicKey })) as PublicKeyCredential | null
    if (!credential) throw new Error('Passkey registration was cancelled.')

    const response = credential.response as AuthenticatorAttestationResponse
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(response.clientDataJSON),
            attestationObject: bufferToBase64url(response.attestationObject),
            transports: typeof response.getTransports === 'function' ? response.getTransports() : [],
        },
        clientExtensionResults: credential.getClientExtensionResults(),
    }
}

/** Run the login (assertion) ceremony and return the credential as server-ready JSON. */
export async function getPasskey(options: any): Promise<any> {
    const publicKey: any = { ...options }
    publicKey.challenge = base64urlToBuffer(options.challenge)
    if (Array.isArray(options.allowCredentials)) {
        publicKey.allowCredentials = options.allowCredentials.map((c: any) => ({ ...c, id: base64urlToBuffer(c.id) }))
    }

    const credential = (await navigator.credentials.get({ publicKey })) as PublicKeyCredential | null
    if (!credential) throw new Error('Passkey login was cancelled.')

    const response = credential.response as AuthenticatorAssertionResponse
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(response.clientDataJSON),
            authenticatorData: bufferToBase64url(response.authenticatorData),
            signature: bufferToBase64url(response.signature),
            userHandle: response.userHandle ? bufferToBase64url(response.userHandle) : null,
        },
        clientExtensionResults: credential.getClientExtensionResults(),
    }
}
