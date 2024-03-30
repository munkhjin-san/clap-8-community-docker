<template>
    <div class="overlay">
        <div title="新規作成" v-if="!newIssue && viewType == 0" id="boardCreate" class="createBoardButton fileNewButton kadaiCreateButton" @click="addNewKadai">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <div class="chatCreate kadaiCreate scrollable">
            
            <div class="recordFormTitle" style="display:flex;margin-bottom: 10px;">
                <div @click="router.go(-1)" style="margin: 2px 10px 0 0;" v-if="responsive.mobile">
                    <svg class="dot-menu" version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>                    
                </div>  
                <p>昇給課題</p>
                <div v-if="!responsive.mobile" class="cursor-pointer" @click="router.go(-1)" style="position:unset; margin:auto 0 auto auto">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="modalWindowCloseButton" viewBox="0 0 32 32">
                        <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                    </svg>                        
                </div> 
            </div>
            <div class="kadai-root">   
                <div>
                    <div class="locale-selector" style="width: 100%;">
                        <select @change="dateChange" name="locales" v-model="selectedDate" class="dropDownSelector cursor-pointer" style="width: fit-content;">
                            <option :value="date" v-for="date in selectAbleDates">{{ parseDate(date) }}</option>
                        </select>
                    </div>
                </div>
                <div v-if="newIssue" style="margin-top: 30px;">
                    <div v-if="!selectedTheme">
                        <div @click="editTemplateId = null, newIssue = false" class="undo-kadai">
                            <svg version="1.1" fill="var(--primary-color)" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>
                            <div>戻る</div>
                        </div>
                        <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">評価課題を選択</p>
                        <table>
                            <thead>
                                <tr>
                                    <th class="levelTitle"></th>
                                    <th class="levelTitle" v-for="level in levels" :key="level">{{ level }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="theme in themes" :key="theme" style="border-bottom: solid thin var(--formBorder);border-right: solid thin var(--formBorder);">
                                <td class="themeTitle">{{ theme }}</td>
                                <td @click="selectThemeConfirm(level, theme)" class="selectable-theme" v-for="level in levels" :key="level">
                                    <div style="padding: 10px;" v-for="issue in getIssues(level, theme)" :key="issue.id">
                                    <strong>{{ issue.title }}</strong>
                                    <p class="issue-content">{{ issue.content }}</p>
                                    </div>
                                </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="selectedTheme">
                        <div @click="selectedTheme = null" class="undo-kadai">
                            <svg fill="var(--primary-color)" version="1.1" height="10" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                            </svg>
                            <div>戻る</div>
                        </div>
                        <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">選択した評価課題</p>
                        <div class="selected-theme">                            
                            <div style="margin-top: 15px;">
                                <div><strong>{{ selectedTheme.title }}</strong></div>
                                <div style="font-size: 12px;margin-top: 10px;white-space: break-spaces;">{{ selectedTheme.content }}</div>
                            </div>
                        </div>
                        <div class="si-box" style="background: var(--background-color);">
                            <ShortInput
                                :initialValue="title"
                                ref="kadaiTitle"
                                placeHolder="昇給課題タイトル（必須）"
                                name="kadaiTitle"
                                rules="required|max:250"
                                label="タイトル"
                                v-model="title"
                            />
                        </div>
                        <div class="si-box" style="background: var(--background-color);">
                            <LongInput
                                :initialValue="content"   
                                ref="kadaiContent"
                                :placeHolder="`昇給課題内容・詳細（必須）`"
                                name="kadaiContent"
                                rules="required|max:2000"
                                label="タイトル"
                                v-model="content"
                            />
                        </div>
                        <div class="si-box" style="background: var(--background-color);">
                            <LongInput
                                :initialValue="content_goal"   
                                ref="kadaiGoal"
                                :placeHolder="`課題達成による取得能力（必須）`"
                                name="kadaiGoal"
                                rules="required|max:2000"
                                label="タイトル"
                                v-model="content_goal"
                            />
                        </div>
                        <div class="si-box">
                            <div v-if="content_review" style="background: var(--bg3);padding: 10px;margin-top: 20px;">
                                <div style="font-weight: 600;margin-bottom: 20px">レビュー結果</div>
                                <div style="white-space: break-spaces;" v-html="content_review"></div>                                
                            </div>
                        </div>
                        <div class="si-box" style="justify-content: center;display: flex;gap:15px;flex-wrap: wrap;">
                            <LoaderButton style="margin: 0" :loading="reviewLoading" @triggered="getReview" :content="content_review ? 'ChatGPT再添削' : 'ChatGPT添削'"/>
                            <LoaderButton v-if="!reviewLoading" style="margin: 0" :loading="saving" @triggered="saveTemplateConfirm" content="保存"/>
                        </div>
                    </div>
                </div>


                <div v-if="!newIssue">
                    <div style="display: flex;margin:20px 0 -10px 0">
                        <div @click="viewType = 0" :class="['kadaiSwitch', {'kadai-active': viewType == 0}]">作成中</div>
                        <div @click="viewType = 1" :class="['kadaiSwitch', {'kadai-active': viewType == 1}]">申請中</div>
                    </div>
                    <div v-if="viewType == 0">
                        <div v-if="emptyTemplates" class="no-comment-text" style="margin-top: 20vh;">
                            現在レコードはありません。
                        </div>
                        <div v-else>
                            <div v-for="template in templateList" class="templateBox">
                                <div @click.stop="menu.setMenu( {id: template.id, name: 'kadaiBoxMenu'})" class="boardMenuContainer cursor-pointer" style="align-self: normal; position: absolute; right: 10px; top: 10px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin: auto;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                </div>
                                <Transition name="modalFade">
                                <div id="kadaiBoxMenu" class="boxMenu boardMenuIcon" v-if="menu.name == 'kadaiBoxMenu' && menu.id == template.id" style="top: 25px;right: 40px;z-index:6;">
                                    <ul>
                                        <li class="boxMenuItems cursor-pointer" @click.stop="editTemplate(template)">編集する</li>
                                        <li class="boxMenuItems cursor-pointer" @click.stop="deleteTemplateConfirm(template)">削除する</li>
                                    </ul>                                            
                                </div>
                                </Transition>
                                <div class="si-box">
                                    <div>評価課題</div>
                                    <div>{{ template.theme }}</div>
                                </div>
                                <div class="si-box">
                                    <div>昇給課題タイトル</div>
                                    <div class="kadai-content">{{ template.title }}</div>
                                </div>
                                <div class="si-box">
                                    <div>昇給課題内容・詳細</div>
                                    <div class="kadai-content">{{ template.content }}</div>
                                </div>
                                <div class="si-box">
                                    <div>課題達成による取得能力</div>
                                    <div class="kadai-content">{{ template.ability }}</div>
                                </div>
                                <div class="si-box">
                                    <div>ChatGPT添削結果</div>
                                    <div class="kadai-content">{{ template.review }}</div>
                                </div>
                                
                            </div>
                            <div class="si-box" style="margin-bottom:20px">
                                <LoaderButton :loading="attaching" @triggered="applyToManagementConfirm" content="申請"/>
                            </div>
                        </div>
                        
                    </div>

                    <div v-if="viewType == 1 && appliedIssues">
                        <div v-if="emptyIssues" class="no-comment-text" style="margin-top: 20vh;">
                            現在レコードはありません。
                        </div>
                        <div v-else style="position: relative;">
                            <div style="position: absolute;right: 0;top:-30px;display: flex;align-items: center;font-size: 13px;gap:5px">
                                <div>ステータス </div>
                                <div><strong>{{ appliedIssues['ステータス']['value'] }}</strong></div>
                            </div>
                            <div class="templateBox" v-for="template in appliedIssues['昇給課題']['value']">
                                <div v-if="appliedIssues['ステータス']['value'] == '未処理' || 'CLAPにて作成済'" @click.stop="menu.setMenu( {id: template.id, name: 'aplliedKadaiBoxMenu'})" class="boardMenuContainer cursor-pointer" style="align-self: normal; position: absolute; right: 10px; top: 10px;">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin: auto;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                </div>
                                <Transition name="modalFade">
                                <div id="aplliedKadaiBoxMenu" class="boxMenu boardMenuIcon" v-if="menu.name == 'aplliedKadaiBoxMenu' && menu.id == template.id" style="top: 25px;right: 40px;z-index:6;">
                                    <ul>
                                        <!-- <li class="boxMenuItems cursor-pointer" @click.stop="editIssue(template)">編集する</li> -->
                                        <li class="boxMenuItems cursor-pointer" @click.stop="deleteAppliedConfirm(template)">削除する</li>
                                    </ul>                                            
                                </div>
                                </Transition>
                                
                                <div>
                                    <div>評価課題</div>
                                    <div>{{ parseValue(template['value'], '評価課題') }}</div>
                                </div>
                                <div class="si-box">
                                    <div>昇給課題タイトル</div>
                                    <div class="kadai-content">{{ parseValue(template['value'], '昇給課題タイトル') }}</div>
                                </div>
                                 <div class="si-box">
                                    <div>昇給課題内容・詳細</div>
                                    <div class="kadai-content">{{ parseValue(template['value'], '昇給課題内容・詳細') }}</div>
                                </div>
                                <div class="si-box">
                                    <div>課題達成による取得能力</div>
                                    <div class="kadai-content">{{ parseValue(template['value'], '課題達成による取得能力') }}</div>
                                </div>
                                <div class="si-box">
                                    <div>ChatGPT添削結果</div>
                                    <div class="kadai-content">{{ parseValue(template['value'], 'ChatGPT添削結果') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import LoaderButton from '../../Global/LoaderButton.vue'
import moment from 'moment'
import OpenAI from "openai";
import LongInput from '../../Form/LongInput.vue';
import ShortInput from '../../Form/ShortInput.vue';
import { inject, onMounted, ref, computed } from 'vue';
import { useRouter } from 'vue-router'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
    const menu = useMenuStore()
    const responsive = useResponsive()
    const { confirm, info, notify } = inject('dialog')
    const router = useRouter()
    const emit = defineEmits(['close'])
    const id = ref('')
    const k_list = ref([])
    const kadaiLoad = ref(false)
    const activeDate = ref('')
    const currentDate = new Date()
    const newIssue = ref(false)
    const levels = ['自己', '組織', '社会']
    const themes = ['意義', '調和', '創造']
    const selectedTheme = ref(null)
    const title = ref('')
    const content = ref('')
    const review = ref('')
    const content_goal = ref('')
    const content_review = ref('')
    const reviewLoading = ref(false)
    const saving = ref(false)
    const attaching = ref(false)
    const selectedDate = ref('')
    const templateList = ref([])
    const editTemplateId = ref(null)
    const editIssueId = ref(null)
    const themeRecords = ref([])
    const appliedIssues = ref(null)
    const viewType = ref(0)
    const emptyTemplates = ref(false)
    const emptyIssues = ref(false)
    onMounted(() => {
        const currentMonth = moment().month();

        if (currentMonth >= 1 && currentMonth < 7) {
            selectedDate.value = moment().month(1).set('date', 1).format('YYYY-MM-DD')
        } else {
            selectedDate.value = moment().month(7).set('date', 1).format('YYYY-MM-DD')
        }
        // const client = new KintoneRestAPIClient({
        // baseUrl: "https://glowd-hldgs.cybozu.com",
        // // Use password authentication
        // auth: {
        //     username: 'kanribu',
        //     password: 'Y72wyZ9z',
        // },
        // // Use API token authentication
        // // auth: { apiToken: process.env.KINTONE_API_TOKEN }
        // // Use OAuth token authentication
        // // auth: { oAuthToken: process.env.KINTONE_OAUTH_TOKEN }

        // // Use session authentication if `auth` is omitted (in browser only)
        // });
        // console.log(client)
        // client.record
        // .getRecords({ app: "928" })
        // .then((resp) => {
        //     console.log(resp.records);
        // })
        // .catch((err) => {
        //     console.log(err);
        // });




        getTemplates()
        getActiveIssues()
        getThemes()
    })
    const selectAbleDates = computed(() => {
        let dateArray = []
        // const currentYear = moment()

        // for (let year = startYear; year <= currentYear; year++) {
            dateArray.push(moment().set('month', 1).set('date', 1).format('YYYY-MM-DD'));
            dateArray.push(moment().set('month', 7).set('date', 1).format('YYYY-MM-DD'));
        // }
        return dateArray
    })
    const evalutionsValues = computed(() => {
        return themeRecords.value
    })
    const dateChange = () => {
        getActiveIssues()
        getTemplates()
    }
    const getActiveIssues = async() => {
        const date = moment(selectedDate.value).format('YYYY年M月実施分')
        try{
            const res = await axios.post('/get_applied_issues', {date: date})
            appliedIssues.value = res.data
            emptyIssues.value = !(res.data && res.data['昇給課題'] && res.data['昇給課題']['value'].length)
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const addNewKadai = () => {
        editTemplateId.value = null
        newIssue.value = true
    }
    const selectThemeConfirm = (level, theme) => {
        // console.log(level)
        selectedTheme.value = getIssues(level, theme)[0]
    }
    const getIssues = (level, theme) => {
        return evalutionsValues.value.find((item) => item.level === level)
        .issues.filter((issue) => issue.theme === theme);
    }
    const getTemplates = async() => {
        try{
            const res = await axios.post('/get_kadai_template', {date: selectedDate.value})
            templateList.value = res.data
            emptyTemplates.value = !(res.data && res.data.length)
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }       
    }
    const getThemes = async() => {
        try{
            const res = await axios.get('/get_kadai_themes')
            if(res.data){
                themeRecords.value = res.data
            }
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }
    const getData = async(date) => {
        kadaiLoad.value = true
        try{
            const res = await axios.post('/get_kadai_list', {id: id.value, date: activeDate.value})
            k_list.value = res.data
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            kadaiLoad.value = false
        }
    }
    const getReview = async(data) => {
        const headTemplate = selectedTheme.value.title_full
        const full = `社員の昇給課題を設定しようとしています。 
                    下記昇給課題の内容は、昇給課題の設定ルールにのっとり、昇給課題のテーマに凡そふさわしい内容になっていますか？
                    一般常識をもって業務にあたれば達成可能な目標は除外します。課題達成することによって、得られる能力が具体的に記されていますか？
                    判定結果と判定理由をわかりやすく教えてください。\nふさわしくない場合は具体的に変更すべき箇所を明示してください。
                    まず昇給課題のテーマは次の通りです。
                    ${headTemplate} ${selectedTheme.value.content}
                    昇給課題の内容は、次の通りです。
                    ${content.value}`
        content_review.value = ''
        reviewLoading.value = true
        const openai = new OpenAI({
            apiKey: import.meta.env.VITE_OPENAI_API_KEY,
            dangerouslyAllowBrowser: true 
        });
        const stream = await openai.chat.completions.create({
            // model: 'gpt-4',
            model: 'gpt-3.5-turbo-16k',
            messages: [{ role: 'assistant', content: full }],
            stream: true,
            temperature: 0.8
        }).catch((err) => {
            if (err instanceof OpenAI.APIError) {
                if(err.status == 500){
                    notify('ChatGPT修正に失敗しました。<br>ChatGPTサーバーから反応がありませんでした。しばらく立ってから再度お試しください。')
                }else{
                    notify('ChatGPT修正に失敗しました。<br>' + err.message)
                }
                
            } else {
                notify('ChatGPT修正に失敗しました。<br>' + err)
            }
            reviewLoading.value = false
        })
        for await (const part of stream) {
            try {
                const content = part.choices[0]?.delta?.content || ''
                let before = content_review.value ? content_review.value : ''
                content_review.value = before + content
            } catch (error) {
                reviewLoading.value = false
            }
            
            

        }
        reviewLoading.value = false

    }
    const editIssue = (issue) => {
        editTemplateId.value = null
        let theme = null;
        for (const item of evalutionsValues.value) {
            const t = item.issues.find((ob) => ob.title_full === parseValue(issue['value'], '評価課題'));
            if (t) {
                theme = t;
            }
        }
        if(theme){
            editIssueId.value = issue.id
            selectedTheme.value = theme
            title.value = parseValue(issue['value'], '昇給課題タイトル')
            content.value = parseValue(issue['value'], '昇給課題内容・詳細')
            content_goal.value = parseValue(issue['value'], '課題達成による取得能力')
            content_review.value = parseValue(issue['value'], 'ChatGPT添削結果')
            newIssue.value = true
        }
        menu.setMenu( {id: null, name: ''})
    }
    const editTemplate = (template) => {
        let theme = null;
        editIssueId.value = null
        for (const item of evalutionsValues.value) {
            const issue = item.issues.find((issue) => issue.title_full === template.theme);
            if (issue) {
                theme = issue;
            }
        }
        if(theme){
            editTemplateId.value = template.id
            selectedTheme.value = theme
            title.value = template.title
            content.value = template.content
            content_goal.value = template.ability
            content_review.value = template.review
            newIssue.value = true
        }
        menu.setMenu( {id: null, name: ''})
    }
    const deleteAppliedConfirm = async(issue) => {
        const answer = await confirm('昇給課題を削除しますか。')
        if(!answer) return     
        deleteApplied(issue)
    }
    const deleteApplied = async(issue) => {
        try{
            await axios.post('/delete_applied_issue', {record_id: appliedIssues.value['$id']['value'], kadai_id: issue.id})
            info('削除しました。')
            getActiveIssues()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            saving.value = false
        }
    }
    const deleteTemplateConfirm = async(template) => {
        const answer = await confirm('昇給課題を削除しますか。')
        if(!answer) return 
        deleteTemplate(template)           
    }
    const deleteTemplate = async(template) => {
        try{
            await axios.post('/delete_kadai_template', {id: template.id})
            info('削除しました。')
            getTemplates()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
            saving.value = false
        }
        
    }
    const saveTemplateConfirm = async() => {
        if(editTemplateId.value){
            const editRecord = templateList.value.find(ob => ob.id == editTemplateId.value)
            if(editRecord && editRecord.content !== content.value && editRecord.review && editRecord.review == content_review.value){
                const answer = await confirm('昇給課題の内容に変更がある場合、再度ChatGPT添削を行ってください。<br>このまま保存すると現在の添削結果は削除されます。')
                if(!answer) return
                saveTemplate('empty_review')
            }else{
                saveTemplate()
            }
        }else if(editIssueId.value){
            const issues = appliedIssues.value['昇給課題']['value']
            const issue = issues.find(ob => ob.id == editIssueId.value)
            if(issue){
                if(issue['value']['昇給課題内容・詳細']['value'] !== content.value && issue['value']['ChatGPT添削結果']['value'] == content_review.value){
                    notify('再度ChatGPT添削を行ってください。')                        
                }else{
                    saveIssue(editIssueId.value)
                }
            }
            
        }
        else{
            saveTemplate()
            
        }
    }
    const saveIssue = async(issue_id) => {

        try{
            saving.value = true
            const params = {
                record_id: appliedIssues.value['$id']['value'],
                kadai_id: issue_id,
                title: title.value,
                content: content.value,
                review: content_review.value,
                ability: content_goal.value,
                theme: selectedTheme.value.title_full
            }
            await axios.post('/update_issue', params)
            info('保存しました。')
            newIssue.value = false
            selectedTheme.value = null
            title.value = content.value = review.value = content_goal.value = ''
            getActiveIssues()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            saving.value = false
        }
    }
    const saveTemplate = async(action) => {
        try{
            saving.value = true
            const params = {
                editId: editTemplateId.value,
                title: title.value,
                content: content.value,
                review: action && action == 'empty_review' ? null : content_review.value,
                ability: content_goal.value,
                theme: selectedTheme.value.title_full,
                date: moment(selectedDate.value).format('YYYY-MM-DD')
            }
            await axios.post('/save_kadai_template', params)
                info('保存しました。')
                newIssue.value = false
                selectedTheme.value = null
                title.value = content.value = review.value = content_goal.value = ''
                getTemplates()
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally {
            saving.value = false
        }
        
    }
    const applyToManagementConfirm = async() => {
        const records = templateList.value
        for(const template of templateList.value){
            if(!template.review){
                notify('申請する前にChatGPT添削を完了してください。')
                return
            }
        }
        const answer = await confirm('申請後には編集ができなくなります。よろしいでしょうか？')
        if(!answer) return
        applyToManagement()

    }
    const applyToManagement = async() => {
        try{
            attaching.value = true
            const date = moment(selectedDate.value).format('YYYY年M月実施分')
            await axios.post('/check_kadai_record', {date: date, record_ids: templateList.value.map(ob => ob.id)})
            getActiveIssues()
            getTemplates()
            info('申請しました。')
        } catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } finally { 
            attaching.value = false
        }
    }
    const parseDate = (date) => {
        return moment(date).format('YYYY年M月実施分')
    }
    const templateStatus = (template) => {
        let result = ''
        switch (true) {
            case (template.status == 0):
                result = '保留'
                break;
            case (template.status == 1):
                result = 'ChatGPT添削済み'
                break;
            case (template.status == 2):
                result = '申請済み'
                break;
            default:
                result = '#';
        }
        return result
    }
    const parseValue = (data, key) => {
        return data[key] && data[key].value ? data[key].value : ''
    }
 
</script>
<style>
.kadaiSwitch{
    padding: 10px;
    background: var(--background-color);
    cursor: pointer;
    font-size: 13px;
}
.kadai-active{
    background: var(--bg3);
}
.kadai-content{
    white-space: break-spaces;
    line-height: 1.5;
}
.kadai-root{
    width: 100%;
    height: auto;
    left: 0;
    top: 0;
    font-size: 14px;
    line-height: 1.5;
}
.evalution-theme-container{
    display: flex;
    width: 100%;
}
.evalution-theme-inner{
    flex: 1;
    border: solid thin var(--formBorder);
    border-top: none;
    box-sizing: border-box;
    border-left: none;
}
.levelTitle{
    height: 30px;
    line-height: 30px;
    background: var(--selected-background);
    border: solid thin var(--formBorder);
}
.themeTitle{
    /* writing-mode: vertical-rl; */
    text-orientation: upright;
    background: var(--selected-background);
    border: solid thin var(--formBorder);
    text-align: center;
    vertical-align: middle;
    width: 30px;
   
}
.selected-theme{
    padding: 10px 15px;
    background: var(--bg3);
}
.undo-kadai{
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 10px;
    margin-bottom: 15px;
}
.kadaiCreate{
    width: 80% !important;
    height: 80%!important;
}
.issue-content{
    font-size: 12px;
    margin-top: 10px;
    white-space: break-spaces;

}
.selectable-theme{
    cursor: pointer;
}
.selectable-theme:hover{
    background: var(--bg2);
}
.templateBox{
    background: var(--bg3);
    padding: 10px;
    margin-top: 30px;
    position: relative;
}
.kadaiCreateButton{
    bottom: 10%;
    right: 10%;
}
@media screen and (max-width: 959px) {
    .kadaiCreate{
        width: 100% !important;
        height: calc(100% - 20px) !important;
    }
    .kadaiCreateButton{
        bottom: 30px;
        right: 20px;
    }
}
</style>