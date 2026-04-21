<template>
    <Teleport to="body">
        <Transition name="oshiFade">
            <div v-if="modelValue" class="oshi-panel-overlay" @click="emit('update:modelValue', false)"></div>
        </Transition>
        <Transition name="oshiSlide">
            <div v-if="modelValue" class="oshi-panel">
                <div class="oshi-panel-header">
                    <div class="oshi-panel-titles">
                        <p class="oshi-panel-title">みんなの推し</p>
                        <p v-if="tag" class="oshi-panel-tag">#{{ tag.text }}</p>
                    </div>
                    <button class="oshi-panel-close" @click="emit('update:modelValue', false)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32" fill="currentColor">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"/>
                        </svg>
                    </button>
                </div>

                <div class="oshi-panel-body">
                    <!-- Loading skeletons -->
                    <div v-if="loading" class="oshi-panel-loading">
                        <div v-for="i in 3" :key="i" class="oshi-panel-skeleton"></div>
                    </div>

                    <!-- Empty state -->
                    <div v-else-if="!loading && albums.length === 0" class="oshi-panel-empty">
                        まだ推しがいません
                    </div>

                    <!-- User album list -->
                    <div v-else v-for="user in albums" :key="user.id" class="oshi-panel-user">
                        <div class="oshi-panel-user-header">
                            <UserPanel :disableInstant="true" :user="user" imgClass="userNormalIcon" size="28"/>
                            <router-link class="oshi-panel-user-name" :to="`/user/${user.id}`">{{ user.name }}</router-link>
                        </div>
                        <div class="oshi-panel-media-grid">
                            <div
                                v-for="album in user.user_album"
                                :key="album.id"
                                class="oshi-panel-media-item"
                                @click="previewAlbum(album)"
                            >
                                <img v-if="album.mime_type === 'image'" :src="movSrc(album)" loading="lazy" />
                                <video v-else-if="album.mime_type.includes('video')" preload="metadata">
                                    <source :src="movSrc(album)">
                                </video>
                                <p v-if="album.title" class="oshi-panel-media-title">{{ album.title }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useApi } from '@/composables/api'
import { useFilePreview } from '@/store/filePreview'
import UserPanel from '@/components/Global/UserPanel.vue'

interface Tag {
    id: number
    text: string
}

interface Props {
    modelValue: boolean
    /** When provided, filters albums by this tag. When null/undefined, shows all users' oshi. */
    tag?: Tag | null
}

const props = withDefaults(defineProps<Props>(), {
    tag: null,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()

const api = useApi()
const filePreview = useFilePreview()

const loading = ref(false)
const albums = ref<any[]>([])

const fetchAlbums = async () => {
    loading.value = true
    albums.value = []
    const payload = props.tag ? { tag_id: props.tag.id } : {}
    const response = await api.post('/get_albums', payload)
    albums.value = response ?? []
    loading.value = false
}

watch(
    () => props.modelValue,
    (opened) => {
        if (opened) fetchAlbums()
        else albums.value = []
    },
)

// Re-fetch when tag changes while panel is open
watch(
    () => props.tag,
    () => {
        if (props.modelValue) fetchAlbums()
    },
)

const movSrc = (mov: any): string => {
    return mov.path.includes('intro')
        ? `/cdn/user_album/${mov.user_id}/${mov.path}`
        : `/cdn/user_album/${mov.user_id}/${mov.id}_${mov.user_id}_${mov.path}.${mov.extension}`
}

const previewAlbum = (file: any) => {
    filePreview.setFilePreview({
        active: true,
        files: [{ ...file, file_path: movSrc(file) }],
        source: 'user',
        source_board_id: null,
        index: 0,
        message: null,
    })
}
</script>

<style>
/* ── Oshi panel ── */
.oshi-panel-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    z-index: 10;
}
.oshi-panel {
    position: fixed;
    top: 0;
    right: 0;
    width: 30%;
    min-width: 320px;
    height: 100%;
    background: var(--background-color);
    z-index: 10;
    display: flex;
    flex-direction: column;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.18);
}
.oshi-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--bg2);
    flex-shrink: 0;
}
.oshi-panel-titles {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.oshi-panel-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--primary-color);
    margin: 0;
}
.oshi-panel-tag {
    font-size: 12px;
    color: var(--third-color);
    margin: 0;
}
.oshi-panel-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--primary-color);
    cursor: pointer;
    flex-shrink: 0;
}
.oshi-panel-close:hover {
    background: var(--bg2);
}
.oshi-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.oshi-panel-loading {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.oshi-panel-skeleton {
    width: 100%;
    height: 120px;
    border-radius: 6px;
    background: linear-gradient(90deg, var(--bg2) 25%, var(--bg3) 50%, var(--bg2) 75%);
    background-size: 200% 100%;
    animation: oshiSkeleton 1.4s infinite;
}
@keyframes oshiSkeleton {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.oshi-panel-empty {
    text-align: center;
    color: var(--third-color);
    font-size: 13px;
    padding: 40px 0;
}
.oshi-panel-user {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.oshi-panel-user-header {
    display: flex;
    align-items: center;
    gap: 8px;
}
.oshi-panel-user-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--primary-color);
    text-decoration: none;
}
.oshi-panel-user-name:hover {
    text-decoration: underline;
}
.oshi-panel-media-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.oshi-panel-media-item {
    position: relative;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    aspect-ratio: 1;
    background: var(--bg2);
}
.oshi-panel-media-item img,
.oshi-panel-media-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.oshi-panel-media-title {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 14px 6px 5px;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.6));
    font-size: 10px;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    pointer-events: none;
}

/* Transitions */
.oshiSlide-enter-active,
.oshiSlide-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.oshiSlide-enter-from,
.oshiSlide-leave-to {
    transform: translateX(100%);
}
.oshiFade-enter-active,
.oshiFade-leave-active {
    transition: opacity 0.3s ease;
}
.oshiFade-enter-from,
.oshiFade-leave-to {
    opacity: 0;
}

@media (max-width: 960px) {
    .oshi-panel {
        width: 100%;
        min-width: unset;
    }
}
</style>
