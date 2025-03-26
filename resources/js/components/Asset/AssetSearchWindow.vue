<template>
    <Modal @close="emit('close')">
        <template #title>
            <p>物品検索</p>
        </template>
        <template #content>
            <!-- <div style="display:flex;position:relative" class="advancedSearchWindowContainer">
                <input 
                    ref="postAdvancedSearch"
                    @click="isFocusing++"
                    @focus="searchFocus" 
                    @keyup.enter.prevent="triggerSearch" 
                    type="search"
                    spellcheck="false" 
                    autocomplete="off" 
                    autocorrect="off" 
                    autocapitalize="off" 
                    placeholder="キーワードを入力" 
                    id="advancedSearchInputPost" 
                    class="searchInputArea"
                    @keyup="setKeyWord"
                    style="border: solid thin var(--primary-color);padding:5px 10px;width:100%;color: var(--primary-color);min-height: 35px;"
                />
            </div> -->
            <div>
                <ItemSelector 
                    :options="possibleProjects"
                    v-model="projects"
                    place-holder="プロジェクト"
                    :close-on-select="true"
                    :clearable="true"
                    :reduce="option => option.id"
                    label="name"
                />
            </div>
            <div class="si-box">
                <MemberSelector 
                    :options="choosAbleMembers"
                    place-holder="メンバー"
                    label="name"
                    v-model="member"
                    :multiple="true"
                />
            </div>
            <div class="si-box">
                <ItemSelector 
                    :options="statuses"
                    place-holder="ステータス"
                    :multiple="false"
                    :close-on-select="true"
                    :clearable="true"
                    :reduce="option => option.value"
                    label="label"
                    v-model="status"
                />
            </div>
            <div class="si-box">
                <ItemSelector 
                    :options="classifications"
                    place-holder="分類"
                    :multiple="false"
                    :close-on-select="true"
                    :clearable="true"
                    :reduce="option => option.value"
                    label="label"
                    v-model="classification"
                />
            </div>
            <div class="si-box">
                <LoaderButton content="検索する" :loading="loading" @triggered="searchStart"/>
            </div>
        </template>
    </Modal>
</template>
<script lang="ts" setup>
import { computed, ref, watch } from 'vue';
import Modal from '../Global/Modal.vue';
import MemberSelector from '../Form/MemberSelector.vue';
import ItemSelector from '../Form/ItemSelector.vue';
import { User } from '@/interface/globalInterface';
import LoaderButton from '../Global/LoaderButton.vue';
const emit = defineEmits(['close', 'search'])
const props = defineProps([
    'possibleProjects', 
    'possibleMembers',
    'statuses',
    'classifications'
])
const isFocusing = ref(0)
const keyword = ref('')
const projects = defineModel<number[]>('projects')
const member = defineModel<User[]>('member')
const status = defineModel('status')
const classification = defineModel('classification')
const loading = ref(false)
const searchFocus = () => {
    isFocusing.value++
}
const triggerSearch = () => {
    isFocusing.value = 0
}
const setKeyWord = (event) => {
    if (event.which === 38 || event.which === 40 || event.which === 13) {
        event.preventDefault()
        return
    } else {
        keyword.value = event.currentTarget.value
    }
}

const searchStart = () => {
    emit('search')
}

const choosAbleMembers = computed(() => {
    if (!projects.value?.length) return props.possibleMembers;
    const filterProjects = props.possibleProjects.filter(project =>
        projects.value?.includes(project.id)
    );
    const memberLists = filterProjects.map(project => 
        [...project.members.map(member => member.id), ...project.manager.map(manager => manager.id)]
    );
    const commonMemberIds = memberLists?.reduce((a, b) => a.filter(id => b.includes(id)));

    return props.possibleMembers.filter(member => commonMemberIds.includes(member.id));
});
</script>