import { defineStore } from 'pinia'
import { ref } from 'vue'

interface MenuPayload {
  name?: string
  id?: number | null
  user_id?: number | null
  parent?: string | null
}

export const useMenuStore = defineStore('menu', () => {
  const name = ref('')
  const id = ref<number | null>(null)
  const user_id = ref<number | null>(null)
  const parent = ref<string | null>(null)

  const setMenu = (payload: MenuPayload) => {
    id.value = payload.id ?? null
    name.value = payload.name ?? ''
    user_id.value = payload.user_id ?? null

    if (payload.parent) {
      console.log(payload.parent)
      parent.value = payload.parent
    }
  }

  const close = () => {
    id.value = null
    name.value = ''
    user_id.value = null
    parent.value = ''
  }

  const toggle = (payload: string) => {
    console.log(`current parent: ${parent.value}, payload: ${payload}`)
    if (parent.value == payload) {
      console.log('close')
      close()
    } else {
        console.log('open')
      setMenu({ parent: payload })
    }
  }

  return {
    name,
    id,
    user_id,
    parent,
    setMenu,
    close,
    toggle
  }
})