
<template>
    <Modal @close="emit('close', false)">
        <template #title>
            <p>{{ editData ? `フォームを編集する` : `フォームを作成する`}}</p>
        </template>
        <template #content>
            <div class="si-box">
                <ShortInput 
                    name="titleRef" 
                    placeHolder="タイトルを入力（必須）" 
                    :rules="'required'"
                    customClass="full"
                    ref="titleRef"
                    type="text"
                    v-model="params.title"
                />                
            </div>
            <div class="si-box" v-if="props.range == 'all'">
                <p class="text-[14px]">フォーム種別</p>
                <div class="mt-[15px] flex flex-wrap gap-[15px]">
                    <label
                        v-for="typeOption in formTypeOptions"
                        :key="typeOption.value"
                        class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer"
                    >
                        <input
                            v-model="formType"
                            class="custom-f-radio"
                            type="radio"
                            :value="typeOption.value"
                        >
                        {{ typeOption.label }}
                    </label>
                </div>
                <p
                    v-if="formType === 'project_creation'"
                    class="text-[gray] text-[12px] mt-[10px]"
                >
                    進行中のプロジェクト作成フォームは1件のみ作成できます。
                </p>
                <p
                    v-else-if="formType === 'public'"
                    class="text-[gray] text-[12px] mt-[10px]"
                >
                    公開フォームはログインなしで回答できます。対象者選択、繰り返し設定、グラウドナインは利用しません。
                </p>
            </div>
            <div class="si-box" v-if="props.range == 'all' && isProjectCreationForm">
                <p class="text-[14px]">プロジェクト種別</p>
                <select v-model="params.project_type_id" class="custom-a-input mt-[15px]">
                    <option :value="null">選択してください</option>
                    <option v-for="type in projectTypes" :key="type.id" :value="type.id">
                        {{ type.label }}
                    </option>
                </select>
            </div>
            <div class="si-box" v-if="props.range == 'all'">
                <MemberSelector 
                    :initialValue="params.admins" 
                    ref="adminSelectorRef"
                    placeHolder="管理者"
                    name="admins"
                    path="get_authorized_users"
                    :multiple="true"
                    v-model="params.admins"
                />
                <span class="text-[gray] text-[12px]">※フォームの回答は管理者のみ閲覧可能です。「システム管理者含む」</span>
            </div>
            <div class="si-box" v-if="props.range == 'all' && isGeneralForm && !params.is_public">
                <p>対象者選択</p>
                <div class="mt-[20px]">
                    <GroupSelector v-model="params.users" place-holder="グループ・プロジェクトから選択"/>
                </div>
                <div class="mt-[20px]">
                    <MemberSelector 
                        :initialValue="params.users" 
                        ref="userSelectorRef"
                        placeHolder="対象者"
                        name="users"
                        path="board_possible_users"
                        :multiple="true"
                        v-model="params.users"
                    />
                    <span class="text-[gray] text-[12px]">※フォームのURLはどなたでもアクセス可能ですが、回答は対象者のみ必須となります。</span>
                </div>
            </div>
            <div class="si-box" v-if="props.range == 'board' && boardUsers && isGeneralForm">
                <div class="si-box">
                    <MemberSelector 
                        :initialValue="params.admins" 
                        ref="adminSelectorRef"
                        placeHolder="管理者"
                        name="admins"
                        :options="boardUsers"
                        :multiple="true"
                        v-model="params.admins"
                    />
                    <span class="text-[gray] text-[12px]">※フォームの回答は管理者のみ閲覧可能です。「システム管理者含む」</span>
                </div>
                <div class="my-[15px]" v-if="!isPublicForm">
                    <div class="switchLabel">
                        <p class="form-lbl" style="white-space: nowrap;font-size: 14px;">全員選択</p>
                    </div>
                    <div class="selectSwitchArea" style="display: flex;width: 100%;">    
                        <input @change="selectAll" :checked="params.users?.length && params.users?.length == boardUsers.length ? true : false" type="checkbox" id="edit_all">
                        <label for="edit_all" style="min-width: 80px;" class="cursor-pointer"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                        
                    </div>  
                </div>
                <div class="mt-[20px]" v-if="!isPublicForm">
                    <MemberSelector 
                        :initialValue="params.users" 
                        ref="userSelectorRef"
                        placeHolder="対象者"
                        name="users"
                        :options="boardUsers"
                        :multiple="true"
                        v-model="params.users"
                    />
                </div>
            </div>
            

            <div class="si-box" v-if="isGeneralForm && !isPublicForm">
                <p class="text-[14px]">繰り返し設定</p>
                <div class="mt-[15px] flex flex-wrap gap-[15px]">
                    <label v-for="rp in [{value: 0, label: '1回のみ'}, {value: 1, label: '毎月'}]" class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer" :key="rp.value">
                        <input class="custom-f-radio" type="radio" v-model="params.repeat_setting" :value="rp.value"/>
                        {{ rp.label }}
                    </label>
                </div>
                <div class="mt-[20px]" v-if="params.repeat_setting == 1">
                    <p class="text-[14px]">回答開始日（リマインドが表示される日）</p>
                    <select v-model="params.repeat_day" class="custom-a-input mt-[15px]" >
                        <option v-for="day in 31">{{ day }}</option>
                    </select>
                </div>


            </div>

            <div class="si-box">
                <p class="mb-[20px]">説明</p>
                <RichEditor ref="richEdit" :initila-value="editData ? editData.description : ''"/>
            </div>
            <div v-if="auth.activeUser.id && [608, 610].includes(auth.activeUser.id) && isGeneralForm && !isPublicForm" class="si-box" style="position: relative">
                <div>
                    <p :class="['form-title-small', 'form-title-active']">グラウドナイン</p>
                </div>
                <div class="selectSwitchArea" style="width: fit-content;">    
                    <input type="checkbox" id="members_only" v-model="params.has_prize">
                    <label for="members_only" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer']"><span></span>
                        <div class="switch-toggle"></div>
                    </label>
                </div>
            </div>
            <div class="si-box">                
                <div ref="sortParent" class="flex flex-col gap-[30px]">
                    <div :key="block.id" v-for="(block, index) in params.blocks">
                        <div class="bg-[var(--bg3)] relative">
                            <div class="flex items-center h-[50px] px-[5px]">
                                <div class="handler flex items-center justify-center gap-[2px] w-[30px] h-[30px] cursor-grab">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" class="dot-menu" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                </div>
                                <div class="text-[12px]">{{ blockTypes.find( t => t.value == block.type)?.label }}</div>
                                <div @click="addBranch(block.id)" title="条件分岐" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center ml-auto">
                                    <svg class="dot-menu" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="13" viewBox="0 0 30 31" enable-background="new 0 0 30 31" xml:space="preserve">
                                        <path d="M29.522,19.792c-0.003-0.006-2.136-1.72-3.168-2.552c0.143-1.024,0.146-2.066,0.01-3.091l3.177-2.525  c0.463-0.367,0.602-1.028,0.299-1.557c-0.003-0.006-2.535-4.419-2.539-4.424c-0.297-0.521-0.94-0.758-1.517-0.531  c0,0-2.79,1.088-3.764,1.469c-0.807-0.625-1.698-1.141-2.642-1.532l-0.603-3.993C18.685,0.458,18.168,0,17.546,0  c-0.001,0-5.094,0-5.095,0c-0.604-0.002-1.136,0.441-1.229,1.056L10.62,5.05C9.675,5.441,8.783,5.958,7.976,6.583  c-1.244-0.486-3.77-1.473-3.768-1.471C3.65,4.894,3.004,5.11,2.697,5.644C2.694,5.651,0.163,10.062,0.16,10.07  c-0.293,0.51-0.177,1.171,0.297,1.549c0,0,2.384,1.894,3.183,2.526c-0.136,1.028-0.131,2.073,0.011,3.098  c-1.034,0.832-3.173,2.554-3.17,2.558c-0.441,0.353-0.566,0.983-0.273,1.486l2.571,4.396c0.273,0.481,0.87,0.701,1.399,0.491  c0,0,2.928-1.166,3.829-1.524c0.84,0.651,1.771,1.182,2.759,1.574c0.206,1.29,0.646,4.084,0.653,4.082  c0.08,0.498,0.513,0.877,1.034,0.872l2.061-0.019c0.081,0.021,0.168,0.034,0.261,0.038l2.772,0.02  c0.524,0.004,0.987-0.377,1.072-0.91c0.002-0.007,0.437-2.771,0.641-4.066c0.979-0.391,1.904-0.919,2.739-1.564  c0.912,0.362,3.815,1.512,3.815,1.512c0.519,0.206,1.124,0.008,1.414-0.489c0.005-0.008,2.566-4.399,2.571-4.408  C30.086,20.799,29.978,20.16,29.522,19.792 M25.724,23.68c-1.305-0.503-3.521-1.359-3.528-1.362  c-0.381-0.146-0.827-0.08-1.152,0.211c-0.912,0.814-1.989,1.446-3.15,1.837c-0.379,0.13-0.673,0.462-0.735,0.887  c0,0-0.38,2.607-0.554,3.798l-1.831,0.013c-0.093,0.004-0.182,0.018-0.262,0.037l-1.15-0.011c-0.167-1.165-0.549-3.832-0.549-3.832  c-0.057-0.392-0.33-0.738-0.73-0.868c-1.158-0.397-2.233-1.028-3.144-1.844c-0.3-0.269-0.741-0.362-1.14-0.203  c0,0-2.374,0.914-3.531,1.359c-0.477-0.836-1.053-1.848-1.541-2.706c0.976-0.774,2.952-2.339,2.952-2.339  c0.336-0.268,0.515-0.713,0.422-1.162c-0.236-1.189-0.232-2.427,0.013-3.613c0.085-0.423-0.062-0.88-0.422-1.17  c-0.004-0.003-1.808-1.453-2.9-2.333c0.489-0.841,1.009-1.734,1.504-2.586l3.456,1.356C8.169,9.309,8.662,9.24,9.02,8.923  c0.904-0.799,1.968-1.413,3.11-1.794c0.425-0.143,0.758-0.513,0.83-0.986c0.001,0,0.346-2.267,0.558-3.656h2.958  c0.194,1.272,0.559,3.654,0.559,3.654c0.066,0.445,0.376,0.837,0.832,0.991c1.141,0.379,2.205,0.994,3.106,1.792  c0.337,0.297,0.824,0.399,1.271,0.225c0,0,2.262-0.889,3.453-1.355c0.496,0.853,1.013,1.741,1.502,2.583  c-0.995,0.8-2.894,2.325-2.894,2.325c-0.342,0.272-0.522,0.724-0.429,1.181c0.244,1.188,0.248,2.43,0.01,3.62  c-0.084,0.422,0.065,0.875,0.427,1.16c0,0,1.964,1.557,2.943,2.334C26.768,21.853,26.203,22.842,25.724,23.68"/>
                                        <path d="M14.999,10.775c-2.687-0.016-4.91,2.228-4.866,4.912c-0.003,2.621,2.255,4.817,4.866,4.735V20.38  c0.611,0.023,1.237-0.078,1.819-0.302c1.177-0.446,2.146-1.382,2.646-2.541C20.849,14.353,18.465,10.771,14.999,10.775   M14.999,18.326v-0.041c-0.685-0.025-1.331-0.328-1.783-0.815c-0.923-0.956-0.907-2.593,0.065-3.5  c1.222-1.207,3.302-0.793,3.98,0.781C17.982,16.378,16.772,18.227,14.999,18.326"/>
                                    </svg>
                                </div>
                                <div title="複製" @click="duplicate(index, block)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                    <svg class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 17.85612 23.5403">
                                        <path d="M6.60832.8297c-.5011-.05275-.52747-.73846,0-.79121,6.14506-.60659,12.81758,6.06593,10.91868,12.29011-1.5033,4.82637-6.72528,6.40879-11.39341,5.67033,1.55604,1.0022,3.05934,2.05714,4.37802,3.34945,1.18681,1.16044-.63297,2.98022-1.81978,1.81978-2.50549-2.47912-5.3011-4.48352-8.22857-6.40879-.71209-.44835-.58022-1.5033.23736-1.76703,3.34945-1.05495,5.98681-2.9011,8.94066-4.74725.73846-.44835,1.3978.55385.8967,1.16044-1.3978,1.63517-3.24396,2.87473-5.22198,3.85055,3.34945.84396,7.85934.5011,9.6-2.61099C17.73799,7.50223,11.56656,1.40992,6.60832.8297Z"/>
                                    </svg>
                                </div>
                                <div title="項目削除" @click="removeItem(block.id)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                    <CloseIcon/>
                                </div>
                            </div>
                            
                            <div @click.stop @mousedown.stop class="px-[15px] pb-[15px]">
                                <div v-if="branches.includes(block.id)" class="mb-[15px] p-[10px] bg-[var(--bg2)] text-[12px] flex flex-col gap-[10px]">
                                    <div v-if="block.depends_on && block.depends_on.length" class="flex flex-col gap-[10px]">
                                        <div v-for="(condition, conditionIndex) in block.depends_on" :key="conditionIndex" class="flex flex-col gap-[10px]">
                                            <div class="flex flex-wrap items-center gap-[10px]">
                                                <div class="min-w-[70px]">表示条件</div>
                                                <select
                                                    class="custom-a-input"
                                                    style="min-width: 180px;"
                                                    :value="condition.block_id ?? ''"
                                                    @change="setConditionParent(block, conditionIndex, ($event.target as HTMLSelectElement).value)"
                                                >
                                                    <option value="">常に表示</option>
                                                    <option
                                                        v-for="parent in getParents(index)"
                                                        :key="parent.id"
                                                        :value="parent.id"
                                                    >
                                                        {{ parent.question ? parent.question : '（未入力）' }}
                                                    </option>
                                                </select>
                                                <select
                                                    v-if="condition.block_id && getParentType(condition.block_id) === 'radio'"
                                                    class="custom-a-input"
                                                    style="min-width: 180px;"
                                                    :value="getConditionSingleElementId(condition) ?? ''"
                                                    @change="setConditionRadioElement(block, conditionIndex, ($event.target as HTMLSelectElement).value)"
                                                >
                                                    <option value="">選択肢を選択</option>
                                                    <option
                                                        v-for="element in getBlockElements(condition.block_id)"
                                                        :key="element.id"
                                                        :value="element.id"
                                                    >
                                                        {{ element.value ? element.value : '（未入力）' }}
                                                    </option>
                                                </select>
                                                <div class="flex ml-auto">
                                                    <div title="分岐削除" @click="removeCondition(block, conditionIndex)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                                        <CloseIcon size="8"/>
                                                    </div>                                
                                                </div>
                                            </div>
                                            <div v-if="condition.block_id && getParentType(condition.block_id) === 'checkbox'" class="flex flex-wrap items-center gap-[10px]">
                                                <select
                                                    class="custom-a-input"
                                                    style="min-width: 120px;"
                                                    :value="condition.match ?? 'any'"
                                                    @change="setConditionMatch(block, conditionIndex, ($event.target as HTMLSelectElement).value)"
                                                >
                                                    <option value="any">いずれかに一致</option>
                                                    <option value="all">すべて一致</option>
                                                </select>
                                                <div class="flex flex-wrap gap-[10px]">
                                                    <label v-for="element in getBlockElements(condition.block_id)" :key="element.id" class="flex items-center gap-[5px] cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            :checked="condition.element_ids?.includes(Number(element.id))"
                                                            @change="toggleConditionElement(block, conditionIndex, Number(element.id))"
                                                        />
                                                        <span>{{ element.value ? element.value : '（未入力）' }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-[10px]">
                                        <div title="分岐追加" @click="addCondition(block, index)" class="h-[30px] w-[30px] cursor-pointer flex items-center justify-center">
                                            <AddIcon size="10"/>
                                        </div>
                                    </div>
                                    <div v-if="!getParents(index).length" class="text-[gray]">
                                        この質問の前にチェックやラジオ項目がありません
                                    </div>
                                </div>
                                <CustomCheckbox 
                                    v-if="block.type == 'checkbox'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomRadio
                                    v-else-if="block.type == 'radio'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomSelect
                                    v-else-if="block.type == 'select'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomSingleText
                                    v-else-if="block.type == 'singletext' || block.type == 'date' || block.type == 'time'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomMultiText
                                    v-else-if="block.type == 'multitext'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomFile
                                    v-else-if="block.type == 'file'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <CustomHeader
                                    v-else-if="block.type == 'header'" 
                                    :block="block"
                                    v-model:question="block.question"
                                />
                                <div v-if="block.type !== 'header' && isProjectCreationForm" class="mt-[15px]">
                                    <AddableItemSelector
                                        v-model="block.category_ids"
                                        place-holder="チェックカテゴリ"
                                        path="/check_item_categories"
                                        :multiple="true"
                                        :close-on-select="false"
                                        :allow-custom="true"
                                        :reduce="option => typeof option === 'string' ? option : option?.id"
                                    />
                                    <p class="text-[11px] text-[gray] mt-[5px]">
                                        カテゴリ未設定の項目はチェックリスト連動の対象外です。
                                    </p>
                                </div>                            
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
                <div class="text-[12px] flex flex-col items-center overflow-hidden whitespace-nowrap mt-[30px]">
                    <div @click.stop="menu.setMenu({parent: 'initial-plus'})" class="w-[30px] h-[30px] flex items-center justify-center min-w-[30px] cursor-pointer">
                        <div class="flex items-center gap-[5px]">
                            <AddIcon size="15"/>
                            <div>項目追加</div>
                        </div>
                    </div>                    
                    <div v-if="menu.parent == 'initial-plus'" id="initial-plus" class="flex gap-[10px] mt-[15px] flex-wrap">
                        <button v-for="type in blockTypes" :key="type.value" @click="addBlock(type.value, params.blocks.length)" class="px-[5px] py-[5px] bg-[var(--primary-color)] text-[var(--background-color)]">{{ type.label }}</button>
                    </div>
                </div>

            </div>
            <div class="si-box">
                <LoaderButton content="保存する" :loading="sending" @triggered="saveForm"/>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import Modal from '@/components/Global/Modal.vue';
import { CustomForm, CustomFormBlock, CustomFormBlockDependsOn, CustomFormBlockType, CustomFormUsage, CustomFormUser } from '@/interface/customFormInterface';
import { computed, nextTick, onMounted, reactive, ref, useTemplateRef } from 'vue';
import ShortInput from '@/components/Form/ShortInput.vue';
import CustomCheckbox from '@/components/Form/CustomElements/CustomCheckbox.vue'
import { useMenuStore } from '@/store/menu';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import CustomRadio from '@/components/Form/CustomElements/CustomRadio.vue';
import CustomSingleText from '@/components/Form/CustomElements/CustomSingleText.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import CustomMultiText from '@/components/Form/CustomElements/CustomMultiText.vue';
import CustomSelect from '@/components/Form/CustomElements/CustomSelect.vue';
import CustomFile from '@/components/Form/CustomElements/CustomFile.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import { useSortable, moveArrayElement } from '@vueuse/integrations/useSortable'
import RichEditor from '@/components/Global/RichEditor.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import GroupSelector from '@/components/Form/GroupSelector.vue';
import AddableItemSelector from '@/components/Form/AddableItemSelector.vue';
import { useAuthUserStore } from '@/store/auth';
import 'styles/customForm.css'
import { useApi } from '@/composables/api';
import { Board } from '@/interface/globalInterface';
import CustomHeader from '@/components/Form/CustomElements/CustomHeader.vue';
import { useDialog } from '@/composables/dialog';
import { watch } from 'vue';
import type { ProjectType } from '@/interface/projectInterface';
const props = defineProps<{
    editData: CustomForm | null
    range: 'all' | 'board'
    board?: Board
}>()
const emit = defineEmits<{
    close: [flag: boolean]
}>()
const auth = useAuthUserStore()
const projectTypes = ref<ProjectType[]>([])
const richEdit = ref<typeof RichEditor | null>(null)

const boardUsers = computed(() => {
    if(!props.board) return []
    return props.board.board_to_users.map( u => u.user)
})
type FormTypeOption = CustomFormUsage | 'public'

const formTypeOptions:{ label: string, value: FormTypeOption }[] = [
    { label: '通常フォーム', value: 'general' },
    { label: '公開フォーム', value: 'public' },
    { label: 'プロジェクトフォーム', value: 'project_creation' },
]
const isProjectCreationForm = computed(() => params.usage === 'project_creation')
const isGeneralForm = computed(() => !isProjectCreationForm.value)
const isPublicForm = computed(() => isGeneralForm.value && !!params.is_public)
const formType = computed<FormTypeOption>({
    get: () => {
        if (params.usage === 'project_creation') {
            return 'project_creation'
        }

        return params.is_public ? 'public' : 'general'
    },
    set: (value) => {
        if (value === 'project_creation') {
            params.usage = 'project_creation'
            params.is_public = false
            return
        }

        params.usage = 'general'
        params.is_public = value === 'public'
    },
})
const normalizeBlockCategoryInputs = (block: CustomFormBlock) => {
    const relationIds = (
        block.checkitemCategories
        ?? block.checkitem_categories
        ?? []
    )
        .map((category) => category.id)
        .filter(Boolean)

    if (relationIds.length) {
        block.category_ids = relationIds
        return
    }

    const legacyLabels = Array.isArray(block.categories) ? block.categories.filter(Boolean) : []
    block.category_ids = legacyLabels.length ? legacyLabels : []
}

const blockTypes:{label:string, value: CustomFormBlockType}[] = [
    {label: 'チェックボックス', value: 'checkbox'}, 
    {label: 'ラジオボタン', value: 'radio'}, 
    {label: 'ドロップダウン', value: 'select'},
    {label: '短文', value: 'singletext'}, 
    {label: '長文', value: 'multitext'},
    {label: '日付', value: 'date'},
    {label: '時間', value: 'time'},
    {label: 'ファイル', value: 'file'},
    {label: '見出しテキスト', value: 'header'}
]
const menu = useMenuStore()
const titleRef = useTemplateRef('titleRef')
const sending = ref(false)
const params = reactive<CustomForm>({
    id: -1,
    title: '',
    description: '',
    blocks: [],
    users: [],
    admins: [],
    repeat_setting: 0,
    repeat_day: 1,
    board_record_id: props.board ? props.board.id : null,
    has_prize: false,
    is_public: false,
    status: 0,
    usage: 'general',
    project_type_id: null,
})
const sortParent = useTemplateRef('sortParent')
const api = useApi()
const branches = ref<number[]>([])
const { ping } = useDialog()
onMounted(() => {
    fetchProjectTypes()
    if(props.editData && props.editData?.id){
        Object.assign(params, props.editData)
        params.status = props.editData.status ?? 0
        params.usage = props.editData.usage ?? 'general'
        params.blocks.forEach((block) => {
            normalizeDependsOn(block)
            normalizeBlockCategoryInputs(block)
        })
        branches.value = params.blocks.filter(b => b.depends_on && b.depends_on.length).map(b => b.id)
    }else{
        params.admins?.push(auth.activeUser as CustomFormUser)
    }

})
const fetchProjectTypes = async() => {
    const data = await api.get('/project_types')
    projectTypes.value = Array.isArray(data) ? data as ProjectType[] : []
}
watch(
    () => params.usage,
    (usage) => {
        if (usage !== 'project_creation') {
            params.project_type_id = null
            return
        }
        params.has_prize = false
        params.is_public = false
        params.repeat_setting = 0
        params.repeat_day = 1
    },
    { immediate: true }
)

useSortable(sortParent, params.blocks, {
    animation: 150,
    handle: '.handler',
    onUpdate: (e) => {
            console.log(e)
        // do something
        moveArrayElement(params.blocks, e.oldIndex, e.newIndex, e)
        // nextTick required here as moveArrayElement is executed in a microtask
        // so we need to wait until the next tick until that is finished.
        nextTick(() => {
        /* do something */
        })
    }
})
const addBlock = (type:CustomFormBlockType, index: number) => {
    const id = -(Math.floor(100000 + Math.random() * 900000))
    const item:CustomFormBlock = {
        type: type,
        elements: [],
        id: id,
        question: '',
        is_required: false,
        placeholder: '', 
        depends_on: [],
        category_ids: [],
    }
    if(!params.blocks){
        params.blocks = []
    }
    params.blocks.splice(index + 1, 0, item);
    menu.close()
}

const removedItems = ref<number[]>([])

const removeItem = (id: number) => {
    if(params?.blocks && params.blocks.length){
        const index = params.blocks?.findIndex( b => b.id == id)
        if(index !== undefined && index > -1){
            removedItems.value.push(params.blocks[index].id)
            params.blocks.splice(index, 1)
        }
    }
}

const saveForm = async() => {
    const valid = await titleRef.value?.validate()
    if (!valid?.valid) {
        ping('必須項目を入力してください。')
        return
    }
    const desc = richEdit.value ? richEdit.value?.editor.getHTML() : null
    params.description = desc
    params.blocks.forEach((block, index) => {
        block.order_number = index + 1
        normalizeDependsOn(block)
    })

    const payload = JSON.parse(JSON.stringify(params)) as CustomForm
    if (payload.usage === 'project_creation') {
        payload.users = []
        payload.has_prize = false
        payload.repeat_setting = 0
        payload.repeat_day = 1
        payload.blocks = (payload.blocks ?? []).map((block) => ({
            ...block,
            categories: null,
        }))
    } else if (payload.is_public) {
        payload.users = []
        payload.has_prize = false
        payload.repeat_setting = 0
        payload.repeat_day = 1
        payload.blocks = (payload.blocks ?? []).map((block) => ({
            ...block,
            categories: null,
            category_ids: null,
        }))
    } else {
        payload.blocks = (payload.blocks ?? []).map((block) => ({
            ...block,
            categories: null,
            category_ids: null,
        }))
    }

    await api.post('/save_custom_form', {
        ...payload,
        removed_items: removedItems.value
    }, {
        toast: '保存しました。'
    })
    emit('close', true)
}
const getParents = (currentIndex: number) => {
    return params.blocks.filter((b, index) => index < currentIndex && (b.type === 'radio' || b.type === 'checkbox'))
}
const getBlockElements = (blockId: number) => {
    const parent = params.blocks.find(b => b.id === blockId)
    return parent ? parent.elements : []
}
const getParentType = (blockId: number) => {
    const parent = params.blocks.find(b => b.id === blockId)
    return parent?.type === 'checkbox' ? 'checkbox' : 'radio'
}
const getConditionSingleElementId = (condition: CustomFormBlockDependsOn) => {
    return condition?.element_ids?.[0] ?? null
}
const addCondition = (block: CustomFormBlock, currentIndex: number) => {
    const parents = getParents(currentIndex)
    const parent = parents[0]
    const parentType = parent?.type === 'checkbox' ? 'checkbox' : 'radio'
    const elements = parent ? getBlockElements(parent.id) : []
    const condition = parent ? {
        block_id: parent.id,
        type: parentType,
        element_ids: parentType === 'radio' && elements[0]?.id ? [Number(elements[0].id)] : [],
        match: parentType === 'checkbox' ? 'any' : undefined,
    } : null
    if (!condition) {
        return
    }
    if (!block.depends_on) {
        block.depends_on = []
    }
    block.depends_on.push(condition)
}
const removeCondition = (block: CustomFormBlock, conditionIndex: number) => {
    if (!block.depends_on) return
    block.depends_on.splice(conditionIndex, 1)
}
const setConditionParent = (block: CustomFormBlock, conditionIndex: number, value: string) => {
    if (!block.depends_on) return
    const parsed = value ? Number(value) : null
    if (!parsed) {
        block.depends_on.splice(conditionIndex, 1)
        return
    }
    const parentType = getParentType(parsed)
    const elements = getBlockElements(parsed)
    block.depends_on[conditionIndex] = {
        block_id: parsed,
        type: parentType,
        element_ids: parentType === 'radio' && elements[0]?.id ? [Number(elements[0].id)] : [],
        match: parentType === 'checkbox' ? 'any' : undefined,
    }
}
const setConditionRadioElement = (block: CustomFormBlock, conditionIndex: number, value: string) => {
    if (!block.depends_on) return
    const parsed = value ? Number(value) : null
    if (!parsed) {
        block.depends_on[conditionIndex].element_ids = []
        return
    }
    block.depends_on[conditionIndex].element_ids = [parsed]
}
const toggleConditionElement = (block: CustomFormBlock, conditionIndex: number, elementId: number) => {
    if (!block.depends_on) return
    const condition = block.depends_on[conditionIndex]
    if (!condition.element_ids) {
        condition.element_ids = []
    }
    const idx = condition.element_ids.indexOf(elementId)
    if (idx > -1) {
        condition.element_ids.splice(idx, 1)
    } else {
        condition.element_ids.push(elementId)
    }
}
const setConditionMatch = (block: CustomFormBlock, conditionIndex: number, value: string) => {
    if (!block.depends_on) return
    block.depends_on[conditionIndex].match = value === 'all' ? 'all' : 'any'
}
const normalizeDependsOn = (block: CustomFormBlock) => {
    const rawDepends = Array.isArray(block.depends_on) ? block.depends_on : (block.depends_on ? [block.depends_on as CustomFormBlockDependsOn] : [])
    if (!rawDepends.length) {
        block.depends_on = []
        return
    }
    const normalized = rawDepends.map((condition) => {
        const blockId = condition.block_id
        if (!blockId) return null
        const parentType = getParentType(blockId)
        const elements = getBlockElements(blockId)
        let elementIds = Array.isArray(condition.element_ids) ? condition.element_ids : []
        const validIds = elementIds
            .map((id) => Number(id))
            .filter((id) => elements.find((el) => Number(el.id) === id))
        if (!validIds.length) return null
        if (parentType === 'radio') {
            return {
                block_id: blockId,
                type: 'radio',
                element_ids: [validIds[0]],
            }
        }
        return {
            block_id: blockId,
            type: 'checkbox',
            element_ids: validIds,
            match: condition.match === 'all' ? 'all' : 'any',
        }
    }).filter(Boolean)
    block.depends_on = normalized as CustomFormBlock['depends_on']
}
const selectAll = () => {
    if(!props.board) return
    if(params.users?.length == boardUsers.value.length){
        params.users = []
    }else{
        params.users = boardUsers.value as CustomFormUser[]
    }
}

const duplicate = (index: number, block: CustomFormBlock) => {
    const newBlock: CustomFormBlock = JSON.parse(JSON.stringify(block))
    newBlock.id = -(Math.floor(100000 + Math.random() * 900000))
    normalizeBlockCategoryInputs(newBlock)
    if(newBlock.elements && newBlock.elements.length){
        newBlock.elements.forEach( e => {
            e.id = -(Math.floor(100000 + Math.random() * 900000))
        })
    }
    params.blocks.splice(index + 1, 0, newBlock)
}
const addBranch = (blockId: number) => {
    const idx = branches.value.indexOf(blockId)
    if(idx > -1){
        branches.value.splice(idx, 1)
    }else{
        branches.value.push(blockId)
    }
}
</script>
