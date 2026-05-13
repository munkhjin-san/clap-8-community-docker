<template>
    <div class="h-full w-full max-w-[4000px] mx-auto">
    <openai-chatkit
        ref="chatkitRef"
        class="h-full w-full"
    />
  </div>
</template>

<script setup lang="ts">
import { useApi } from '@/composables/api'
import { useTheme } from '@/store/theme'
import { onMounted, ref } from 'vue'



const chatkitRef = ref<any | null>(null)
const api = useApi()
const theme = useTheme()
onMounted(() => {
  chatkitRef.value?.setOptions({
    api: {
      async getClientSecret(currentClientSecret?: string) {
        const data = await api.post('/chatkit/session')

        if (!data?.client_secret) {
          console.error('No client_secret returned:', data)
          throw new Error('Missing client_secret')
        }

        return data.client_secret
      },
    },
    composer: {
        placeholder: 'メッセージを入力...',
        dictation: {
            enabled: true
        }
    },
    startScreen: {

        prompts: [
            {icon: 'circle-question', label: 'リフレッシュ補助金について', prompt: 'リフレッシュ補助金について' },
            {icon: 'circle-question', label: '労務管理全般', prompt: '有給休暇の取得要件について' },
            {icon: 'circle-question', label: '給与計算', prompt: '時間外労働の割増賃金率について' },
            {icon: 'circle-question', label: '社会保険・労働保険', prompt: '社会保険の加入要件について' },
            {icon: 'circle-question', label: '人事評価・昇進・降格・解雇', prompt: '解雇の正当な理由について' },
        ]

    },
    locale: 'ja',
    theme: {
        colorScheme: theme.dark ? 'dark' : 'light',
        radius: 'soft',
        typography: {
            fontFamily: "'Noto Sans JP', sans-serif",
            baseSize: 14,
            fontSources: [
                {
                    family: 'Noto Sans JP',
                    src: 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap',
                    weight: 400,
                    style: 'normal',
                    display: 'swap'
                }
            ]
        },
        density: 'spacious',
    },
    widgets: {
        async onAction(action:any, item:any) {
            console.log('Widget action triggered:', action, item)
            if (action.type === "file.open") {
                // const fileId = action.payload?.id

                // // Vue side logic
                // openFileModal(fileId)
                // // or router.push(`/files/${fileId}`)
            }
        },
    },
  })
})
</script>