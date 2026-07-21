<template>
<Modal @close="router.push({name: 'contact'})" size="large" :disableScroll="activeTab === 'comment'">
    <template #title>
        <div class="flex items-center gap-[14px] min-w-0 pr-[10px]">
            <ContactIcon :contact="contact" size="46"/>
            <div class="min-w-0">
                <div class="flex items-center gap-[8px] flex-wrap">
                    <span class="text-[19px] font-bold leading-[1.2] text-[var(--primary-color)]">{{ contact.name }}</span>
                    <span
                        v-for="t in (contact.types ?? [])"
                        :key="t.id ?? t.title"
                        class="inline-flex items-center rounded-full px-[11px] py-[3px] text-[12.5px] font-medium bg-[var(--kebab-bg1)] text-[var(--primary-color)] whitespace-nowrap"
                    >{{ t.title }}</span>
                    <span v-if="!(contact.types ?? []).length" class="inline-flex items-center rounded-full px-[11px] py-[3px] text-[12.5px] font-medium bg-[var(--kebab-bg1)] text-[gray] whitespace-nowrap">未設定</span>
                </div>
                <div class="text-[13.5px] text-[gray] mt-[3px] overflow-hidden text-ellipsis whitespace-nowrap">{{ companyLine }}</div>
            </div>
        </div>
    </template>
    <template #menu>
        <div class="ml-auto flex gap-2">
            <CommandButton
                v-if="actionTypes.viewer"
                :buttons="[{ title: 'フォロー', action: () => follow() }]"
            />
            <CommandButton
                v-if="actionTypes.follower"
                :buttons="[{ title: 'フォロー中', action: () => unfollow() }]"
            />
            <ItemMenu v-if="actionTypes.owner" :items="[
                { title: '編集', action: () => emit('edit', contact) },
                { title: '削除', action: () => emit('delete', Number(contact.id)) }
            ]"/>
        </div>
    </template>
    <template #content>
      <div :class="activeTab === 'comment' ? 'flex flex-col h-full min-h-0' : ''">
        <!-- Tab bar -->
        <div :class="['flex gap-[2px] border-b border-[var(--panel-separate)] overflow-x-auto overflow-y-hidden', activeTab === 'comment' ? 'flex-none mb-[16px]' : 'sticky top-0 z-[2] bg-[var(--background-color)] mb-[22px]']">
            <button
                v-for="t in tabs"
                :key="t.key"
                @click="activeTab = t.key"
                :class="[
                    'py-[13px] px-[4px] mx-[10px] bg-transparent border-none whitespace-nowrap cursor-pointer text-[13.5px] transition-colors -mb-px border-b-2',
                    activeTab === t.key ? 'border-[var(--primary-color)] text-[var(--primary-color)] font-bold' : 'border-transparent text-[gray] font-medium'
                ]"
            >
                <span class="flex items-center gap-[6px]">
                    {{ t.label }}
                    <span v-if="t.key === 'comment' && badge.contactBadge.some(c => c.contact_id === contact.id)" class="side-notification" style="position: static; width: 14px; height: 14px; min-width: 14px;">
                        {{ badge.contactBadge.find(c => c.contact_id === contact.id)?.comments }}
                    </span>
                    <svg v-if="t.key === 'company' && enrichmentPending" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" class="text-[gray]">
                        <path d="M12 3a9 9 0 1 0 9 9"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></path>
                    </svg>
                </span>
            </button>
        </div>

        <!-- 表面情報 -->
        <div v-if="activeTab === 'front'" class="flex gap-[22px] flex-wrap">
            <div class="flex-1 min-w-[300px]">
                <div class="border border-[var(--normalBorder)] overflow-hidden">
                    <div
                        v-for="(f, i) in fields"
                        :key="f.label"
                        class="grid"
                        style="grid-template-columns: 150px 1fr;"
                        :class="i !== 0 ? 'border-t border-[var(--normalBorder)]' : ''"
                    >
                        <div class="py-[12px] px-[15px] text-[13px] text-[gray] bg-[var(--bg3)] border-r border-[var(--normalBorder)]">{{ f.label }}</div>
                        <div class="py-[12px] px-[15px] text-[13.5px] text-[var(--primary-color)] leading-[1.5] break-words">{{ f.value }}</div>
                    </div>
                </div>
            </div>
            <div class="flex-none w-[280px] max-w-full">
                <div class="text-[12px] text-[gray] mb-[8px]">名刺（表面）</div>
                <img
                    v-if="contact.card_path && !cardBroken"
                    :src="`/cdn/${contact.card_path}`"
                    loading="lazy"
                    @error="cardBroken = true"
                    class="w-full max-w-[280px] max-h-[240px] object-contain border border-[var(--formBorder)] bg-[var(--bg2)]"
                />
                <div v-else class="w-[280px] max-w-full aspect-[91/55] border border-[var(--formBorder)] bg-[var(--bg2)] flex items-center justify-center text-[gray]">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="8.5" cy="9.5" r="1.5"/></svg>
                </div>
                <div class="flex items-center gap-[6px] mt-[12px] text-[gray] text-[12px]">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                    登録日時 {{ fmtDateTime(contact.created_at) }}
                </div>
            </div>
        </div>

        <!-- 裏面・メモ -->
        <div v-else-if="activeTab === 'back'" class="flex flex-col gap-[24px]">
            <div>
                <div class="flex items-center justify-between mb-[11px]">
                    <span class="text-[13px] font-bold text-[var(--primary-color)]">名刺画像・写真</span>
                    <button v-if="canManage" @click="pickPhotos" :disabled="uploading" class="inline-flex items-center gap-[5px] h-[28px] px-[10px] text-[12px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-solid border-[var(--normalBorder)] cursor-pointer disabled:opacity-50">
                        <AddIcon width="10" height="10"/>
                        写真を追加
                    </button>
                </div>
                <div class="flex gap-[12px] flex-wrap">
                    <!-- 表面 (the scanned card) -->
                    <div class="w-[200px] aspect-[91/55] border border-[var(--formBorder)] bg-[var(--bg2)] flex items-center justify-center relative overflow-hidden">
                        <span class="absolute top-[8px] left-[10px] text-[9px] text-[gray] border border-[var(--formBorder)] px-[6px] py-px z-[1]">表面</span>
                        <img v-if="contact.card_path && !cardBroken" :src="`/cdn/${contact.card_path}`" loading="lazy" @error="cardBroken = true" @click="zoomSrc = `/cdn/${contact.card_path}`" class="w-full h-full object-cover cursor-zoom-in"/>
                        <svg v-else viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="gray" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 16l5-5 4 4 3-3 6 6"/><circle cx="8.5" cy="9.5" r="1.5"/></svg>
                    </div>
                    <!-- extra uploaded photos -->
                    <div v-for="f in photoFiles" :key="f.id" class="w-[200px] aspect-[91/55] border border-[var(--formBorder)] bg-[var(--bg2)] relative overflow-hidden">
                        <img :src="fileUrl(f)" loading="lazy" @click="zoomSrc = fileUrl(f)" class="w-full h-full object-cover cursor-zoom-in"/>
                        <button v-if="canManage" @click="scanPhoto(f)" :disabled="scanningId !== null" title="この名刺から読み取ってフォームに反映" class="absolute top-[6px] left-[6px] h-[22px] px-[6px] inline-flex items-center gap-[3px] bg-[#000000a6] text-white text-[10px] cursor-pointer border-none disabled:opacity-60">
                            <svg v-if="scanningId !== f.id" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M20 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2M4 12h16"/></svg>
                            <svg v-else viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></path></svg>
                            {{ scanningId === f.id ? '読取中' : '読み取り' }}
                        </button>
                        <button v-if="canManage" @click="deleteFile(f)" title="削除" class="absolute top-[6px] right-[6px] w-[22px] h-[22px] flex items-center justify-center bg-[#000000a6] text-white cursor-pointer border-none">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                    <!-- add tile -->
                    <button
                        v-if="canManage"
                        @click="pickPhotos"
                        :disabled="uploading"
                        class="w-[200px] aspect-[91/55] bg-transparent border-[1.5px] border-dashed border-[var(--formBorder)] flex flex-col items-center justify-center gap-[7px] text-[gray] cursor-pointer text-[12px] transition-colors hover:text-[var(--primary-color)] disabled:opacity-50"
                    >
                        <svg v-if="!uploading" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></path></svg>
                        {{ uploading ? 'アップロード中…' : '写真を追加（裏面・複数可）' }}
                    </button>
                </div>
            </div>
            <div>
                <div class="text-[13px] font-bold text-[var(--primary-color)] mb-[11px]">添付ファイル</div>
                <div v-if="attachmentFiles.length" class="flex flex-col gap-[8px] mb-[10px]">
                    <div v-for="f in attachmentFiles" :key="f.id" class="flex items-center gap-[11px] bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] py-[10px] px-[12px]">
                        <button @click="previewAttachment(f)" class="flex items-center gap-[11px] flex-1 min-w-0 text-left bg-transparent border-none cursor-pointer p-0">
                            <FileIcon :ext="f.extension"/>
                            <div class="flex-1 min-w-0">
                                <div class="text-[13px] text-[var(--primary-color)] overflow-hidden text-ellipsis whitespace-nowrap">{{ f.name }}</div>
                                <div class="text-[11px] text-[gray] mt-px">{{ fileSize(f.size) }}</div>
                            </div>
                        </button>
                        <button v-if="canManage" @click="deleteFile(f)" title="削除" class="w-[26px] h-[26px] flex-none flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)] bg-transparent border-none cursor-pointer">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>
                </div>
                <button
                    v-if="canManage"
                    @click="pickFiles"
                    :disabled="uploading"
                    class="w-full flex items-center justify-center gap-[7px] border-[1.5px] border-dashed border-[var(--formBorder)] bg-transparent p-[20px] text-[gray] cursor-pointer text-[13px] transition-colors hover:text-[var(--primary-color)] disabled:opacity-50"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M5 20h14"/></svg>
                    ドキュメント・添付ファイルをアップロード
                </button>
                <div v-else-if="!attachmentFiles.length" class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[20px] text-center text-[gray] text-[13px]">添付ファイルはありません</div>
            </div>

            <input ref="photoInput" type="file" accept="image/*" multiple class="hidden" @change="onFilesPicked($event, 'image')"/>
            <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesPicked($event, 'file')"/>
        </div>

        <!-- 関連 -->
        <div v-else-if="activeTab === 'rel'" class="flex flex-col gap-[24px]">
            <!-- 関連プロジェクト -->
            <div>
                <div class="flex items-center justify-between mb-[11px]">
                    <span class="text-[13px] font-bold text-[var(--primary-color)]">関連プロジェクト</span>
                    <button @click="projectPickerOpen = !projectPickerOpen" class="inline-flex items-center gap-[5px] h-[28px] px-[10px] text-[12px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-solid border-[var(--normalBorder)] cursor-pointer">
                        <AddIcon width="10" height="10" />
                        追加
                    </button>
                </div>
                <div v-if="projectPickerOpen" class="mb-[10px]">
                    <input
                        v-model="projectQuery"
                        @input="searchProjects"
                        placeholder="プロジェクト名で検索"
                        class="!box-border w-full h-[38px] px-[12px] bg-[var(--inactive-background)] border border-solid border-[var(--normalBorder)] text-[var(--primary-color)] text-[13px] outline-none"
                    />
                    <div v-if="projectResults.length" class="mt-[6px] max-h-[200px] overflow-y-auto border border-solid border-[var(--normalBorder)] bg-[var(--menu-bg)]">
                        <button v-for="p in projectResults" :key="p.id" @click="linkProject(p)" class="!box-border flex items-center w-full text-left px-[12px] py-[9px] text-[13px] text-[var(--primary-color)] hover:bg-[var(--selected-background)]">{{ p.name }}</button>
                    </div>
                    <div v-else class="mt-[6px] text-[12px] text-[gray]">参加しているプロジェクトから選べます。該当がありません。</div>
                </div>
                <div v-if="(contact.projects ?? []).length" class="flex flex-col gap-[8px]">
                    <div v-for="p in contact.projects" :key="p.id" class="flex items-center gap-[11px] bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] py-[11px] px-[14px]">
                        <span class="w-[30px] h-[30px] flex-none bg-[var(--kebab-bg1)] text-[gray] flex items-center justify-center">
                            <Project width="15" height="15"/>
                        </span>
                        <span class="flex-1 text-[13.5px] text-[var(--primary-color)] min-w-0 overflow-hidden text-ellipsis whitespace-nowrap">{{ p.name }}</span>
                        <button @click="unlinkProject(p)" title="解除" class="w-[26px] h-[26px] flex-none flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)]">
                            <CloseIcon width="8" height="8"/>
                        </button>
                    </div>
                </div>
                <div v-else class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[18px] text-center text-[gray] text-[13px]">紐付くプロジェクトはありません</div>
            </div>

            <!-- 関連コンタクト -->
            <div>
                <div class="flex items-center justify-between mb-[11px]">
                    <span class="text-[13px] font-bold text-[var(--primary-color)]">関連コンタクト</span>
                    <button @click="contactPickerOpen = !contactPickerOpen" class="inline-flex items-center gap-[5px] h-[28px] px-[10px] text-[12px] bg-[var(--kebab-bg1)] text-[var(--primary-color)] border border-solid border-[var(--normalBorder)] cursor-pointer">
                        <AddIcon width="10" height="10" />
                        追加
                    </button>
                </div>
                <div v-if="contactPickerOpen" class="mb-[10px]">
                    <input
                        v-model="contactQuery"
                        placeholder="氏名・会社・部署で検索"
                        class="!box-border w-full h-[38px] px-[12px] bg-[var(--inactive-background)] border border-solid border-[var(--normalBorder)] text-[var(--primary-color)] text-[13px] outline-none"
                    />
                    <div v-if="contactSuggestions.length" class="mt-[6px] max-h-[240px] overflow-y-auto border border-solid border-[var(--normalBorder)] bg-[var(--menu-bg)]">
                        <button v-for="c in contactSuggestions" :key="c.id ?? c.name" @click="linkContact(c)" class="!box-border flex items-center gap-[10px] w-full text-left px-[12px] py-[8px] hover:bg-[var(--selected-background)]">
                            <ContactIcon :contact="c" size="26"/>
                            <span class="flex-1 min-w-0 overflow-hidden text-ellipsis whitespace-nowrap"><span class="text-[13px] text-[var(--primary-color)]">{{ c.name }}</span><span class="text-[11.5px] text-[gray] ml-[6px]">{{ c.company_name }}</span></span>
                        </button>
                    </div>
                    <div v-else class="mt-[6px] text-[12px] text-[gray]">該当するコンタクトがありません</div>
                </div>
                <div v-if="(contact.related_contacts ?? []).length" class="flex flex-col gap-[8px]">
                    <div v-for="r in contact.related_contacts" :key="r.id ?? r.name" class="flex items-center gap-[12px] bg-[var(--message-background)] border border-solid border-[var(--normalBorder)] py-[11px] px-[14px]">
                        <button @click="openRelated(r)" class="flex items-center gap-[12px] flex-1 min-w-0 text-left bg-transparent border-none cursor-pointer p-0">
                            <ContactIcon :contact="r" size="26"/>
                            <div class="flex-1 min-w-0">
                                <div class="text-[13.5px] font-medium text-[var(--primary-color)] overflow-hidden text-ellipsis whitespace-nowrap">{{ r.name }}</div>
                                <div class="text-[12px] text-[gray] mt-px overflow-hidden text-ellipsis whitespace-nowrap">{{ [r.company_name, r.department].filter(Boolean).join(' ・ ') }}</div>
                            </div>
                        </button>
                        <button @click="unlinkContact(r)" title="解除" class="w-[26px] h-[26px] flex-none flex items-center justify-center text-[gray] hover:bg-[var(--soft-bg)] hover:text-[var(--primary-color)]">
                            <CloseIcon width="8" height="8"/>
                        </button>
                    </div>
                </div>
                <div v-else class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[18px] text-center text-[gray] text-[13px]">関連コンタクトは登録されていません</div>
            </div>
        </div>

        <!-- 履歴 -->
        <div v-else-if="activeTab === 'hist'">
            <div v-if="historiesLoading" class="text-xs text-[gray] text-center py-6">読み込み中…</div>
            <div v-else-if="histories.length" class="flex flex-col">
                <div v-for="(h, i) in histories" :key="h.id" class="flex gap-[12px]">
                    <div class="flex flex-col items-center flex-none">
                        <span class="w-[9px] h-[9px] rounded-full mt-[5px]" :class="h.event === 'created' ? 'bg-green-500' : 'bg-[var(--primary-color)]'"></span>
                        <span v-if="i < histories.length - 1" class="w-px flex-1 bg-[var(--normalBorder)] my-[2px]"></span>
                    </div>
                    <div class="pb-[18px] min-w-0 flex-1">
                        <div class="text-[13px] text-[var(--primary-color)]">
                            <template v-if="h.event === 'created'">コンタクトを登録しました</template>
                            <template v-else><span class="font-medium">{{ historyFieldLabel(h.field) }}</span>を変更しました</template>
                        </div>
                        <div v-if="h.event === 'updated'" class="text-[12px] mt-[4px] leading-[1.6] break-words">
                            <span class="text-[gray] line-through">{{ h.old_value || '（空）' }}</span>
                            <span class="mx-[6px] text-[gray]">→</span>
                            <span class="text-[var(--primary-color)]">{{ h.new_value || '（空）' }}</span>
                        </div>
                        <div class="text-[11px] text-[gray] mt-[4px]">{{ h.user?.name || '—' }} ・ {{ fmtDateTime(h.created_at) }}</div>
                    </div>
                </div>
            </div>
            <div v-else class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[24px] text-center text-[gray] text-[13px]">変更履歴はまだ記録されていません</div>
        </div>

        <!-- 企業情報 -->
        <div v-else-if="activeTab === 'company'">
            <div v-if="contact.data" class="contact-company-info text-[13.5px] text-[var(--primary-color)] leading-[1.7]" v-html="contact.data"></div>
            <div v-else-if="enrichmentPending" class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[28px] text-center text-[var(--primary-color)]">
                <svg class="mx-auto mb-[10px]" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M12 3a9 9 0 1 0 9 9"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></path>
                </svg>
                <div class="text-[13px] mb-[6px] font-bold">企業情報を取得しています…</div>
                <div class="text-[12px] text-[gray]">会社名・URLをもとにバックグラウンドで取得中です。完了すると自動で反映されます。</div>
            </div>
            <div v-else-if="enrichmentFailed" class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[28px] text-center text-[gray]">
                <div class="text-[13px] mb-[6px]">企業情報を取得できませんでした</div>
                <div class="text-[12px]">会社名やURLが不足しているか、検索で情報が見つかりませんでした。</div>
            </div>
            <div v-else class="border-[1.5px] border-dashed border-[var(--formBorder)] p-[28px] text-center text-[gray]">
                <div class="text-[13px] mb-[6px]">企業情報はまだ取得されていません</div>
                <div class="text-[12px]">名刺の一括取り込み時に、会社名・URLから自動で取得されます。</div>
            </div>
        </div>

        <!-- 公開コメント -->
        <div v-else-if="activeTab === 'comment' && canComment" class="flex-1 min-h-0">
            <ContactComment :item="contact" @refresh="emit('closeCreate', true)"/>
        </div>
      </div>

      <div v-if="zoomSrc" @click="zoomSrc = ''" class="fixed top-0 left-0 w-full h-full z-[9999] bg-[#000000d9] flex items-center justify-center p-[24px] cursor-zoom-out">
          <img :src="zoomSrc" class="max-w-full max-h-full object-contain"/>
      </div>
    </template>
</Modal>
</template>
<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import Modal from '@/components/Global/Modal.vue';
import ContactIcon from './ContactIcon.vue';
import { ContactFile, ContactHistory, ContactProject, ContactRecord } from '@/interface/contactInterface';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import { computed, ref, watch } from 'vue';
import ContactComment from './Comment/ContactComment.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import FileIcon from '@/components/Board/Mixed/FileIcon.vue';
import Project from '@/components/Icons/Project.vue';
import CloseIcon from '@/components/Form/CloseIcon.vue';
import { filesize } from 'filesize';
import { useAuthUserStore } from '@/store/auth';
import { useApi } from '@/composables/api';
import { useBadgeStore } from '@/store/badge';
import { useDialog } from '@/composables/dialog';
import { useFilePreview } from '@/store/filePreview';
import AddIcon from '@/components/Form/AddIcon.vue';

const router = useRouter()
const props = defineProps<{
    contact: ContactRecord
    related?: ContactRecord[]
}>()
const emit = defineEmits<{
    edit: [data: ContactRecord]
    delete: [id: number]
    closeCreate: [flag: boolean]
}>()
const badge = useBadgeStore()
const auth = useAuthUserStore()
const dialog = useDialog()
const api = useApi()

const actionTypes = computed(() => {
    const me = auth.activeUser?.id
    const collabs = props.contact?.collaborators ?? []
    const mine = collabs.find(c => c.id === me)
    const role = mine?.pivot?.role ?? null // 'owner' | 'follower' | null
    const owner = role === 'owner'
    const follower = role === 'follower'
    const viewer = !owner && !follower
    return { owner, follower, viewer }
})

const canComment = computed(() => actionTypes.value.follower || actionTypes.value.owner)

const filePreview = useFilePreview()

// File management (裏面 photos + attachments): owner/follower only, viewers read-only.
const canManage = canComment
const uploading = ref(false)
const photoInput = ref<HTMLInputElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const zoomSrc = ref('')

const photoFiles = computed(() => (props.contact.files ?? []).filter(f => f.contact_file_kind === 'image'))
const attachmentFiles = computed(() => (props.contact.files ?? []).filter(f => f.contact_file_kind === 'file'))

const fileUrl = (f: ContactFile) => `/cdn/contact_files/${f.id}_${f.user_id}.${f.extension}`
const fileSize = (bytes: number) => filesize(bytes ?? 0, { standard: 'jedec', round: (bytes ?? 0) > 1000000 ? 1 : 0 })

const pickPhotos = () => photoInput.value?.click()
const pickFiles = () => fileInput.value?.click()

const onFilesPicked = async (e: Event, kind: 'image' | 'file') => {
    const input = e.target as HTMLInputElement
    const picked = input.files
    if (!picked || !picked.length || !props.contact.id) { if (input) input.value = ''; return }
    uploading.value = true
    try {
        const form = new FormData()
        Array.from(picked).forEach((f, i) => form.append(String(i), f))
        const temp = await api.post('/attach_upload_api', form)
        if (temp && temp.length) {
            await api.post('/contact_attach_files', {
                record_id: props.contact.id,
                kind,
                attached_temp_files: temp,
            }, { toast: kind === 'image' ? '写真を追加しました。' : 'ファイルを追加しました。' })
            emit('closeCreate', true)
        }
    } finally {
        uploading.value = false
        input.value = ''
    }
}

const deleteFile = async (f: ContactFile) => {
    await api.post('/contact_file_delete', { id: f.id }, { ask: '削除しますか？', toast: '削除しました。' })
    emit('closeCreate', true)
}

// OCR a stored photo, merge the read fields onto the contact, and open the edit
// form so the user reviews before saving (saving is what records 履歴).
const scanningId = ref<number | null>(null)
const OCR_FIELDS = ['company_name', 'name', 'position', 'department', 'address', 'phone', 'email', 'fax', 'url'] as const
const scanPhoto = async (f: ContactFile) => {
    if (scanningId.value !== null) return
    scanningId.value = f.id
    let res: any = null
    try {
        res = await api.post('/contact_scan_file', { file_id: f.id })
    } finally {
        scanningId.value = null
    }
    const d = res?.data
    if (!d) return
    const merged: ContactRecord = { ...props.contact }
    let changed = 0
    for (const k of OCR_FIELDS) {
        const v = d[k]
        if (v != null && String(v).trim() !== '' && String(v) !== String((props.contact as any)[k] ?? '')) {
            ;(merged as any)[k] = v
            changed++
        }
    }
    if (!changed) {
        dialog.ping('この画像から新しい情報は読み取れませんでした。')
        return
    }
    dialog.toast('名刺を読み取りました。内容を確認して保存してください。')
    emit('edit', merged)
}

const previewAttachment = (f: ContactFile) => {
    const target: Record<string, any> = {
        ...f,
        file_path: fileUrl(f),
        doc_path: `/contact_files/${f.id}_${f.user_id}.${f.extension}`,
    }
    filePreview.setFilePreview({ active: true, files: [target], source: 'message', index: 0, message: null })
}

const HISTORY_FIELD_LABELS: Record<string, string> = {
    name: '氏名', company_name: '会社名', department: '部署', position: '役職',
    address: '住所', phone: '電話番号', email: 'メールアドレス', fax: 'FAX', url: 'URL',
    description: 'メモ', types: 'コンタクト種類',
}
const historyFieldLabel = (f: string | null) => (f ? (HISTORY_FIELD_LABELS[f] ?? f) : '情報')

const tabs = computed(() => {
    const base = [
        { key: 'front', label: '表面情報' },
        { key: 'back', label: '裏面' },
        { key: 'rel', label: '関連' },
        { key: 'hist', label: '履歴' },
        { key: 'company', label: '企業情報' },
    ]
    if (canComment.value) {
        base.push({ key: 'comment', label: '公開コメント' })
    }
    return base
})

const companyLine = computed(() =>
    [props.contact.company_name, props.contact.department].filter(Boolean).join(' ・ ') || '—'
)

// Background company enrichment (create flow) reports progress via enrichment_status.
const enrichmentPending = computed(() => !props.contact.data && props.contact.enrichment_status === 'pending')
const enrichmentFailed = computed(() => !props.contact.data && props.contact.enrichment_status === 'failed')

const fields = computed(() => [
    { label: 'コンタクト種類', value: (props.contact.types ?? []).map(t => t.title).join('、') || '未設定' },
    { label: '氏名', value: props.contact.name || '—' },
    { label: '会社名', value: props.contact.company_name || '—' },
    { label: '部署', value: props.contact.department || '—' },
    { label: '役職', value: props.contact.position || '—' },
    { label: '住所', value: props.contact.address || '—' },
    { label: 'メールアドレス', value: props.contact.email || '—' },
    { label: '電話番号', value: props.contact.phone || '—' },
    { label: 'FAX', value: props.contact.fax || '—' },
    { label: '関係者', value: (props.contact.collaborators ?? []).map(c => c.name).filter(Boolean).join('、') || '—' },
    { label: '最終更新者', value: props.contact.updater?.name || '—' },
])

const fmtDateTime = (value: string) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    const pad = (n: number) => (n < 10 ? '0' + n : '' + n)
    return `${date.getFullYear()}/${pad(date.getMonth() + 1)}/${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const openRelated = (r: ContactRecord) => {
    if (!r.id) return
    router.push({ name: 'contactDetail', params: { contactId: r.id } })
}

// --- Relationship linking (projects + related contacts) ---
const linking = ref(false)
const refresh = () => emit('closeCreate', true)

const projectPickerOpen = ref(false)
const projectQuery = ref('')
const projectResults = ref<ContactProject[]>([])

const searchProjects = async () => {
    const data = await api.get('/contact_project_search', { q: projectQuery.value.trim() }, { silent: true })
    projectResults.value = Array.isArray(data) ? data : []
}
watch(projectPickerOpen, (open) => { if (open) { projectQuery.value = ''; searchProjects() } })

const linkProject = async (p: ContactProject) => {
    if (linking.value || !props.contact.id) return
    await api.post('/contact_link_project', { contact_id: props.contact.id, project_id: p.id }, {
        toast: 'プロジェクトを関連付けました。', loadingRef: linking,
    })
    projectPickerOpen.value = false
    refresh()
}
const unlinkProject = async (p: ContactProject) => {
    if (!props.contact.id) return
    await api.del('/contact_link_project', { contact_id: props.contact.id, project_id: p.id }, {
        ask: '関連付けを解除しますか？', toast: '解除しました。',
    })
    refresh()
}

const contactPickerOpen = ref(false)
const contactQuery = ref('')
const contactSuggestions = computed(() => {
    const q = contactQuery.value.trim().toLowerCase()
    const linkedIds = new Set((props.contact.related_contacts ?? []).map(c => c.id))
    const pool = (props.related ?? []).filter(c => c.id !== props.contact.id && !linkedIds.has(c.id))
    const company = (props.contact.company_name || '').trim()
    const base = q
        ? pool.filter(c => [c.name, c.company_name, c.department].some(v => String(v ?? '').toLowerCase().includes(q)))
        : pool.filter(c => company && (c.company_name || '').trim() === company)
    return base.slice(0, 20)
})
const linkContact = async (c: ContactRecord) => {
    if (linking.value || !props.contact.id || !c.id) return
    await api.post('/contact_link_related', { contact_id: props.contact.id, related_id: c.id }, {
        toast: '関連コンタクトを追加しました。', loadingRef: linking,
    })
    contactPickerOpen.value = false
    contactQuery.value = ''
    refresh()
}
const unlinkContact = async (c: ContactRecord) => {
    if (!props.contact.id || !c.id) return
    await api.del('/contact_link_related', { contact_id: props.contact.id, related_id: c.id }, {
        ask: '関連付けを解除しますか？', toast: '解除しました。',
    })
    refresh()
}

// Fall back to the placeholder if the card image fails to load; reset per contact.
const cardBroken = ref(false)

// 履歴 is loaded on demand (not shipped with the list) when the tab is opened.
const histories = ref<ContactHistory[]>([])
const historiesLoading = ref(false)
const loadHistories = async () => {
    if (!props.contact.id) return
    historiesLoading.value = true
    try {
        const data = await api.get(`/contact_histories/${props.contact.id}`, null, { silent: true })
        histories.value = Array.isArray(data) ? data : []
    } finally {
        historiesLoading.value = false
    }
}

const activeTab = ref(useRoute().query.mention && canComment.value ? 'comment' : 'front')
watch(activeTab, (t) => { if (t === 'hist') loadHistories() })
watch(() => props.contact?.id, () => {
    cardBroken.value = false
    histories.value = []
    if (activeTab.value === 'hist') loadHistories()
})
const follow = async () => {
    const message = 'フォローすると、この連絡先に関する更新通知を受け取れます。\nコメントの投稿や個人メモの保存もできます。\nこのコンタクトをフォローしますか？'
    await api.post('/follow_contact', { record_id: props.contact?.id }, {
        ask: message,
        toast: 'コンタクトをフォローしました。',
    })
    emit('closeCreate', true)
}
const unfollow = async () => {
    const message = 'フォローを解除しますか？\n通知やコメント、個人メモの機能が使えなくなります。'
    await api.del(`/unfollow_contact/${props.contact.id}`, {}, {
        ask: message,
        toast: 'フォローを解除しました。'
    })
    emit('closeCreate', true)
}
</script>
<style scoped>
.contact-company-info :deep(h1),
.contact-company-info :deep(h2),
.contact-company-info :deep(h3) {
    font-weight: 700;
    margin: 16px 0 8px;
}
.contact-company-info :deep(ul),
.contact-company-info :deep(ol) {
    padding-left: 20px;
    list-style: revert;
}
.contact-company-info :deep(a) {
    color: var(--link-color);
    text-decoration: underline;
}
.contact-company-info :deep(img) {
    max-width: 120px;
    height: auto;
}
/* Tailwind preflight is disabled app-wide, so border-width utilities need an
   explicit border-style to render (dashed empty-states already set their own). */
[class~="border"],
[class~="border-2"] { border-style: solid; }
[class~="border-t"] { border-top-style: solid; }
[class~="border-b"],
[class~="border-b-2"] { border-bottom-style: solid; }
[class~="border-r"] { border-right-style: solid; }
/* Global reset forces content-box; keep bordered boxes from overflowing. */
[class*="border"] { box-sizing: border-box !important; }
</style>
