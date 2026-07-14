import { ref } from 'vue'

/**
 * Optional browser-tab title override. When set (e.g. to an opened custom app's name), it takes
 * precedence over the route's static meta title. Reset on every navigation (see router.afterEach)
 * and set by the view that wants a dynamic title.
 */
export const pageTitleOverride = ref<string | null>(null)
