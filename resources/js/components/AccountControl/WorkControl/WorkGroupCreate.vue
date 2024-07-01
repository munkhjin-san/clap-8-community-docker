<template>
    <div class="chatCreate scrollable">
        <div class="recordFormTitle" style="display:flex;">
            <p>{{ editWorkGroupData ? 'ワークグループを編集する' : '新しいワークグループを作成する'}} </p>
            <div class="cursor-pointer" @click="emit('closeModal')" style="position:unset; margin:auto 0 auto auto;display:flex;">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                    <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                </svg>                        
            </div> 
        </div>
        <div style="background:inherit;height:100%;display:flex;flex-direction:column">  
            <div class="si-box">
                <ShortInput 
                    ref="workGroupName"
                    v-model="work_group_name"
                    placeHolder="グループ名"
                    rules="required"
                    name="work_group_name"
                />   
            </div>
            <div class="si-box">
                <MemberSelector 
                    placeHolder="承認者"
                    v-model="workgroup_pm"
                    :options="optionPms"
                    rules=""
                    name="workgroup_pm"
                    :closeOnSelect="true"
                    :limit="1"
                />
            </div>
            <div class="si-box">
                <MemberSelector 
                    placeHolder="メンバー"
                    v-model="workgroup_users"
                    :options="optionUsers"
                    rules=""
                    name="workgroup_users"
                    :closeOnSelect="false"
                />
            </div>
            <div class="si-box" style="margin-top: auto; margin-bottom: 30px;">
                <LoaderButton
                    @triggered="workGroupAdd()"
                    :loading="processing"
                    content="保存する" 
                />
            </div>           
        </div>
    </div>
</template>
<script setup>
    import ShortInput from '../../Form/ShortInput.vue';
    import MemberSelector from '../../Form/MemberSelector.vue';
    import LoaderButton from '../../Global/LoaderButton.vue';
    import { inject, onMounted, ref } from 'vue';
    import { computed } from 'vue';
    const props = defineProps(['editWorkGroupData', 'userList'])
    const emit = defineEmits(['postFinish', 'closeModal'])
    const users = computed(() => {
        return props.editWorkGroupData ? props.editWorkGroupData.members.filter(ob => ob.pivot.authority == 0) : []
    })
    const pm = computed(() => {
        return props.editWorkGroupData ? props.editWorkGroupData.members.filter(ob => ob.pivot.authority == 1) : []
    })
    const work_group_name = ref(props.editWorkGroupData ? props.editWorkGroupData.name : '')
    const processing = ref(false)
    const workgroup_users = ref(users.value ? users.value : [])
    const workgroup_pm = ref(pm.value ? pm.value : [])
    const { notify, info } = inject('dialog')
    const workGroupName = ref(null)
    const optionUsers = computed(() => {
        return props.userList.filter(user => user.retire == 0 && user.partner_flag == 0).map(user => ({
                id: user.id,
                name: user.name,
                icon_id: user.icon_id,
                name_kana: user.name_kana,
                position_id: user.position_id
            }))
    })
    const optionPms = computed(() => {
        return optionUsers.value.filter(user => user.position_id === 6)
    })
    const workGroupAdd = async() => {
        const result = await workGroupName.value.validate();
        
        if (processing.value) return;

        if(result.valid){
            processing.value = true;
            console.log(workgroup_pm.value)
            const params = {
                work_group_id : props.editWorkGroupData ? props.editWorkGroupData.id : null,
                work_group_name : work_group_name.value,
                work_group_users : workgroup_users.value.map(ob => ob.id),
                work_group_pm : workgroup_pm.value.map(ob => ob.id)
            }
            try {
                await axios.post('/work_group_add', params)
                info(props.editWorkGroupData ? '更新しました。' : '作成しました。')
                emit('postFinish')
            } catch (error) {
                if (error.response) notify('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) notify('エラーが発生しました。')
                else notify('エラーが発生しました。 ' + error.message)
            } finally {
                processing.value = false
            }
        }
    }
</script>
<style scoped lang="scss">
    .work-delete-button{
        width: auto; 
        height: 30px; 
        line-height: 30px; 
        background: var(--primary-button); 
        color: rgb(255, 255, 255); 
        cursor: pointer; font-size: 12px; 
        padding: 0px 8px;
        margin-right: 20px;
    }
    .post-error{
        bottom: -12px !important;
    }
#workGroupContent {
    z-index: 3;
    width: 50%;
    height: 50%;
    padding: 0px;
    background: #fff;
    -webkit-overflow-scrolling: touch;
    overflow:auto;
  }
  .workGroupFormHeader {
    width: 100%;
    height: auto;
    margin: 0 auto;
    margin-bottom: 30px;
    font-size: 17px;
}

.input-wrapper{
    display: flex;
    flex-direction: row;
    align-items: center;
    position: relative;
    background: inherit;
  }
  .workGroup.form-label{
    font-size:14px;
    width: 10%;
  }
  .input-inner-wrapper{
    position: relative;
    width:100%;
  }
  .w-100{
    width:100%;
  }
  .recordText-workGroup {
    width: 100%;
    margin: 0 auto;
    padding: 10px;
    line-height: 1.6;
    font-size: 16px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    color: var(--primary-color);
  }
  .recordText-workGroup::placeholder{
    font-size:14px !important;
  }
 .selectArea {
    height: auto ;
    background-repeat: no-repeat;
    background-position: top 5px right 5px;
}
.mt-20{
    margin-top: 20px;
  }
  

</style>