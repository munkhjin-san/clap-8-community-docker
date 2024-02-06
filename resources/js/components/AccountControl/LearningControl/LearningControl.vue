<template>
    <div class="post-container" style="gap: 0">  
        <Transition name="modalFade">                              
            <LessonCreate 
                v-if="createWindow"
                :editTarget="editTarget"
                :lessonThemeId="activeLesson"
                @createFinish="createFinish"           
            />
            
        </Transition> 
        <Transition name="modalFade">                              
            <ThemeCreate
                v-if="createThemeWindow"
                @closeModal="closeThemeCreate"
                :editTarget="editThemeTarget"
            />
            
        </Transition>       
        <div style="display:flex;align-items:center;font-size:12px;width: fit-content;">
            <div @click="tab = 0" class="footerTabSelector" :class="{selectedMenu : tab == 0}" style="position: relative;width: fit-content;padding: 0 20px;">
                コンテンツ管理                
            </div>
            <div v-if="activeLesson" @click="tab = 1, getPortfolios()" class="footerTabSelector" :class="{selectedMenu : tab == 1}" style="position: relative;width: fit-content;padding: 0 20px;">
                研修生
            </div>
        </div>      
        <div v-if="tab == 0" style="height: calc(100% - 35px);">
            <div v-if="!activeLesson" class="lcontrol">
                <h2 style="padding: 20px;">テーマ</h2>
                <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap: 20px;padding: 0 20px;">
                    <div class="theme-item" v-for="theme in themeRecords">
                        <div @click="getLesson(theme.id)" style="max-width: 90%;overflow: hidden;text-overflow: ellipsis;">
                            <div style="font-size: 20px;">{{ theme.title }}</div>
                            <div style="font-size: 12px;margin-top: 15px;color: gray;">
                                <span>グループディスカッション日付：{{ theme.discussion_date ? theme.discussion_date : '未設定' }}</span>
                            </div>
                            <div style="font-size: 12px;margin-top: 15px;color: gray;">
                                <span>アクティブ：{{ theme.active ? 'ON' : 'OFF' }}</span>
                            </div>
                        </div>                 
                    
                        <div class="boardMenuContainer cursor-pointer" @click.stop="$store.commit('setMenu', {name: 'themeBoxMenu', id: theme.id})" @touchstart.stop style="position: absolute;right: 10px;top: 10px;">                                            
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin:auto;">
                                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                            </svg>
                        </div>
                        <Transition name="modalFade">
                            <div id="themeBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'themeBoxMenu' && $store.state.menu.id == theme.id" style="top: 25px;right: 40px;z-index:6;">
                                <ul>
                                    <li class="boxMenuItems cursor-pointer" @click.stop="editTheme(theme)">編集する</li>
                                    <li class="boxMenuItems cursor-pointer" @click.stop="deleteThemeConfirm(theme.id)">削除する</li>
                                </ul>                                            
                            </div>
                        </Transition>             
                    </div>                    
                </div>
                <div @click="createThemeWindow = true" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
                </div>                
            </div>
            <div v-if="activeLesson" class="lcontrol">
                <div style="display: flex;align-items: center;padding: 20px;gap: 15px;position: sticky;top: 0;background: var(--background-color);z-index: 7;">
                    <svg style="cursor: pointer;" @click="activeLesson = null, lessons = []" class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>   
                    <h2>{{ selectedLesson.title }}</h2>
                </div>
                <div style="padding: 0 20px">
                    <div v-for="lesson in lessons" class="lesson-preview">
                        <div style="overflow: hidden;margin: 20px 40px 20px 20px;height: calc(100% - 40px);" v-html="lesson.title"></div>
                        <div class="boardMenuContainer cursor-pointer" @click.stop="$store.commit('setMenu', {name: 'topicBoxMenu', id: lesson.id})" @touchstart.stop style="position: absolute;right: 10px;top: 10px;">                                            
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin:auto;">
                                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                            </svg>
                        </div>
                        <Transition name="modalFade">
                        <div id="topicBoxMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'topicBoxMenu' && $store.state.menu.id == lesson.id" style="top: 25px;right: 40px;z-index:6;">
                            <ul>
                                <li class="boxMenuItems cursor-pointer" @click.stop="editLesson(lesson)">編集する</li>
                                <li class="boxMenuItems cursor-pointer" @click.stop="deleteConfirm(lesson.id)">削除する</li>
                            </ul>                                            
                        </div>
                        </Transition>   
                    </div>
                </div>
                <div @click="createWindow = true, editTarget = null" class="createBoardButton fileNewButton" title="新規作成" id="boardCreate">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill: rgb(0, 0, 0); margin: auto;"><path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path></svg>
                </div>
            </div>          
        </div>
        <div v-else-if="tab == 1" style="height: calc(100% - 35px);">
            <div class="lcontrol">
                <div v-if="selectedLesson" style="display: flex;align-items: center;padding: 20px;gap: 15px;position: sticky;top: 0;background: var(--background-color);z-index: 7;">
                    <svg style="cursor: pointer;" @click="activeLesson = null, lessons = [], tab = 0" class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>   
                    <h2>{{ selectedLesson.title }}</h2>
                    <div class="admin-button" style="width: fit-content;flex: 0;margin: 0 0 0 auto;" @click="downloadCSV">CSVダウンロード</div>
                </div>
                <div>
                    <!-- <div v-for="portfolio in portfolios">

                    </div> -->
                    <table class="portfolio-control-table">
                        <thead>
                            <tr>
                                <th>研修生</th>
                                <th>ステータス</th>
                                <!-- <th>基礎知識理解</th> -->
                                <th>ディスカッション用ポートフォリオ</th>
                                <th>本ポートフォリオ</th>
                                <th>ポジティブフィードバック</th>
                                <th>ネガティブフィードバック</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="portfolio in portfolios">
                                <td style="max-width: 110px;overflow: hidden;white-space: nowrap;position: relative;">{{ portfolio?.user.name }}</td>
                                <td style="white-space: nowrap;text-align: left;position: relative;" @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `status_control${portfolio.id}`})">
                                    <div v-for="status in portfolio.status" style="padding: 5px 0;">
                                        {{ status_values[status] }}
                                        <span style="margin-left:15px">
                                            <button style="padding: 2px 10px;font-size: 10px;" @click="statusUpdate(status - 1, portfolio.id)" class="commentEditButton">差し戻す</button>
                                        </span>
                                    </div>
                                    
                                </td>
                                <!-- <td style="text-align: center;">
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_understand${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">{{ portfolio.understand ? '✅' : '❌' }}</p>
                                        <p v-if="$store.state.menu.name == `pt_understand${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_understand${portfolio.id}`" class="pt-popup shadow-me" v-html="understandValue(portfolio)"></p>
                                    </div>
                                </td> -->
                                <td>
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_content${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">
                                            <p v-if="portfolio.portfolio_title">{{ portfolio.portfolio_title }}</p>
                                            <p>{{ portfolio.content }}</p>
                                        </p>
                                        <p v-if="$store.state.menu.name == `pt_content${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_content${portfolio.id}`" class="pt-popup shadow-me">
                                            <p v-if="portfolio.portfolio_title">{{ portfolio.portfolio_title }}</p>
                                            <p>{{ portfolio.content }}</p>
                                        </p>
                                    </div>
                                </td>
                                <td>
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_content_public${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">
                                            <p v-if="portfolio.public_title">{{ portfolio.public_title }}</p>
                                            <p>{{ portfolio.public_content }}</p>
                                        </p>
                                        <p v-if="$store.state.menu.name == `pt_content_public${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_content_public${portfolio.id}`" class="pt-popup shadow-me">
                                            <p v-if="portfolio.public_title">{{ portfolio.public_title }}</p>
                                            <p>{{ portfolio.public_content }}</p>
                                        </p>
                                    </div>
                                </td>
                                <!-- <td>
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_content${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">{{ portfolio.content }}</p>
                                        <p v-if="$store.state.menu.name == `pt_content${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_content${portfolio.id}`" class="pt-popup shadow-me">{{ portfolio.content }}</p>
                                    </div>
                                </td> -->
                                <td>
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_positive${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">{{ portfolio.positive_feedback }}</p>
                                        <p v-if="$store.state.menu.name == `pt_positive${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_positive${portfolio.id}`" class="pt-popup shadow-me">{{ portfolio.positive_feedback }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="pt-content">
                                        <p @click.stop="$store.commit('setMenu', { id: portfolio.id, name: `pt_negative${portfolio.id}`})" style="overflow: hidden;max-height: 40px;">{{ portfolio.negative_feedback }}</p>
                                        <p v-if="$store.state.menu.name == `pt_negative${portfolio.id}` && $store.state.menu.id == portfolio.id" :id="`pt_negative${portfolio.id}`" class="pt-popup shadow-me">{{ portfolio.negative_feedback }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue';
import LessonCreate from './LessonCreate.vue';
import ThemeCreate from './ThemeCreate.vue';
import axios from 'axios';
    const tab = ref(0)
    const lessons = ref([])
    const activeLesson = ref(null)
    const editTarget = ref(null)
    const createWindow = ref(false)
    const createThemeWindow = ref(false)
    const editThemeTarget = ref(null)
    const themeRecords = ref([])
    const portfolios = ref([])
    const status_values = ['', '✅基礎知識', '✅グループディスカッション', '✅ポートフォリオ']
    onMounted(() => {
        getThemes()
    })

    const selectedLesson = computed(() => {
        return themeRecords.value && activeLesson.value ?  themeRecords.value.filter( ob => ob.id == activeLesson.value)[0] : null
    })
    const editTheme = (theme) => {
        editThemeTarget.value = theme
        createThemeWindow.value = true
    }
    const deleteThemeConfirm = (id) => {
        var uniqueChannell = Math.random().toString(36).substring(5);   
        const answers = ['はい', 'いいえ']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: '削除しますか。',
            closeButton: false, 
            autoClose: false,
            answers: answers,
            channel: uniqueChannell

        })            
        emitter.on(uniqueChannell, (data) => { data.answer === answers[0] ? deleteTheme(id): false});
    }
    const statusUpdate = (value, id) => {
        axios.put(`/update_portfolio_status`, {id: id, value: value}).then(response => {
            const data = {
                text: '保存しました。',
                channel: Math.random().toString(36).substring(5),
                icon: 0,
                view: true
            }
            emitter.emit('setInfo', data)
            getPortfolios()
        }).catch(function (error) {
            if (error.response) errorToast('エラーが発生しました。 ' + error.response.data.message)
            else if (error.request) errorToast('エラーが発生しました。')
            else errorToast('エラーが発生しました。 ' + error.message)                       
        });
    }
    const deleteTheme = (id) => {
        axios.delete(`/delete_learning_theme?id=${id}`).then(response => {
            getThemes(activeLesson.value)
        })
    }
    const closeThemeCreate = (flag) => {
        createThemeWindow.value = false
        if(flag){
            getThemes()
        }
    }
    const deleteLesson = (lessonId) => {
        axios.delete(`/lesson_remove_record?id=${lessonId}`).then(response => {
            getLesson(activeLesson.value)
        })
    }
    const getLesson = (id) => {
        activeLesson.value = id
        axios.get(`/get_lessons?lesson_theme_id=${id}`).then(response => {
            lessons.value = response.data
        })
    }
    const deleteConfirm = (id) => {    
        var uniqueChannell = Math.random().toString(36).substring(5);   
        const answers = ['はい', 'いいえ']
        emitter.emit('setToast', {
            active: true,  
            type: 'info', 
            content: '削除しますか。',
            closeButton: false, 
            autoClose: false,
            answers: answers,
            channel: uniqueChannell

        })            
        emitter.on(uniqueChannell, (data) => { data.answer === answers[0] ? deleteLesson(id): false});
        
    }

    const getThemes = () => {

        axios.get('/get_learning_themes').then(res => {
            if(res.data){
                themeRecords.value = res.data          
            }
        })
    }

    const editLesson = (lesson) => {
        editTarget.value = lesson
        createWindow.value = true
    }

    const createFinish = (reload) => {
        createWindow.value = false
        if(reload){
            getLesson(activeLesson.value)
        }
        
    }

    const getPortfolios = () => {
        axios.get(`/get_portfolios_list?theme_id=${activeLesson.value}`).then(response => {
            portfolios.value = response.data
        })
    }

    const understandValue = (portfolio) => {
        let content = ''
        if(portfolio.not_understand_content){
            content = content + `<strong>理解できなかった理由</strong><br>${portfolio.not_understand_content}`
        }
        if(portfolio.not_understand_content && portfolio.basic_knowledge)
        {
            content = content + `<div>⬇️⬇️⬇️</div><br>`
        }
        if(portfolio.basic_knowledge){
            content = content + `<strong>理解しました。</strong><br>${portfolio.basic_knowledge}`
        }
        return content
    }
    const downloadCSV = () => {
        var csv = '\ufeff' + '研修生,ステータス,基礎知識理解,ポートフォリオ,ポジティブフィードバック,ネガティブフィードバック\n';
        portfolios.value.forEach(item => {
            const status = status_values[item.status]
            const understand = item.understand ? `理解しました。${item.basic_knowledge}` : `理解できました。${item.not_understand_content}`
            var line = `${item.user.name},${status},${understand.replace(/,|\n/g, '')},${item.content.replace(/,|\n/g, '')},${item.positive_feedback.replace(/,|\n/g, '')},${item.negative_feedback.replace(/,|\n/g, '')}\n`;
            csv += line;
        });
        let blob = new Blob([csv], { type: 'text/csv' });
        let link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `${selectedLesson.value.title}.csv`;
        link.click();
    }
    const errorToast = (message) => {
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })  
            processing.value = false
            
        }
</script>
<style>
.lcontrol{
    font-size: 14px;
    background: var(--background-color);
    width: 100%;
    height: 100%;
    overflow: hidden auto;
}
.lesson-preview{
    background: var(--background-color);
    border: solid thin var(--calendarBorder);
    height: 80px;
    white-space: nowrap;
    margin-bottom: 20px;
    line-height: 1.5;
    position: relative;
}
.theme-item{
    padding: 20px 20px;
    cursor: pointer;
    background: var(--background-color);
    color: var(--primary-color);
    border: solid thin var(--calendarBorder);
    position: relative;
    line-height: 2;
}
.portfolio-control-table {
        border-collapse: collapse;
        width: 100%;

        th, td{
            border: 1px solid var(--calendarBorder);
            padding: 10px;
            font-size: 13px;         
            vertical-align: middle;
            box-sizing: border-box;
        }
        thead{
            white-space: nowrap;
        }
        .pt-content{
            max-height: 40px;
            line-height: 1.5;
            position: relative;
        }
        .pt-popup{
            position: absolute;
            top: 0px;
            left: 0;
            white-space: break-spaces;
            line-height: 1.5;
            z-index: 5;
            background: var(--background-color);
            padding: 15px;
            text-align: left;
            width: max-content;
            max-width: 40vw;
            
        }
    }

  
        
    
</style>