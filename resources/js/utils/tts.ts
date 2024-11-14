import { useTtsStore } from "@/store/ttsStore";
import OpenAI from "openai";
const audio = new Audio();
const ttsStore = useTtsStore()
const closeData = {
    active: false,
    id: undefined
}
export const convertToSpeech = async (textContent: string, id: number) => {
    // const textContent = getTextContent(filteredContent.value);
    if (ttsStore.active) return
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
        }
        ttsStore.setTtsStore(openData)
        // Function to process and append chunks from the queue
        const processQueue = async () => {
            while (chunkQueue.length > 0 && !sourceBuffer.updating) {
                const chunk = chunkQueue.shift();
                if (chunk && chunk.byteLength > 0) {  // Check if chunk is valid
                    sourceBuffer.appendBuffer(chunk);
                    if (isFirstChunk) {
                        audio.play();
                        isFirstChunk = false;
                    }
                    await waitForBufferToBeReady();  // Wait for the buffer to finish
                }
            }

            // If the queue is empty and stream is done, end the stream
            if (isDone && chunkQueue.length === 0) {
                if (!sourceBuffer.updating) {
                    mediaSource.endOfStream();
                }
            }
        };

        // Helper function to wait for the buffer to be ready
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
            const response = await openai.audio.speech.create({
                model: "tts-1",
                voice: "nova",
                input: textContent
            });

            const reader = response?.body?.getReader();
            if (!reader) {
                throw new Error("Error reading audio response");
            }
            // Read and queue chunks for processing
            while (true) {
                const { done, value } = await reader?.read();
                if (done) {
                    isDone = true;
                    break;
                }

                // Split the buffer and add chunks to the queue
                const chunks: Uint8Array[] = splitBuffer(value);
                chunkQueue.push(...chunks);

                // Process the queue if the buffer is not updating
                if (!sourceBuffer.updating) {
                    await processQueue();
                }
            }
            
            

            // Ensure all remaining chunks are appended and end the stream
            await waitForBufferToBeReady();
            await processQueue();  // Process any remaining chunks
        } catch (error) {
            console.error("Error during audio streaming:", error);
            mediaSource.endOfStream("decode");
        }
    });
    audio.addEventListener('ended', () => {
        console.log('Audio finished')
        ttsStore.setTtsStore(closeData)
    })
    mediaSource.addEventListener('sourceended', () => {
        console.log("Audio playback finished.");
    });

    mediaSource.addEventListener('error', (error) => {
        console.error("MediaSource error:", error);
        audio.pause();
    });
};

// Function to split buffer into smaller chunks
const splitBuffer = (buffer: Uint8Array, chunkSize = 4096): Uint8Array[] => {
    let offset = 0;
    const chunks: Uint8Array[] = [];
    while (offset < buffer.length) {
        chunks.push(buffer.slice(offset, offset + chunkSize));
        offset += chunkSize;
    }
    return chunks;
};

export const stopPlay = () => {
    if (audio) {
        ttsStore.setTtsStore(closeData)
        audio.pause()
    }
}
