<template>
    <div :id="`record-${record.id}`" v-if="record">
        <div class="post-item-outer post-record-shell">
            <div class="flex relative justify-between">
                <div class="flex gap-2.5 items-center flex-wrap">
                    <PostIcon v-if="appName == 'post'" :which="record.app_type" size="20" />
                    <div title="このチャレンジはミニチャレンジです" v-if="record.mini"
                        class="rounded-full bg-[var(--bg3)] px-3 py-1 flex items-center gap-2">
                        <img src="/images/minisuke.webp" class="h-[23px]" />
                        <p class="text-[12px] whitespace-nowrap">ミニ</p>
                    </div>
                    <div v-html="title" class="post-title under500:hidden"></div>
                </div>
                <div class="flex flex-wrap-reverse items-center justify-end">
                    <div @click="updateStatus()" v-if="record.app_type == 2"
                        class="whitespace-nowrap cursor-pointer text-[12px] pl-4 pr-3 py-1 rounded-full bg-[var(--bg3)] mr-2">
                        <span v-once v-if="badge.post.changed_ids && badge.post.changed_ids.includes(record.id)"
                            title="ステータスが更新されました"
                            class="w-[10px] h-[10px] bg-[tomato] rounded-full inline-block mx-1"></span>
                        {{ status }}
                        <span v-if="isOwner" class="ml-1">
                            <Back class="rotate-[270deg]" size="10" />
                        </span>
                    </div>
                    <div v-if="record.app_type == 7"
                        class="whitespace-nowrap text-[12px] pl-4 pr-3 py-1 rounded-full bg-[var(--bg3)] mr-2">
                        {{ status }}
                    </div>
                    <div v-if="readableText" title="読み上げる"
                        class="h-[25px] flex justify-center relative min-w-[25px] mr-2">
                        <TTSPlayer :text="readableText" :key="`tts_post_${record.id}`" color="var(--kebab-icon)" />
                    </div>
                    <ItemMenu v-if="isOwner || auth.id === 516" :items="postMenu" />
                </div>
            </div>
            <div v-html="title" class="mt-3 post-title hidden under500:block"></div>
            <div class="post-second-wrap post-meta-grid">
                <div class="post-meta-main">
                    <div :class="['post-user-wrap', 'post-user-wrap-tight', { 'post-users-wrap': isMultipleUsers }]">
                        <RouterLink class="user-link flex items-center cursor-pointer"
                            :to="`${appName}?member=${record.user.name}`" v-if="record.app_type !== 2">
                            <UserPanel :user="record.user" :disableInstant="true" imgClass="userNormalIcon" size="30" />
                            <p class="userName">{{ record.user ? record.user.name : '' }}</p>
                        </RouterLink>
                        <div v-if="record.app_type == 2 || record.app_type == 0 || record.app_type == 7" class="relative">
                            <div class="flex items-center">
                                <svg v-if="record.app_type == 0 || record.app_type == 7" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                    class="nice-arrow mr-4" viewBox="0 0 47 32">
                                    <path
                                        d="M46.75 13.96c-1.286-1.149-2.572-2.298-3.869-3.435-1.292-1.144-2.595-2.274-3.895-3.409-1.297-1.138-2.607-2.261-3.913-3.389-1.31-1.122-2.629-2.24-3.956-3.343-0.652-0.542-1.621-0.512-2.238 0.105-0.64 0.645-0.61 1.699 0.020 2.357 1.179 1.236 2.371 2.458 3.567 3.674 1.214 1.227 2.426 2.455 3.65 3.669 0.888 0.887 1.777 1.775 2.667 2.659 0.221 0.219 0.064 0.59-0.244 0.587-1.406-0.018-2.813-0.030-4.221-0.038-3.599-0.027-7.198-0.002-10.796 0.011l-5.399 0.034-5.399 0.064c-3.599 0.052-7.198 0.11-10.796 0.221-1.068 0.035-1.94 0.916-1.928 2.010 0.012 1.076 0.914 1.934 1.99 1.966 3.578 0.107 7.156 0.165 10.734 0.219l5.399 0.064 5.399 0.034c3.598 0.012 7.197 0.035 10.796 0.011 1.397-0.009 2.793-0.021 4.19-0.038 0.308-0.003 0.465 0.369 0.244 0.587-0.887 0.875-1.771 1.755-2.659 2.633-1.227 1.213-2.44 2.44-3.659 3.662l-1.815 1.844-1.806 1.858c-0.646 0.67-0.66 1.766 0.043 2.444 0.643 0.622 1.669 0.614 2.35 0.037l1.935-1.635 1.966-1.684c1.301-1.132 2.609-2.258 3.904-3.398s2.597-2.274 3.884-3.422c1.292-1.141 3.235-2.764 4.046-3.634 0.808-0.872 0.777-2.458-0.19-3.322z">
                                    </path>
                                </svg>
                                <div ref="toUsersRef"
                                    :class="['toUserListContainer', { expandToUserListContainer: expand }]">
                                    <RouterLink class="user-link flex items-center cursor-pointer"
                                        :to="`${appName}?member=${user.name}`" :key="user.id"
                                        v-for="user in record.to_users">
                                        <UserPanel size="30" :disableInstant="true" :user="user"
                                            :imgClass="isMultipleUsers ? 'toUsersIconSmall' : 'toUsersIcon'" />
                                        <p class="w-max userName">{{ user.name }}</p>
                                    </RouterLink>
                                </div>
                            </div>

                            <div v-if="viewExpand" @click="expand = !expand" class="toUserExpandButton">
                                <svg :class="['userListArrow', { reverse: expand }]" version="1.1" width="25"
                                    viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z">
                                    </path>
                                </svg>
                                <span class="mx-1.5">({{ record.to_users.length }})</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="record.app_type == 2 && (record.challenge_main_category || record.challenge_sub_category || record.donation_target)"
                        class="post-meta-notes">
                        <div v-if="record.challenge_main_category || record.challenge_sub_category"
                            class="flex flex-wrap gap-2 text-xs mt-2 bg-[var(--bg3)] rounded-full w-fit px-3 py-1">
                            <span v-if="record.challenge_main_category" class="">
                                {{ record.challenge_main_category }}
                            </span>
                            <span>/</span>
                            <span v-if="record.challenge_sub_category" class="">
                                {{ record.challenge_sub_category }}
                            </span>
                        </div>
                        <div v-if="record.donation_target" class="post-meta-note">
                            寄付先:
                            <RouterLink
                                :to="donationTargetRoute()"
                                class="post-meta-note-link"
                            >
                                {{ record.donation_target }}
                            </RouterLink>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-end items-end gap-2 flex-1">
                    <div class="flex items-center text-sm gap-2 whitespace-nowrap">
                        <PostDate :record="record" which="period" />
                    </div>
                    <div v-if="record.app_type == 2 && challengeProgressMeta"
                        class="w-full min-w-[160px] max-w-[160px] post-progress-block">
                        <div class="h-[13px] overflow-hidden border-[softgray] bg-[var(--bg3)] relative rounded-full">
                            <div class="h-full bg-[var(--check-inactive)] transition-[width] duration-500 ease-out"
                                :style="{ width: `${challengeProgressMeta.progress}%` }"></div>
                            <div class="absolute inset-0 flex items-center justify-center text-[10px]">{{
                                challengeProgressMeta.progress }}%</div>
                        </div>
                    </div>

                    <span v-once v-if="badge.post.last_chargeable_ids.some(id => id === record.id)"
                        class="text-sm text-[tomato] inline-block mx-1">チャージする最終日</span>
                </div>
            </div>
            <div class="post-content-stack">
                <div>
                    <div ref="bodyContentRef" :class="['record-content', { 'line-clamp-3': !showMore.content }]"
                        v-html="body"></div>
                    <span v-if="showMore.content || bodyNeedsMore" @click="toggleShowMore('content')"
                        class="jump-link">{{ showMore.content ? '閉じる' : '続きを表示する' }}</span>
                </div>
                <div v-if="goal">
                    <div class="post-separetor">
                        <div>達 成 条 件</div>
                    </div>
                    <div ref="goalContentRef" :class="['record-content', { 'line-clamp-3': !showMore.goal }]"
                        v-html="goal"></div>
                    <span v-if="showMore.goal || goalNeedsMore" @click="toggleShowMore('goal')" class="jump-link">{{
                        showMore.goal ? '閉じる' : '続きを表示する' }}</span>
                </div>
                <PostFiles v-if="record.files.length" :items="record.files" />
                <div v-if="result">
                    <div class="post-separetor">
                        <div>{{ record.status_flag == 5 ? '進 捗 状 況' : '結 果 発 表' }}</div>
                    </div>
                    <div ref="resultContentRef" :class="['record-content', { 'line-clamp-3': !showMore.result }]"
                        v-html="result"></div>
                    <span v-if="showMore.result || resultNeedsMore" @click="toggleShowMore('result')"
                        class="jump-link">{{ showMore.result ? '閉じる' : '続きを表示する' }}</span>
                </div>
                <PostFiles v-if="record.result_files && record.result_files.length" :items="record.result_files" />
                <div v-if="record.grants && record.grants.length && record.grantable && totalExpenses > 0">
                    <div class="post-separetor">
                        <div>必 要 経 費</div>
                    </div>
                    <div v-for="grant in record.grants" :key="grant.id">
                        <div>{{ grant.content }}</div>
                        <div v-if="grant.expenses" class="text-[14px]">金額：{{ amountOfMoneyParser(grant.expenses) }}円
                        </div>
                        <div v-if="grant.file_path" class="mt-2 mb-4">
                            <div v-if="isGrantImage(grant)">
                                <img @click="previewGrantFile(grant)" style="height:120px;cursor: pointer;"
                                    v-if="grant?.file_path" :src="grantFileUrl(grant)" />
                            </div>
                            <div v-else>
                                <div class="cursor-pointer" style="position:relative;" @click="previewGrantFile(grant)">
                                    <FileIcon :ext="grantFileExtension(grant)" />
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div v-if="record.app_type == 6 && (auth.activeUser.id == 610 || record.user_id == auth.id)">
                    <div class="post-separetor">
                        <div>領収（非公開）</div>
                    </div>
                    <PostFiles path="/post_receipts" v-if="record.receipts.length" :items="record.receipts" />
                </div>

                <div class="post-url" v-if="record.referrer">
                    参照元 : <a :href="record.referrer" target="_blank" rel="noopener noreferrer">{{ record.referrer }}</a>
                </div>
                <div v-if="tags.length" class="flex gap-x-2.5 gap-y-2 flex-wrap">
                    <PostTag v-for="tag in tags" :tag="tag" :key="tag.id" />
                </div>
                <div class="post-entries-panel" v-if="record.entries?.length && viewEntries">
                    <div class="bg-[var(--bg2)] flex justify-between sticky top-0 z-10 p-2 items-center cursor-pointer">
                        <div class="text-[14px] cursor-pointer" @click="viewParticipants">参加者 {{ participants.length }}人
                        </div>
                        <CloseIcon size="12" @click="closeAndScroll(record.id)" />
                    </div>
                    <div :style="{
                        borderBottom: index === record.entries.length - 1
                            ? 'none'
                            : '1px solid var(--check-inactive)'
                    }" v-for="(entry, index) in record.entries" :key="entry.id">
                        <PostEntryRecord :entry="entry" @setClap="setClap" />
                    </div>

                    <!-- <div v-if="record.entries.length > 1" class="pt-2">
                        <button
                        type="button"
                        class="text-sm text-blue-600 hover:underline"
                        @click="viewEntries = !viewEntries"
                        >
                        {{ viewEntries ? '閉じる' : `他 ${record.entries.length - 1} 件を見る` }}
                        </button>
                    </div> -->
                </div>
                <div v-if="(record.app_type == 2 && record.status_flag == 0) || (record.app_type == 7)"
                    class="text-[12px] flex-wrap justify-center flex w-fit mx-auto text-[gray] items-center gap-2 whitespace-nowrap">
                    <p>チャージ受付期間：</p>
                    <PostDate :record="record" class="!m-0" which="charge_period" />
                </div>
                <div class="flex flex-col justify-center items-center gap-2 mb-6 mx-auto w-full"
                    v-if="challengeButtonView">
                    <button @click="emit('setChargeTarget', record)" v-if="challengeButtonSwitch" id="chargeAddButton"
                        class="chargeFormeAddButton cursor-pointer">{{ props.record.app_type == 7 ? 'チャージする' : 'チャレンジにチャージする' }}</button>
                    <button v-else class="chargeFormeAddButton" disabled>{{ canNotCharge }}</button>
                </div>
                <div v-if="record.app_type == 5">
                    <button id="glowlympicButton" class="chargeFormeAddButton cursor-pointer">参加期間は終了しました</button>
                </div>
            </div>
            <div class="post-footer mb-1 text-sm justify-end" v-if="record.app_type == 2 || record.app_type == 7">
                <div>現在のチャージ総額 {{ totalChargeAmmount }}円</div>
            </div>
            <div class="post-footer">
                <div class="flex items-center gap-3 ml-auto flex-wrap justify-end">
                    <div class="post-footer-wrap" v-if="record.app_type == 2 && record.grantable && totalExpenses > 0">
                        <div class="text-[14px]">経費合計: {{ amountOfMoneyParser(totalExpenses) }}円</div>
                    </div>
                    <div v-if="record.app_type == 2 || record.app_type == 7" class="post-footer-wrap">
                        <div class="text-[14px] cursor-pointer" @click="viewSupporters" v-if="supporters.length">サポーター
                            {{ supporters.length }}人</div>
                    </div>
                    <div v-if="record.app_type == 5" class="post-footer-wrap">
                        <div class="text-[14px] mr-[20px]">カロリー合計: <span v-if="totalCalories">🔥 </span>{{
                            amountOfMoneyParser(totalCalories) }} kcal</div>
                        <div class="text-[14px] cursor-pointer flex items-center gap-1"
                            @click="viewEntries = !viewEntries">
                            <People size="25" />
                            {{ record.entries.length }}
                        </div>
                    </div>
                    <div v-if="record.app_type == 6 && record.refresh_amount && auth.id === record.user_id"
                        class="post-footer-wrap">
                        <div class="text-[14px] cursor-pointer">リフレッシュ総額（非公開）: {{ record.refresh_amount }}円</div>
                    </div>

                    <div class="post-footer-wrap">
                        <div class="post-comment-trigger" :title="hasProgressReportBadge ? '新しい進捗報告があります' : undefined"
                            @click="isExpanded = !isExpanded">
                            <span v-if="hasProgressReportBadge" class="post-comment-badge custom-heartbeat"
                                aria-hidden="true"></span>
                            <svg class="comment-icon" version="1.1" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 40 32">
                                <path
                                    d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z">
                                </path>
                                <path
                                    d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z">
                                </path>
                            </svg>
                            <span class="comment-count leading-none" v-if="record.comments_count">{{
                                record.comments_count }}</span>
                        </div>
                    </div>
                    <ClapButton v-if="clapButtonView" @updateClap="setClap" :item="record" :appName="appName" />
                </div>

            </div>
            <div v-if="relayChainUserCount > 1" class="post-meta-note relay-chain-footer">
                <p class="mb-[10px] text-xs flex items-center gap-1.5">
                    <span>{{ record.app_type === 2 ? 'チャレンジリレー' : 'ナイスリレー' }}</span>
                    <span v-if="isNiceRelayComplete" class="relay-complete-badge">コンプリート</span>
                </p>
                
                <div class="relay-chain-row">
                    <template v-for="(group, index) in relayChainGroups" :key="`relay-group-${index}`">
                        <!-- <span
                            v-if="index > 0"
                            :class="[
                                'relay-chain-line',
                                group.connector === 'dashed' ? 'relay-chain-line--dashed' : 'relay-chain-line--solid'
                            ]"
                        ></span> -->
                        <Back v-if="index > 0" class="rotate-180" size="12" fill="gray"/>
                        <span class="relay-chain-user-group">
                            <UserPanel
                                v-for="user in group.users.slice(0, 3)"
                                :key="user.id"
                                :user="user"
                                :disableInstant="true"
                                size="24"
                            />
                            <p @click="viewRelayUsers(group.users)" style="margin-top:2px;cursor:pointer;font-size:12px;margin-left: 3px;" v-if="group.users.length > 3">({{group.users.length}})</p> 
                        </span>
                    </template>
                </div>
            </div>
            <transition name="commentArea">
                <PostComment v-if="isExpanded" :key="`comment-${record.id}-${isExpanded ? 'open' : 'close'}`"
                    :app_name="appName" :record="record" />
            </transition>
        </div>
        <div class="post-reaction-row">
            <div class="flex w-fit relative items-center gap-2">
                <Transition name="downShiftPop">
                    <div class="w-max absolute p-4 bg-[var(--background-color)] z-10 bottom-[35px] shadow-xl"
                        :id="`iokawaReactionPop_${record.id}`" v-if="menu.parent == `iokawaReactionPop_${record.id}`">
                        <div class="grid grid-cols-5 gap-2">
                            <div class="flex items-end justify-center transition-transform duration-200 ease-out hover:scale-105"
                                v-for="oikawa in oikawaMap" :key="oikawa.name" @click="sendEmote(oikawa.name)">
                                <Character :size="40" :emoteName="oikawa.name" />
                            </div>
                        </div>
                    </div>
                </Transition>
                <div class="cursor-pointer" @click.stop="emoteAction(record)">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18" height="19" viewBox="0 0 30 30"
                        style="fill: var(--check-inactive)">
                        <path
                            d="M14.977,0C6.735-0.056-0.127,6.93,0.002,15.153c-0.028,8.165,6.816,14.938,14.975,14.811v-0.04c0.967,0.013,1.936-0.067,2.889-0.242c4.817-0.863,9.055-4.275,10.937-8.8C32.985,11.039,25.688-0.021,14.977,0 M14.977,27.902C6.08,27.658-0.075,18.755,3.433,10.373C7.814,0.291,22.13,0.293,26.49,10.386C30.002,18.61,23.886,27.788,14.977,27.902">
                        </path>
                        <path
                            d="M22.441,18.263c-0.623-0.436-1.479-0.284-1.917,0.338c0.007-0.011,0.002-0.006-0.001-0.004c-0.002,0.002-0.006,0.005-0.011,0.01l-0.027,0.025c-0.734,0.658-1.568,1.264-2.479,1.639c-0.291,0.123-0.596,0.222-0.9,0.292c-0.67,0.185-1.332,0.349-2.043,0.376c-2.039,0.059-4.107-0.841-5.435-2.355c-1.226-1.563-3.443,0.199-2.196,1.769c0.199,0.27,0.418,0.529,0.646,0.772c1.784,1.911,4.359,3.094,6.986,3.106c1.119,0.021,2.305-0.08,3.354-0.525c1.753-0.72,3.36-1.896,4.362-3.526C23.214,19.556,23.063,18.698,22.441,18.263">
                        </path>
                        <path
                            d="M18.513,14.558c0.905,0.201,1.834-0.509,2.073-1.585c0.239-1.076-0.302-2.111-1.208-2.313c-0.904-0.201-1.833,0.509-2.072,1.585C17.065,13.322,17.606,14.357,18.513,14.558">
                        </path>
                        <path
                            d="M11.44,14.558c0.906-0.201,1.446-1.236,1.208-2.313c-0.239-1.076-1.167-1.786-2.074-1.585c-0.906,0.203-1.446,1.238-1.208,2.313C9.605,14.049,10.534,14.759,11.44,14.558">
                        </path>
                    </svg>
                </div>

            </div>
            <div @click="setEmoteUsers(record.emoted_users)" v-if="record.emoted_users && record.emoted_users.length">
                <div class="flex items-end cursor-pointer text-[var(--primary-color)] flex-wrap gap-1">
                    <TransitionGroup name="downShiftPop">
                        <Character v-for="emote in emotes" :key="emote" :size="40" :emoteName="emote" />
                    </TransitionGroup>
                </div>
            </div>
        </div>

    </div>
</template>
<script setup lang="ts">
import UserPanel from '@/components/Global/UserPanel.vue'
import TTSPlayer from '@/components/Global/TTSPlayer.vue'
import PostDate from './PostDate.vue'
import PostTag from './PostTag.vue';
import ClapButton from './ClapButton.vue';
import PostComment from './PostComment.vue'
import PostFiles from './PostFiles.vue';
import { computed, inject, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import PostIcon from './PostIcon.vue';
import { DateTime } from 'luxon';
import { amountOfMoneyParser, customParser, oikawaMap, urlCheck } from '@/utils/tools';
import { Post, PostEntry } from '@/interface/postInterface';
import { User } from '@/interface/globalInterface';
import PostEntryRecord from './PostEntryRecord.vue';
import { fileExtensionFromPath, filePreviewTypeFromPath, workFilePreview } from '@/utils/workApi';
import People from '../Icons/People.vue';
import CloseIcon from '../Form/CloseIcon.vue';
import FileIcon from '../Board/Mixed/FileIcon.vue';
import { useBadgeStore } from '@/store/badge';
import Character from '@/components/Global/Character.vue';
import { useApi } from '@/composables/api';
import { useModal } from '@/composables/modal';
import { PostMethods, PostMethodsKey } from '@/interface/keys';
import Back from '../Icons/Back.vue';
import { useTheme } from '@/store/theme.js';
const messageUsers = useMessageUsers()
const menu = useMenuStore()
const auth = useAuthUserStore()
const responsive = useResponsive()
const badge = useBadgeStore()
const api = useApi()
const { setEmoteUsers } = useModal()
const props = defineProps<{
    record: Post,
    appNameJp: string,
    appName: string,
    apps: string[]
}>()
const grantFileExtension = (grant: { file_path?: string | null }) => {
    return fileExtensionFromPath(grant.file_path)
}
const grantFileType = (grant: { file_path?: string | null }) => {
    return filePreviewTypeFromPath(grant.file_path)
}
const isGrantImage = (grant: { file_path?: string | null }) => {
    return Boolean(grant.file_path) && grantFileType(grant) === 'image'
}
const grantFileUrl = (grant: { file_path?: string | null }) => {
    return `/cdn/post_grant_files/${grant.file_path}`
}
const previewGrantFile = (grant: { file_path?: string | null }) => {
    if (!grant.file_path) return

    workFilePreview(grant.file_path, grantFileType(grant), '/cdn/post_grant_files')
}
const emit = defineEmits<{
    'setChargeTarget': [Post],
    'setClap': [number],
    'editRecord': [Post],
    'updateStatus': [Post],
    'deleteRecord': [Post],
    'setEntryData': [{ record: Post, editData: PostEntry | null }],
}>()
const { updateRecord } = inject(PostMethodsKey) as PostMethods
const route = useRoute()
const donationTargetRoute = () => {
    const query = { ...route.query }
    delete query.id
    delete query.create
    delete query.status
    delete query.progress_checkpoint

    return {
        path: '/post',
        query: {
            ...query,
            app_type: '2',
            donation_target: 'exists',
        },
    }
}
const maxLength = ref(200)
const truncated = ref<{
    type: string,
    active: boolean,
    expand: boolean
}[]>([
    { type: 'body', active: false, expand: false },
    { type: 'goal', active: false, expand: false },
    { type: 'result', active: false, expand: false }
])
// const showAll = ref(false)
const expand = ref(false)
const viewExpand = ref(false)
const isExpanded = ref(false)
const showMore = ref({
    content: false,
    goal: false,
    result: false
})
const theme = useTheme()
const toUsersRef = useTemplateRef('toUsersRef');
const bodyContentRef = useTemplateRef<HTMLElement>('bodyContentRef')
const goalContentRef = useTemplateRef<HTMLElement>('goalContentRef')
const resultContentRef = useTemplateRef<HTMLElement>('resultContentRef')
const bodyNeedsMore = ref(false)
const goalNeedsMore = ref(false)
const resultNeedsMore = ref(false)
const viewEntries = ref(false)
const excludedRelayUserIds = [100, 101, 102, 103, 608, 610, 830]
const NICE_RELAY_LIMIT = 9
const relayChainGroups = computed(() => {
    if (props.record.relay_chain_groups?.length) {
        return props.record.relay_chain_groups.filter(group => group.connector !== 'dashed')
    }

    if (props.record.app_type === 0 && props.record.post_relays?.length) {
        const niceRelays = props.record.post_relays.filter(relay => relay.relay_type === 'nice' && !excludedRelayUserIds.includes(relay.to_user_id))
        const continuedRelay = niceRelays.find(relay => relay.accepted_post_id)
        const visibleRelays = continuedRelay
            ? [continuedRelay]
            : niceRelays.filter(relay => Number(relay.status) === 0)
        const relayUsers = visibleRelays
            .map(relay => relay.to_user ?? props.record.to_users.find(user => user.id === relay.to_user_id))
            .filter((user): user is User => Boolean(user))

        if (relayUsers.length) {
            return [
                { users: [props.record.user] },
                {
                    users: relayUsers,
                    connector: 'solid' as const,
                }
            ]
        }
    }

    if (props.record.relay_chain?.length) {
        return props.record.relay_chain.filter(node => node.connector !== 'dashed').map(node => ({
            users: [node.user],
            connector: node.connector,
        }))
    }

    return (props.record.relay_chain_users ?? []).map(user => ({
        users: [user],
        connector: 'solid' as const,
    }))
})
const relayChainUserCount = computed(() => relayChainGroups.value.reduce((count, group) => count + group.users.length, 0))
const isNiceRelayComplete = computed(() => props.record.app_type === 0 && relayChainUserCount.value === NICE_RELAY_LIMIT)
onMounted(() => {
    const to_user = toUsersRef.value
    if (to_user && to_user.scrollHeight > to_user.clientHeight) {
        viewExpand.value = true
    }
    nextTick(() => {
        if (bodyContentRef.value)
            bodyNeedsMore.value = bodyContentRef.value.scrollHeight > bodyContentRef.value.clientHeight
        if (goalContentRef.value)
            goalNeedsMore.value = goalContentRef.value.scrollHeight > goalContentRef.value.clientHeight
        if (resultContentRef.value)
            resultNeedsMore.value = resultContentRef.value.scrollHeight > resultContentRef.value.clientHeight
    })
    const queryId = route.query.id as string
    if (queryId) {
        const id = parseInt(queryId)
        if (id == props.record.id) {
            isExpanded.value = true
            if (props.record.app_type == 2 && isOwner.value && route.query.status) {
                updateStatus()

            }

        }
    }
})
const toggleShowMore = (which: keyof typeof showMore.value) => {
    showMore.value[which] = !showMore.value[which]
}
const clapButtonView = computed(() => {
    return props.record.created_at && DateTime.fromISO(props.record.created_at) > DateTime.fromISO('2026-04-12') ? false : true
})
const showAll = (type: string) => {
    const item = truncated.value.find(t => t.type === type)
    if (item) item.expand = !item.expand
}
const closeAndScroll = (id: number) => {
    viewEntries.value = false
    nextTick(() => {
        const el = document.getElementById(`record-${id}`)
        console.log(el)
        el?.scrollIntoView({ behavior: "smooth", block: "start" })
    })
}
const postMenu = computed(() => {
    const appName = props.apps?.[props.record.app_type] ?? 'アプリ'

    const items: any[] = [];

    if (props.record.app_type !== 2) {
        items.push({ title: `${appName}を編集する`, action: () => emit('editRecord', props.record) })
    } else {
        items.push({ title: 'ステータスを変更・進捗入力', action: updateStatus })
    }
    items.push({ title: `${appName}を削除する`, action: () => emit('deleteRecord', props.record) },)
    return items
})

const totalExpenses = computed(() => {
    if (props.record.grants && props.record.grants.length) {
        const amounts = props.record.grants.map(ob => ob.expenses ? ob.expenses : 0)
        const sum = amounts.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
        return sum
    }
    return 0
})
const isMultipleUsers = computed(() => {
    return responsive.mobile && props.record && props.record.to_users && props.record.to_users.length > 1
})
// A rakuaward nice is chargeable like a mini challenge: max 500, window = created_at -> end of month.
const niceChargeEnd = computed(() => DateTime.fromISO(props.record.created_at).endOf('month'))
const status = computed(() => {
    if (props.record.app_type === 7) {
        if (props.record.rakuaward_granted_at) return 'MVP'
        return DateTime.now() <= niceChargeEnd.value ? 'チャージ受付中' : 'ノミネート'
    }
    if (props.record.app_type !== 2) return;
    const statusMap = {
        0: DateTime.now() <= customParser(props.record.date_end) ? 'チャージ受付中' : '結果待ち',
        1: '達成',
        2: '未達成',
        3: '中止',
        4: '不成立',
        5: 'チャレンジ進行中'
    };
    return statusMap[props.record.status_flag as keyof typeof statusMap];
});
const challengeProgressMeta = computed(() => {
    if (props.record.app_type !== 2) {
        return null
    }
    if (props.record.status_flag == 2 || props.record.status_flag == 3 || props.record.status_flag == 4) {
        return {
            progress: 0,
            label: status.value
        }
    }
    const start = DateTime.fromISO(props.record.date_start).set({ hour: 0, minute: 0, second: 0, millisecond: 0 })
    const end = DateTime.fromISO(props.record.date_end).set({ hour: 23, minute: 59, second: 59, millisecond: 999 })
    const now = DateTime.now()

    const isBetween = now >= start && now <= end
    if (!isBetween) {
        return null
    }

    if (!start.isValid || !end.isValid || end <= start) {
        return null
    }

    const totalMillis = end.toMillis() - start.toMillis()
    const elapsedMillis = now.toMillis() - start.toMillis()
    const rawProgress = (elapsedMillis / totalMillis) * 100
    const progress = Math.max(0, Math.min(100, Math.round(rawProgress)))

    if (now < start) {
        return {
            progress: 0,
            label: `${Math.max(0, Math.ceil(start.diff(now, 'days').days ?? 0))}日後に開始`
        }
    }

    if (now > end) {
        return {
            progress: 100,
            label: '期間終了'
        }
    }

    return {
        progress,
        label: `残り${Math.max(0, Math.ceil(end.diff(now, 'days').days ?? 0))}日`
    }
})
const hasProgressReportBadge = computed(() => {
    return badge.post.progress_report_ids?.includes(props.record.id) ?? false
})
const supporters = computed(() => {
    if (props.record.app_type == 2 || props.record.app_type == 7) {
        return props.record.awards
    }
    return []
})
const totalChargeAmmount = computed(() => {
    if (props.record.app_type == 2 || props.record.app_type == 7) {
        const amounts = props.record.awards.map(ob => {
            return ob.pivot ? ob.pivot.award_bet : 0
        })
        const sum = amounts.reduce((accumulator, currentValue) => accumulator + currentValue, 0);
        return sum
    }
    return ''
})
const challengeButtonSwitch = computed(() => {
    const charged_user = props.record.awards.some(obj => obj.id == auth.id);
    if (charged_user) return false
    if (props.record.app_type == 7) {
        return DateTime.now() <= niceChargeEnd.value
    }
    if (DateTime.fromISO(props.record.created_at) <= DateTime.now().minus({ days: 14 })) return false
    return DateTime.now() <= customParser(props.record.date_end) && (props.record.status_flag == 0 || props.record.status_flag == 5)
})
const canNotCharge = computed(() => {
    const charged_user = props.record.awards.find(obj => obj.id == auth.id);
    if (charged_user) return '既にチャージしています'
    if (props.record.app_type == 7) {
        return DateTime.now() > niceChargeEnd.value ? 'チャージ期間終了しました' : ''
    }
    if (DateTime.fromISO(props.record.created_at) <= DateTime.now().minus({ days: 14 })) {
        return 'チャージ期間終了しました'
    }
    if (props.record.status_flag > 0 && props.record.status_flag < 5) {
        return 'チャレンジ終了'
    }
    if (DateTime.now() > customParser(props.record.date_end)) {
        return 'チャージ期間終了しました'
    }
    return ''
})
const challengeButtonView = computed(() => {
    if (props.record.app_type == 2) {
        return !props.record.to_users.some(obj => obj.id == auth.id)
    }
    if (props.record.app_type == 7) {
        const isRecipient = props.record.to_users.some(obj => obj.id == auth.id)
        const isAuthor = props.record.user_id == auth.id
        return !isRecipient && !isAuthor
    }
    return false
})
const isOwner = computed(() => {
    if (props.record && auth.user) {
        if (props.record.app_type == 2) {
            const player = props.record.to_users.some(ob => ob.id == auth.id)
            return player
        } else {
            return props.record.user_id == auth.id
        }
    }
})
const tags = computed(() => {
    return props.record.tags ? props.record.tags : []
})
const title = computed(() => {
    return props.record && props.record.title ? props.record.title : ''
})
// Plain-text version of the post's content for text-to-speech.
const htmlToText = (html: string | null | undefined) => {
    if (!html) return ''
    const div = document.createElement('div')
    div.innerHTML = html
        .replace(/<\/(p|div|h[1-6]|li|tr)>/gi, '$&\n')
        .replace(/<br\s*\/?>/gi, '\n')
    return (div.textContent ?? '').replace(/\n{3,}/g, '\n\n').trim()
}
const readableText = computed(() => {
    const isChallenge = props.record.app_type == 2
    const parts = [
        htmlToText(title.value),
        htmlToText(isChallenge ? props.record.content_rule : props.record.content),
    ]
    if (isChallenge) {
        const g = htmlToText(props.record.content_goal)
        if (g) parts.push('達成条件。\n' + g)
    }
    const r = htmlToText(props.record.result)
    if (r) parts.push((props.record.status_flag == 5 ? '進捗状況。\n' : '結果発表。\n') + r)
    return parts.filter(Boolean).join('\n\n').replace(/https?:\/\/[^\s]+/g, '')
})
const body = computed(() => {
    const text = props.record.app_type == 2 ? props.record.content_rule : props.record.content
    const urlParse = urlCheck(text)
    return urlParse

})
const goal = computed(() => {
    const text = props.record.app_type == 2 ? props.record.content_goal : ''
    const urlParse = urlCheck(text)
    return urlParse
})
const result = computed(() => {
    const text = props.record.app_type == 2 ? props.record.result : props.record.result
    const urlParse = urlCheck(text)
    return urlParse
})
const updateStatus = () => {
    if (isOwner.value && (props.record.status_flag < 4 || props.record.status_flag == 5)) {
        emit('updateStatus', props.record)
    }
}
const closeMenu = () => {
    menu.setMenu({ name: '', id: null })
}
const viewSupporters = () => {
    const data = {
        active: true,
        userList: supporters.value,
        title: 'サポーター'
    }
    messageUsers.setMessageUsers(data)
}
const viewRelayUsers = (users: User[]) => {
    const data = {
        active: true,
        userList: users,
        title: 'リレー参加者'
    }
    messageUsers.setMessageUsers(data)
}
const viewParticipants = () => {
    const data = {
        active: true,
        userList: participants.value,
        title: '参加者'
    }
    messageUsers.setMessageUsers(data)
}
const setClap = () => {
    emit('setClap', props.record.id)
}
const emotes = computed(() => {
    if (!props.record.emoted_users?.length) return []
    return props.record.emoted_users.map(item => item.pivot.emote_name)
})
const sendEmote = async (name: string) => {
    menu.close()
    const data = await api.post('/post_send_emote', { id: props.record.id, reaction: name })
    updateRecord(data)
}
const emoteAction = (record: Post) => {
    menu.setMenu({ parent: `iokawaReactionPop_${record.id}` })
}
const cutter = (string: string, len: number, type: string) => {
    if (!string) {
        return ''
    }
    const showMore = truncated.value.find(t => t.type === type)?.expand
    if (showMore || string.length <= len || string.length <= len + 50) {
        return string
    }
    const last = string.substring(len - 5, len + 5)
    const check_emoji = last.match(/[\p{Emoji}\u200d]+/gu)
    if (!check_emoji) {

        const item = truncated.value.find(t => t.type === type)
        if (item) {
            item.active = true
        }

        return string.substring(0, len) + '...'

    } else {
        return cutter(string, len + 5, type)
    }

}
const participants = computed(() => {
    // Map entries to users and filter out duplicates by creating a Map with user.id as key
    const userMap = new Map<number, User>();
    props.record.entries.forEach(entry => {
        if (entry.user && !userMap.has(entry.user.id)) {
            userMap.set(entry.user.id, entry.user);
        }
    });
    return Array.from(userMap.values()) as User[];
});
const totalCalories = computed(() => {
    return props.record.entries.reduce((total, entry) => {
        return total + (entry.calories || 0);
    }, 0);
});
</script>
<style scoped>
.post-record-shell {
    display: flex;
    flex-direction: column;
}

.post-meta-grid {

    gap: 12px;
    padding-bottom: 12px;
    /* border-bottom: 1px solid var(--calendarBorder); */
}

.post-meta-main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.post-user-wrap-tight {
    min-width: 0;
}

.post-meta-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    min-width: 0;
}

.post-progress-block {
    margin: 2px 0;
}

.post-meta-notes {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.post-meta-chip {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
}

.post-meta-chip-main {
    background: var(--bg2);
    color: var(--primary-color);
}

.post-meta-chip-sub {
    background: var(--bg3);
    color: var(--sub-color);
}

.post-meta-note {
    font-size: 12px;
    line-height: 1.5;
    color: var(--sub-color);
}

.post-meta-note-link {
    color: var(--link-color);
    text-decoration: none;
}

.post-meta-note-link:hover {
    text-decoration: underline;
}

.relay-chain-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.relay-chain-footer {
    margin-top: 10px;
}

.relay-complete-badge {
    background: var(--bg3);
    color: var(--primary-color);
    font-size: 10px;
    line-height: 1;
    padding: 4px 12px;
    border-radius: 9999px;
    white-space: nowrap;
}

.relay-chain-user-group {
    display: inline-flex;
    align-items: center;
}

.relay-chain-line {
    width: 18px;
    border-top: 2px solid var(--sub-color);
    opacity: 0.7;
}

.relay-chain-line--dashed {
    border-top-style: dashed;
}

.relay-chain-line--solid {
    border-top-style: solid;
}

.post-content-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.post-entries-panel {
    margin: 4px 0;
    display: flex;
    flex-direction: column;
    gap: 16px;
    background: var(--bg2);
    padding: 12px;
}


.post-reaction-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    margin-left: 20px;
    width: calc(100% - 20px);
}

.post-comment-trigger {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 5px;
}

.post-comment-badge {
    top: 1px;
    right: -1px;
    width: 9px;
    height: 9px;
    background: #d97706;
    border-radius: 9999px;
}
</style>
