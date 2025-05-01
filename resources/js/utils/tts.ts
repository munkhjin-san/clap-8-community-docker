import { useTtsStore } from "@/store/ttsStore";
import OpenAI from "openai";

// Global audio instance
const audio = new Audio();

// Keep track of active MediaSource instance
let activeMediaSource: MediaSource | null = null;
let activeSourceBuffer: SourceBuffer | null = null;
// Track if we're intentionally stopping the audio
let intentionalStop = false;
// Track if we're using the fallback method for iOS Safari
let usingFallback = false;
// Track if we're currently processing audio
let isProcessing = false;
// Store audio chunks for fallback method
let audioChunks: Blob[] = [];
// Current audio ID
let currentAudioId: number | undefined = undefined;

const ttsStore = useTtsStore();
const closeData = {
    active: false,
    id: undefined,
    play: false
};

/**
 * Checks if MediaSource is fully supported in the current browser
 */
const isMediaSourceFullySupported = (): boolean => {
    // Check if MediaSource exists and supports MP3
    if (!window.MediaSource || !MediaSource.isTypeSupported('audio/mpeg')) {
        return false;
    }
    
    // iOS Safari specific detection
    const ua = navigator.userAgent;
    const isSafari = /Safari/i.test(ua) && !/Chrome/i.test(ua);
    const isIOS = /iPad|iPhone|iPod/.test(ua) || 
                 (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    
    // Return false for iOS Safari which has limited MSE support
    if (isIOS && isSafari) {
        console.log("Detected iOS Safari - using fallback method");
        return false;
    }
    
    return true;
};

/**
 * Converts text to speech using OpenAI's TTS API
 */
export const convertToSpeech = async (textContent: string, id: number) => {
    try {
        // Reset flags
        intentionalStop = false;
        
        // Validate input and state
        if (!textContent?.trim()) {
            throw new Error('Empty text content');
        }
        
        if (ttsStore.active && isProcessing) return;
        
        // Clean up any existing audio before creating a new one
        cleanupAudio(false);
        
        // Set current audio ID
        currentAudioId = id;
        isProcessing = true;
        
        // Initialize OpenAI client
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });
        
        // Update store state
        const openData = {
            active: true,
            id: id,
            play: true,
        };
        ttsStore.setTtsStore(openData);

        // Determine if we should use MediaSource or fallback
        usingFallback = !isMediaSourceFullySupported();
        
        if (usingFallback) {
            // Use the fallback method (for iOS Safari)
            await processWithFallback(textContent, openai);
        } else {
            // Use MediaSource method (for fully supported browsers)
            // await processWithMediaSource(textContent, openai);
        }

    } catch (error) {
        console.error('TTS Error:', error);
        cleanupAudio(false);
        ttsStore.setTtsStore(closeData);
        isProcessing = false;
        throw error; // Allow caller to handle
    }
};

/**
 * Process text using fallback method for iOS Safari with improved streaming
 */
const processWithFallback = async (textContent: string, openai: OpenAI) => {
    try {
        // Reset array of audio chunks
        audioChunks = [];
        let isPlaying = false;
        let audioPlayPromise: Promise<void> | null = null;
        
        // Split text into manageable chunks for API calls
        const textChunks = chunkText(textContent, 1400);
        
        // Process each text chunk
        for (const chunk of textChunks) {
            // Skip if we've intentionally stopped
            if (intentionalStop) break;
            
            try {
                const response = await openai.audio.speech.create({
                    model: "gpt-4o-mini-tts",
                    voice: "nova",
                    input: chunk,
                    instructions: "発音: ほとんど日本語ですので、日本語の発音に注意ください。ネイティブ日本語っぽく。 声: 温かみがあり、共感的で、プロフェッショナルな口調で、お客様の問題が理解され解決されることをお客様に安心させます。\n\n句読点: 自然な間を置いた構造で、明瞭で安定した落ち着いた流れを実現します。\n\n話し方: 落ち着いて辛抱強く、聞き手に思いやりのあるサポートと理解のある口調で話します。\n\n言い回し: 明確かつ簡潔で、専門用語を避けながらプロ意識を維持し、お客様にわかりやすい言葉を使用します。\n\n口調: 共感的でソリューション重視で、理解と積極的な支援の両方を重視します。"
                });

                if (!response.body) {
                    throw new Error("Empty response from TTS API");
                }

                // Process the response stream in smaller portions
                const reader = response.body.getReader();
                
                // Used to accumulate audio data until we have enough to play
                const audioBufferChunks: Uint8Array[] = [];
                let accumulatedBytes = 0;
                const MIN_PLAYABLE_CHUNK = 32 * 1024; // 32KB minimum to start playing
                
                // Streaming loop for each API response
                while (true) {
                    if (intentionalStop) break;
                    
                    const { done, value } = await reader.read();
                    
                    if (done) break;
                    
                    if (value && value.byteLength > 0) {
                        audioBufferChunks.push(value);
                        accumulatedBytes += value.byteLength;
                        
                        // If we have enough data, create a playable chunk
                        if (accumulatedBytes >= MIN_PLAYABLE_CHUNK || done) {
                            // Create a blob from accumulated chunks
                            const audioBlob = new Blob(audioBufferChunks, { type: 'audio/mpeg' });
                            audioChunks.push(audioBlob);
                            
                            // Reset accumulation buffer
                            audioBufferChunks.length = 0;
                            accumulatedBytes = 0;
                            
                            // Start playing immediately if not already playing
                            if (!isPlaying) {
                                isPlaying = true;
                                audioPlayPromise = playNextChunk();
                            }
                        }
                    }
                }
                
                // Make sure we process any remaining audio data
                if (audioBufferChunks.length > 0) {
                    const audioBlob = new Blob(audioBufferChunks, { type: 'audio/mpeg' });
                    audioChunks.push(audioBlob);
                    
                    // Start playing if not already playing
                    if (!isPlaying) {
                        isPlaying = true;
                        audioPlayPromise = playNextChunk();
                    }
                }
                
                // Wait for current audio to finish before requesting the next chunk
                if (audioPlayPromise && isPlaying && audioChunks.length === 0) {
                    await audioPlayPromise;
                }
                
            } catch (error) {
                console.error(`Error processing chunk: ${error}`);
                // Continue with next chunk if possible
                if (intentionalStop) break;
            }
        }
        
    } catch (error) {
        console.error("Error during fallback audio processing:", error);
        isProcessing = false;
        if (!intentionalStop) {
            ttsStore.setTtsStore(closeData);
        }
    }
};

/**
 * Play the next audio chunk in the fallback method
 * Returns a promise that resolves when the current chunk playback ends
 */
const playNextChunk = async (): Promise<void> => {
    return new Promise((resolve) => {
        if (audioChunks.length === 0 || intentionalStop) {
            // No more chunks to play or stopped intentionally
            if (!intentionalStop) {
                // Only clean up if not stopped intentionally
                ttsStore.setTtsStore(closeData);
            }
            isProcessing = false;
            resolve();
            return;
        }
        
        // Get the next chunk and create object URL
        const chunk = audioChunks.shift();
        if (!chunk) {
            // No chunk available, resolve and return
            isProcessing = false;
            resolve();
            return;
        }
        const audioUrl = URL.createObjectURL(chunk);
        
        // Set up event handlers
        audio.onended = () => {
            // Revoke the URL to free memory
            URL.revokeObjectURL(audioUrl);
            
            // Check if there are more chunks to play
            if (audioChunks.length > 0 && !intentionalStop) {
                // Play the next chunk
                playNextChunk().catch(console.error);
            } else if (!intentionalStop) {
                // No more chunks and not stopped intentionally
                ttsStore.setTtsStore(closeData);
                isProcessing = false;
            }
            
            // Resolve this chunk's promise
            resolve();
        };
        
        audio.onerror = (e) => {
            console.error("Audio playback error:", e);
            URL.revokeObjectURL(audioUrl);
            
            if (!intentionalStop) {
                ttsStore.setTtsStore(closeData);
                isProcessing = false;
            }
            
            // Resolve even on error
            resolve();
        };
        
        // Set source
        audio.src = audioUrl;
        
        // Create a variable to track if we're waiting for user interaction
        let waitingForInteraction = false;
        
        // Try to play
        audio.play().catch(error => {
            console.error("Failed to play audio:", error);
            
            // Handle auto-play restrictions
            if (error.name === 'NotAllowedError') {
                waitingForInteraction = true;
                // Pause and update state
                ttsStore.setTtsStore({active: true, play: false, id: currentAudioId});
                
                // Set up a one-time click handler to resume
                const resumePlayback = async () => {
                    document.removeEventListener('click', resumePlayback);
                    waitingForInteraction = false;
                    ttsStore.setTtsStore({active: true, play: true, id: currentAudioId});
                    try {
                        await audio.play();
                    } catch (e) {
                        console.error("Failed to resume after user interaction:", e);
                        ttsStore.setTtsStore(closeData);
                        isProcessing = false;
                        resolve();
                    }
                };
                
                document.addEventListener('click', resumePlayback);
                console.warn("Audio playback requires user interaction. Click to resume.");
            } else {
                // For other errors, clean up
                if (!intentionalStop) {
                    ttsStore.setTtsStore(closeData);
                    isProcessing = false;
                }
                resolve();
            }
        });
        
        // If we're in a user interaction waiting state, don't auto-resolve
        // The click handler will take care of continuing playback
        if (waitingForInteraction) {
            // Don't resolve yet - will be resolved after user interaction
            return;
        }
    });
};

/**
 * Process text using MediaSource Extension for supported browsers
 */
const processWithMediaSource = async (textContent: string, openai: OpenAI) => {
    // Clean up any existing MediaSource before creating a new one
    cleanupMediaSource(false);
    
    // Create new MediaSource instance
    activeMediaSource = new MediaSource();
    audio.src = URL.createObjectURL(activeMediaSource);

    activeMediaSource.addEventListener('sourceopen', async () => {
        // Skip if source buffer is already added to this MediaSource
        if (activeMediaSource?.readyState !== 'open' || activeSourceBuffer) return;

        if (!MediaSource.isTypeSupported('audio/mpeg')) {
            console.error('audio/mpeg not supported');
            cleanupMediaSource(false);
            isProcessing = false;
            return;
        }
        
        try {
            activeSourceBuffer = activeMediaSource.addSourceBuffer('audio/mpeg');
        } catch (error) {
            console.error('Failed to add source buffer:', error);
            cleanupMediaSource(false);
            isProcessing = false;
            return;
        }
        
        const chunkQueue: Uint8Array[] = [];
        let isFirstChunk = true;
        let isDone = false;
        
        // Buffer management configuration
        const MAX_BUFFER_SIZE = 50 * 1024 * 1024; // 50MB buffer size
        const BUFFER_THRESHOLD = 0.8; // 80% threshold for buffer cleanup

        // Function to check and manage buffer size
        const manageBufferSize = async () => {
            if (!activeSourceBuffer || activeSourceBuffer.buffered.length === 0) return;
            
            const currentBufferSize = activeSourceBuffer.buffered.end(activeSourceBuffer.buffered.length - 1) - 
                                    activeSourceBuffer.buffered.start(0);
            
            if (currentBufferSize > MAX_BUFFER_SIZE * BUFFER_THRESHOLD) {
                const currentTime = audio.currentTime;
                const removeStart = activeSourceBuffer.buffered.start(0);
                const removeEnd = Math.max(currentTime - 5, removeStart); // Keep 5 seconds before current time

                if (removeEnd > removeStart && !activeSourceBuffer.updating) {
                    try {
                        activeSourceBuffer.remove(removeStart, removeEnd);
                        await waitForBufferToBeReady();
                    } catch (error) {
                        console.warn("Buffer removal failed:", error);
                    }
                }
            }
        };

        // Enhanced queue processing with buffer management
        const processQueue = async () => {
            if (!activeSourceBuffer) return;
            
            while (chunkQueue.length > 0 && !activeSourceBuffer.updating) {
                await manageBufferSize();
                
                const chunk = chunkQueue.shift();
                if (chunk && chunk.byteLength > 0) {
                    try {
                        activeSourceBuffer.appendBuffer(chunk);
                        if (isFirstChunk) {
                            audio.play().catch(console.error);
                            isFirstChunk = false;
                        }
                        await waitForBufferToBeReady();
                    } catch (error) {
                        console.error("Error appending to SourceBuffer:", error);
                        
                        if (error.name === 'QuotaExceededError') {
                            // Put the chunk back at the front of the queue
                            chunkQueue.unshift(chunk);
                            // Force buffer cleanup
                            try {
                                if (activeSourceBuffer.buffered.length > 0 && !activeSourceBuffer.updating) {
                                    const start = activeSourceBuffer.buffered.start(0);
                                    const mid = activeSourceBuffer.buffered.start(0) + 
                                             (activeSourceBuffer.buffered.end(0) - activeSourceBuffer.buffered.start(0)) / 2;
                                    activeSourceBuffer.remove(start, mid);
                                    await waitForBufferToBeReady();
                                }
                            } catch (e) {
                                console.warn("Emergency buffer cleanup failed:", e);
                            }
                            // Wait before retrying
                            await new Promise(resolve => setTimeout(resolve, 1000));
                            continue;
                        }
                        
                        // For other errors, just log and continue with next chunk
                        continue;
                    }
                }
            }

            if (isDone && chunkQueue.length === 0 && activeSourceBuffer && !activeSourceBuffer.updating && activeMediaSource) {
                try {
                    activeMediaSource.endOfStream();
                    isProcessing = false;
                } catch (e) {
                    console.warn("Error ending stream:", e);
                }
            }
        };

        const waitForBufferToBeReady = () => {
            if (!activeSourceBuffer) return Promise.resolve();
            
            return new Promise<void>((resolve) => {
                if (!activeSourceBuffer?.updating) {
                    resolve();
                } else {
                    const updateEndHandler = () => {
                        activeSourceBuffer?.removeEventListener('updateend', updateEndHandler);
                        resolve();
                    };
                    activeSourceBuffer?.addEventListener('updateend', updateEndHandler, { once: true });
                }
            });
        };

        try {
            // Split text into manageable chunks for API calls
            const textChunks = chunkText(textContent, 1400);

            for (const chunk of textChunks) {
                // Skip processing if MediaSource or SourceBuffer was cleaned up
                if (!activeMediaSource || !activeSourceBuffer) break;
                
                const response = await openai.audio.speech.create({
                    model: "gpt-4o-mini-tts",
                    voice: "nova",
                    input: chunk,
                    instructions: "発音: ほとんど日本語ですので、日本語の発音に注意ください。ネイティブ日本語っぽく。 声: 温かみがあり、共感的で、プロフェッショナルな口調で、お客様の問題が理解され解決されることをお客様に安心させます。\n\n句読点: 自然な間を置いた構造で、明瞭で安定した落ち着いた流れを実現します。\n\n話し方: 落ち着いて辛抱強く、聞き手に思いやりのあるサポートと理解のある口調で話します。\n\n言い回し: 明確かつ簡潔で、専門用語を避けながらプロ意識を維持し、お客様にわかりやすい言葉を使用します。\n\n口調: 共感的でソリューション重視で、理解と積極的な支援の両方を重視します。"
                });

                const reader = response?.body?.getReader();
                if (!reader) {
                    throw new Error("Error reading audio response");
                }

                while (true) {
                    // Skip processing if MediaSource or SourceBuffer was cleaned up
                    if (!activeMediaSource || !activeSourceBuffer) break;
                    
                    const { done, value } = await reader.read();
                    if (done) {
                        isDone = true;
                        break;
                    }

                    // Split into smaller chunks for better buffer management
                    const chunks = splitBuffer(value, 32 * 1024); // Smaller chunk size (32KB)
                    chunkQueue.push(...chunks);

                    // Process queue if buffer is not updating
                    if (activeSourceBuffer && !activeSourceBuffer.updating) {
                        await processQueue();
                    }
                }

                if (activeSourceBuffer) {
                    await waitForBufferToBeReady();
                    await processQueue();
                }
            }
        } catch (error) {
            console.error("Error during audio streaming:", error);
            isProcessing = false;
            if (activeMediaSource && activeMediaSource.readyState === 'open') {
                try {
                    activeMediaSource.endOfStream("decode");
                } catch (e) {
                    console.warn("Error ending stream after error:", e);
                }
            }
        }
    });

    // Setup event handlers
    setupAudioEventHandlers();
};

/**
 * Set up event handlers for the audio element
 */
const setupAudioEventHandlers = () => {
    // Remove existing listeners first to prevent duplicates
    audio.removeEventListener('ended', audioEndedHandler);
    audio.removeEventListener('error', audioErrorHandler);
    
    // Add new listeners
    audio.addEventListener('ended', audioEndedHandler);
    audio.addEventListener('error', audioErrorHandler);
};

/**
 * Handler for audio ended event
 */
const audioEndedHandler = () => {
    console.log('Audio finished');
    // Only handle end event for MediaSource mode
    // Fallback mode handles this in playNextChunk
    if (!usingFallback) {
        // Use intentional stop when audio ends naturally to prevent error cascade
        cleanupAudio(true);
        ttsStore.setTtsStore(closeData);
        isProcessing = false;
    }
};

/**
 * Handler for audio error event
 */
const audioErrorHandler = (event: Event) => {
    // Skip reporting errors if they're from an intentional stop action
    if (intentionalStop) {
        console.log("Intentional stop - ignoring error event");
        return;
    }
    
    // For unintentional errors, log and clean up
    console.error("Audio error:", audio.error);
    cleanupAudio(false);
    ttsStore.setTtsStore(closeData);
    isProcessing = false;
};

/**
 * Clean up audio resources based on the current mode
 */
const cleanupAudio = (isIntentionalStop: boolean = false) => {
    intentionalStop = isIntentionalStop;
    
    if (usingFallback) {
        // Clean up fallback mode resources
        cleanupFallback(isIntentionalStop);
    } else {
        // Clean up MediaSource mode resources
        cleanupMediaSource(isIntentionalStop);
    }
    
    // After a short delay, reset the intentional stop flag
    if (isIntentionalStop) {
        setTimeout(() => {
            intentionalStop = false;
        }, 100);
    }
};

/**
 * Clean up resources for fallback mode
 */
const cleanupFallback = (isIntentionalStop: boolean) => {
    // Clear any remaining audio chunks
    audioChunks = [];
    
    // Stop any playing audio
    if (audio) {
        audio.pause();
        
        // Only change the src if this is not an intentional stop
        if (!isIntentionalStop) {
            audio.src = '';
        }
        
        // Clean up event handlers
        audio.onended = null;
        audio.onerror = null;
    }
};

/**
 * Clean up MediaSource and SourceBuffer to prevent memory leaks and quota errors
 */
const cleanupMediaSource = (isIntentionalStop: boolean) => {
    if (activeSourceBuffer && activeMediaSource) {
        try {
            if (activeMediaSource.readyState === 'open') {
                // Remove the source buffer if possible
                try {
                    activeMediaSource.removeSourceBuffer(activeSourceBuffer);
                } catch (e) {
                    console.warn("Could not remove source buffer:", e);
                }
                
                // End the stream if possible
                try {
                    activeMediaSource.endOfStream();
                } catch (e) {
                    console.warn("Could not end media stream:", e);
                }
            }
        } catch (e) {
            console.warn("Error during MediaSource cleanup:", e);
        }
    }
    
    // Reset references
    activeSourceBuffer = null;
    activeMediaSource = null;
    
    // Stop any playing audio
    if (audio) {
        audio.pause();
        
        // Only change the src if this is not an intentional stop
        // This prevents unnecessary error events when stopping
        if (!isIntentionalStop) {
            audio.src = '';
        }
    }
};

/**
 * Split a buffer into smaller chunks for better buffer management
 */
const splitBuffer = (buffer: Uint8Array, chunkSize = 32 * 1024): Uint8Array[] => {
    let offset = 0;
    const chunks: Uint8Array[] = [];
    while (offset < buffer.length) {
        chunks.push(buffer.slice(offset, offset + chunkSize));
        offset += chunkSize;
    }
    return chunks;
};

/**
 * Split text into manageable chunks for API calls
 */
const chunkText = (text: string, chunkSize: number): string[] => {
    const chunks: string[] = [];
    let currentChunk = '';
    
    // Split by sentences to maintain natural breaks
    const sentences = text.match(/[^.!?。！？\s][^.!?。！？]*[.!?。！？]?/g) || [];
    
    for (const sentence of sentences) {
        // If adding this sentence exceeds chunk size, push current chunk and start a new one
        if (currentChunk.length + sentence.length > chunkSize && currentChunk.length > 0) {
            chunks.push(currentChunk);
            currentChunk = '';
        }
        
        // If a single sentence is larger than chunk size, split it
        if (sentence.length > chunkSize) {
            // Add what fits to current chunk
            if (currentChunk.length > 0) {
                chunks.push(currentChunk);
                currentChunk = '';
            }
            
            // Split the long sentence
            for (let i = 0; i < sentence.length; i += chunkSize) {
                chunks.push(sentence.slice(i, i + chunkSize));
            }
        } else {
            // Add sentence to current chunk
            currentChunk += sentence + ' ';
        }
    }
    
    // Add the last chunk if it's not empty
    if (currentChunk.length > 0) {
        chunks.push(currentChunk);
    }
    
    return chunks;
};

/**
 * Toggle play/pause for the TTS audio
 */
export const stopPlay = (id: number) => {
    // Check if audio element has a valid source before attempting to play/pause
    if (audio && audio.src && audio.src !== '') {
        if (ttsStore.play) {
            ttsStore.setTtsStore({active: true, play: false, id: id});
            audio.pause();
        } else {
            // Verify that we're not trying to play an empty source
            if (audio.readyState > 0) {
                ttsStore.setTtsStore({active: true, play: true, id: id});
                audio.play().catch(error => {
                    console.error('Failed to resume playback:', error);
                    ttsStore.setTtsStore({active: true, play: false, id: id});
                });
            } else {
                console.warn('Cannot play audio: no valid source loaded');
                ttsStore.setTtsStore({active: false, play: false, id: undefined});
            }
        }
    } else {
        console.warn('Cannot play/pause: audio source is empty');
        ttsStore.setTtsStore({active: false, play: false, id: undefined});
    }
};

/**
 * Stop playback and reset TTS state
 */
export const endPlay = () => {
    if (audio) {
        // Mark as intentional stop to prevent error cascade
        cleanupAudio(true);
        ttsStore.setTtsStore(closeData);
        isProcessing = false;
    }
};