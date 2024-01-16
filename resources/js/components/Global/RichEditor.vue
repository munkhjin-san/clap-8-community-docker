<template>
    <div class="editor-root" style="padding: 0;overflow: hidden auto;height: calc(100% - 30px);">
        <div class="toolbar-root" v-if="editor">
            <button @click="editor.chain().focus().toggleBold().run()" :class="['toolbar-button', {'command-active': editor.isActive('bold')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="inherit"><path d="M8 11H12.5C13.8807 11 15 9.88071 15 8.5C15 7.11929 13.8807 6 12.5 6H8V11ZM18 15.5C18 17.9853 15.9853 20 13.5 20H6V4H12.5C14.9853 4 17 6.01472 17 8.5C17 9.70431 16.5269 10.7981 15.7564 11.6058C17.0979 12.3847 18 13.837 18 15.5ZM8 13V18H13.5C14.8807 18 16 16.8807 16 15.5C16 14.1193 14.8807 13 13.5 13H8Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleItalic().run()"  :class="['toolbar-button', {'command-active': editor.isActive('italic')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M15 20H7V18H9.92661L12.0425 6H9V4H17V6H14.0734L11.9575 18H15V20Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleUnderline().run()"  :class="['toolbar-button', {'command-active': editor.isActive('underline')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 3V12C8 14.2091 9.79086 16 12 16C14.2091 16 16 14.2091 16 12V3H18V12C18 15.3137 15.3137 18 12 18C8.68629 18 6 15.3137 6 12V3H8ZM4 20H20V22H4V20Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleStrike().run()" :class="['toolbar-button', {'command-active': editor.isActive('strike')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M13 9H11V6H5V4H19V6H13V9ZM13 15V20H11V15H13ZM3 11H21V13H3V11Z"></path></svg>
            </button>

            <button @click="setLink" :class="['toolbar-button', {'command-active': editor.isActive('link')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.3638 15.5355L16.9496 14.1213L18.3638 12.7071C20.3164 10.7545 20.3164 7.58866 18.3638 5.63604C16.4112 3.68341 13.2453 3.68341 11.2927 5.63604L9.87849 7.05025L8.46428 5.63604L9.87849 4.22182C12.6122 1.48815 17.0443 1.48815 19.778 4.22182C22.5117 6.95549 22.5117 11.3876 19.778 14.1213L18.3638 15.5355ZM15.5353 18.364L14.1211 19.7782C11.3875 22.5118 6.95531 22.5118 4.22164 19.7782C1.48797 17.0445 1.48797 12.6123 4.22164 9.87868L5.63585 8.46446L7.05007 9.87868L5.63585 11.2929C3.68323 13.2455 3.68323 16.4113 5.63585 18.364C7.58847 20.3166 10.7543 20.3166 12.7069 18.364L14.1211 16.9497L15.5353 18.364ZM14.8282 7.75736L16.2425 9.17157L9.17139 16.2426L7.75717 14.8284L14.8282 7.75736Z"></path></svg>
            </button>

            <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="['toolbar-button', {'command-active': editor.isActive('heading', {level: 2})}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M4 4V11H11V4H13V20H11V13H4V20H2V4H4ZM18.5 8C20.5711 8 22.25 9.67893 22.25 11.75C22.25 12.6074 21.9623 13.3976 21.4781 14.0292L21.3302 14.2102L18.0343 18H22V20H15L14.9993 18.444L19.8207 12.8981C20.0881 12.5908 20.25 12.1893 20.25 11.75C20.25 10.7835 19.4665 10 18.5 10C17.5818 10 16.8288 10.7071 16.7558 11.6065L16.75 11.75H14.75C14.75 9.67893 16.4289 8 18.5 8Z"></path></svg>
            </button>
            <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="['toolbar-button', {'command-active': editor.isActive('heading', {level: 3})}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 8L21.9984 10L19.4934 12.883C21.0823 13.3184 22.25 14.7728 22.25 16.5C22.25 18.5711 20.5711 20.25 18.5 20.25C16.674 20.25 15.1528 18.9449 14.8184 17.2166L16.7821 16.8352C16.9384 17.6413 17.6481 18.25 18.5 18.25C19.4665 18.25 20.25 17.4665 20.25 16.5C20.25 15.5335 19.4665 14.75 18.5 14.75C18.214 14.75 17.944 14.8186 17.7056 14.9403L16.3992 13.3932L19.3484 10H15V8H22ZM4 4V11H11V4H13V20H11V13H4V20H2V4H4Z"></path></svg>
            </button>

            <!-- <button @click="editor.chain().focus().toggleOrderedList().run()" :class="['toolbar-button', {'command-active': editor.isActive('orderedList')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.75024 3.5H4.71733L3.25 3.89317V5.44582L4.25002 5.17782L4.25018 8.5H3V10H7V8.5H5.75024V3.5ZM10 4H21V6H10V4ZM10 11H21V13H10V11ZM10 18H21V20H10V18ZM2.875 15.625C2.875 14.4514 3.82639 13.5 5 13.5C6.17361 13.5 7.125 14.4514 7.125 15.625C7.125 16.1106 6.96183 16.5587 6.68747 16.9167L6.68271 16.9229L5.31587 18.5H7V20H3.00012L2.99959 18.8786L5.4717 16.035C5.5673 15.9252 5.625 15.7821 5.625 15.625C5.625 15.2798 5.34518 15 5 15C4.67378 15 4.40573 15.2501 4.37747 15.5688L4.3651 15.875H2.875V15.625Z"></path></svg>
            </button>
            <button @click="editor.chain().focus().toggleBulletList().run()" :class="['toolbar-button', {'command-active': editor.isActive('bulletList')}]">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 4H21V6H8V4ZM3 3.5H6V6.5H3V3.5ZM3 10.5H6V13.5H3V10.5ZM3 17.5H6V20.5H3V17.5ZM8 11H21V13H8V11ZM8 18H21V20H8V18Z"></path></svg>
            </button> -->
            <div style="display: flex;position: relative;">
                <button @click.stop="$store.commit('setMenu', { name: 'ckpick', id: 55})" :class="['toolbar-button', {'command-active': editor.isActive('textStyle', 'color')}]">
                    <svg style="color:#ff8787" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.55397 22H3.3999L10.9999 3H12.9999L20.5999 22H18.4458L16.0458 16H7.95397L5.55397 22ZM8.75397 14H15.2458L11.9999 5.88517L8.75397 14Z"></path></svg>
                </button>
                <button @click.stop="$store.commit('setMenu', { name: 'ckpick', id: 20})" :class="['toolbar-button', {'command-active': editor.isActive('highlight')}]">
                    <svg style="background: #fcffa6;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M5.55397 22H3.3999L10.9999 3H12.9999L20.5999 22H18.4458L16.0458 16H7.95397L5.55397 22ZM8.75397 14H15.2458L11.9999 5.88517L8.75397 14Z"></path></svg>
                </button>
                <Transition name="slidePop">
                    <div 
                        id="ckpick" 
                        class="color-grid" 
                        v-if="$store.state.menu.name == 'ckpick' && ($store.state.menu.id == 20 || $store.state.menu.id == 55)"
                        :style="{left: `-${$store.state.menu.id}px`}"
                        >                        
                        <div v-for="row in colorShadesArray" :key="row[0]" class="color-row">
                            <div 
                            v-for="color in row"
                                :key="color"
                                class="color-item"
                                :style="{ backgroundColor: color }"
                                @click="selectColor(color)"
                            ></div>
                        </div>
                        <button style="background: var(--bg2);width: fit-content;padding: 5px;margin-top: 10px;font-size: 12px;" @click="resetColor">リセット</button>
                    </div>
                </Transition>
            </div>

            <!-- <button :class="['toolbar-button']">
                <label for="videoPicker" class="toolbar-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M16 4C16.5523 4 17 4.44772 17 5V9.2L22.2133 5.55071C22.4395 5.39235 22.7513 5.44737 22.9096 5.6736C22.9684 5.75764 23 5.85774 23 5.96033V18.0397C23 18.3158 22.7761 18.5397 22.5 18.5397C22.3974 18.5397 22.2973 18.5081 22.2133 18.4493L17 14.8V19C17 19.5523 16.5523 20 16 20H2C1.44772 20 1 19.5523 1 19V5C1 4.44772 1.44772 4 2 4H16ZM15 6H3V18H15V6ZM8 8H10V11H13V13H9.999L10 16H8L7.999 13H5V11H8V8ZM21 8.84131L17 11.641V12.359L21 15.1587V8.84131Z"></path></svg>
                </label>
                <input @change="uploadVideo" type="file" style="display: none;" name="videoPicker" id="videoPicker"/>
            </button> -->

            <button :class="['toolbar-button']">
                <label for="imagePicker" class="toolbar-button">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M21 15V18H24V20H21V23H19V20H16V18H19V15H21ZM21.0082 3C21.556 3 22 3.44495 22 3.9934V13H20V5H4V18.999L14 9L17 12V14.829L14 11.8284L6.827 19H14V21H2.9918C2.44405 21 2 20.5551 2 20.0066V3.9934C2 3.44476 2.45531 3 2.9918 3H21.0082ZM8 7C9.10457 7 10 7.89543 10 9C10 10.1046 9.10457 11 8 11C6.89543 11 6 10.1046 6 9C6 7.89543 6.89543 7 8 7Z"></path></svg>
                </label>
                <input @change="uploadVideo" type="file" style="display: none;" name="imagePicker" id="imagePicker"/>
            </button>
        </div>
        <div class="editor-wrap">             
            <editor-content :editor="editor" />
        </div>        
    </div>  
</template>  
<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import { Color } from '@tiptap/extension-color'
import TextStyle from '@tiptap/extension-text-style'
import Highlight from '@tiptap/extension-highlight'
import Image from '@tiptap/extension-image'
import { computed, onMounted, ref } from 'vue'
import { useStore } from 'vuex'

const props = defineProps(['initilaValue'])
const store = useStore()
const editor = useEditor({
  content: props.initilaValue,
  extensions: [
    StarterKit,
    Underline,
    Link,
    TextStyle,
    Color,
    Highlight.configure({
        multicolor: true,
    }),
    Image
  ],
})
defineExpose({editor})
const colorShadesArray = [
  ['#000000', '#666666', '#999999', '#cccccc', '#d9d9d9', '#f3f3f3', '#ffffff'],
  ['#980000', '#ff9900', '#ffff00', '#00ffff', '#4a86e8', '#9900ff', '#ff00ff'],
  ['#e6b8af', '#fce5cd', '#fff2cc', '#d0e0e3', '#c9daf8', '#d9d2e9', '#ead1dc'],
  ['#dd7e6b', '#f9cb9c', '#ffe599', '#a2c4c9', '#a4c2f4', '#b4a7d6', '#d5a6bd'],
  ['#cc4125', '#f6b26b', '#ffd966', '#76a5af', '#6d9eeb', '#8e7cc3', '#c27ba0'],
  ['#a61c00', '#e69138', '#f1c232', '#45818e', '#3c78d8', '#674ea7', '#a64d79'],
  ['#85200c', '#b45f06', '#bf9000', '#134f5c', '#1155cc', '#351c75', '#741b47'],
  ['#5b0f00', '#783f04', '#7f6000', '#0c343d', '#1c4587', '#20124d', '#4c1130']
];
const resetColor = () => {
    if(store.state.menu.id == 55){
        editor.value.chain().focus().unsetColor().run()
    }else if(store.state.menu.id == 20){
        editor.value.chain().focus().unsetHighlight().run()
    }
    store.commit('setMenu', {id: null, name: ''})
}
const selectColor = (color) => {
    console.log(color)
    if(store.state.menu.id == 55){
        editor.value.chain().focus().setColor(color).run()
    }else if(store.state.menu.id == 20){
        editor.value.chain().focus().setHighlight({ color: color }).run()
    }
    store.commit('setMenu', {id: null, name: ''})
}
const setLink = () => {
      const previousUrl = editor.value.getAttributes('link').href
      const url = window.prompt('URL', previousUrl)
      if (url === null) {
        return
      }
      if (url === '') {
        editor.value
          .chain()
          .focus()
          .extendMarkRange('link')
          .unsetLink()
          .run()

        return
      }
      editor.value
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url })
        .run()
    }
const uploadingProgress = ref(0)
const uploadVideo = (event) => {
    console.log(event.target.files)
    const files = event.target.files
    if(files){
        const formData = new FormData()                   
      
        formData.append('file', files[0])
        formData.append('type', event.target.id)
        // this.uploadStart(formData)
    
        axios.post('/upload_lesson_file', formData , { onUploadProgress: (e) => uploadingProgress.value = Math.floor((e.loaded * 100) / e.total) } )
        .then(response =>{    
            if(event.target.id == 'imagePicker'){
                editor.value.chain().focus().setImage({ src: response.data }).run()
            }else if(event.target.id == 'videoPicker'){
                editor.value.chain().focus().insertContent(`hello`).run()
                console.log('tttttttt')
            }
            
            
        })
        .catch((error) => {
        })
        .then(() => {

        });

    }
}   
</script>
<style scoped>

.color-grid {
    top: 30px;
    display: flex;
    flex-direction: column;
    position: absolute;
    background: var(--background-color);
    z-index: 5;
    padding: 10px;
    box-shadow: 0 1px 2px rgba(0,0,0,.07), 0 2px 4px rgba(0,0,0,.07), 0 4px 8px rgba(0,0,0,.07), 0 8px 16px rgba(0,0,0,.07), 0 16px 32px rgba(0,0,0,.07), 0 32px 64px rgba(0,0,0,.07);

}

.color-row {
  display: flex;
  gap: 5px;
}

.color-item {
  width: 15px;
  height: 15px;
  cursor: pointer;
}

.selected-color {
  width: 50px;
  height: 50px;
  margin-top: 10px;
  border-radius: 4px;
}
.tiptap{
    outline: none;
}
.editor-toolbar{
    padding: 10px;
    background: var(--bg2);
}
.editor-wrap{
    line-height: 1.3;
    padding: 15px;
}
.editor-wrap h1, h2, h3 {
    font-size: revert !important;
    font-weight: normal !important;
}
.ql-container {
    font-family: 'Noto Sans JP', sans-serif !important;
    font-size: 16px;
}
.ql-editor {
    min-height: 300px;
}
.toolbar-button{
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 35px;
    min-height: 35px;
    color: var(--primary-color);
    fill: var(--primary-color);
    background: var(--bg3);
}
.toolbar-button:hover {
    background: var(--bg2);
}
.command-active{
    background: var(--side-menu-border);
}
.toolbar-root{
    display: flex;
    
    background: var(--bg3);
    position: relative;
    position: sticky;
    top: 0;
    z-index: 5;
}
.editor-root{
    border: 1px solid var(--primary-color);
}
</style>