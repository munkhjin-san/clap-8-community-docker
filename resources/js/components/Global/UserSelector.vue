<template>
    <div style="position:relative;background:inherit;">
        <Form :ref="uId" v-slot="{ errors }" style=";background:inherit">
        <span style="z-index:5" :class="{smallPlc : $store.state.activeInput == 'taskUserSelector'|| (value.length) || qualified_users.length}" class="form-plc">{{ placeHolder }}</span> 
        <v-select 
            label="name"
            :class="['taskUserSelecArea', 'independSelector', {selectorFocus : $store.state.activeInput == 'taskUserSelector'}]"            
            v-model="qualified_users" 
            name="qualified_users" 
            :options="options"
            :filterable="false"
            multiple
            @search="fetchOptions" 
            @search:focus="$store.commit('setActiveInput', 'taskUserSelector')"
            @search:blur="$store.commit('setActiveInput', '')"
            @input="value = $event.target.value"
            :inputId="'taskUserSelector'"
            :components="{Deselect}"
        >
            <template #selected-option="option">
                <div style="display: flex;align-items: center;gap:10px;font-size: 13px;padding: 5px 0;margin-right: 5px;">
                    <UserIcon :user="option" imgClass="userMidIcon"/>
                    <p>{{ option.name }}</p>
                </div>
            </template>
            <template #no-options="{ search, searching, loading }">
                <template v-if="searching">
                    <div style="font-size: 14px;opacity: 0.8;padding:10px 0;">{{ $t('noMembersFound') }}</div>                    
                </template>
                <div v-else style="font-size: 14px;opacity: 0.8;padding:10px 0;">お名前を入力してください。</div>
            </template>
            <template slot="option" slot-scope="option" v-slot:option="option" >
                <div style="display: flex;align-items: center;gap:10px;font-size: 13px;padding: 5px 0;">
                    <UserIcon :user="option" imgClass="userMidIcon"/>
                    <p>{{ option.name }}</p>
                </div>
            
            </template>
            
        </v-select>
        <Field 
            autocomplete="off" 
            :id="uId" 
            :class="['recordText']" 
            type="text" 
            :name="name" 
            :rules="rules" 
            v-model="fakeValue"
            style="display:none"
        />
        <span class="form-error" style="font-size: 11px;color:tomato">{{ errors[uId] }}</span>
        </Form>
        <!-- <span style="position:unset;" v-if="qualified_users.length == 0" class="valid-error">{{$t('required')}}</span> -->
    </div>
</template>
<script>
import UserIcon from '../Board/Mixed/UserIcon.vue';
import { markRaw, onDeactivated } from 'vue';
import { Field, Form } from 'vee-validate'
export default{
    props: ['uId', 'selfInclude', 'initialSelected', 'placeHolder', 'name', 'rules', 'board', 'selectAll'],
    emit: ['setUser'],
    data(){
        return{
            qualified_users: this.initialSelected,
            options: [],
            possibleWords: [],
            value: '',
            Deselect: markRaw({
                template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
            })      
        }
    },
    mounted(){
        if(this.initialSelected){
            this.$emit('setUser', this.initialSelected)
        }
        if(this.board){
            this.options = this.board.board_to_users.map(ob => ob.user)
        }
    },
    watch:{
        qualified_users(after){
            this.$emit('setUser', after)
        },
        selectAll(after){
            if (after) {
                this.qualified_users = this.options
            }else{
                // this.qualified_users = []
            }
        }
        
    },
    methods:{   
        localLoad(search, loading){
            let users = this.board.board_to_users.map(ob => ob.user)
            let filtered = users.filter(user => user.name.toLowerCase().includes(search.toLowerCase()))
            this.options = filtered
            setTimeout(() => {
                loading(false);
            }, 0);
        },
        fetchOptions(search, loading) {
            if(search.length) {
                
                loading(true);
                
                  
                if(this.board){
                    this.localLoad(search, loading)   
                }else{
                    this.search(loading, search, this);
                }                     
                        
                                  

                

            }else{
                if(this.board){
                    this.options = this.board.board_to_users.map(ob => ob.user)
                }else{
                    this.options = []
                }
                
            }
        },
        search: _.debounce((loading, search, vm) => {
            
            axios.post('/post_get_users', {key: search, self: vm.selfInclude, board_id: vm.board ? vm.board.id : null})
            .then(response => {
                vm.options = response.data
                loading(false);
            })
            
            
        }, 350),
    },
    computed:{
        fakeValue(){
            return this.qualified_users.length ? 'value' : ''
        }
    },
    components: {
        UserIcon,
        Field, 
        Form,
    }
}
</script>
<style lang="scss">
// .independSelector .vs__search{
//     padding-left: 13px;
// }
.selectorFocus{
    border: 1px solid var(--primary-color) !important;
}
</style>