<template>
    <Modal size="medium" :loader="saving" @close="emit('close')">
        <template #title>
            <span>{{ title }}</span>
        </template>
        <template #menu>
            <div v-if="!readonly" class="partner-form__menu">
                <template v-if="isEditing">
                    <button type="button" class="partner-form__button" :disabled="saving" @click="cancelEdit">
                        キャンセル
                    </button>
                    <LoaderButton :content="partner ? '保存する' : '登録する'" :loading="saving" @triggered="save" />
                </template>
                <template v-else-if="partner">
                    <button type="button" class="partner-form__icon-button" title="編集" @click="mode = 'edit'">
                        <Edit size="15" />
                    </button>
                    <button
                        type="button"
                        class="partner-form__icon-button"
                        title="削除"
                        :disabled="saving"
                        @click="removePartner"
                    >
                        <Trash size="15" />
                    </button>
                </template>
            </div>
        </template>
        <template #content>
            <div class="partner-form">
                <div class="partner-form__body">
                    <!-- 入力欄は編集中だけ。閲覧時は読み取り専用の表示に切り替える。 -->
                    <template v-if="isEditing">
                    <section class="partner-form__section">
                        <p class="partner-form__section-title">基本情報</p>
                        <div class="partner-form__grid">
                            <div class="partner-form__field partner-form__field--wide">
                                <ShortInput
                                    ref="nameInput"
                                    v-model="form.name"
                                    name="partner_name"
                                    type="text"
                                    rules="required"
                                    placeHolder="取引先名"
                                />
                                <span class="partner-form__hint">freeeとの突き合わせに使う名前です。freee側と同じ表記にしてください。</span>
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.name_kana" name="partner_name_kana" type="text" placeHolder="カナ" />
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.long_name" name="partner_long_name" type="text" placeHolder="正式名称" />
                            </div>
                            <div class="partner-form__field">
                                <!-- ラジオもselectと同じ枠・高さに揃える（グリッドの行がガタつかないように）。 -->
                                <div class="partner-form__boxed">
                                    <span class="partner-form__boxed-label">区分</span>
                                    <div class="partner-form__radio-options">
                                        <label v-for="option in ENTITY_TYPES" :key="option.value" class="partner-form__radio-option">
                                            <input
                                                v-model="form.entity_type"
                                                type="radio"
                                                class="custom-f-radio"
                                                name="partner_entity_type"
                                                :value="option.value"
                                            />
                                            <span>{{ option.label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="partner-form__field">
                                <div class="partner-form__boxed">
                                    <label class="partner-form__boxed-label">取引区分</label>
                                    <select v-model="form.transaction_category" class="partner-form__select-input">
                                        <option :value="null">未設定</option>
                                        <option v-for="option in TRANSACTION_CATEGORIES" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.code" name="partner_code" type="text" placeHolder="取引先コード" />
                            </div>
                            <div class="partner-form__field">
                                <ShortInput
                                    v-model="form.invoice_registration_number"
                                    name="partner_invoice_number"
                                    type="text"
                                    placeHolder="適格請求書番号（T + 13桁）"
                                />
                                <a href="https://www.invoice-kohyo.nta.go.jp/" target="_blank">適格請求書発行事業者公表サイト</a>
                            </div>
                            <div class="partner-form__field">
                                <ShortInput
                                    v-model="form.corporate_number"
                                    name="partner_corporate_number"
                                    type="text"
                                    placeHolder="法人番号（13桁）"
                                />
                                <a href="https://www.houjin-bangou.nta.go.jp/" target="_blank">法人番号公表サイト</a>
                            </div>
                        </div>
                    </section>

                    <section class="partner-form__section">
                        <p class="partner-form__section-title">所在地</p>
                        <div class="partner-form__grid">
                            <div class="partner-form__field">
                                <ShortInput v-model="form.postal_code" name="partner_postal_code" type="text" placeHolder="郵便番号" />
                            </div>
                            <div class="partner-form__field">
                                <!-- ShortInput は input 専用のため、selectだけは
                                     同じ見た目（枠線・高さ・上部ラベル）を手で組む。 -->
                                <div class="partner-form__boxed">
                                    <label class="partner-form__boxed-label">都道府県</label>
                                    <select v-model="form.prefecture_code" class="partner-form__select-input">
                                        <option :value="null">未設定</option>
                                        <option v-for="pref in PREFECTURES" :key="pref.code" :value="pref.code">{{ pref.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="partner-form__field partner-form__field--wide">
                                <ShortInput v-model="form.address_1" name="partner_address_1" type="text" placeHolder="住所1（市区町村・番地）" />
                            </div>
                            <div class="partner-form__field partner-form__field--wide">
                                <ShortInput v-model="form.address_2" name="partner_address_2" type="text" placeHolder="住所2（建物名・部屋番号）" />
                            </div>
                        </div>
                    </section>

                    <section class="partner-form__section">
                        <p class="partner-form__section-title">連絡先</p>
                        <div class="partner-form__grid">
                            <div class="partner-form__field">
                                <ShortInput v-model="form.contact_name" name="partner_contact_name" type="text" placeHolder="担当者" />
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.contact_position" name="partner_contact_position" type="text" placeHolder="役職" />
                                
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.phone" name="partner_phone" type="text" placeHolder="電話番号" />
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.email" name="partner_email" type="text" placeHolder="メール" />
                            </div>
                            <div class="partner-form__field">
                                <ShortInput v-model="form.website" name="partner_website" type="text" placeHolder="Webサイト" />
                                
                            </div>
                        </div>
                    </section>

                    <section class="partner-form__section">
                        <button type="button" class="partner-form__collapse" @click="infoOpen = !infoOpen">
                            <span class="partner-form__collapse-icon" :class="{ 'is-open': infoOpen }">
                                <svg width="9" height="9" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                    <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                </svg>
                            </span>
                            <span class="partner-form__section-title partner-form__collapse-title">情報管理</span>
                            <span class="partner-form__collapse-count">
                                情報セキュリティ {{ answeredCount(form.information_security_answers, INFO_SECURITY_QUESTIONS) }} / {{ INFO_SECURITY_QUESTIONS.length }}
                                ・労働契約 {{ answeredCount(form.labor_contract_answers, LABOR_CONTRACT_QUESTIONS) }} / {{ LABOR_CONTRACT_QUESTIONS.length }}
                            </span>
                        </button>

                        <div v-if="infoOpen" class="partner-form__collapse-body">
                            <div class="partner-form__grid">
                                <div class="partner-form__field">
                                    <ShortInput v-model="form.isms_registration_number" name="partner_isms" type="text" placeHolder="ISMS認証登録番号" />
                                    <a href="https://isms.jp/lst/ind/" target="_blank">ISMS認証取得組織検索サイト</a>
                                </div>
                                <div class="partner-form__field">
                                    <ShortInput v-model="form.privacy_mark_number" name="partner_pmark" type="text" placeHolder="プライバシーマーク許諾番号" />
                                    <a href="https://entity-search.jipdec.or.jp/pmark/top/" target="_blank">プライバシーマーク付与事業者検索</a>
                                </div>
                            </div>

                            <div class="partner-form__sheet">
                                <p class="partner-form__sheet-title">情報セキュリティに関するヒアリング</p>
                                <label
                                    v-for="question in INFO_SECURITY_QUESTIONS"
                                    :key="question.key"
                                    class="partner-form__sheet-item"
                                >
                                    <input
                                        type="checkbox"
                                        class="custom-f-checkbox"
                                        :checked="!!form.information_security_answers[question.key]"
                                        @change="toggleAnswer(form.information_security_answers, question.key, $event)"
                                    />
                                    <span>{{ question.text }}</span>
                                </label>
                            </div>

                            <div class="partner-form__sheet">
                                <p class="partner-form__sheet-title">労働契約に関する質問</p>
                                <label
                                    v-for="question in LABOR_CONTRACT_QUESTIONS"
                                    :key="question.key"
                                    class="partner-form__sheet-item"
                                >
                                    <input
                                        type="checkbox"
                                        class="custom-f-checkbox"
                                        :checked="!!form.labor_contract_answers[question.key]"
                                        @change="toggleAnswer(form.labor_contract_answers, question.key, $event)"
                                    />
                                    <span>{{ question.text }}</span>
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="partner-form__section">
                        <p class="partner-form__section-title">その他</p>
                        <div class="partner-form__field">
                            <LongInput v-model="form.note" name="partner_note" placeHolder="備考" />
                            
                        </div>
                        <label class="partner-form__check">
                            <input v-model="form.available" type="checkbox" class="custom-f-checkbox" />
                            <span>使用中（外すと使用不可として扱います）</span>
                        </label>
                    </section>
                    </template>

                    <!-- 閲覧表示。編集時と同じ並び・同じ位置に置く（見る場所が変わらないように）。 -->
                    <template v-else-if="partner">
                        <section class="partner-form__section">
                            <p class="partner-form__section-title">基本情報</p>
                            <div class="partner-form__grid">
                                <div class="partner-view__item partner-form__field--wide">
                                    <span class="partner-view__label">取引先名</span>
                                    <span class="partner-view__value">{{ dash(partner.name) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">カナ</span>
                                    <span class="partner-view__value">{{ dash(partner.name_kana) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">正式名称</span>
                                    <span class="partner-view__value">{{ dash(partner.long_name) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">区分</span>
                                    <span class="partner-view__value">{{ optionLabel(ENTITY_TYPES, partner.entity_type) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">取引区分</span>
                                    <span class="partner-view__value">{{ optionLabel(TRANSACTION_CATEGORIES, partner.transaction_category) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">取引先コード</span>
                                    <span class="partner-view__value">{{ dash(partner.code) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">適格請求書番号</span>
                                    <span class="partner-view__value">{{ dash(partner.invoice_registration_number) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">法人番号</span>
                                    <span class="partner-view__value">{{ dash(partner.corporate_number) }}</span>
                                </div>
                            </div>
                        </section>

                        <section class="partner-form__section">
                            <p class="partner-form__section-title">所在地</p>
                            <div class="partner-form__grid">
                                <div class="partner-view__item">
                                    <span class="partner-view__label">郵便番号</span>
                                    <span class="partner-view__value">{{ dash(partner.postal_code) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">都道府県</span>
                                    <span class="partner-view__value">{{ prefectureName }}</span>
                                </div>
                                <div class="partner-view__item partner-form__field--wide">
                                    <span class="partner-view__label">住所1</span>
                                    <span class="partner-view__value">{{ dash(partner.address_1) }}</span>
                                </div>
                                <div class="partner-view__item partner-form__field--wide">
                                    <span class="partner-view__label">住所2</span>
                                    <span class="partner-view__value">{{ dash(partner.address_2) }}</span>
                                </div>
                            </div>
                        </section>

                        <section class="partner-form__section">
                            <p class="partner-form__section-title">連絡先</p>
                            <div class="partner-form__grid">
                                <div class="partner-view__item">
                                    <span class="partner-view__label">担当者</span>
                                    <span class="partner-view__value">{{ dash(partner.contact_name) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">役職</span>
                                    <span class="partner-view__value">{{ dash(partner.contact_position) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">電話番号</span>
                                    <span class="partner-view__value">{{ dash(partner.phone) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">メール</span>
                                    <span class="partner-view__value">{{ dash(partner.email) }}</span>
                                </div>
                                <div class="partner-view__item">
                                    <span class="partner-view__label">Webサイト</span>
                                    <span class="partner-view__value">{{ dash(partner.website) }}</span>
                                </div>
                            </div>
                        </section>

                        <section class="partner-form__section">
                            <button type="button" class="partner-form__collapse" @click="infoOpen = !infoOpen">
                                <span class="partner-form__collapse-icon" :class="{ 'is-open': infoOpen }">
                                    <svg width="9" height="9" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                                    </svg>
                                </span>
                                <span class="partner-form__section-title partner-form__collapse-title">情報管理</span>
                                <span class="partner-form__collapse-count">
                                    情報セキュリティ {{ answeredOf(partner.information_security_answers, INFO_SECURITY_QUESTIONS).length }} / {{ INFO_SECURITY_QUESTIONS.length }}
                                    ・労働契約 {{ answeredOf(partner.labor_contract_answers, LABOR_CONTRACT_QUESTIONS).length }} / {{ LABOR_CONTRACT_QUESTIONS.length }}
                                </span>
                            </button>

                            <div v-if="infoOpen" class="partner-form__collapse-body">
                                <div class="partner-form__grid">
                                    <div class="partner-view__item">
                                        <span class="partner-view__label">ISMS認証登録番号</span>
                                        <span class="partner-view__value">{{ dash(partner.isms_registration_number) }}</span>
                                    </div>
                                    <div class="partner-view__item">
                                        <span class="partner-view__label">プライバシーマーク許諾番号</span>
                                        <span class="partner-view__value">{{ dash(partner.privacy_mark_number) }}</span>
                                    </div>
                                </div>

                                <div class="partner-form__sheet">
                                    <p class="partner-form__sheet-title">情報セキュリティに関するヒアリング</p>
                                    <!-- 未回答も同じ位置に残す。編集時と行がずれると読み比べられない。 -->
                                    <div
                                        v-for="question in INFO_SECURITY_QUESTIONS"
                                        :key="question.key"
                                        class="partner-form__sheet-item"
                                        :class="{ 'is-off': !partner.information_security_answers?.[question.key] }"
                                    >
                                        <span class="partner-view__mark">{{ partner.information_security_answers?.[question.key] ? '✓' : '－' }}</span>
                                        <span>{{ question.text }}</span>
                                    </div>
                                </div>

                                <div class="partner-form__sheet">
                                    <p class="partner-form__sheet-title">労働契約に関する質問</p>
                                    <div
                                        v-for="question in LABOR_CONTRACT_QUESTIONS"
                                        :key="question.key"
                                        class="partner-form__sheet-item"
                                        :class="{ 'is-off': !partner.labor_contract_answers?.[question.key] }"
                                    >
                                        <span class="partner-view__mark">{{ partner.labor_contract_answers?.[question.key] ? '✓' : '－' }}</span>
                                        <span>{{ question.text }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="partner-form__section">
                            <p class="partner-form__section-title">その他</p>
                            <div class="partner-view__item">
                                <span class="partner-view__label">備考</span>
                                <span class="partner-view__value partner-view__note">{{ dash(partner.note) }}</span>
                            </div>
                            <div class="partner-view__item">
                                <span class="partner-view__label">状態</span>
                                <span class="partner-view__value">{{ partner.available ? '使用中' : '使用不可' }}</span>
                            </div>
                        </section>
                    </template>

                    <section v-if="partner && !isEditing" class="partner-form__section">
                        <p class="partner-form__section-title">紐付けプロジェクト</p>
                        <div class="partner-form__inline">
                            <span>{{ projectSummary }}</span>
                            <button v-if="!readonly" type="button" class="partner-form__button" @click="emit('manage-projects', partner)">
                                紐付けを編集
                            </button>
                        </div>
                    </section>

                    <section v-if="partner && !isEditing" class="partner-form__section partner-form__section--boxed">
                        <p class="partner-form__section-title">freee連携</p>
                        <div class="partner-form__inline">
                            <span :class="['partner-form__chip', { 'is-linked': freee.linked }]">
                                {{ freee.linked ? `#${freee.partnerId}` : '未連携' }}
                            </span>
                            <span v-if="freee.unsynced" class="partner-form__hint">未反映の変更あり</span>
                            <div v-if="!readonly" class="partner-form__inline-buttons">
                                <template v-if="freee.linked">
                                    <button type="button" class="partner-form__button" :disabled="freeeBusy" @click="checkFreee">照合</button>
                                    <button type="button" class="partner-form__button" :disabled="freeeBusy" @click="pushToFreee(false)">反映</button>
                                    <button type="button" class="partner-form__button" :disabled="freeeBusy" @click="unlinkFreee">解除</button>
                                </template>
                                <button v-else type="button" class="partner-form__button" :disabled="freeeBusy" @click="pushToFreee(false)">連携する</button>
                            </div>
                        </div>
                        <p v-if="!readonly" class="partner-form__hint">
                            freeeへの反映はこのボタンからのみ行われます。保存操作では送信しません。
                        </p>
                    </section>

                </div>

            </div>
        </template>
    </Modal>

    <Teleport to="body">
        <PartnerDifference
            v-if="differenceView"
            :title="differenceView.title"
            :message="differenceView.message"
            :differences="differenceView.differences"
            :resolvable="differenceView.resolvable"
            :busy="freeeBusy"
            @close="differenceView = null"
            @keep-local="pushToFreee(true)"
            @keep-freee="pullFromFreee"
        />
    </Teleport>
</template>
<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import Modal from '@/components/Global/Modal.vue'
import LoaderButton from '@/components/Global/LoaderButton.vue'
import ShortInput from '@/components/Form/ShortInput.vue'
import LongInput from '@/components/Form/LongInput.vue'
import PartnerDifference from './PartnerDifference.vue'
import Trash from '@/components/Icons/Trash.vue'
import Edit from '@/components/Icons/Edit.vue'
import { useApi } from '@/composables/api'
import { useDialog } from '@/composables/dialog'
import {
    ENTITY_TYPES,
    INFO_SECURITY_QUESTIONS,
    LABOR_CONTRACT_QUESTIONS,
    PREFECTURES,
    TRANSACTION_CATEGORIES,
    type PartnerQuestion,
    type PartnerCheckResponse,
    type PartnerFieldDifference,
    type PartnerRecord,
    type PartnerSaveResponse,
    type PartnerSyncResponse,
} from '@/interface/partnerInterface'

/**
 * 取引先の詳細モーダル。閲覧（view）と編集（edit）を切り替える。
 * 一覧以外の画面からも使えるよう、対象は partner プロップだけで決まるようにしている。
 */
const props = withDefaults(defineProps<{
    /** 表示・編集する取引先。null なら新規登録。 */
    partner?: PartnerRecord | null
    /** 既存レコードを開くときの初期モード。新規は常に編集。 */
    mode?: 'view' | 'edit'
    /**
     * 参照専用。編集・削除・freee連携・紐付け変更をすべて隠す。
     * プロジェクト詳細など、管理以外の画面から開くときに使う
     * （取引先の管理操作は管理画面だけに置く）。
     */
    readonly?: boolean
}>(), {
    partner: null,
    mode: 'view',
    readonly: false,
})
const emit = defineEmits(['close', 'saved', 'refresh', 'manage-projects'])

const api = useApi()
const { ping } = useDialog()

// 新規は編集から始める。既存は既定で閲覧。
const mode = ref<'view' | 'edit'>(props.readonly ? 'view' : (props.partner ? props.mode : 'edit'))
const isEditing = computed(() => mode.value === 'edit')

const title = computed(() => {
    if (!props.partner) return '取引先を登録する'

    return isEditing.value ? '取引先を編集する' : props.partner.name
})

const dash = (value: unknown) =>
    value === null || value === undefined || value === '' ? '—' : String(value)

const optionLabel = (options: { value: string; label: string }[], value: string | null) =>
    options.find(o => o.value === value)?.label ?? '—'

const prefectureName = computed(() =>
    PREFECTURES.find(p => p.code === props.partner?.prefecture_code)?.name ?? '—')

/** 「はい」と答えた設問だけを返す（未回答はキーを持たない）。 */
const answeredOf = (answers: Record<string, boolean> | null | undefined, questions: PartnerQuestion[]) =>
    questions.filter(q => answers?.[q.key])

const saving = ref(false)
// 必須チェックは ShortInput 側の validate() に任せ、枠下にインラインで出す。
const nameInput = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)

// freee連携の状態はこの画面で完結させる。一覧は結果を受けて読み直すだけ。
const freeeBusy = ref(false)
const freee = reactive({
    linked: Boolean(props.partner?.freee_linked),
    partnerId: props.partner?.freee_partner_id ?? null,
    unsynced: Boolean(props.partner?.has_unsynced_changes),
})

const differenceView = ref<{
    title: string
    message: string
    differences: PartnerFieldDifference[]
    resolvable: boolean
} | null>(null)

const projectSummary = computed(() => {
    const list = props.partner?.projects ?? []
    if (!list.length) return '紐付けなし'

    return `${list.length}件：${list.map(p => p.name).join('、')}`
})

/** freeeの操作結果で手元の表示を更新し、一覧にも読み直させる。 */
const applyFreeeResult = (partner?: PartnerRecord) => {
    if (partner) {
        freee.linked = Boolean(partner.freee_linked)
        freee.partnerId = partner.freee_partner_id
        freee.unsynced = Boolean(partner.has_unsynced_changes)
    }
    emit('refresh')
}

const pushToFreee = async (force: boolean) => {
    if (!props.partner || freeeBusy.value) return
    freeeBusy.value = true
    try {
        const response = await api.post(`/admin/partners/${props.partner.id}/freee/push`, { force }) as PartnerSyncResponse | null
        if (!response) return

        if (response.result === 'conflict') {
            differenceView.value = {
                title: '同期の競合',
                message: response.message,
                differences: response.conflicts,
                resolvable: true,
            }
            return
        }

        differenceView.value = null
        ping(response.message)
        applyFreeeResult(response.partner)
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        freeeBusy.value = false
    }
}

const pullFromFreee = async () => {
    if (!props.partner || freeeBusy.value) return
    freeeBusy.value = true
    try {
        const response = await api.post(`/admin/partners/${props.partner.id}/freee/pull`) as PartnerSyncResponse | null
        if (!response) return

        differenceView.value = null
        ping(response.message)
        applyFreeeResult(response.partner)
        // 取り込んだ内容は入力欄にも反映させる（開いたままだと古い値が残るため）。
        emit('saved', response.partner)
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        freeeBusy.value = false
    }
}

const checkFreee = async () => {
    if (!props.partner || freeeBusy.value) return
    freeeBusy.value = true
    try {
        const response = await api.get(`/admin/partners/${props.partner.id}/freee`) as PartnerCheckResponse | null
        if (!response) return

        if (!response.differences.length) {
            ping(response.message)
            applyFreeeResult(response.partner)
            return
        }

        differenceView.value = {
            title: 'freeeとの差分',
            message: response.message,
            differences: response.differences,
            resolvable: true,
        }
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        freeeBusy.value = false
    }
}

const unlinkFreee = async () => {
    if (!props.partner || freeeBusy.value) return
    freeeBusy.value = true
    try {
        const response = await api.del(`/admin/partners/${props.partner.id}/freee`, null, {
            ask: 'freeeとの連携を解除しますか？（freee側の取引先は削除されません）',
        })
        if (!response) return

        ping(response.message)
        applyFreeeResult(response.partner)
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        freeeBusy.value = false
    }
}

const removePartner = async () => {
    if (!props.partner || saving.value) return
    try {
        const response = await api.del(`/admin/partners/${props.partner.id}`, null, {
            ask: `「${props.partner.name}」を削除しますか？（freee側の取引先は削除されません）`,
        })
        if (!response) return

        ping(response.message)
        emit('saved')
    } catch {
        // メッセージは useApi が表示済み
    }
}

const buildForm = () => ({
    name: props.partner?.name ?? '',
    name_kana: props.partner?.name_kana ?? '',
    long_name: props.partner?.long_name ?? '',
    entity_type: props.partner?.entity_type ?? null,
    transaction_category: props.partner?.transaction_category ?? null,
    code: props.partner?.code ?? '',
    corporate_number: props.partner?.corporate_number ?? '',
    invoice_registration_number: props.partner?.invoice_registration_number ?? '',
    postal_code: props.partner?.postal_code ?? '',
    prefecture_code: props.partner?.prefecture_code ?? null,
    address_1: props.partner?.address_1 ?? '',
    address_2: props.partner?.address_2 ?? '',
    phone: props.partner?.phone ?? '',
    contact_name: props.partner?.contact_name ?? '',
    contact_position: props.partner?.contact_position ?? '',
    email: props.partner?.email ?? '',
    website: props.partner?.website ?? '',
    note: props.partner?.note ?? '',
    isms_registration_number: props.partner?.isms_registration_number ?? '',
    privacy_mark_number: props.partner?.privacy_mark_number ?? '',
    // 未回答は「キーを持たない」で表す。null が来ても空オブジェクトに正規化しておく。
    information_security_answers: { ...(props.partner?.information_security_answers ?? {}) } as Record<string, boolean>,
    labor_contract_answers: { ...(props.partner?.labor_contract_answers ?? {}) } as Record<string, boolean>,
    available: props.partner?.available ?? true,
})

const form = reactive(buildForm())

/** 編集をやめる。新規なら閉じ、既存なら入力を捨てて閲覧へ戻す。 */
const cancelEdit = () => {
    if (!props.partner) {
        emit('close')

        return
    }

    Object.assign(form, buildForm())
    mode.value = 'view'
}

// 親が対象を差し替えたときに、前のレコードの入力が残らないようにする。
watch(() => props.partner?.id, () => {
    Object.assign(form, buildForm())
    mode.value = props.readonly ? 'view' : (props.partner ? props.mode : 'edit')
})

// 情報管理は設問が多いので既定は閉じる。既に回答や番号があるときだけ開いた状態で始める。
const infoOpen = ref(Boolean(
    props.partner?.isms_registration_number
    || props.partner?.privacy_mark_number
    || Object.values(props.partner?.information_security_answers ?? {}).some(Boolean)
    || Object.values(props.partner?.labor_contract_answers ?? {}).some(Boolean),
))

const toggleAnswer = (answers: Record<string, boolean>, key: string, event: Event) => {
    const checked = (event.target as HTMLInputElement).checked
    // チェックを外したらキーごと消す。false を残すと「いいえ」と「未回答」が区別できなくなる。
    if (checked) {
        answers[key] = true
    } else {
        delete answers[key]
    }
}

const answeredCount = (answers: Record<string, boolean>, questions: PartnerQuestion[]) => {
    return questions.filter(q => answers[q.key]).length
}

/** 空文字は送らずに null にする。freee側の「未設定」と揃えるため。 */
const payload = () => {
    const blanked = (value: string) => (value.trim() === '' ? null : value.trim())

    return {
        name: form.name.trim(),
        name_kana: blanked(form.name_kana),
        long_name: blanked(form.long_name),
        entity_type: form.entity_type,
        transaction_category: form.transaction_category,
        code: blanked(form.code),
        corporate_number: blanked(form.corporate_number),
        invoice_registration_number: blanked(form.invoice_registration_number),
        postal_code: blanked(form.postal_code),
        prefecture_code: form.prefecture_code,
        address_1: blanked(form.address_1),
        address_2: blanked(form.address_2),
        phone: blanked(form.phone),
        contact_name: blanked(form.contact_name),
        contact_position: blanked(form.contact_position),
        email: blanked(form.email),
        website: blanked(form.website),
        note: blanked(form.note),
        isms_registration_number: blanked(form.isms_registration_number),
        privacy_mark_number: blanked(form.privacy_mark_number),
        information_security_answers: form.information_security_answers,
        labor_contract_answers: form.labor_contract_answers,
        available: form.available,
    }
}

/**
 * 保存はこちらのDBだけ。freeeへの反映は一覧の「freee連携」列（連携する／反映／確認）で
 * 明示的に行う。編集のたびにfreeeへ書きに行くと、意図しないタイミングで
 * 相手側を書き換えたり、差分が無いのに競合確認が出たりするため。
 */
const save = async () => {
    if (saving.value) return

    const check = await nameInput.value?.validate()
    if (check && !check.valid) return

    saving.value = true
    try {
        const body = payload()
        const response = props.partner
            ? await api.put(`/admin/partners/${props.partner.id}`, body) as PartnerSaveResponse | null
            : await api.post('/admin/partners', body) as PartnerSaveResponse | null

        if (!response) return

        ping(response.message)

        // 既存の更新は閲覧へ戻して結果を見せる。新規は親側で閉じる。
        if (props.partner) {
            mode.value = 'view'
        }
        emit('saved', response.partner)
    } catch {
        // メッセージは useApi が表示済み
    } finally {
        saving.value = false
    }
}
</script>

<style scoped>
/* スクロールは Modal の .scrollable に任せる。ここで独自のスクロール枠を作ると
   スクロールバーが中身に張り付いて窮屈に見える。 */
.partner-form {
    display: flex;
    flex-direction: column;
    gap: 26px;
    font-size: 13px;
}

.partner-form__body {
    display: flex;
    flex-direction: column;
    gap: 26px;
}

.partner-form__section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.partner-form__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    /* 行間を広めに取る。ShortInput のエラー文（.i-error）は position:absolute で
       レイアウトを押し広げないため、余白が無いと次の行に重なる。 */
    gap: 24px 20px;
    align-items: start;
}

.partner-form__field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
}

.partner-form__field--wide {
    grid-column: 1 / -1;
}

/* freeeへ送らない項目の目印。入力欄の下に小さく置く。 */
.partner-form__tag {
    width: fit-content;
    padding: 1px 8px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 10px;
}

/*
 * ShortInput は input 専用なので、都道府県の select だけは見た目を合わせて手で組む。
 * 値の有無に関わらずラベルは上に固定する（ShortInput の focused 状態と同じ位置）。
 */
.partner-form__boxed {
    position: relative;
    display: flex;
    align-items: center;
    border: 1px solid var(--primary-color);
    transition: border 0.3s ease;
}

.partner-form__boxed-label {
    position: absolute;
    top: 15px;
    left: 15px;
    color: var(--primary-color);
    font-size: 11px;
    line-height: normal;
    pointer-events: none;
    transform: translateY(-50%);
}

.partner-form__select-input {
    width: 100%;
    box-sizing: border-box;
    padding: 25px 10px 10px 15px;
    border: 0;
    background: transparent;
    color: inherit;
    font-size: 16px;
    line-height: 1.6;
}

.partner-form__select-input:focus {
    outline: none;
}

/* ラジオは select と同じ内側余白で並べ、枠の高さを揃える。 */
.partner-form__radio-options {
    display: flex;
    align-items: center;
    gap: 20px;
    width: 100%;
    padding: 25px 10px 10px 15px;
    /* 枠の高さは文字サイズではなくここで揃える。select の行box（16px × 1.6）と同じ。
       文字を大きくして高さを稼ぐと、ラベルだけ他より目立ってしまう。 */
    min-height: 25.6px;
}

.partner-form__radio-option {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    user-select: none;
}

/* 折りたたみ見出し。他のセクション見出しと同じ罫線・色に揃える。 */
.partner-form__collapse {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 0 0 6px;
    border: 0;
    border-bottom: 1px solid var(--calendarBorder);
    background: transparent;
    text-align: left;
    cursor: pointer;
}

.partner-form__collapse-title {
    padding-bottom: 0;
    border-bottom: 0;
}

.partner-form__collapse-icon {
    display: flex;
    align-items: center;
    color: var(--third-color);
    /* 既定（閉じている）は右向き、開いたら下向き。 */
    transform: rotate(180deg);
    transition: transform 0.2s ease;
}

.partner-form__collapse-icon.is-open {
    transform: rotate(270deg);
}

.partner-form__collapse-count {
    margin-left: auto;
    color: var(--third-color);
    font-size: 11px;
}

.partner-form__collapse-body {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-top: 4px;
}

.partner-form__sheet {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--calendarBorder);
}

.partner-form__sheet-title {
    color: var(--third-color);
    font-size: 12px;
}

/* 設問は長文なので、チェックボックスを先頭行に固定して折り返す。 */
.partner-form__sheet-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: start;
    gap: 10px;
    line-height: 1.7;
    cursor: pointer;
}

.partner-form__sheet-item input {
    margin-top: 4px;
}

/* freee連携のように「操作」を持つ区画は枠で囲って入力欄と区別する。 */
.partner-form__section--boxed {
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--calendarBorder);
}

.partner-form__section--boxed .partner-form__section-title {
    padding-bottom: 0;
    border-bottom: 0;
}

.partner-form__inline {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.partner-form__inline-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.partner-form__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--bg3);
    color: var(--third-color);
    font-size: 11px;
}

.partner-form__chip::before {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--check-inactive, #a1a1aa);
    content: '';
}

.partner-form__chip.is-linked::before {
    background: #22a447;
}

/* 削除は取り消しの利かない操作。派手な赤ではなく落ち着いたレンガ色の枠線で示す。 */
/* 見出し右の操作は横並びで、閉じるボタンと同じ行に収める。 */
.partner-form__menu {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* LoaderButton は既定で margin:auto・最小幅100pxのため、行に合わせて詰める。 */
.partner-form__menu :deep(.l-button) {
    margin: 0;
    min-width: 84px;
    min-height: 30px;
    font-size: 12px;
}

.partner-form__icon-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    padding: 0;
    border: 0;
    background: transparent;
    cursor: pointer;
    fill: var(--primary-color);
}

.partner-form__icon-button:hover {
    background: var(--bg3);
}

.partner-form__icon-button:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.partner-form__button--danger {
    border-color: #c0392b;
    color: #c0392b;
}

.partner-form__button--danger:hover {
    border-color: #c0392b;
}

/*
 * 閲覧表示。編集時の入力欄と同じグリッド（partner-form__grid）に載せ、
 * 見出し・並び・段組みを揃える。モード切替で項目の位置が動かないようにするため。
 */
/*
 * 罫線は区切り（セクション見出し）だけに使い、項目には引かない。
 * 項目ごとに縦線を入れると、見出しの横線と向きが混在して落ち着かないうえ、
 * 空欄の項目が入力欄のように見えてしまう。
 */
.partner-view__item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
    padding: 2px 0;
}

.partner-view__label {
    color: var(--third-color);
    font-size: 11px;
}

.partner-view__value {
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.partner-view__note {
    white-space: pre-wrap;
}

/* 未回答の設問は薄く。行は残して位置を保つ。 */
.partner-view__mark {
    width: 14px;
    color: var(--primary-color);
    text-align: center;
}

.partner-form__sheet-item.is-off {
    color: var(--third-color);
}

.partner-form__sheet-item.is-off .partner-view__mark {
    color: var(--third-color);
}

.partner-form__hint {
    color: var(--third-color);
    font-size: 11px;
    line-height: 1.6;
}

.partner-form__check {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.partner-form__button {
    padding: 8px 14px;
    border: 1px solid var(--formBorder);
    background: var(--background-color);
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
}

.partner-form__button:hover {
    border-color: var(--primary-color);
}

.partner-form__button:disabled {
    color: var(--third-color);
    cursor: not-allowed;
    opacity: 0.6;
}

@media screen and (max-width: 959px) {
    .partner-form__grid {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
