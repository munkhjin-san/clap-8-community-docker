<template>
    <div style="background:inherit;">        
        <div style="position:relative;background:inherit;">
            <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);">
                <span style="z-index:5" class="form-plc smallPlc">
                    {{ placeHolder }}
                    
                </span>                 
                <drop-selector        
                    :class="['global-user-select']"               
                    :create-option="tag => ({ text: tag, id: randomId()})"
                    style="background-image: unset; margin:0px;" 
                    v-model="selectedTag" 
                    name="to_users"             
                    :options="tagOptions" 
                    @search="search"
                    multiple 
                    taggable
                    label="text"
                    :components="{Deselect}"
                    :closeOnSelect="false"
                >                       
                    <template v-slot:no-options="{ search, searching }">
                        <template v-if="searching">
                            <span>検索結果ありません。</span>                            
                        </template>
                        <span style="font-size: 13px;opacity: 0.5" v-else>タグを検索するにはキーワードを入力してください</span>
                    </template>
                </drop-selector>
                <!-- <button @click="generateTag">タグ自動生成</button> -->
            </div>            
        </div>
    </div>
</template>
<script setup>
import { markRaw, onMounted, ref, watch,} from 'vue';

import OpenAI from "openai";

    const props = defineProps(['placeHolder', 'specialTags', 'suggestion'])
    const tagOptions = ref([])
    const Deselect = markRaw({
        template: `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 32 32"><path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path></svg>`
    })      
    const selectedTag = defineModel()
    const superCounter = ref(0)
    const suggestedTagsOptions = ref([])
    onMounted(() => {
        superFetch()
        // console.log(props.suggestion)
    })
    // watch(props.suggestion, (after) => {
    //     console.log(after)
    //     if(after && after.length && after.length > 4){
    //         search(key)
    //     }
    // })
    watch(() => props.suggestion, (after) => {
        // console.log(after)
        // if(after && after.length && after.length > 4){
        //     suggestedTags(after)
        // }
    })       
    // const generateTag = async() => {
    //     // console.log(props.suggestion)
    //     // return
    //     const openai = new OpenAI({
    //         apiKey: import.meta.env.VITE_OPENAI_API_KEY,
    //         dangerouslyAllowBrowser: true 
    //     });

    //     const response = await openai.chat.completions.create({
    //         model: "gpt-3.5-turbo",
    //         messages: [
    //             {
    //                 "role": "system",
    //                 "content": "挙げられた投稿文書の中から分析し、ちゃんと意味を持つタグにするキーワードをピックアップしコンマで分けてください。最大10件。"
    //             },
    //             {
    //                 "role": "user",
    //                 "content": props.suggestion
    //             }
    //         ],
    //         temperature: 0.5,
    //         max_tokens: 1000,
    //     });
    //     const tags = response?.choices[0].message?.content || ''
    //     const tagsList = tags.split(',').map(item => item.trim());
    //     console.log(selectedTag)
    //     const pre = []
    //     tagsList.forEach(tag => {
    //         pre.push({
    //             id: randomId(),
    //             text: tag
    //         })
    //     });
    //     selectedTag.value = pre
        

    // } 
    // const suggestedTags = _.debounce(async(key) => {
    //     const openai = new OpenAI({
    //         apiKey: import.meta.env.VITE_OPENAI_API_KEY,
    //         dangerouslyAllowBrowser: true 
    //     });

    //     const response = await openai.chat.completions.create({
    //         model: "gpt-3.5-turbo",
    //         messages: [
    //             {
    //             "role": "system",
    //             "content": "You will be provided with a block of text, and your task is to extract a list of keywords from it."
    //             },
    //             {
    //             "role": "user",
    //             "content": `ドコモショップのスマホ初期設定スタッフとして
    //             活動している宮本さんをご紹介させていただきます。
    //             昨年11月からドコモショップへスマートフォンの初期設定や操作案内などを対応し
    //             ています。
    //             店舗からの評価も良く作業が早く店長絶賛でした。
    //             そんな宮本さんにナイスです。`
    //             }
    //         ],
    //         temperature: 0.5,
    //         max_tokens: 1000,
    //         top_p: 1,
    //     });
    // }, 350)
    const search = _.debounce((key) => {
        axios.post('/post_get_tags', {key: key, super: false})
        .then(response => {
            tagOptions.value = response.data
        })
    }, 350)
    const superFetch = () => {
        if(superCounter > 0) return
        axios.post('/post_get_tags', {key: '', super: true, special: props.specialTags && props.specialTags.length ? props.specialTags : []})
        .then(response => {
            tagOptions.value = response.data
            superCounter.value ++
        })
    }
    const randomId = () => {
        return Math.random().toString(36).substring(5);
    }



</script>
<style lang="scss">
.selectorFocus{
    border: 1px solid var(--primary-color) !important;
}
.global-user-select{
    border: none !important;
}
</style>