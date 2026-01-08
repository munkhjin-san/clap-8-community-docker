
import { defineStore } from "pinia"
import { ref } from "vue"
interface TutorialState {
    active: boolean,
    name: string[]
}
export const useTutorialStore = defineStore('tutorial', () => {
    const state = ref<TutorialState>({
        active: false,
        name: []
    })
    function setTutorial(payload: TutorialState) {
        state.value = payload
    }

    return { setTutorial, state}
})