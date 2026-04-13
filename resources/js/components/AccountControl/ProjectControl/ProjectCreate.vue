<template>
    <div class="overlay" @click="closeOrBack">
        <div class="projectModalInner" @click.stop>
            <div class="projectModalMainHeader">
                <p class="ml-[30px]">{{ editData ? 'プロジェクトを編集する' : '新しいプロジェクトを作成する' }}</p>
                <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer" @click="closeOrBack">
                    <CloseIcon size="13"/>
                </div>
            </div>
            <div class="projectModalContainer" v-if="!isQuestion">                
                <div class="projectModalSideMenu">
                    <div class="projectModalSideMenuInner">
                        <div 
                            v-for="(title, index) in stepTitles" 
                            :key="index" 
                            class="projectModalSideMenuItem" 
                            :class="{'active-step': title.hash == activeHash }" 
                            @click="jumpTo(title.hash)">
                            {{ title.name }}
                        </div>
                    </div>

                </div>
                <div class="projectModalContent" @scroll="onScroll">
                    <div class="projectModalContentInner">
                        <AiLoader v-if="loaderPayload.loading" :message="loaderPayload.message"/>
                        <div id="basic" class="mb-[60px] section-hd">
                            <p class="mb-[20px]"><strong>基本情報</strong></p>
                            <div class="relative">
                                <ShortInput 
                                    name="name"
                                    v-model="projectParams.name"
                                    :rules="'required'"
                                    placeHolder="タイトル"
                                    type="text"
                                    ref="projectTitle"
                                />
                            </div>
                            <div class="si-box">
                                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">プロジェクト種別</p>
                                <select
                                    v-model="projectParams.project_type_id"
                                    class="custom-a-input"
                                >
                                    <option :value="null">選択してください</option>
                                    <option v-for="type in projectTypes" :key="type.id" :value="type.id">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p v-if="projectTypeError" class="text-[12px] text-[tomato] mt-[8px]">{{ projectTypeError }}</p>
                            </div>
                            <div class="si-box">
                                <MemberSelector 
                                    name="manager"
                                    rules="required"
                                    v-model="projectParams.manager"
                                    :options="managerOptions"
                                    :multiple="true"
                                    placeHolder="管理者"
                                    ref="projectManager"
                                />
                            </div>
                            <div v-if="fullAccess" class="si-box">
                                <MemberSelector 
                                    name="member"
                                    v-model="projectParams.members"
                                    placeHolder="メンバー"
                                    :options="userList"
                                    :closeOnSelect="false"
                                    :multiple="true"
                                    
                                />
                            </div>
                            <div class="si-box">
                                <div class="flex gap-5">
                                    <div>
                                        <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">プロジェクト開始日</p>
                                        <ShortInput 
                                            name="startDate" 
                                            :rules="'required'"
                                            :initialValue="projectParams.date_start"
                                            customClass="date"
                                            ref="startDateRef"
                                            type="date"
                                            v-model="projectParams.date_start"
                                        />
                                        
                                    </div>
                                    <!-- <div style="align-self: center;margin: 0 20px;font-size: 14px;color: gray;">ー</div> -->

                                    <div>
                                        <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">プロジェクト終了日</p>
                                        <ShortInput 
                                            name="endDate" 
                                            :rules="'required'"
                                            :initialValue="projectParams.date_end"
                                            customClass="date"
                                            ref="endDateRef"
                                            type="date"
                                            v-model="projectParams.date_end"
                                        />
                                    </div>
                                </div>
                                
                            </div>
                            <div class="si-box">
                                <p :class="['form-title-small', 'form-title-active']" style="margin-bottom: 10px;">契約開始日</p>
                                <div class="flex">
                                    <ShortInput 
                                        name="cStartdate" 
                                        :rules="'required'"
                                        :initialValue="projectParams.contract_started_at"
                                        customClass="date"
                                        ref="contractStartedAtRef"
                                        type="date"
                                        v-model="projectParams.contract_started_at"
                                    />
                                </div>  
                            </div>
                            <div class="si-box">
                                <p class="text-[14px]">更新の可能性</p>
                                <div class="mt-[15px] flex flex-wrap gap-[15px]">
                                    <label v-for="rp in [{value: 1, label: '有'}, {value: 0, label: '無'}]" class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer" :key="rp.value">
                                        <input class="custom-f-radio" type="radio" v-model="projectParams.is_renewable" :value="rp.value"/>
                                        {{ rp.label }}
                                    </label>
                                </div>
                            </div>
                            <!-- <div class="si-box">
                                <p class="text-[14px]">部門</p>
                                <div class="mt-[15px] flex flex-wrap gap-[15px]">
                                    <label v-for="rp in [{value: 1, label: '新規'}, {value: 0, label: '既存'}]" class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer" :key="rp.value">
                                        <input class="custom-f-radio" type="radio" v-model="projectParams.is_new" :value="rp.value"/>
                                        {{ rp.label }}
                                    </label>
                                </div>
                                <div class="si-box" v-if="projectParams.is_new">
                                    <p class="text-[14px]">既存扱い開始日</p>
                                    <div class="mt-[15px] flex">
                                        <ShortInput 
                                            name="transitionDate" 
                                            :initialValue="projectParams.transitioned_at"
                                            customClass="date"
                                            type="date"
                                            v-model="projectParams.transitioned_at"
                                        />
                                    </div>
                                </div>
                                
                            </div> -->
                        </div>
                        <div v-if="fullAccess" id="projectCreateAchievements" class="mb-[60px] section-hd">
                            <p class="mb-[20px]"><strong>実績管理機能</strong></p>
                            <div class="selectSwitchArea" style="width: fit-content;">    
                                <input type="checkbox" id="set_actual" v-model="projectParams.has_actual_func">
                                <label for="set_actual" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer']"><span></span>
                                    <div class="switch-toggle"></div>
                                </label>
                            </div>
                            <p class="text-[12px] text-[gray] mt-[8px] leading-normal">
                                実績確認機能をONにすると、メンバーの日々の実績（件数など）を記録・集計できるようになります。
                            </p>
                            <div v-if="projectParams.has_actual_func">
                                <div class="si-box" id="goalSetting">
                                    <p class="text-[14px]">目標</p>
                                    <div class="mt-[10px] flex flex-wrap gap-[15px]">
                                        <label class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer">
                                            <input class="custom-f-checkbox" type="checkbox" v-model="projectParams.has_goals"/>
                                            目標値（ゴール）
                                        </label>
                                    </div>
                                    <p class="text-[12px] text-[gray] mt-[8px] leading-normal">メンバーごとの月次目標値を設定できます。設定した目標は、月・四半期・年間の実績画面で比較表示されます。</p>
                                </div>
                                <div class="si-box" id="unitSelection">
                                    <p class="text-[14px]">成果単位</p>
                                    <div class="mt-[10px] flex flex-wrap gap-[15px]">
                                        <label v-for="unit in unitOptions" :key="unit.value" class="flex items-center gap-[10px] text-[12px] user-select-none cursor-pointer">
                                            <input class="custom-f-radio" type="radio" v-model="projectParams.unit_id" :value="unit.value"/>
                                            {{ unit.label }}
                                        </label>
                                    </div>
                                    <div class="mt-[10px]" v-if="projectParams.unit_id === 'CUSTOM'">
                                        <ShortInput 
                                            name="custom_unit_label"
                                            v-model="projectParams.custom_unit_label"
                                            placeHolder="カスタム単位（例：リード、契約数など）"
                                            type="text"
                                        />
                                    </div>
                                </div>
                                <div class="si-box" id="achievementItems">
                                    <p class="text-[14px]">実績項目</p>
                                
                                    <div class="flex flex-col gap-[10px] mt-[10px]">
                                        <div v-for="(row, index) in statusRows" :key="`status-${index}`" class="flex flex-wrap items-center gap-[10px]">
                                            <input class="custom-f-checkbox" type="checkbox" v-model="row.selected"/>
                                            <span v-if="row.is_system_default" class="text-[13px]">{{ row.label }}</span>
                                            <input
                                                v-else
                                                v-model="row.label"
                                                type="text"
                                                class="flex-1 border border-solid border-[var(--normalBorder)] px-[10px] py-[8px] text-[13px] min-w-[200px] text-[var(--primary-color)]"
                                                placeholder="実績を分類する項目名を設定してください。"
                                            />
                                            <button
                                                v-if="!row.is_system_default"
                                                type="button"
                                                class="text-[12px] px-[10px] py-[6px] border-solid border border-[var(--normalBorder)]"
                                                @click="removeStatusRow(index)"
                                            >
                                                削除
                                            </button>
                                        </div>
                                        <button type="button" class="text-left text-[13px] text-[var(--primary-color)]" @click="addCustomStatus">
                                            + 項目を追加
                                        </button>
                                    </div>
                                    <div v-if="suggestedStatuses.length" class="flex flex-wrap items-center gap-[8px] mb-[10px]">
                                        <span class="text-[12px] text-[gray] mr-[6px]">他プロジェクトからの候補:</span>
                                        <button
                                            v-for="s in suggestedStatuses"
                                            :key="`sug-${s}`"
                                            type="button"
                                            class="px-[10px] py-[6px] text-[12px] border border-solid border-[var(--normalBorder)] bg-[var(--bg3)] hover:border-[var(--primary-color)]"
                                            @click="() => addSuggestedStatus(s)"
                                        >
                                            {{ s }}
                                        </button>
                                    </div>
                                </div>
                                <p class="text-[12px] text-[gray] leading-normal mt-[10px]">
                                    すべての項目をOFFにすると、項目名は「実績」として表示されます。
                                </p>
                            </div>
                        </div>
                        <div id="overview" class="mb-[60px] section-hd">
                            <p class="mb-[20px]"><strong>概要</strong></p>
                            <div>
                                <div style="background:inherit;">        
                                    <div style="position:relative;background:inherit;">
                                        <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="serviceCategoryRef">
                                            <v-autocomplete
                                                chips
                                                :items="serviceCategories"
                                                :multiple="true"
                                                closable-chips
                                                flat
                                                tile
                                                bg-color="var(--background-color)"
                                                clear-on-select
                                                hide-details
                                                hide-selected
                                                hide-no-data
                                                focused
                                                eager
                                                label="サービスカテゴリ"
                                                :menu-props="{ scrollStrategy: 'close'}"
                                                v-model="projectParams.category"
                                                @update:model-value="validateServiceCategory(true)"
                                                
                                            >
                                                <template v-slot:chip="{ props, item }">
                                                    <v-chip
                                                        closable
                                                        v-bind="props"
                                                        :text="item.title"
                                                        :close-icon="CloseIcon"
                                                        rounded="0"
                                                        density="compact"
                                                    >
                                                    </v-chip>
                                                </template>
                                                <template v-slot:item="{ props, item }">
                                                    <!-- <v-list-item :width="serviceCategoryRef && serviceCategoryRef?.clientWidth ? serviceCategoryRef?.clientWidth - 32 : undefined" v-bind="props" :subtitle="item.raw.subtitle" :text="item.raw" rounded="0" density="compact" :ripple="false" variant="flat"></v-list-item>                     -->
                                                    <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: serviceCategoryRef && serviceCategoryRef?.clientWidth ? `${serviceCategoryRef?.clientWidth}px` : undefined}">
                                                        <div class="px-[15px] text-[var(--primary-color)]">
                                                            {{ item.title }}
                                                        </div>
                                                        <div class="text-gray-500 text-[10px] px-[30px] mt-[10px]">
                                                            {{ item.raw.subtitle }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </v-autocomplete>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="serviceCategoryError" style="position: unset;" class="i-error">{{ serviceCategoryError }}</p>

                            </div>
                            <div class="si-box flex flex-col gap-[15px]">
                                <PartnerSelector 
                                    name="customer"
                                    v-model="projectParams.customers!"
                                    placeHolder="顧客企業（正式名称）"
                                    ref="partnerSelectorRef"
                                />
                            </div>
                            <div class=si-box>
                                <div style="background:inherit;">        
                                    <div style="position:relative;background:inherit;">
                                        <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="industryTypeRef">
                                            <v-autocomplete
                                                chips
                                                :items="ProjectIndustryTypesData"
                                                :multiple="true"
                                                closable-chips
                                                flat
                                                tile
                                                bg-color="var(--background-color)"
                                                clear-on-select
                                                hide-details
                                                hide-selected
                                                hide-no-data
                                                focused
                                                eager
                                                label="業種区分"
                                                :menu-props="{ scrollStrategy: 'close'}"
                                                v-model="projectParams.industry_type"
                                                
                                            >
                                                <template v-slot:chip="{ props, item }">
                                                    <v-chip
                                                        closable
                                                        v-bind="props"
                                                        :text="item.title"
                                                        :close-icon="CloseIcon"
                                                        rounded="0"
                                                        density="compact"
                                                    >
                                                    </v-chip>
                                                </template>
                                                <template v-slot:item="{ props, item }">
                                                    <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: industryTypeRef && industryTypeRef?.clientWidth ? `${industryTypeRef?.clientWidth}px` : undefined}">
                                                        <div class="px-[15px] text-[var(--primary-color)]">
                                                            {{ item.title }}
                                                        </div>
                                                    </div>
                                                </template>
                                            </v-autocomplete>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="industryTypeError" class="i-error">{{ industryTypeError }}</p>

                            </div>

                            <div class="si-box relative">
                                <LongInput 
                                    name="private_memo"
                                    v-model="projectParams.private_memo"
                                    placeHolder="管理者用非公開メモ"
                                    ref="projectMemo"
                                    rules="required"                        
                                />

                            </div>
                            <div class="si-box relative">
                                <AiGenerationProject 
                                    v-model:text="projectParams.description"
                                    url-prefix="/project_generate_description"
                                    place-holder="概要"
                                    which="description"
                                    ref="descriptionGenerator"
                                    config-key="project_description_generation"
                                    rules="required"
                                    :data="projectParams"
                                />                                                                
                            </div>
                            <p class="text-[12px] text-[gray] mt-[15px] leading-normal">概要は管理者用の非公開メモから自動生成されます。プロジェクト情報を詳しく入力すると、より正確な概要が作成されます。</p>
 
                        </div>
                        <div class="mb-[60px] section-hd" id="miso">
                            <p class="mb-[20px]"><strong>MISO</strong></p>
                            <div class="relative">
                                <AiGenerationProject 
                                    v-model:text="projectParams.mission"
                                    url-prefix="/project_generate_miso"
                                    place-holder="ミッション"
                                    which="mission"
                                    ref="missionGenerator"
                                    config-key="project_miso_generation"
                                    rules="required"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">                                
                                <AiGenerationProject 
                                    v-model:text="projectParams.innovation"
                                    url-prefix="/project_generate_miso"
                                    place-holder="イノベーション"
                                    which="innovation"
                                    ref="innovationGenerator"
                                    config-key="project_miso_generation"
                                    rules="required"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">
                                <AiGenerationProject 
                                    v-model:text="projectParams.strategy_miso"
                                    url-prefix="/project_generate_miso"
                                    place-holder="ストラテジー"
                                    which="strategy"
                                    ref="strategyGenerator"
                                    config-key="project_miso_generation"
                                    rules="required"
                                    :data="projectParams"
                                /> 
                            </div>
                            <div class="si-box">
                                <AiGenerationProject 
                                    v-model:text="projectParams.operation"
                                    url-prefix="/project_generate_miso"
                                    place-holder="オペレーション"
                                    which="operation"
                                    ref="operationGenerator"
                                    config-key="project_miso_generation"
                                    rules="required"
                                    :data="projectParams"
                                /> 
                            </div>
                        </div>
                        <!-- <div class="mb-[60px] section-hd" id="manual">
                            <p class="mb-[20px]"><strong>業務マニュアル</strong></p>
                            <div class="relative" ref="flowContainer">
                                <BusinessManual v-model="manualDrafts" @editing-change="setBusinessManualEditing" />
                            </div>
                        </div> -->
                        <div v-if="fullAccess" class="section-hd" id="tasks">
                            <p class="mb-[20px]"><strong>タスクの自動生成</strong></p>
                            <div class="relative" ref="flowContainer">
                                <div>
                                    <p class="text-[13px] text-[gray] mt-[30px] leading-normal">
                                        プロジェクトのMISO「ミッション、イノベーション、ストラテジー、オペレーション」を元にタスクを自動生成します。<br>
                                    </p>
                                </div>
                                <div class="mt-5 flex gap-[10px]">
                                    <CommandButton 
                                        :buttons="[
                                            { title: '生成する', action: generateTasks},
                                            ...(generatedTasks.length > 0 ? [{ title: 'キャンセル', action: () => generatedTasks = [] }] : [])
                                        ]"
                                    />
                                </div>
                                <div class="mt-5 flex flex-col gap-[20px]" v-if="generatedTasks.length">
                                    <VueFlow 
                                        :nodes="flowTasks.nodes" 
                                        :edges="flowTasks.edges" 
                                        fit-view-on-init
                                        :default-zoom="1" 
                                        :min-zoom="1" 
                                        :max-zoom="1" 
                                        :nodes-draggable="false" 
                                        :zoom-on-scroll="false"
                                        :zoom-on-double-click="false" 
                                        :zoom-on-pinching="false" 
                                        :pan-on-drag="false"
                                        :pan-on-scroll="false" 
                                        :edges-deleteable="false" 
                                        :default-viewport="{ x: 40, y: 80, zoom: 1 }"
                                        @pane-ready="(vueFlowInstance) => flowInitilized(vueFlowInstance)"
                                        :style="{ 
                                            height: `${flowTasks.totalHeight}px`, 
                                            minHeight: `${flowTasks.totalHeight}px`, 
                                            minWidth: '100%' 
                                        }"
                                    >

                                        <template #node-custom="nodeProps">
                                            <Handle type="target" :position="Position.Left" :connectable="false" />
                                            <Handle type="source" :position="Position.Left" :connectable="false" />                   
                                                <SampleTask 
                                                    :task="nodeProps.data.task"
                                                    ref="mainTaskRef"
                                                    @delete="deleteTask"
                                                    @update="updateTask"
                                                />
                                        </template>

                                    </VueFlow>
                        </div> 
                    </div>                            
                </div>
                <div v-if="fullAccess" class="section-hd mt-[60px]" id="legal">
                    <p class="mb-5"><strong>契約レビュー</strong></p>
                    <div class="selectSwitchArea" style="width: fit-content;">    
                        <input type="checkbox" id="legal_review" v-model="legal_review">
                        <label for="legal_review" style="min-width: 80px;width: fit-content;" :class="['cursor-pointer']"><span></span>
                            <div class="switch-toggle"></div>
                        </label>
                    </div>
                    <p class="text-[12px] text-[gray] mt-[8px] leading-normal">
                        契約書を AI が自動レビューし、リスクや不備の可能性を簡易的に確認できます。
                        詳細な検証はプロジェクトの「リーガル」タブで実施できます。
                    </p>
                    <div v-if="legal_review" class="relative si-box" ref="flowContainer">
                        <div class="mb-[30px]" style="background:inherit;">        
                            <div style="position:relative;background:inherit;">
                                <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="serviceCategoryRef">
                                    <v-autocomplete
                                        chips
                                        :items="contractTypeDefaults"
                                        :multiple="false"
                                        closable-chips
                                        flat
                                        tile
                                        bg-color="var(--background-color)"
                                        clear-on-select
                                        hide-details
                                        hide-selected
                                        hide-no-data
                                        focused
                                        item-title="label"
                                        eager
                                        label="契約種別"
                                        :menu-props="{ scrollStrategy: 'close'}"
                                        v-model="contract_type"
                                        
                                    >
                                        <template v-slot:chip="{ props, item }">
                                            <v-chip
                                                closable
                                                v-bind="props"
                                                :text="item.label"
                                                :close-icon="CloseIcon"
                                                rounded="0"
                                                density="compact"
                                            >
                                            </v-chip>
                                        </template>
                                        <template v-slot:item="{ props, item }">
                                            <!-- <v-list-item :width="serviceCategoryRef && serviceCategoryRef?.clientWidth ? serviceCategoryRef?.clientWidth - 32 : undefined" v-bind="props" :subtitle="item.raw.subtitle" :text="item.raw" rounded="0" density="compact" :ripple="false" variant="flat"></v-list-item>                     -->
                                            <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: serviceCategoryRef && serviceCategoryRef?.clientWidth ? `${serviceCategoryRef?.clientWidth}px` : undefined}">
                                                <div class="px-[15px] text-[var(--primary-color)]">
                                                    {{ item.raw.label }}
                                                </div>
                                                <div class="text-gray-500 text-[10px] px-[30px] mt-[10px]">
                                                    {{ item.raw.focus }}
                                                </div>
                                            </div>
                                        </template>
                                    </v-autocomplete>
                                </div>
                            </div>
                        </div>
                        <div class="mb-[30px]" style="background:inherit;">        
                            <div style="position:relative;background:inherit;">
                                <div style="position: relative;background:inherit;border: 1px solid var(--primary-color);" ref="serviceCategoryRef">
                                    <v-autocomplete
                                        chips
                                        :items="contractRoleDefaults"
                                        :multiple="false"
                                        closable-chips
                                        flat
                                        tile
                                        bg-color="var(--background-color)"
                                        clear-on-select
                                        hide-details
                                        hide-selected
                                        hide-no-data
                                        focused
                                        item-title="label"
                                        eager
                                        label="当事者区分"
                                        :menu-props="{ scrollStrategy: 'close'}"
                                        v-model="contract_role"
                                        
                                    >
                                        <template v-slot:chip="{ props, item }">
                                            <v-chip
                                                closable
                                                v-bind="props"
                                                :text="item.label"
                                                :close-icon="CloseIcon"
                                                rounded="0"
                                                density="compact"
                                            >
                                            </v-chip>
                                        </template>
                                        <template v-slot:item="{ props, item }">
                                            <div v-bind="props" class="text-[14px] py-[15px] hover:bg-[var(--bg2)] cursor-pointer" :style="{width: industryTypeRef && industryTypeRef?.clientWidth ? `${industryTypeRef?.clientWidth}px` : undefined}">
                                                <div class="px-[15px] text-[var(--primary-color)]">
                                                    {{ item.raw.label }}
                                                </div>
                                            </div>
                                        </template>
                                    </v-autocomplete>
                                </div>
                            </div>
                        </div>
                        <div class="mb-[30px]">
                            <div
                                class="legal-upload"
                                :class="{'legal-upload--filled': !!uploadedMeta}"
                                role="button"
                                tabindex="0"
                                @click="triggerFileInput"
                                @keydown.enter.prevent="triggerFileInput"
                                @keydown.space.prevent="triggerFileInput"
                            >
                                <input
                                    ref="contractInput"
                                    type="file"
                                    class="legal-upload__input"
                                    @change="onChange"
                                />
                                <template v-if="!uploadedMeta">
                                    <div class="legal-upload__placeholder">
                                        <div class="legal-upload__icon">
                                            <FileIcon ext="file" />
                                        </div>
                                        <div class="legal-upload__text">
                                            <p class="legal-upload__title">契約書ファイルをアップロード</p>
                                            <p class="legal-upload__hint">PDF / Office ドキュメントなどを 1 件まで選択できます。</p>
                                            <p class="legal-upload__cta">クリックしてファイルを選択</p>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="legal-upload__content">
                                        <div class="legal-upload__info">
                                            <div class="legal-upload__icon">
                                                <FileIcon :ext="uploadedMeta.ext" />
                                            </div>
                                            <div class="legal-upload__details">
                                                <p class="legal-upload__filename" :title="uploadedMeta.name">{{ uploadedMeta.name }}</p>
                                                <p class="legal-upload__meta">{{ uploadedMeta.sizeLabel }}</p>
                                            </div>
                                        </div>
                                        <div class="legal-upload__actions">
                                            <button type="button" class="legal-upload__btn" @click.stop="previewUploaded">開く</button>
                                            <button type="button" class="legal-upload__btn legal-upload__btn--ghost" @click.stop="clearUploaded">削除</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <ProjectContract :contract="contract"/>
                        </div>
                        
                        <div>
                            <CommandButton 
                                :buttons="[
                                    { title: 'AIレビュー', action:ai_review}
                                ]"
                            />
                        </div>
                    </div>
                    
                </div>
                    <div v-if="fullAccess" class="si-box">
                        <LoaderButton @triggered="createProject(editData?.status)" content="保存する"/>
                    </div>
                    <div v-else class="si-box flex gap-[30px] justify-center" id="projectCreateButton">
                        <LoaderButton @triggered="createProject(confirmDraftStatus)" :loading="isLoading(confirmDraftStatus)" content="下書き保存" style="margin:0;"/>
                        <LoaderButton @triggered="goToConfirmApply" :loading="isLoading('pending_director')" content="次へ" style="margin:0;"/>
                    </div>
                    </div>
                </div>
            </div>
            <div class="projectModalContainer" v-else>
                <div class="projectModalSideMenu">
                    <div class="projectModalSideMenuInner">
                        <div
                            v-for="(title, index) in confirmStepTitles"
                            :key="index"
                            class="projectModalSideMenuItem"
                            :class="{'active-step': title.hash == activeHash }"
                            @click="jumpTo(title.hash)"
                        >
                            {{ title.name }}
                        </div>
                    </div>
                </div>
                <div class="projectModalContent" @scroll="onScroll">
                    <div class="projectModalContentInner">
                        <div id="confirm" class="mb-[60px] section-hd">
                            <ProjectCreationForm
                                ref="projectCreationFormRef"
                                :has-privilage="auth.hasPrivilage"
                                :edit-data="editData?.specs?.spec_data"
                                :project-type-id="projectParams.project_type_id ?? null"
                            />
                        </div>
                        <div class="si-box flex gap-[30px] justify-center" id="projectCreateConfirmButton">
                            <LoaderButton
                                @triggered="saveDraftFromProjectCreationForm"
                                :loading="isLoading(confirmDraftStatus)"
                                content="下書き保存"
                                style="margin:0;"
                            />
                            <LoaderButton
                                @triggered="submitFromProjectCreationForm"
                                :loading="isLoading('pending_director')"
                                content="申請する"
                                style="margin:0;"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts" setup>
import ShortInput from '@/components/Form/ShortInput.vue';
import LongInput from '@/components/Form/LongInput.vue';
import MemberSelector from '@/components/Form/MemberSelector.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import PartnerSelector from '@/components/Form/PartnerSelector.vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref, toRaw, useTemplateRef, watch } from 'vue';
import { Task } from '@/interface/globalInterface';
import { ComponentExposed } from 'vue-component-type-helpers';
import { Project } from '@/interface/projectInterface';  
import SampleTask from '@/components/Task/Gantt/SampleTask.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import { DateTime } from 'luxon';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import 'styles/selector.css'
import { useAuthUserStore } from '@/store/auth';
import { type Node, type Edge, MarkerType, VueFlow, VueFlowStore, Position, Handle } from '@vue-flow/core';
import AiLoader from '@/components/Global/AiLoader.vue';
import ProjectServiceCategories from 'assets/ProjectServiceCategories.json'
import ProjectIndustryTypes from 'assets/ProjectIndustryTypes.json'
import { useApi } from '@/composables/api';
const ProjectIndustryTypesData = ProjectIndustryTypes
import { useDialog } from '@/composables/dialog';
import AiGenerationProject from '@/components/Global/AiGenerationProject.vue';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';
import { useFilePreview } from '@/store/filePreview';
import { filesize } from 'filesize';
import ProjectContract from './ProjectContract.vue';
import { contractTypeDefaults, contractRoleDefaults } from '@/utils/tools';
import { useTour } from '@/composables/useTour';
import { useTutorialStore } from '@/store/tutorial';
import ProjectCreationForm from '@/components/Project/ProjectTabs/Overview/ProjectCreationForm.vue';
import { validator } from '@/validation/validator';
import type { ProjectCreationSpecData } from '@/components/Project/ProjectTabs/Overview/projectCreationForm';
import type { ProjectActualStatus, ProjectType } from '@/interface/projectInterface';
import { useDashboardStore } from '@/store/dashboard';
import { useBadgeStore } from '@/store/badge';

type ProjectStatus = 'draft' | 'creating' | 'pending_director' | 'director_approved' | 'running' | 'returned'

const { getBatchDashboardData } = useDashboardStore()
const emit = defineEmits(['close', 'getProjects'])
const props = defineProps(['userList', 'editData'])
const api = useApi()
const {ask, ping } = useDialog()
const loadingStatus = ref<ProjectStatus | null>(null)
const taskCreating = ref(false)
const misoCreating = ref(false)
const contractReviewing = ref(false)
const auth = useAuthUserStore()
const filePreview = useFilePreview()
const projectTypes = ref<ProjectType[]>([])
const projectTypeError = ref('')
const uploaded = ref<File | null>(null)
const contractInput = ref<HTMLInputElement | null>(null)
const contractPreviewUrl = ref<string | null>(null)
const legal_review = ref(false)
const unitOptions = [
    { value: 'JPY', label: '円 (JPY)' },
    { value: 'COUNT', label: '件' },
    { value: 'HOUR', label: '時間' },
    { value: 'CUSTOM', label: 'カスタム' },
] as const
type StatusRow = { status_id: number | null; label: string; selected: boolean; sort_order: number; is_system_default: boolean }
const statusRows = ref<StatusRow[]>([])
const suggestedStatuses = ref<string[]>([])
const stepTitles = computed(() => [
  { name: "基本情報", hash: "#basic" },
  ...(fullAccess.value ? [{ name: "実績管理", hash: "#projectCreateAchievements" }] : []),
  { name: "概要", hash: "#overview" },
  { name: "MISO", hash: "#miso" },
  ...(fullAccess.value ? [{ name: "タスク自動生成", hash: "#tasks" }] : []),
  ...(fullAccess.value ? [{ name: "契約レビュー", hash: "#legal" }] : []),
//   { name: "業務マニュアル", hash: "#manual" },
]);
const confirmStepTitles = [{ name: "確認事項", hash: "#confirm" }]
const isQuestion = ref(false)
const projectCreationPayload = ref<ProjectCreationSpecData | undefined>(undefined)
const defaultPlan = {
    revenue: "",
    salary: "",
    outsourcing: "",
    travel: "",
    communication: "",
    supplies: "",
    vehicle: "",
    rent: "",
    rental: "",
    lease: "",
    other: "",
    remarks: ""
}
const normalizePlanData = (raw: unknown) => {
    if (!raw) return { ...defaultPlan }
    let parsed: any = raw
    if (typeof raw === 'string') {
        try {
            parsed = JSON.parse(raw)
        } catch {
            parsed = {}
        }
    }
    const obj = (parsed && typeof parsed === 'object') ? parsed : {}
    return {
        ...defaultPlan,
        ...obj,
        lease: obj.lease ?? obj.leasing ?? ""
    }
}
const plan = reactive(normalizePlanData(props.editData?.specs?.plan_data))
const projectParams = reactive<Partial<Project>>(props.editData ? { ...toRaw(props.editData) } : {
    name: '',
    project_type_id: null,
    description: '',
    strategy_miso: '',
    mission: '',
    innovation: '',
    operation: '',
    category: [],
    manager: [],
    members: [],
    industry_type: [],
    date_start: '',
    date_end: '',
    board_id: null,
    is_new: 1,
    is_renewable: 1,
    has_goals: false,
    has_actual_func: false,
    unit_id: 'JPY',
    custom_unit_label: '',
    transitioned_at: '',
    contract_started_at: '',
})
const fetchProjectTypes = async() => {
    const data = await api.get('/project_types')
    projectTypes.value = Array.isArray(data) ? data as ProjectType[] : []
}
const contract_type = ref('outsourcing')
const contract_role = ref('乙')
const generatedTasks = ref<Task[]>([])
type ContractResp = {
    json: any;
    path: string;
    role: string;
    type: string;
}
const contract = ref<ContractResp | null>(null)
const flowInstance = ref<VueFlowStore | null>(null)
const { startTour, stopTour } = useTour()
const tutorialStore = useTutorialStore()
const hydrateStatusRows = () => {
    if (props.editData?.actual_statuses && Array.isArray(props.editData.actual_statuses)) {
        statusRows.value = props.editData.actual_statuses.map((status: ProjectActualStatus, idx: number) => ({
            status_id: status.status_id ?? null,
            label: status.label ?? status.custom_label ?? '',
            selected: true,
            sort_order: status.sort_order ?? idx + 1,
            is_system_default: status.is_system_default ?? Boolean(status.status_id),
        }));
        return;
    }
    statusRows.value.push({
        status_id: null,
        label: '',
        selected: true,
        sort_order: statusRows.value.length + 1,
        is_system_default: false,
    })
}
hydrateStatusRows();

const fetchSuggestedStatuses = async() => {
    try {
        const data = await api.get('/project_actual_status_suggestions');
        const list = Array.isArray(data?.suggestions) ? data.suggestions : [];
        suggestedStatuses.value = list.filter((label: string) => !!label);
    } catch (e) {
        suggestedStatuses.value = [];
    }
}
fetchSuggestedStatuses();


if (props.editData) {
    projectParams.has_goals = projectParams.has_goals ?? false;
    projectParams.unit_id = projectParams.unit_id ?? 'JPY';
    projectParams.custom_unit_label = projectParams.custom_unit_label ?? '';
    projectParams.date_start = DateTime.fromISO(projectParams.date_start || '').toISODate() || DateTime.now().toISODate();
    projectParams.date_end = DateTime.fromISO(projectParams.date_end || '').toISODate() || DateTime.now().plus({ days: 30 }).toISODate();
    projectParams.has_actual_func = projectParams.has_actual_func ?? false;
}
onMounted(() => {
    fetchProjectTypes()
    if(projectParams.customers == null){
        projectParams.customers = []
    }
    if(projectParams.partners == null){
        projectParams.partners = []
    }
    if(!props.editData){
        projectParams.date_start = DateTime.now().toISODate()
        projectParams.date_end = DateTime.now().plus({ days: 30 }).toISODate()
        if(auth.activeUser && projectManager.value){
            projectManager.value.selectBy([auth.activeUser])
        }
        
    }
    if(tutorialStore.state.active && tutorialStore.state.name.includes('project.create')){
        
        setTimeout(() => {
            startTour('project.create.achievements', { version: '2025-09' });
        }, 100);
        tutorialStore.setTutorial({ active: true, name: [] });
    }

})

const startDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('startDateRef')
const endDateRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('endDateRef')
const projectTitle = useTemplateRef<ComponentExposed<typeof ShortInput>>('projectTitle')
const projectManager = useTemplateRef<ComponentExposed<typeof MemberSelector>>('projectManager')
const mainTaskRef = useTemplateRef<ComponentExposed<typeof SampleTask>[]>('mainTaskRef')
const projectMemo = useTemplateRef<ComponentExposed<typeof LongInput>>('projectMemo')
const flowContainer = useTemplateRef('flowContainer')
const contractStartedAtRef = useTemplateRef<ComponentExposed<typeof ShortInput>>('contractStartedAtRef')
const projectCreationFormRef = useTemplateRef<ComponentExposed<typeof ProjectCreationForm>>('projectCreationFormRef')
const serviceCategoryRef = useTemplateRef('serviceCategoryRef')
const industryTypeRef = useTemplateRef('industryTypeRef')
const descriptionGenerator = useTemplateRef<ComponentExposed<typeof AiGenerationProject>>('descriptionGenerator')
const missionGenerator = useTemplateRef<ComponentExposed<typeof AiGenerationProject>>('missionGenerator')
const innovationGenerator = useTemplateRef<ComponentExposed<typeof AiGenerationProject>>('innovationGenerator')
const strategyGenerator = useTemplateRef<ComponentExposed<typeof AiGenerationProject>>('strategyGenerator')
const operationGenerator = useTemplateRef<ComponentExposed<typeof AiGenerationProject>>('operationGenerator')
const serviceCategories = ProjectServiceCategories
const serviceCategoryError = ref('')
const industryTypeError = ref('')
const serviceCategoryTrigger = ref(false)
const industryTypeTrigger = ref(false)
const partnerSelectorRef = useTemplateRef<ComponentExposed<typeof PartnerSelector>>('partnerSelectorRef')
const managerOptions = computed(() => {
    return props.userList.filter((user: { position_id: number; }) => user.position_id <= 6)
})
watch(
    () => projectParams.category,
    () => {
        validateServiceCategory(true)
    },
    { deep: true }
)

const uploadedMeta = computed(() => {
    if (!uploaded.value) return null
    const name = uploaded.value.name
    const extension = name.includes('.') ? name.split('.').pop()?.toLowerCase() || 'file' : 'file'
    const formattedSize = filesize(
        uploaded.value.size,
        uploaded.value.size > 1000000 ? { standard: 'jedec', round: 1 } : { standard: 'jedec', round: 0 }
    )
    const mimeType = (() => {
        if (!uploaded.value.type) return 'application'
        if (uploaded.value.type.startsWith('image/')) return 'image'
        if (uploaded.value.type.startsWith('video/')) return 'video'
        if (uploaded.value.type.startsWith('audio/')) return 'audio'
        if (uploaded.value.type.startsWith('text/')) return 'text'
        return 'application'
    })()
    return {
        name,
        ext: extension,
        sizeLabel: formattedSize,
        mimeType,
    }
})
const activeHash = ref('#basic');
const loaderPayload = computed(() => {
  const taskMessage = 'ガントチャート用のタスクをAIで自動生成中です。<br>この処理には数分かかる場合があります。'
  const misoMessage = '自動生成中です。<br>この処理には数分かかる場合があります。'
  const contractMessage = 'AIレビュー中です。<br>この処理には数分かかる場合があります。'

  if (taskCreating.value)      return { loading: true,  message: taskMessage,    kind: 'task' }
  if (misoCreating.value)      return { loading: true,  message: misoMessage,    kind: 'miso' }
  if (contractReviewing.value) return { loading: true,  message: contractMessage, kind: 'contract' }

  return { loading: false, message: '', kind: null }
})
const fullAccess = computed(() => {
    return props.editData && (props.editData.status == 'pending_director' || props.editData.status == 'director_approved' || props.editData.status == 'running')
})
const toProjectStatus = (value: unknown, fallback: ProjectStatus = 'draft'): ProjectStatus => {
    if (value === 'draft' || value === 'pending_director' || value === 'director_approved' || value === 'running' || value === 'returned') return value
    return fallback
}
const confirmDraftStatus = computed<ProjectStatus>(() => toProjectStatus(props.editData?.status, 'draft'))
const closeOrBack = () => {
    if (isQuestion.value) {
        isQuestion.value = false
        activeHash.value = '#basic'
    } else {
        emit('close')
    }
}
const onChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target && target.files ? target.files[0] : null
    if (file) {
        if (contractPreviewUrl.value) {
            URL.revokeObjectURL(contractPreviewUrl.value)
        }
        uploaded.value = file
        contractPreviewUrl.value = URL.createObjectURL(file)
        // previewUploaded()
    }
    if (target) {
        target.value = ''
    }
}
const triggerFileInput = () => {
    contractInput.value?.click()
}
const clearUploaded = () => {
    if (filePreview.active) {
        filePreview.setFilePreview({
            active: false,
            files: null,
            source: null,
            source_board_id: null,
            index: 0,
            message: null,
        })
    }
    if (contractPreviewUrl.value) {
        URL.revokeObjectURL(contractPreviewUrl.value)
    }
    contractPreviewUrl.value = null
    uploaded.value = null
    if (contractInput.value) {
        contractInput.value.value = ''
    }
}
const previewUploaded = () => {
    if (!uploaded.value || !contractPreviewUrl.value || !uploadedMeta.value) return
    filePreview.setFilePreview({
        active: true,
        files: [
            {
                id: 'legal-review-local',
                name: uploaded.value.name,
                file_path: contractPreviewUrl.value,
                doc_path: contractPreviewUrl.value,
                mime_type: uploadedMeta.value.mimeType,
                extension: uploadedMeta.value.ext,
                size: uploaded.value.size,
            }
        ],
        source: 'storage',
        source_board_id: null,
        index: 0,
        message: null,
    })
}
const ai_review = async() => {
    if (!uploaded.value) {
        ping('契約書ファイルをアップロードしてください。')
        return
    }
    
    if (props.editData?.contract) {
        let answer = { value: false}
        answer = await ask('すでにレビュー結果が存在します。\n新しいファイルをレビューすると、法務レビューに新しいファイルとして追加されます。\n新しいファイルをレビューしてもよろしいですか?')
        if (!answer.value) return
    }
    
    contractReviewing.value = true
    const formData = new FormData();
    formData.append('file', uploaded.value)
    formData.append('role', contract_role.value)
    formData.append('type', contract_type.value)
    formData.append('review_type', 'quick')
    const data = await api.post('/review_document', formData)
    if (data) {
        contract.value = data
    }
    contractReviewing.value = false
}
onBeforeUnmount(() => {
    if (contractPreviewUrl.value) {
        URL.revokeObjectURL(contractPreviewUrl.value)
    }
})

const flowTasks = computed(() => {
    const nodes = <Node[]>[]
    const edges = <Edge[]>[]
    let topOffset = 20

    generatedTasks.value.forEach((task) => {
        const offsetX = 0
        nodes.push({
            id: task.id.toString(),
            type: 'custom',
            label: task.title as string,
            position: { x: offsetX, y: topOffset },
            data: { task: task, mainTask: null },
            style:{
                width: `50%`,
                minWidth: '60px',
            }
        })
        topOffset += 116
        task.sub_tasks.forEach((subTask) => {
            topOffset += 15
            nodes.push({
                id: subTask.id.toString(),
                type: 'custom',
                label: subTask.title as string,
                position: { x: 60, y: topOffset },
                data: { task: subTask, mainTask: task },
                connectable: false,
                style:{
                    width: `50%`,
                    minWidth: '60px',
                }
            })

            edges.push({
                id: subTask.id.toString(),
                source: task.id.toString(),
                target: subTask.id.toString(),
                type: 'smoothstep',
                style:{
                    strokeWidth: 2
                },
                markerEnd: MarkerType.ArrowClosed,
            })
            topOffset += 116

        })
        topOffset += 30
    })
    return {
        nodes: nodes,
        totalHeight: topOffset,
        edges: edges,
        totalWidth: flowContainer.value?.clientWidth
    }
})
const validation = async(mode: 'draft' | 'submit' = 'submit') => {
    if (mode === 'draft' && isQuestion.value) {
        return !!projectParams.name
    }
    if (mode === 'submit' && isQuestion.value) {
        return true
    }

    const validationTargets = mode === 'draft'
        ? [projectTitle.value]
        : [
            startDateRef.value,
            endDateRef.value,
            projectTitle.value,
            projectMemo.value,
            contractStartedAtRef.value,
            { validate: () => validateServiceCategory() },
            partnerSelectorRef.value,
            descriptionGenerator.value,
            missionGenerator.value,
            innovationGenerator.value,
            strategyGenerator.value,
            operationGenerator.value,
        ]
    let result = true
    for(const target of validationTargets){                
        const val = await target?.validate() || {valid:false}
        result = result && val.valid
    }
    return result
}
const managerValidation = async() => {
    if (isQuestion.value) return true
    if (!projectManager.value) return false
    const val = await projectManager.value?.validate() || { valid: false}
    return val.valid
}
const validateServiceCategory = async(passive = false) => {
    if (passive && !serviceCategoryTrigger.value) return { valid: true }
    const { isValid, errorMessage } = await validator('required', projectParams.category)
    serviceCategoryError.value = isValid ? '' : (errorMessage || '')
    serviceCategoryTrigger.value = true
    return { valid: isValid }
}
const validateIndustryType = async(passive = false) => {
    if (passive && !industryTypeTrigger.value) return { valid: true }
    const { isValid, errorMessage } = await validator('required', projectParams.industry_type)
    industryTypeError.value = isValid ? '' : (errorMessage || '')
    industryTypeTrigger.value = true
    return { valid: isValid }
}
const validateByStatus = async(status: ProjectStatus) => {
    const errors: string[] = []

    if (status === 'draft') {
        const titleValid = await validation('draft')
        const projectTypeValid = Boolean(projectParams.project_type_id)
        projectTypeError.value = projectTypeValid ? '' : 'プロジェクト種別を選択してください。'
        if (!titleValid || !projectTypeValid) {
            errors.push('下書き保存にはタイトルとプロジェクト種別の入力が必要です。')
        }
        return errors
    }

    const baseValid = await validation('submit')
    const managerValid = await managerValidation()
    const projectTypeValid = Boolean(projectParams.project_type_id)
    projectTypeError.value = projectTypeValid ? '' : 'プロジェクト種別を選択してください。'
    if (!baseValid || !managerValid || !projectTypeValid) {
        errors.push('基本情報の必須項目を入力してください。')
    }

    return errors
}
const goToConfirmApply = async() => {
    const errors = await validateByStatus('pending_director')
    if (errors.length) {
        ping(errors.join('<br>'))
        return
    }
    isQuestion.value = true
    activeHash.value = '#confirm'
}
const getProjectCreationPayload = (): ProjectCreationSpecData | undefined => {
    const latestPayload = projectCreationFormRef.value?.getPayload?.()
    if (latestPayload) {
        projectCreationPayload.value = latestPayload
        return latestPayload
    }
    return projectCreationPayload.value
}
const saveDraftFromProjectCreationForm = () => {
    const status = toProjectStatus(props.editData?.status, 'draft')
    const specs = getProjectCreationPayload()
    createProject(status, specs)
}
const badge = useBadgeStore()
const submitFromProjectCreationForm = () => {
    const result = projectCreationFormRef.value?.validate?.()
    if (!result?.valid || !result.payload) return
    projectCreationPayload.value = result.payload
    createProject('pending_director', result.payload)
    getBatchDashboardData(['projects'])
    badge.clearProjectConfirmBadge()
}
const contractPayload = computed(() => {
    const c = contract.value
    if (!c || !c.json) return null
    return {
        data: c.json,
        file_path: c.path,
        type: c.type,
        role: c.role
    }
})
const addCustomStatus = () => {
    statusRows.value.push({
        status_id: null,
        label: '',
        selected: true,
        sort_order: statusRows.value.length + 1,
        is_system_default: false,
    })
}
const addSuggestedStatus = (label: string) => {
    const exists = statusRows.value.some(row => (row.label || '').trim() === label.trim());
    if (exists) return;
    statusRows.value.push({
        status_id: null,
        label: label,
        selected: true,
        sort_order: statusRows.value.length + 1,
        is_system_default: false,
    });
}
const removeStatusRow = (index: number) => {
    const target = statusRows.value[index]
    if (!target || target.is_system_default) return
    statusRows.value.splice(index, 1)
}
const statusPayload = computed(() => {
    let order = 1
    return statusRows.value
        .filter(row => row.selected && row.label?.trim())
        .map(row => ({
            status_id: row.is_system_default ? row.status_id : null,
            custom_label: row.is_system_default ? null : row.label.trim(),
            sort_order: order++,
        }))
})
const buildParams = (status: ProjectStatus, specs?: any) => ({
    id: props.editData?.id,
    params: {
        ...projectParams,
        actual_statuses: statusPayload.value,
        status: status,
    },
    tasks: generatedTasks.value ?? [],
    contract_data: contractPayload.value?.data,
    contract_file_path: contractPayload.value?.file_path,
    contract_role: contractPayload.value?.role,
    contract_type: contractPayload.value?.type,
    specs: specs,
    plan: { ...plan }
})
const isLoading = (s: ProjectStatus) => loadingStatus.value === s
const createProject = async(status: ProjectStatus, specs?: any) => {
    if (loadingStatus.value) return

    const errors = await validateByStatus(status)
    if(errors.length) {
        ping(errors.join('<br>'))
        return
    }

    if (status !== 'draft') {
        const membersIds = projectParams.members?.map((member: { id: number; }) => member.id) ?? []
        const managerIds = projectParams.manager?.map((manager: { id: number; }) => manager.id) ?? []
        const checkDuplicated = membersIds.filter((id: number) => managerIds.includes(id))
        if(checkDuplicated.length > 0){
            ping('メンバーと管理者に同じユーザーが含まれています。')
            return
        }
    }

    const params = buildParams(status, specs)
    
    loadingStatus.value = status
    const data = await api.post('/create_project', params, {
        toast: '保存しました。',
    })
    if(data){
        emit('close')
        emit('getProjects')
    }
    loadingStatus.value = null
}
const generateTasks = async() => {

    const managerValidate = await managerValidation()
    if(!managerValidate) return
    if (!projectParams.mission && !projectParams.innovation && !projectParams.strategy_miso && !projectParams.operation) {
        ping('タスクを生成するには、ミッション、イノベーション、ストラテジー、オペレーションのいずれかが必要です。')
        return
    }
    let result = {value: true}
    if (generatedTasks.value.length) {
        result = await ask('既存のタスクは上書きされます。よろしいですか？')
    }
    if (!result.value) return
    try {
        generatedTasks.value = []
        taskCreating.value = true   

        const userMessage = 
        `
        プロジェクト名 : ${projectParams.name}
        プロジェクトの実施期間 : ${projectParams.date_start} ~ ${projectParams.date_end}
        プロジェクトの概要 : ${projectParams.description}
        ミッション : ${projectParams.mission}
        イノベーション : ${projectParams.innovation}
        ストラテジー : ${projectParams.strategy_miso}
        オペレーション : ${projectParams.operation}
        `

        const data = await api.post('/non_stream_prompt', { message: userMessage, config_key: 'project_task_generation' })
        const parsedData = JSON.parse(data);
        generatedTasks.value = parsedData.tasks.map((task: Task) => {
            return {
                ...task,
                executors: projectParams.manager,
                sub_tasks: task.sub_tasks.map((subTask: Task) => {
                    return {
                        ...subTask,
                        executors: projectParams.manager,
                    }
                })
            }
        })
        taskCreating.value = false
        
    } catch (err) {        
        ping('タスクの自動生成に失敗しました。<br>' + err)        
        taskCreating.value = false
    }
}

const flowInitilized = (vueFlowInstance: VueFlowStore) => {
    flowInstance.value = vueFlowInstance
    if (flowInstance.value)
        flowInstance.value.setViewport({ x: 40, y: 0, zoom: 1 })
}

const deleteTask = (id: number) => {
    const index = generatedTasks.value.findIndex(task => task.id === id);
    if (index !== -1) {
        generatedTasks.value.splice(index, 1);
    } else {
        generatedTasks.value.forEach(task => {
            const subtaskIndex = task.sub_tasks.findIndex(subtask => subtask.id === id);
            if (subtaskIndex !== -1) {
                task.sub_tasks.splice(subtaskIndex, 1);
            }
        });
    }
}
const onScroll = (event: Event) => {
    const target = event.target as HTMLElement
    const sections = document.querySelectorAll(".section-hd");
    let currentSection = "";
    sections.forEach((section) => {
        const rect = section.getBoundingClientRect();
        if (rect.top <= 200 && rect.bottom >= 200) {
        currentSection = section.id;
        }
    });
    if (target.scrollTop + target.clientHeight >= target.scrollHeight) {
        currentSection = "tasks";
    }
    if (currentSection) {
        activeHash.value = `#${currentSection}`;
    }
}
const jumpTo = (hash:string) => {
    const elId = hash.replace('#', '')
    const target = document.getElementById(elId)
    if(target){
        target.scrollIntoView({behavior: 'smooth', block: 'start'})
    }
}

const updateTask = (data: { id: number; column: 'remarks'; value: string }) => {
    const task = generatedTasks.value.find(task => task.id === data.id)
    if (task) {
        task[data.column] = data.value
    } else {
        generatedTasks.value.forEach(task => {
            const subTask = task.sub_tasks.find(subTask => subTask.id === data.id)
            if (subTask) {
                subTask[data.column] = data.value
            }
        });
    }
}
watch(() => projectParams.has_actual_func, (val) => {
    if (val) setTutorialStep('project.create.achievements.detail')
})
const setTutorialStep = (key:string) => {
    if(!tutorialStore.state.active) return; 
    jumpTo('#projectCreateAchievements')
    stopTour()
    setTimeout(() => {
        startTour(key, { version: '2025-09' });
    }, 300);       
    tutorialStore.setTutorial({ active: false, name: [] })
}

</script>
<style scoped>
.legal-upload {
    display: flex;
    flex-direction: column;
    gap: 16px;
    border: 1px dashed var(--primary-color);
    background: var(--bg3);
    padding: 24px;
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}
.legal-upload:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.legal-upload:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}
.legal-upload--filled {
    border-style: solid;
}
.legal-upload__input {
    display: none;
}
.legal-upload__placeholder {
    display: flex;
    gap: 20px;
    align-items: center;
}
.legal-upload__icon {
    width: 64px;
    min-width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.legal-upload__text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.legal-upload__title {
    font-weight: 600;
    font-size: 14px;
    margin: 0;
    color: var(--primary-color);
}
.legal-upload__hint,
.legal-upload__cta {
    font-size: 12px;
    margin: 0;
    color: var(--font-color, #555);
}
.legal-upload__cta {
    font-weight: 500;
}
.legal-upload__content {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.legal-upload__info {
    display: flex;
    gap: 16px;
    align-items: center;
}
.legal-upload__details {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.legal-upload__filename {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: var(--primary-color);
    word-break: break-all;
}
.legal-upload__meta {
    font-size: 12px;
    color: var(--font-color, #666);
    margin: 0;
}
.legal-upload__actions {
    display: flex;
    gap: 12px;
}
.legal-upload__btn {
    background: var(--primary-color);
    color: var(--background-color);
    border: none;
    padding: 6px 18px;
    font-size: 12px;
    cursor: pointer;
    transition: opacity 0.2s ease;
}
.legal-upload__btn:hover {
    opacity: 0.85;
}
.legal-upload__btn--ghost {
    background: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}
@media (max-width: 959px) {
    .legal-upload {
        padding: 18px;
        border-radius: 12px;
    }
    .legal-upload__icon {
        width: 56px;
        height: 56px;
        min-width: 56px;
    }
}
</style>
