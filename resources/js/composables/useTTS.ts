import { ref, computed, onUnmounted } from 'vue'
import axios, { type AxiosInstance } from 'axios'

export type TTSStatus = 'idle' | 'loading' | 'playing' | 'paused' | 'stopped' | 'error'

export interface TTSOptions {
  apiUrl: string
  axiosInstance?: AxiosInstance
  onComplete?: () => void
  onError?: (error: Error) => void
  onUserInteractionRequired?: () => void
}

export function useTTS(options: TTSOptions) {
  const status = ref<TTSStatus>('idle')
  const error = ref<Error | null>(null)
  const progress = ref(0)
  
  let audioContext: AudioContext | null = null
  let audioElement: HTMLAudioElement | null = null
  let mediaSource: MediaSource | null = null
  let sourceBuffer: SourceBuffer | null = null
  let abortController: AbortController | null = null
  
  // Queue for audio chunks
  let chunkQueue: Uint8Array[] = []
  let isAppending = false
  let streamComplete = false      // network body finished downloading
  let endOfStreamCalled = false   // mediaSource.endOfStream() already called (guard)

  // Fallback mode for iOS Safari
  let useFallbackMode = false
  let audioChunks: Blob[] = []
  let isPlayingFallback = false
  let intentionalStop = false
  let currentAudioId = 0
  let waitingForUserInteraction = false
  let audioInitialized = false
  let audioPlayPromise: Promise<void> | null = null

  // Store event handler references for cleanup
  let audioEventHandlers: {
    playing?: () => void
    pause?: () => void
    ended?: () => void
    error?: (e: Event) => void
  } = {}

  // Use provided axios instance or default
  const axiosInstance = options.axiosInstance || axios

  const isIdle = computed(() => status.value === 'idle')
  const isLoading = computed(() => status.value === 'loading')
  const isPlaying = computed(() => status.value === 'playing')
  const isPaused = computed(() => status.value === 'paused')
  const isStopped = computed(() => status.value === 'stopped')
  const hasError = computed(() => status.value === 'error')

  /**
   * Detect if we need to use fallback mode (iOS Safari)
   */
  const detectFallbackMode = (): boolean => {
    // Check if MediaSource is supported
    if (!window.MediaSource) {
      return true
    }

    // Detect iOS Safari
    const ua = navigator.userAgent
    const isIOS = /iPad|iPhone|iPod/.test(ua) && !(window as any).MSStream
    const isSafari = /^((?!chrome|android).)*safari/i.test(ua)
    
    // iOS Safari has limited MediaSource support
    if (isIOS || (isSafari && isIOS)) {
      return true
    }

    // Check if MP3 is supported in MediaSource
    if (!MediaSource.isTypeSupported('audio/mpeg')) {
      return true
    }

    return false
  }

  /**
   * Get CSRF token from meta tag or cookie
   */
  const getCsrfToken = (): string | null => {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (metaToken) return metaToken

    const cookieValue = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1]
    
    return cookieValue ? decodeURIComponent(cookieValue) : null
  }

  /**
   * Initialize audio element with user interaction (important for iOS)
   */
  const initializeAudio = () => {
    if (audioInitialized) return

    if (!audioElement) {
      audioElement = new Audio()
    }

    // Play and immediately pause to "unlock" audio on iOS
    // This satisfies the user interaction requirement
    audioElement.play().then(() => {
      audioElement?.pause()
      audioInitialized = true
    }).catch(() => {
      // Ignore errors - this is just to unlock audio
      audioInitialized = true
    })
  }

  /**
   * Play the next audio chunk in fallback mode
   */
  const playNextChunkFallback = async (): Promise<void> => {
    return new Promise((resolve) => {
      if (audioChunks.length === 0 || intentionalStop) {
        if (!intentionalStop) {
          status.value = 'idle'
          progress.value = 0
          options.onComplete?.()
        }
        isPlayingFallback = false
        resolve()
        return
      }

      const chunk = audioChunks.shift()
      if (!chunk) {
        isPlayingFallback = false
        resolve()
        return
      }

      const audioUrl = URL.createObjectURL(chunk)

      if (!audioElement) {
        audioElement = new Audio()
      }

      audioElement.onended = () => {
        URL.revokeObjectURL(audioUrl)

        // Immediately start next chunk without waiting
        if (audioChunks.length > 0 && !intentionalStop) {
          // Use setImmediate-like behavior for smooth transition
          Promise.resolve().then(() => {
            playNextChunkFallback().catch(console.error)
          })
        } else if (!intentionalStop) {
          status.value = 'idle'
          progress.value = 0
          options.onComplete?.()
          isPlayingFallback = false
        }

        resolve()
      }

      audioElement.onerror = (e) => {
        console.error('Audio playback error:', e)
        URL.revokeObjectURL(audioUrl)

        if (!intentionalStop) {
          error.value = new Error('Audio playback error')
          status.value = 'error'
          options.onError?.(error.value)
          isPlayingFallback = false
        }

        resolve()
      }

      audioElement.src = audioUrl

      audioElement.play().catch(err => {
        console.error('Failed to play audio:', err)

        // This should rarely happen now since we pre-initialized
        if (err.name === 'NotAllowedError') {
          waitingForUserInteraction = true
          status.value = 'paused'
          options.onUserInteractionRequired?.()

          const resumePlayback = async () => {
            document.removeEventListener('click', resumePlayback)
            waitingForUserInteraction = false
            status.value = 'playing'
            
            try {
              await audioElement?.play()
            } catch (e) {
              console.error('Failed to resume after user interaction:', e)
              error.value = new Error('Failed to resume playback')
              status.value = 'error'
              options.onError?.(error.value)
              isPlayingFallback = false
              resolve()
            }
          }

          document.addEventListener('click', resumePlayback, { once: true })
        } else {
          if (!intentionalStop) {
            error.value = err instanceof Error ? err : new Error('Playback failed')
            status.value = 'error'
            options.onError?.(error.value)
            isPlayingFallback = false
          }
          resolve()
        }
      })
    })
  }

  /**
   * Start TTS with fallback mode (iOS Safari)
   */
  const startFallback = async (text: string) => {
    try {
      // Initialize audio element with user interaction
      initializeAudio()

      audioChunks = []
      isPlayingFallback = false
      intentionalStop = false
      currentAudioId++
      waitingForUserInteraction = false

      const csrfToken = getCsrfToken()
      if (!csrfToken) {
        throw new Error('CSRF token not found')
      }

      abortController = new AbortController()

      const baseURL = axiosInstance.defaults.baseURL || ''
      const fullUrl = baseURL + options.apiUrl

      const response = await fetch(fullUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'audio/mpeg',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ text }),
        signal: abortController.signal,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      if (!response.body) {
        throw new Error('Response body is null')
      }

      const reader = response.body.getReader()
      const audioBufferChunks: Uint8Array[] = []
      let accumulatedBytes = 0
      const MIN_PLAYABLE_CHUNK = 512 * 1024 // 512KB minimum - matching your original approach

      while (true) {
        if (intentionalStop) break

        const { done, value } = await reader.read()

        if (done) {
          // Process remaining data
          if (audioBufferChunks.length > 0) {
            const audioBlob = new Blob(audioBufferChunks as BlobPart[], { type: 'audio/mpeg' })
            audioChunks.push(audioBlob)

            if (!isPlayingFallback) {
              isPlayingFallback = true
              status.value = 'playing'
              // Don't await - let it play in background
              playNextChunkFallback()
            }
          }
          break
        }

        if (value && value.byteLength > 0) {
          audioBufferChunks.push(value)
          accumulatedBytes += value.byteLength

          if (accumulatedBytes >= MIN_PLAYABLE_CHUNK) {
            const audioBlob = new Blob(audioBufferChunks as BlobPart[], { type: 'audio/mpeg' })
            audioChunks.push(audioBlob)

            audioBufferChunks.length = 0
            accumulatedBytes = 0

            if (!isPlayingFallback) {
              isPlayingFallback = true
              status.value = 'playing'
              // Don't await - let it play in background
              playNextChunkFallback()
            }
          }
        }
      }

      progress.value = 1

    } catch (err) {
      if (err instanceof Error) {
        if (err.name === 'AbortError') {
          status.value = 'idle'
          return
        }
        error.value = err
        status.value = 'error'
        options.onError?.(err)
      }
    }
  }

  /**
   * Append next chunk from queue to SourceBuffer
   */
  const appendNextChunk = () => {
    if (isAppending || !sourceBuffer || sourceBuffer.updating || chunkQueue.length === 0) return

    isAppending = true
    const chunk = chunkQueue[0] // peek; only drop after the append is accepted

    try {
      sourceBuffer.appendBuffer(chunk as BufferSource)
      chunkQueue.shift()
    } catch (err) {
      isAppending = false
      if (err instanceof DOMException && err.name === 'QuotaExceededError') {
        // SourceBuffer is full (~12MB cap). Do NOT drop the chunk — evict
        // already-played audio and retry on the next 'updateend'. The
        // backpressure gate in the read loop keeps this rare.
        if (audioElement && sourceBuffer.buffered.length) {
          const evictEnd = Math.max(0, audioElement.currentTime - 10)
          if (evictEnd > sourceBuffer.buffered.start(0)) {
            try {
              sourceBuffer.remove(0, evictEnd) // fires 'updateend' -> retry
            } catch (e) {
              console.error('Error evicting buffer:', e)
            }
          }
        }
      } else {
        console.error('Error appending buffer:', err)
        chunkQueue.shift() // non-recoverable: drop to avoid an infinite retry
      }
    }
  }

  /**
   * Finalize the MediaSource once the network stream has finished AND the
   * append queue has fully drained. Fixes the race where endOfStream() was
   * only attempted once (right after fetch) while chunks were still queued,
   * leaving MediaSource 'open' forever so 'ended'/onComplete never fired.
   */
  const tryEndOfStream = () => {
    if (endOfStreamCalled || !mediaSource || mediaSource.readyState !== 'open') return
    if (!streamComplete || chunkQueue.length > 0) return
    if (sourceBuffer && sourceBuffer.updating) return
    try {
      mediaSource.endOfStream()
      endOfStreamCalled = true
    } catch (e) {
      console.error('Error ending stream:', e)
    }
  }

  /**
   * Start TTS streaming with MediaSource (modern browsers)
   */
  const startMediaSource = async (text: string) => {
    try {
      // Initialize audio element with user interaction
      initializeAudio()

      chunkQueue = []
      isAppending = false
      streamComplete = false
      endOfStreamCalled = false

      if (!audioElement) {
        audioElement = new Audio()
      }
      audioElement.autoplay = true

      mediaSource = new MediaSource()
      audioElement.src = URL.createObjectURL(mediaSource)

      await new Promise<void>((resolve, reject) => {
        if (!mediaSource) return reject(new Error('MediaSource not created'))
        
        mediaSource.addEventListener('sourceopen', () => resolve(), { once: true })
        mediaSource.addEventListener('error', (e) => reject(e), { once: true })
      })

      sourceBuffer = mediaSource.addSourceBuffer('audio/mpeg')

      // Start playback explicitly so a blocked autoplay surfaces (via
      // onUserInteractionRequired) instead of failing silently. The
      // MediaSource path previously relied only on autoplay=true.
      let playRequested = false
      const attemptPlay = () => {
        audioElement?.play().catch((err) => {
          if (err && err.name === 'NotAllowedError') {
            waitingForUserInteraction = true
            status.value = 'paused'
            options.onUserInteractionRequired?.()
            const resumeOnClick = () => {
              document.removeEventListener('click', resumeOnClick)
              waitingForUserInteraction = false
              audioElement?.play().catch(() => {})
            }
            document.addEventListener('click', resumeOnClick, { once: true })
          }
        })
      }

      sourceBuffer.addEventListener('updateend', () => {
        isAppending = false
        if (!playRequested) {
          playRequested = true
          attemptPlay()
        }
        appendNextChunk()
        // Finalize the stream once the queue drains after the download ended
        // (see tryEndOfStream) — fixes the endOfStream race.
        tryEndOfStream()
      })

      sourceBuffer.addEventListener('error', (e) => {
        console.error('SourceBuffer error:', e)
      })

      // Setup audio element event handlers
      audioEventHandlers.playing = () => {
        // Update to playing when audio actually starts
        status.value = 'playing'
      }

      audioEventHandlers.pause = () => {
        // Only update to paused if we're currently playing and it wasn't intentional
        if (status.value === 'playing' && !intentionalStop) {
          status.value = 'paused'
        }
      }

      audioEventHandlers.ended = () => {
        status.value = 'idle'
        progress.value = 0
        options.onComplete?.()
      }

      audioEventHandlers.error = (e) => {
        // Ignore errors if we intentionally stopped
        if (intentionalStop) return
        
        console.error('Audio element error:', e)
        error.value = new Error('Audio playback error')
        status.value = 'error'
        options.onError?.(error.value)
      }

      audioElement.addEventListener('playing', audioEventHandlers.playing)
      audioElement.addEventListener('pause', audioEventHandlers.pause)
      audioElement.addEventListener('ended', audioEventHandlers.ended)
      audioElement.addEventListener('error', audioEventHandlers.error)

      const csrfToken = getCsrfToken()
      if (!csrfToken) {
        throw new Error('CSRF token not found')
      }

      abortController = new AbortController()

      const baseURL = axiosInstance.defaults.baseURL || ''
      const fullUrl = baseURL + options.apiUrl

      const response = await fetch(fullUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'audio/mpeg',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ text }),
        signal: abortController.signal,
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      if (!response.body) {
        throw new Error('Response body is null')
      }

      const reader = response.body.getReader()
      let receivedLength = 0
      const MAX_FORWARD_BUFFER = 45 // seconds of audio to keep buffered ahead

      while (true) {
        // Backpressure: the backend delivers the whole (possibly 10+ min)
        // audio much faster than realtime. If we appended it all, the forward
        // buffer would exceed the SourceBuffer's ~12MB cap -> QuotaExceededError
        // -> lost/truncated audio. So stop reading while we're far ahead of
        // playback and resume as it drains (TCP flow-control throttles the
        // backend too).
        if (audioElement && audioElement.buffered.length && !intentionalStop) {
          let forward = audioElement.buffered.end(audioElement.buffered.length - 1) - audioElement.currentTime
          while (forward > MAX_FORWARD_BUFFER && !intentionalStop) {
            await new Promise((r) => setTimeout(r, 200))
            if (!audioElement.buffered.length) break
            forward = audioElement.buffered.end(audioElement.buffered.length - 1) - audioElement.currentTime
          }
        }

        const { done, value } = await reader.read()

        if (done) {
          streamComplete = true
          tryEndOfStream() // finalize now if the queue already drained
          break
        }

        receivedLength += value.length

        chunkQueue.push(value)
        appendNextChunk()

        // Update progress but keep it below 100% until audio actually completes
        progress.value = Math.min(receivedLength / 100000, 0.95)
      }

      // If chunks are still draining, the 'updateend' handler calls
      // endOfStream() once the queue empties (tryEndOfStream).
      
    } catch (err) {
      if (err instanceof Error) {
        if (err.name === 'AbortError') {
          status.value = 'idle'
          return
        }
        error.value = err
        status.value = 'error'
        options.onError?.(err)
      }
    }
  }

  /**
   * Start TTS streaming
   */
  const start = async (text: string) => {
    stop()
    error.value = null
    status.value = 'loading'
    progress.value = 0

    // Detect if we need fallback mode
    useFallbackMode = detectFallbackMode()

    if (useFallbackMode) {
      console.log('Using fallback mode for iOS Safari')
      await startFallback(text)
    } else {
      await startMediaSource(text)
    }
  }

  /**
   * Pause playback
   */
  const pause = () => {
    if (!audioElement || (status.value !== 'playing' && !waitingForUserInteraction)) return
    audioElement.pause()
    status.value = 'paused'
  }

  /**
   * Resume playback
   */
  const resume = () => {
    if (!audioElement || status.value !== 'paused') return
    
    status.value = 'playing' // Immediately update status
    
    audioElement.play().catch(err => {
      console.error('Error resuming playback:', err)
      error.value = err instanceof Error ? err : new Error('Resume failed')
      status.value = 'error'
    })
  }

  /**
   * Stop playback and reset
   */
  const stop = () => {
    intentionalStop = true

    if (abortController) {
      abortController.abort()
      abortController = null
    }

    if (audioElement) {
      // Remove all event listeners before stopping to prevent errors
      if (audioEventHandlers.playing) {
        audioElement.removeEventListener('playing', audioEventHandlers.playing)
      }
      if (audioEventHandlers.pause) {
        audioElement.removeEventListener('pause', audioEventHandlers.pause)
      }
      if (audioEventHandlers.ended) {
        audioElement.removeEventListener('ended', audioEventHandlers.ended)
      }
      if (audioEventHandlers.error) {
        audioElement.removeEventListener('error', audioEventHandlers.error)
      }
      
      // Clear handler references
      audioEventHandlers = {}
      
      // Pause and clear source
      try {
        audioElement.pause()
        audioElement.removeAttribute('src')
        audioElement.load()
      } catch (e) {
        // Ignore any errors during cleanup
      }
    }

    if (mediaSource && mediaSource.readyState === 'open') {
      try {
        mediaSource.endOfStream()
      } catch (e) {
        // Ignore errors
      }
    }
    mediaSource = null
    sourceBuffer = null

    chunkQueue = []
    audioChunks = []
    isAppending = false
    streamComplete = false
    endOfStreamCalled = false
    isPlayingFallback = false
    waitingForUserInteraction = false
    progress.value = 0
    status.value = 'idle'
    intentionalStop = false // Reset for next playback
  }

  /**
   * Toggle play/pause
   */
  const toggle = () => {
    if (status.value === 'playing') {
      pause()
    } else if (status.value === 'paused') {
      resume()
    }
  }

  onUnmounted(() => {
    stop()
    // Clean up audio element on unmount
    if (audioElement) {
      audioElement.pause()
      audioElement.src = ''
      audioElement = null
    }
    audioInitialized = false
  })

  return {
    status,
    error,
    progress,
    isIdle,
    isLoading,
    isPlaying,
    isPaused,
    isStopped,
    hasError,
    start,
    pause,
    resume,
    stop,
    toggle,
  }
}