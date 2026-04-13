<template>
    <Modal size="large" @close="emit('close')">
        <template #title>
            <p>カテゴリ管理</p>
        </template>

        <template #content>
            <div v-show="view === 'list'" class=" bg-[var(--background-color)]">
                <div class="p-3 flex items-center gap-2">
                    <div v-if="loading" class="ml-auto text-[12px] text-[gray]">読み込み中...</div>
                </div>
                <div class="space-y-5">
                    <div v-if="!items.length && !loading" class="text-[gray] p-3">項目がありません</div>

                    <div ref="itemsSortParent" class="space-y-2">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="sortable-item text-left p-3 hover:bg-[var(--bg3)] flex items-center gap-2 border border-solid border-[var(--formBorder)] rounded cursor-pointer"
                            :class="{ 'bg-[var(--bg3)]': item.id === activeItemId }"
                            @click="selectItem(item)"
                        >
                            <div
                                class="item-handler flex items-center justify-center gap-[2px] w-[30px] h-[30px] cursor-grab"
                                title="並び替え"
                                @click.stop
                            >
                                <svg fill="gray" version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" style="min-width: 3px;">
                                    <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                    <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                    <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                </svg>
                                <svg fill="gray" version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" style="min-width: 3px;">
                                    <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                    <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                    <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0 space-y-2 leading-normal">
                                <div class="text-[13px] truncate">{{ item.title }}</div>
                                <div class="text-[12px] text-[gray]">フィールド {{ item.fields.length }}</div>
                            </div>

                            <ItemMenu :items="itemMenuItems(item)" />
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-[var(--bg3)] mt-4">
                    <div v-if="creating" class="flex items-center gap-2">
                        <input
                            v-model="newItemTitle"
                            type="text"
                            placeholder="種類名（例: ノートPC / Googleアカウント）"
                            class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded flex-1"
                        />
                        <button
                            class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                            @click="createItem"
                        >
                            追加
                        </button>
                        <button
                            class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                            @click="cancelCreate"
                        >
                            キャンセル
                        </button>
                    </div>
                    <div
                        v-else
                        class="cursor-pointer flex items-center justify-center gap-2 px-3 py-2 bg-[var(--background-color)] text-[var(--primary-color)]"
                        @click="startCreate"
                    >
                        <AddIcon size="14" fill="currentColor" />
                        追加
                    </div>
                </div>
            </div>

            <!-- EDIT VIEW -->
            <div v-show="view === 'edit'" class="border border-solid border-[var(--formBorder)] rounded bg-[var(--background-color)]">
                <div class="p-3 bg-[var(--bg3)] flex items-center gap-2">
                    <button
                        type="button"
                        class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                        @click="backToList"
                    >
                        戻る
                    </button>
                    <div class="ml-auto" v-if="activeItem">
                        <ItemMenu :items="editMenuItems" />
                    </div>
                </div>

                <div class="p-3">
                    <div v-if="!activeItem">
                        <div class="text-[12px] text-[gray]">種類が選択されていません。</div>
                    </div>

                    <template v-else>
                        <div class="flex items-center gap-2 flex-wrap mb-4">
                            <input
                                v-model="activeItem.title"
                                type="text"
                                placeholder="種類名"
                                class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded flex-1 min-w-[240px]"
                            />
                            <button
                                class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                                @click="updateItem(activeItem)"
                            >
                                保存
                            </button>
                        </div>

                        <div class="text-[12px] text-[gray] mb-2">フィールド</div>
                        <div v-if="!fieldList.length" class="text-[12px] text-[gray]">フィールドがありません</div>
                    </template>

                    <!-- Keep this element always mounted so Sortable can bind once -->
                    <div ref="fieldsSortParent">
                        <template v-if="activeItem">
                            <div v-for="field in fieldList" :key="field.id" class="sortable-field flex items-center gap-2 flex-wrap mb-2">
                                <div
                                    class="field-handler flex items-center justify-center gap-[2px] w-[30px] h-[30px] cursor-grab"
                                    title="並び替え"
                                    @click.stop
                                >
                                    <svg fill="gray" version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                    <svg fill="gray" version="1.1" xmlns="http://www.w3.org/2000/svg" height="13" viewBox="0 0 7 32" style="min-width: 3px;">
                                        <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path>
                                        <path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path>
                                        <path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
                                    </svg>
                                </div>
                                <input
                                    v-model="field.label"
                                    type="text"
                                    placeholder="ラベル"
                                    class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded w-[160px]"
                                />

                                <select
                                    v-model="field.input_type"
                                    class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded"
                                >
                                    <option value="shorttext">短文</option>
                                    <option value="longtext">長文</option>
                                    <option value="password">パスワード</option>
                                </select>

                                <input
                                    v-model="field.placeholder"
                                    type="text"
                                    placeholder="プレースホルダー"
                                    class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded flex-1"
                                />

                                <label class="flex items-center gap-2 text-[13px] text-[gray]">
                                    <input type="checkbox" v-model="field.required" />
                                    必須
                                </label>

                                <button
                                    class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                                    @click="updateField(activeItem, field)"
                                >
                                    保存
                                </button>

                                <button
                                    class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                                    @click="deleteField(field)"
                                >
                                    削除
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="mt-3 bg-[var(--bg3)] p-3" v-if="activeItem">
                        <div class="flex items-center gap-2 flex-wrap">
                            <input
                                v-model="fieldDraft(activeItem.id).label"
                                type="text"
                                placeholder="ラベル"
                                class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded w-[160px]"
                            />

                            <select
                                v-model="fieldDraft(activeItem.id).input_type"
                                class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded"
                            >
                                <option value="shorttext">短文</option>
                                <option value="longtext">長文</option>
                                <option value="password">パスワード</option>
                            </select>

                            <input
                                v-model="fieldDraft(activeItem.id).placeholder"
                                type="text"
                                placeholder="プレースホルダー"
                                class="p-2 border border-solid border-[var(--formBorder)] bg-[var(--background-color)] text-[var(--primary-color)] rounded flex-1"
                            />

                            <label class="flex items-center gap-2 text-[13px] text-[gray]">
                                <input type="checkbox" v-model="fieldDraft(activeItem.id).required" />
                                必須
                            </label>

                            <button
                                class="px-3 py-2 rounded bg-[var(--background-color)] text-[var(--primary-color)] border border-solid border-[var(--formBorder)]"
                                @click="createField(activeItem)"
                            >
                                フィールド追加
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Modal>
</template>

<script setup lang="ts">
import { nextTick, reactive, ref, useTemplateRef, watch } from 'vue'
import Modal from '../Global/Modal.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import ItemMenu from '@/components/Global/ItemMenu.vue'
import AddIcon from '@/components/Form/AddIcon.vue'
import type { MenuList } from '@/interface/globalInterface'
import { moveArrayElement, useSortable } from '@vueuse/integrations/useSortable'

type ApiAssetCategoryItemField = {
    id: number
    asset_category_item_id: number
    label: string | null
    input_type: 'shorttext' | 'longtext' | 'password'
    placeholder: string | null
    rules: string | null
}

type ApiAssetCategoryItem = {
    id: number
    type?: 'asset' | 'account' | null
    title: string
    required_data: string | null
    fields: ApiAssetCategoryItemField[]
}

type UiField = {
    id: number
    asset_category_item_id: number
    label: string
    input_type: 'shorttext' | 'longtext' | 'password'
    placeholder: string
    required: boolean
}

type UiItem = {
    id: number
    title: string
    fields: UiField[]
}

const emit = defineEmits<{
    close: []
    updated: [items: ApiAssetCategoryItem[]]
}>()

const api = useApi()
const { ask, ping } = useDialog()

const loading = ref(false)
const items = ref<UiItem[]>([])
const newItemTitle = ref('')

const itemsSortParent = useTemplateRef<HTMLElement | null>('itemsSortParent')
const fieldsSortParent = useTemplateRef<HTMLElement | null>('fieldsSortParent')

const activeItemId = ref<number | null>(null)
const creating = ref(false)
const view = ref<'list' | 'edit'>('list')

const isRequired = (rules: string | null) => !!rules && rules.includes('required')

const fieldDrafts = reactive<
    Record<
        number,
        {
            label: string
            input_type: UiField['input_type']
            placeholder: string
            required: boolean
        }
    >
>({})

const fieldDraft = (itemId: number) => {
    if (!fieldDrafts[itemId]) {
        fieldDrafts[itemId] = {
            label: '',
            input_type: 'shorttext',
            placeholder: '',
            required: false,
        }
    }
    return fieldDrafts[itemId]
}

const toUiItems = (data: ApiAssetCategoryItem[]): UiItem[] => {
    return (data ?? []).map(item => {
        const uiFields: UiField[] = (item.fields ?? []).map(field => ({
            id: field.id,
            asset_category_item_id: field.asset_category_item_id,
            label: field.label ?? '',
            input_type: field.input_type,
            placeholder: field.placeholder ?? '',
            required: isRequired(field.rules),
        }))

        return {
            id: item.id,
            title: item.title,
            fields: uiFields,
        }
    })
}

const activeItem = ref<UiItem | null>(null)
const fieldList = ref<UiField[]>([])

const syncActiveItem = () => {
    if (activeItemId.value === null) {
        activeItem.value = null
        fieldList.value = []
        return
    }
    activeItem.value = items.value.find(i => i.id === activeItemId.value) ?? null
    fieldList.value = activeItem.value?.fields ?? []
}

const fetchItems = async () => {
    loading.value = true
    const data = (await api.get('/get_asset_category_items')) as ApiAssetCategoryItem[]

    items.value = toUiItems(data ?? [])
    emit('updated', data ?? [])
    syncActiveItem()
    loading.value = false
}

const persistItemOrder = async () => {
    if (!items.value.length) return
    await api.post('/reorder_asset_category_items', {
        ids: items.value.map(i => i.id),
    })
    await fetchItems()
}

const persistFieldOrder = async () => {
    if (!activeItem.value) return
    if (!fieldList.value.length) return

    const ids = fieldList.value
        .filter((f): f is UiField => !!f && typeof (f as UiField).id === 'number')
        .map(f => f.id)

    // If Sortable emitted an inconsistent array (e.g. undefined entry),
    // avoid throwing and just restore from server.
    if (ids.length !== fieldList.value.length) {
        await fetchItems()
        return
    }

    await api.post('/reorder_asset_category_item_fields', {
        asset_category_item_id: activeItem.value.id,
        ids,
    })
    await fetchItems()
}

const selectItem = (item: UiItem) => {
    activeItemId.value = item.id
    creating.value = false
    syncActiveItem()
    view.value = 'edit'
}

const startCreate = () => {
    creating.value = true
    activeItemId.value = null
    syncActiveItem()
    view.value = 'list'
}

const cancelCreate = () => {
    creating.value = false
    newItemTitle.value = ''
}

const backToList = () => {
    view.value = 'list'
}

const sortableItems = useSortable(itemsSortParent, items, {
    animation: 150,
    handle: '.item-handler',
    draggable: '.sortable-item',
    watchElement: true,
    disabled: true,
    onEnd: (e: { oldIndex?: number | null; newIndex?: number | null }) => {
        const oldIndex = typeof e.oldIndex === 'number' ? e.oldIndex : -1
        const newIndex = typeof e.newIndex === 'number' ? e.newIndex : -1
        if (oldIndex < 0 || newIndex < 0) return
        if (oldIndex === newIndex) return
        if (oldIndex >= items.value.length || newIndex >= items.value.length) return

        moveArrayElement(items.value, oldIndex, newIndex, e as any)
        nextTick(() => persistItemOrder())
    },
})

const sortableFields = useSortable(fieldsSortParent, fieldList, {
    animation: 150,
    handle: '.field-handler',
    draggable: '.sortable-field',
    watchElement: true,
    disabled: true,
    onEnd: (e: { oldIndex?: number | null; newIndex?: number | null }) => {
        const oldIndex = typeof e.oldIndex === 'number' ? e.oldIndex : -1
        const newIndex = typeof e.newIndex === 'number' ? e.newIndex : -1
        if (oldIndex < 0 || newIndex < 0) return
        if (oldIndex === newIndex) return
        if (oldIndex >= fieldList.value.length || newIndex >= fieldList.value.length) return

        moveArrayElement(fieldList.value, oldIndex, newIndex, e as any)
        nextTick(() => persistFieldOrder())
    },
})

watch(
    () => ({ view: view.value, loading: loading.value, hasActive: !!activeItem.value }),
    ({ view: currentView, loading: isLoading, hasActive }) => {
        sortableItems.option('disabled', currentView !== 'list' || isLoading)
        sortableFields.option('disabled', currentView !== 'edit' || isLoading || !hasActive)
    },
    { immediate: true }
)

const itemMenuItems = (item: UiItem): MenuList[] => {
    return [
        {
            title: '編集',
            action: () => selectItem(item),
        },
        {
            title: '複製',
            action: () => duplicateItem(item),
        },
        {
            title: '削除',
            action: () => deleteItem(item),
        },
    ]
}

const editMenuItems = [
    {
        title: '複製',
        action: () => {
            if (activeItem.value) duplicateItem(activeItem.value)
        },
    },
    {
        title: '削除',
        action: () => {
            if (activeItem.value) deleteItem(activeItem.value)
        },
    },
] as MenuList[]

const createItem = async () => {
    const title = newItemTitle.value.trim()

    if (!title) {
        ping('種類名を入力してください。')
        return
    }

    await api.post(
        '/create_asset_category_item',
        {
            title,
        },
        { toast: '追加しました' }
    )

    newItemTitle.value = ''
    creating.value = false
    await fetchItems()
}

const updateItem = async (item: UiItem) => {
    if (!item.title.trim()) {
        ping('種類名を入力してください。')
        return
    }

    await api.put(
        '/update_asset_category_item',
        {
            id: item.id,
            title: item.title.trim(),
        },
        { toast: '保存しました' }
    )

    await fetchItems()
}

const deleteItem = async (item: UiItem) => {
    const confirmed = await ask('種類を削除しますか？（中のフィールドも削除されます）')
    if (!confirmed.value) return

    await api.del('/delete_asset_category_item', { id: item.id }, { toast: '削除しました' })
    if (activeItemId.value === item.id) {
        activeItemId.value = null
        syncActiveItem()
        view.value = 'list'
    }
    await fetchItems()
}

const duplicateItem = async (item: UiItem) => {
    await api.post('/duplicate_asset_category_item', { id: item.id }, { toast: '複製しました' })
    await fetchItems()
}

const createField = async (item: UiItem) => {
    const draft = fieldDraft(item.id)

    await api.post(
        '/create_asset_category_item_field',
        {
            asset_category_item_id: item.id,
            label: draft.label.trim() || null,
            input_type: draft.input_type,
            placeholder: draft.placeholder.trim() || null,
            rules: draft.required ? 'required' : null,
        },
        { toast: '追加しました' }
    )

    draft.label = ''
    draft.input_type = 'shorttext'
    draft.placeholder = ''
    draft.required = false

    await fetchItems()
}

const updateField = async (item: UiItem, field: UiField) => {
    await api.put(
        '/update_asset_category_item_field',
        {
            id: field.id,
            asset_category_item_id: item.id,
            label: field.label.trim() || null,
            input_type: field.input_type,
            placeholder: field.placeholder.trim() || null,
            rules: field.required ? 'required' : null,
        },
        { toast: '保存しました' }
    )

    await fetchItems()
}

const deleteField = async (field: UiField) => {
    const confirmed = await ask('フィールドを削除しますか？')
    if (!confirmed.value) return

    await api.del('/delete_asset_category_item_field', { id: field.id }, { toast: '削除しました' })
    await fetchItems()
}

fetchItems()
</script>
