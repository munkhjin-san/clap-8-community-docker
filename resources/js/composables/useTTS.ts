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
    if (isAppending || !sourceBuffer || chunkQueue.length === 0) return
    
    isAppending = true
    const chunk = chunkQueue.shift()!
    
    try {
      sourceBuffer.appendBuffer(chunk as BufferSource)
    } catch (err) {
      console.error('Error appending buffer:', err)
      isAppending = false
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
      
      sourceBuffer.addEventListener('updateend', () => {
        isAppending = false
        appendNextChunk()
        
        // End stream only after all chunks are appended and stream is complete
        if (chunkQueue.length === 0 && mediaSource?.readyState === 'open') {
          // Check if we should end the stream (when no more data is coming)
          // This will be handled after the fetch completes
        }
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
      let streamComplete = false

      while (true) {
        const { done, value } = await reader.read()
        
        if (done) {
          streamComplete = true
          // Mark stream as complete but don't change status
          // Status will change naturally when audio ends
          break
        }
        
        receivedLength += value.length
        
        chunkQueue.push(value)
        appendNextChunk()
        
        // Update progress but keep it below 100% until audio actually completes
        progress.value = Math.min(receivedLength / 100000, 0.95)
      }

      // Stream is complete, but audio may still be playing
      if (streamComplete && chunkQueue.length === 0 && mediaSource?.readyState === 'open') {
        try {
          mediaSource.endOfStream()
        } catch (e) {
          console.error('Error ending stream:', e)
        }
      }
      
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