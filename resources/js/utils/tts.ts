import { useTtsStore } from "@/store/ttsStore";
import OpenAI from "openai";
const audio = new Audio();

const ttsStore = useTtsStore()
const closeData = {
    active: false,
    id: undefined,
    play: false
}
export const convertToSpeech = async (textContent: string, id: number) => {
    try {
        if (!textContent?.trim()) {
            throw new Error('Empty text content');
        }
        if (ttsStore.active) return;
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });

        const mediaSource = new MediaSource();
        audio.src = URL.createObjectURL(mediaSource);

        mediaSource.addEventListener('sourceopen', async () => {
            const sourceBuffer = mediaSource.addSourceBuffer('audio/mpeg');
            const chunkQueue: Uint8Array[] = [];
            let isFirstChunk = true;
            let isDone = false;
            const openData = {
                active: true,
                id: id,
                play: true,
            };
            ttsStore.setTtsStore(openData);

            // Buffer management configuration
            const MAX_BUFFER_SIZE = 50 * 1024 * 1024; // 50MB buffer size
            const BUFFER_THRESHOLD = 0.8; // 80% threshold for buffer cleanup

            // Function to check and manage buffer size
            const manageBufferSize = async () => {
                if (sourceBuffer.buffered.length > 0) {
                    const currentBufferSize = sourceBuffer.buffered.end(sourceBuffer.buffered.length - 1) - 
                                           sourceBuffer.buffered.start(0);
                    
                    if (currentBufferSize > MAX_BUFFER_SIZE * BUFFER_THRESHOLD) {
                        const currentTime = audio.currentTime;
                        const removeStart = sourceBuffer.buffered.start(0);
                        const removeEnd = Math.max(currentTime - 5, removeStart); // Keep 5 seconds before current time

                        if (removeEnd > removeStart && !sourceBuffer.updating) {
                            try {
                                sourceBuffer.remove(removeStart, removeEnd);
                                await waitForBufferToBeReady();
                            } catch (error) {
                                console.warn("Buffer removal failed:", error);
                            }
                        }
                    }
                }
            };

            // Enhanced queue processing with buffer management
            const processQueue = async () => {
                while (chunkQueue.length > 0 && !sourceBuffer.updating) {
                    await manageBufferSize();
                    
                    const chunk = chunkQueue.shift();
                    if (chunk && chunk.byteLength > 0) {
                        try {
                            sourceBuffer.appendBuffer(chunk);
                            if (isFirstChunk) {
                                audio.play().catch(console.error);
                                isFirstChunk = false;
                            }
                            await waitForBufferToBeReady();
                        } catch (error) {
                            if (error.name === 'QuotaExceededError') {
                                // Put the chunk back at the front of the queue
                                chunkQueue.unshift(chunk);
                                // Wait for buffer space to be freed
                                await new Promise(resolve => setTimeout(resolve, 1000));
                                continue;
                            }
                            console.error("Error appending to SourceBuffer:", error);
                            return;
                        }
                    }
                }

                if (isDone && chunkQueue.length === 0 && !sourceBuffer.updating) {
                    mediaSource.endOfStream();
                }
            };

            const waitForBufferToBeReady = () => {
                return new Promise<void>((resolve) => {
                    if (!sourceBuffer.updating) {
                        resolve();
                    } else {
                        const updateEndHandler = () => {
                            sourceBuffer.removeEventListener('updateend', updateEndHandler);
                            resolve();
                        };
                        sourceBuffer.addEventListener('updateend', updateEndHandler, { once: true });
                    }
                });
            };

            try {
                const textChunks = chunkText(textContent, 4000);
                for (const chunk of textChunks) {
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
                        const { done, value } = await reader.read();
                        if (done) {
                            isDone = true;
                            break;
                        }

                        const chunks = splitBuffer(value, 32 * 1024); // Smaller chunk size (32KB)
                        chunkQueue.push(...chunks);

                        if (!sourceBuffer.updating) {
                            await processQueue();
                        }
                    }

                    await waitForBufferToBeReady();
                    await processQueue();
                }
            } catch (error) {
                console.error("Error during audio streaming:", error);
                mediaSource.endOfStream("decode");
            }
        });

        audio.addEventListener('ended', () => {
            console.log('Audio finished');
            ttsStore.setTtsStore(closeData);
        });

        mediaSource.addEventListener('sourceended', () => {
            console.log("Audio playback finished.");
        });

        mediaSource.addEventListener('error', (error) => {
            console.error("MediaSource error:", error);
            audio.pause();
            ttsStore.setTtsStore(closeData);
        });
    } catch (error) {
        console.error('TTS Error:', error);
        ttsStore.setTtsStore(closeData);
        throw error; // Allow caller to handle
    }
};

const splitBuffer = (buffer: Uint8Array, chunkSize = 32 * 1024): Uint8Array[] => {
    let offset = 0;
    const chunks: Uint8Array[] = [];
    while (offset < buffer.length) {
        chunks.push(buffer.slice(offset, offset + chunkSize));
        offset += chunkSize;
    }
    return chunks;
};

const chunkText = (text: string, chunkSize: number): string[] => {
    const chunks: string[] = [];
    for (let i = 0; i < text.length; i += chunkSize) {
        chunks.push(text.slice(i, i + chunkSize));
    }
    return chunks;
};
export const stopPlay = (id: number) => {
    if (audio) {
        if (ttsStore.play) {
            ttsStore.setTtsStore({active: true, play: false, id: id})
            audio.pause()
        } else {
            ttsStore.setTtsStore({active: true, play: true, id: id})
            audio.play()
        }
    }
}
export const endPlay = () => {
    if (audio) {
        ttsStore.setTtsStore(closeData)
        audio.pause()
    }
}

