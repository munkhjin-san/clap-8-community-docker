<template>
    <div class="post-root">
        <div class="calendar-root-header" style="min-height: 60px;">
            <HamBurger v-if="$store.state.mobile"/>
            <div class="calendar-search-wrap" id="memberSearchResultWindow" >
                <PostSearchBar 
                    @searchStart="searchStart"  
                    :searching="searching"
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`メンバーを検索`"
                />                
            </div>         
        </div>
        <div class="member-container">
            <div v-for="position in positions" style="">
                <p class="position-title">{{position.name}}</p>
                <div v-if="position.employees" class="employee-container">
                    <MemberItem 
                        v-for="member in position.employees"
                        :key="member.id"
                        :member="member" 
                    />
                </div>
            </div>
        </div>       
    </div>        
</template>
<script>
import PostSearchBar from '../Post/PostSearchBar.vue';
import HamBurger from '../Global/HamBurger.vue';
import MemberItem from './MemberItem.vue';

export default{
    data(){
        return{
            memberList: [],
            searching: false,
            possibleWords: []
        }
    },
    computed: {
        positions(){
            if(this.memberList.length){
                let list = []
                for(let pos of this.memberList){
                    let item = pos
                    let members = pos.employees
                    if(this.possibleWords.length){
                        const filteredUsers = members.filter(user => {
                            return this.possibleWords.some(keyword => {
                                const indexes = ['name', 'name_kana', 'phone_number', 'work_emai'];
                                let word_set = ''
                                for(const index of indexes){
                                    if(user[index]){
                                        word_set = word_set + ` ${user[index]}`
                                    }
                                }
                                return word_set.includes(keyword)
                            });
                        });
                        if(filteredUsers.length){
                            item['employees'] = filteredUsers
                            list.push(item)
                        }
                    }else{
                        if(members.length){
                            list.push(pos)
                        }                        
                    }                    
                }
                return list
            }else{
                return []
            }
        },
    },
    components: {
        PostSearchBar,
        HamBurger,
        MemberItem
    },
    mounted(){
        this.getMembers()
    },
    methods:{
        searchStart(val){
            if(val){                    
                const encoded = encodeURI(val);
                const url_add = 'https://www.google.com/transliterate?langpair=ja-Hira|ja&text='
                fetch(url_add + encoded) 
                .then((response) => response.json())
                .then((data) => {
                    this.possibleWords = []
                    data.forEach(ob => {
                        if(ob.length > 1){
                            const list = ob[1]
                            if(list.length){
                                list.forEach(word => {
                                    this.possibleWords.push(word)
                                })
                            }
                        }
                        
                    })
                })
                .catch((e) => {
                    this.possibleWords.push(after)
                });
            }else{
                this.possibleWords = []
            }
        },
        getMembers(){
            axios.post('/get_members_list').then(response => {  
                this.memberList = response.data                 
            }).catch(function (error) {                        
            }.bind(this));
        }
    }
}
</script>
<style>
.member-container{
    height: calc(100% - 60px);
    overflow: hidden auto;
    padding: 0 20px;
    color: var(--primary-color);
}
.employee-container{
  display: flex;
  flex-wrap: wrap;
  flex-direction: row;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  background: var(--background-color);
}
.employee-card{
  width:33.33%;
  margin: 10px 0;
  transition: transform .1s;
}
.employee-card > .employee-card-inner{
  margin:0 10px;
  background: var(--background-color);
}
.employee-card-body{
  padding: 10px;
  display: flex;
  align-items: center;
}
.employee-info{  
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
  height: fit-content;
  margin: auto 0 auto 20px;
  font-size: 14px;
}
.employee-user-list{
  padding: 10px 0 0 0; 
  line-height: 17px;
}
.position-title{
  font-size: 14px;
  padding: 15px 0;
}
@media screen and (max-width: 959px) {
    .employee-card{
        width: 100%;
        transition: transform .1s;
    }  
}
</style>