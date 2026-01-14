<template>
    <div class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            <div class="projectModalMainHeader !bg-[var(--bg3)]">
                <div class="flex flex-col ml-[30px]">
                    <p>リソース</p>
                </div>
                <div class="flex items-center justify-center w-[60px] h-[60px] min-w-[60px] ml-auto cursor-pointer"
                    @click="router.back()">
                    <CloseIcon size="13" />
                </div>
            </div>
            <div class="relative h-full">
              <div class="sticky top-0 bg-[var(--background-color)] z-[7] min-h-[60px] flex justify-between items-center md:flex-nowrap flex-wrap px-5 py-5 md:py-0 md:px-[20px] gap-[10px]">
                <div class="w-full md:w-[30%]">
                  <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`メンバー・プロジェクト検索`" 
                    @search-start="(word) => {keywords = word}"
                  />
                </div>
                <div class="flex items-center gap-[20px] relative w-full justify-center md:justify-end flex-wrap md:flex-nowrap">
                    <div class="flex items-center">
                        <button @click="shiftRange(-1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                            <Back size="13"/>
                        </button>
                    </div>
                    
                    <PeriodRangePicker
                        :start="periodStartIso"
                        :end="periodEndIso"
                        :max-months="12"
                        @change="handleRangeChange"
                    />
                    <div class="flex items-center">
                        <button @click="shiftRange(1)" class="flex items-center justify-center h-[30px] w-fit gap-2 min-w-[30px]">
                            <Back size="13" class="rotate-180"/>
                        </button>
                    </div>
                    
                </div>
              </div>
              <div class="overflow-auto h-[calc(100%-175px)] md:h-[calc(100%-120px)] md:mx-0 mx-5">
                <div class="cal-month-loader" style="height: 100%; top: 0;opacity: 0.6;" v-if="loader">
                      <div id="loaderMini">
                          <div class="spinner-mini"
                              style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                      </div>
                  </div>
                <table>
                  <thead>
                    <tr>
                      <th :rowspan="2" class="sticky-left first-col top-border">メンバー名</th>
                      <th :rowspan="2" class="sticky-left second-col top-border">プロジェクト名</th>
                      <th 
                          v-for="(p, i) in periods"
                          :key="p.period"
                          colspan="5"
                          :class="[
                            'border-r border-[var(--calendarBorder)]',
                            '[border-right-style:solid]',
                            'top-border'
                          ]"
                      >
                          <div :id="p.period" class="flex justify-center">
                              <span>{{ p.year }}年{{ p.month }}月</span>
                          </div>
                      </th>
                      <th v-if="showComment" :rowspan="2" class="sticky-right comment-cell top-border">コメント</th>
                    </tr>
                    <tr>
                        <template v-for="p in periods" :key="p.period">
                            <th data-cell="right-border">給料手当数量</th>
                            <th data-cell="right-border">所定労働日数</th>
                            <th data-cell="right-border">給料手当出金</th>
                            <th data-cell="right-border">数量合計</th>
                            <th>数量残り</th>
                        </template>
                    </tr>
                  </thead>
                  <tbody v-for="member in searchResults" :key="member.member">
                    <tr v-if="member.projects.length === 0">
                      <td>{{ member.member }}</td>
                      <td></td>
                      <template v-for="p in periods" :key="p.period">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                      </template>
                      <td v-if="showComment" class="sticky-right comment-cell">
                        <div class="inner-col">
                          <span class="mobile">コメント</span>
                          <div class="flex items-center gap-2 cursor-pointer">
                            <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33" @click="selectMemberComment(member.member)">
                              <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                            </svg>
                            <span v-if="commentCount[member.member] > 0" class="text-xs">{{ commentCount[member.member] }}</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr
                      v-for="(project, projectIndex) in member.projects"
                      :key="`${member.member}-${project.project}`"
                      v-bind="projectIndex === member.projects.length - 1 ? { 'data-cell': 'last-row' } : {}"
                    >
                      <td data-cell="right-border" class="sticky-left first-col" v-if="projectIndex === 0" :rowspan="member.projects.length">
                        {{ member.member }}
                      </td>
                      <td data-cell="right-border" class="sticky-left second-col">{{ project.project }}</td>
                      <template v-for="p in periods" :key="p.period">
                        <td data-cell="right-border">
                          <div class="inner-col">
                            <span class="mobile">給料手当数量</span>
                            <div class="flex items-center">
                              <span class="flex-1 text-left tabular-nums">
                                {{ formatNumber(project.periods[p.period]?.['給料手当数量']) }}
                              </span>

                              <div v-if="managementAccounts" class="ml-4 shrink-0">
                                <CommandButton
                                  :buttons="[
                                    { title: '編集', action: () => editQuantity(project.periods[p.period], member.member, project.project) }
                                  ]"
                                />
                              </div>
                            </div>
                          </div>
                          
                        </td>

                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                            <span class="mobile">所定労働日数</span>
                            {{ totalWorkingDays(member.projects, p.period) }}
                          </div>
                        </td>
                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                             <span class="mobile">給料手当出金</span>
                             {{ totalWithDrawal(member.projects, p.period) }}
                          </div>
                        </td>
                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                            <span class="mobile">数量合計</span>
                            {{ totalQuantity(member.projects, p.period) }}
                          </div>
                        </td>
                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                            <span class="mobile">数量残り</span>
                            {{ remainingQuantity(member.projects, p.period) }}
                          </div>
                        </td>
                      </template>
                      <td v-if="showComment && projectIndex === 0" class="sticky-right comment-cell" :rowspan="member.projects.length">
                        <div class="inner-col">
                          <span class="mobile">コメント</span>
                          <div class="flex items-center gap-2 cursor-pointer">
                            <svg fill="var(--primary-color)" height="15" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 33" @click="selectMemberComment(member.member)">
                              <path d="M10.788 8.109c1.574-0.063 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.345 0.021-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.896 0.010-0.459 0.386-0.823 0.834-0.844zM10.788 13.050c1.574-0.052 3.148-0.083 4.711-0.104l2.356-0.031 2.356-0.010 2.356 0.010c0.782 0 1.574 0.021 2.356 0.031 1.574 0.031 3.148 0.063 4.711 0.136 0.459 0.021 0.823 0.417 0.803 0.876-0.021 0.438-0.375 0.771-0.803 0.792-1.574 0.073-3.148 0.115-4.711 0.136-0.782 0.010-1.574 0.031-2.356 0.031l-2.356 0.010-2.356-0.010-2.356-0.031c-1.574-0.021-3.148-0.052-4.711-0.104-0.479-0.021-0.855-0.417-0.844-0.907 0.021-0.438 0.396-0.803 0.844-0.823zM10.788 17.991c0.74-0.052 1.491-0.083 2.231-0.104l1.115-0.031c0.375-0.010 0.74-0.010 1.115-0.010 0.74 0 1.491 0.010 2.231 0.042 0.75 0.031 1.491 0.063 2.231 0.136 0.459 0.052 0.803 0.459 0.75 0.928-0.042 0.407-0.365 0.709-0.75 0.75-0.75 0.073-1.491 0.115-2.231 0.136-0.75 0.031-1.491 0.042-2.231 0.042-0.375 0-0.74 0-1.115-0.010l-1.115-0.031c-0.74-0.021-1.491-0.052-2.231-0.104-0.479-0.042-0.844-0.459-0.803-0.938 0.031-0.427 0.375-0.771 0.803-0.803z"></path><path d="M39.432 11.393c-0.188-1.063-0.521-2.116-0.99-3.106-0.479-0.99-1.105-1.897-1.835-2.71s-1.564-1.511-2.45-2.106c-0.886-0.594-1.835-1.084-2.794-1.501-1.939-0.813-3.95-1.313-5.973-1.605s-4.055-0.396-6.066-0.365c-2.022 0.042-4.055 0.219-6.066 0.605-2.012 0.396-4.013 1.001-5.889 1.949-0.938 0.479-1.845 1.042-2.679 1.699-0.834 0.667-1.616 1.428-2.272 2.293-0.667 0.855-1.209 1.824-1.605 2.835-0.396 1.021-0.636 2.095-0.74 3.169-0.052 0.532-0.052 1.084-0.042 1.605 0.010 0.532 0.052 1.053 0.125 1.584 0.146 1.053 0.417 2.116 0.844 3.117s1.011 1.939 1.72 2.762c0.709 0.823 1.532 1.532 2.418 2.126 1.772 1.188 3.44 1.824 5.41 2.356 1.803 0.49 3.867 0.782 5.681 0.876 0.146 0.010 0.281 0.073 0.386 0.177 0.459 0.5 0.938 1.074 1.449 1.511 0.667 0.584 1.407 1.126 2.178 1.584 0.761 0.448 1.564 0.803 2.387 1.115 0.865 0.313 2.21 0.605 2.929 0.657 0.698 0.052 0.782-0.479 0.563-0.938-0.229-0.469-0.281-0.552-0.375-0.761s-0.188-0.417-0.271-0.625-0.344-0.844-0.49-1.261c-0.115-0.344-0.292-0.938-0.386-1.407-0.031-0.167 0.083-0.323 0.25-0.344 1.626-0.229 3.242-0.552 4.847-1.032 0.98-0.292 1.939-0.657 2.877-1.094s1.855-0.98 2.7-1.626c0.844-0.646 1.626-1.418 2.272-2.293 0.323-0.438 0.615-0.907 0.865-1.397s0.459-0.99 0.636-1.511c0.344-1.032 0.532-2.106 0.594-3.169 0.021-1.032-0.021-2.106-0.208-3.169zM37.347 14.478c-0.031 0.896-0.167 1.782-0.427 2.616-0.125 0.417-0.292 0.823-0.479 1.22s-0.407 0.771-0.657 1.126c-0.5 0.719-1.115 1.365-1.814 1.928-1.397 1.126-3.106 1.928-4.899 2.522-0.896 0.302-1.814 0.542-2.752 0.75-0.928 0.208-1.876 0.375-2.835 0.511h-0.031c-0.396 0.063-0.709 0.396-0.719 0.813-0.010 0.594 0.083 1.126 0.208 1.626s0.292 0.969 0.469 1.438c0.146 0.375 0.292 0.698 0.542 1.105 0.042 0.073-0.021 0.146-0.104 0.125-1.167-0.365-2.304-0.907-3.461-1.845-1.23-0.99-1.762-1.584-2.814-2.835-0.146-0.177-0.365-0.302-0.615-0.323h-0.031c-1.908-0.188-3.805-0.479-5.629-0.98-1.814-0.5-3.565-1.199-5.055-2.22-0.74-0.511-1.407-1.105-1.97-1.772-0.563-0.678-1.022-1.418-1.355-2.231s-0.552-1.678-0.657-2.564-0.125-1.824-0.031-2.689c0.104-0.876 0.313-1.73 0.646-2.543 0.334-0.803 0.771-1.564 1.324-2.251 1.115-1.386 2.595-2.481 4.232-3.273 0.823-0.396 1.678-0.74 2.564-1.022s1.793-0.511 2.71-0.678c1.845-0.354 3.742-0.511 5.639-0.532 1.907-0.010 3.815 0.073 5.67 0.344 1.866 0.271 3.69 0.709 5.378 1.418 1.689 0.698 3.242 1.668 4.44 2.95 0.594 0.636 1.105 1.355 1.491 2.126s0.667 1.605 0.834 2.481c0.167 0.855 0.219 1.751 0.188 2.658z"></path>
                            </svg>
                            <span v-if="commentCount[member.member] > 0" class="text-xs">{{ commentCount[member.member] }}</span>
                          </div>
                        </div>
                      </td>
                      
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <Transition name="smLoad">
              <CommentWindow
                v-if="selectedCommentMember"
                :memberName="selectedCommentMember"
                :period="periodStartIso"
                @close="selectedCommentMember = null"
                @getCommentCounts="fetchCommentCounts"
              />
            </Transition>
            <Transition name="modalFade">
              <ResourceEdit 
                v-if="editModal"
                :edit-data="editData"
                @close="editData = null, editModal = false"
                @reload="fetchData"
              />
            </Transition>
        </div>
    </div>
</template>
<script lang="ts" setup>
import { useRouter } from 'vue-router';
import CloseIcon from '../Form/CloseIcon.vue';
import { useApi } from '@/composables/api';
import { DateTime, MonthNumbers } from 'luxon';
import { computed, onMounted, ref, watch } from 'vue';
import PeriodRangePicker from './ProjectTabs/Finance/PeriodRangePicker.vue';
import Back from '../Icons/Back.vue';
import { User } from '@/interface/globalInterface';
import PostSearchBar from '../Post/PostSearchBar.vue';
import CommentWindow from './Resource/CommentWindow.vue';
import { useAuthUserStore } from '@/store/auth';
import CommandButton from '../Global/CommandButton.vue';
import ResourceEdit from './Resource/ResourceEdit.vue';
type PeriodCell = { year:number; month:number; period:string; fiscalYear:number }

const router = useRouter()
const api = useApi()
const MAX_RANGE_MONTHS = 12
const currentMonth = DateTime.now().startOf('month')
type ResourceValue = {
  '給料手当数量': number
  '所定労働日数': number
  '給料手当出金': number
  'レコード番号': number
  '部門コード': string 
}
type EditResourceValue = ResourceValue & {
  project: string
  member: string
}
const props = defineProps<{
  userList: User[]
}>()
const keywords = ref('')
const resourceData = ref<Record<string, Record<string, Record<string, ResourceValue>>>>({})
const commentCount = ref<Record<string, number>>({})
const selectedCommentMember = ref<string | null>(null)
const auth = useAuthUserStore()
const loader = ref(true)
const editData = ref<EditResourceValue | null>(null)
const editModal = ref(false)
const editQuantity = (data: ResourceValue, member: string, project: string) => {
  editData.value = {
    ...data,
    project,
    member,
  }
  editModal.value = true
}
const monthDiff = (a: DateTime, b: DateTime) => {
  return (b.year - a.year) * 12 + (b.month - a.month)
}
const normalizeRange = (rawStart: DateTime, rawEnd: DateTime) => {
  let start = rawStart.startOf('month')
  let end = rawEnd.startOf('month')

  if (end < start) {
    const tmp = start
    start = end
    end = tmp
  }

  const monthsApart = monthDiff(start, end)
  if (monthsApart > MAX_RANGE_MONTHS - 1) {
    end = start.plus({ months: MAX_RANGE_MONTHS - 1 })
  }

  return { start, end }
}

const periodStart = ref<DateTime>(currentMonth)
const periodEnd = ref<DateTime>(currentMonth)

const periodStartIso = computed(() => periodStart.value.toFormat('yyyy-MM'))
const periodEndIso = computed(() => periodEnd.value.toFormat('yyyy-MM'))

const normalizedRange = computed(() => normalizeRange(periodStart.value, periodEnd.value))
const monthCount = computed(() =>
  Math.round(normalizedRange.value.end.diff(normalizedRange.value.start, 'months').months ?? 0) + 1
)
const managementAccounts = computed(() => {
  return auth.activeUser.id === 608 || auth.activeUser.id === 610 
})
const showComment = computed(() => monthCount.value === 1 && managementAccounts.value)
const pad2 = (n:number) => String(n).padStart(2, '0')
const periodKey = (y:number, m:number) => `${y}-${pad2(m)}`
const fiscalYearFrom = (y:number, m:number) => (m >= 3 ? y : y - 1)
const generatePeriodRange = (start: DateTime, end: DateTime): PeriodCell[] => {
  const out: PeriodCell[] = []
  let cursor = start.startOf('month')
  const stop = end.startOf('month')
  while (cursor <= stop) {
    out.push({
      year: cursor.year,
      month: cursor.month,
      period: periodKey(cursor.year, cursor.month),
      fiscalYear: fiscalYearFrom(cursor.year, cursor.month),
    })
    cursor = cursor.plus({ months: 1 })
  }
  return out
}

const periods = computed<PeriodCell[]>(() =>
  generatePeriodRange(normalizedRange.value.start, normalizedRange.value.end)
)

const resourceRows = computed(() =>
  Object.entries(resourceData.value || {}).map(([member, projects]) => ({
    member,
    projects: Object.entries(projects || {}).map(([project, periods]) => ({
      project,
      periods: periods || {},
    })),
  }))
)
const searchResults = computed(() => {
  const keyword = keywords.value.trim().toLowerCase()
  if (!keyword) {
    return resourceRows.value
  }

  return resourceRows.value.reduce((acc, member) => {
    const memberName = String(member.member ?? '').toLowerCase()
    if (memberName.includes(keyword)) {
      acc.push(member)
      return acc
    }

    const projects = member.projects.filter((project) =>
      String(project.project ?? '').toLowerCase().includes(keyword)
    )
    if (projects.length) {
      acc.push({ ...member, projects })
    }
    return acc
  }, [] as typeof resourceRows.value)
})
const intervalPayload = computed(() => ({
  startYear: normalizedRange.value.start.year,
  startMonth: normalizedRange.value.start.month as MonthNumbers,
  endYear: normalizedRange.value.end.year,
  endMonth: normalizedRange.value.end.month as MonthNumbers,
}))
const fetchData = async() => {
    loader.value = true
    const res = await api.post('/get_resources_kintone', {interval: intervalPayload.value}) 
    resourceData.value = res.data?.data ?? res.data ?? {}
    loader.value = false
}
const fetchCommentCounts = async () => {
  if (!showComment.value) {
    commentCount.value = {}
    return
  }
  const members = resourceRows.value.map(row => row.member)
  if (!members.length) {
    commentCount.value = {}
    return
  }
  const data = await api.post('/get_resource_comment_counts', {
    member_names: members,
    period: periodStartIso.value,
  })
  commentCount.value = data ?? {}
}
const selectMemberComment = (memberName: string) => {
  if (!showComment.value) return
  selectedCommentMember.value = memberName
}
const formatNumber = (value: unknown) => {
  const num = typeof value === 'number' ? value : Number(value)
  return Number.isFinite(num) ? num.toLocaleString('ja-JP') : ''
}
const totalQuantityValue = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) =>
  projects.reduce((sum, project) => {
    return sum + (project.periods?.[period]?.['給料手当数量'] ?? 0)
  }, 0)

const getWorkingDaysValue = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  for (const project of projects) {
    const raw = project.periods?.[period]?.['所定労働日数']
    if (raw === null || raw === undefined) {
      continue
    }
    const value = typeof raw === 'number' ? raw : Number(raw)
    if (Number.isFinite(value)) {
      return value
    }
  }
  return null
}
const getWithDrawalValue = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  for (const project of projects) {
    const raw = project.periods?.[period]?.['給料手当出金']
    if (raw === null || raw === undefined) {
      continue
    }
    const value = typeof raw === 'number' ? raw : Number(raw)
    if (Number.isFinite(value)) {
      return value
    }
  }
  return null
}
const totalQuantity = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => formatNumber(totalQuantityValue(projects, period))

const totalWorkingDays = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  const value = getWorkingDaysValue(projects, period)
  return value === null ? '' : formatNumber(value)
}
const totalWithDrawal = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  const value = getWithDrawalValue(projects, period)
  return value === null ? '' : formatNumber(value)
}
const remainingQuantity = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  const workingDays = getWorkingDaysValue(projects, period)
  if (workingDays === null) {
    return ''
  }
  const remaining = workingDays - totalQuantityValue(projects, period)
  return formatNumber(remaining)
}
const parsePeriod = (value: string): DateTime => {
  const dt = DateTime.fromFormat(`${value}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
  return dt.isValid ? dt : DateTime.now().startOf('month')
}
const applyRange = (start: DateTime, end: DateTime) => {
  const normalized = normalizeRange(start, end)
  periodStart.value = normalized.start
  periodEnd.value = normalized.end
}
const handleRangeChange = ({ start, end }: { start: string; end: string }) => {
  const startDt = parsePeriod(start)
  const endDt = parsePeriod(end)
  applyRange(startDt, endDt)
}
const shiftRange = (months: number) => {
  applyRange(periodStart.value.plus({ months }), periodEnd.value.plus({ months }))
}
watch([periodStartIso, periodEndIso], async () => {
    await fetchData()
    await fetchCommentCounts()
})
watch(showComment, (value) => {
    if (!value) {
        selectedCommentMember.value = null
    }
})
onMounted(async () => {
    await fetchData()
    await fetchCommentCounts()
})
</script>
<style scoped lang="scss">
table {
    box-sizing: border-box !important;
    --first-col-width: 150px;
    --second-col-width: 75px;
    width: max-content;
    min-width: 100%;
    border-collapse: separate;
    font-size: 13px;
    line-height: 1.5;
    white-space: nowrap;
    thead {
        position: sticky;
        top: 0;
        z-index: 6;
        
        th {
            padding: 10px;
            font-weight: 500;
            text-align: left;
            background-color: var(--bg3);
            border-bottom: 1px solid var(--calendarBorder);
        }
        th:nth-of-type(5n + 5) {
            border-right: 1px solid var(--calendarBorder);
        }
        th.sticky-left,
        th.sticky-right {
            z-index: 6;
        }
    }

    tbody {
        tr {
            
            td {
                padding: 10px;
                border-bottom: 1px solid var(--calendarBorder);
                font-weight: 400;
                text-align: left;
                border-left: none;
                box-sizing: border-box !important;
                span {
                    display: block;
                }
            }
            td.sticky-left,
            td.sticky-right {
                background-color: var(--background-color);
            }
            
        }
    }
}
.sticky-left {
    position: sticky;
    left: 0;
    z-index: 2;
}
.sticky-right {
    position: sticky;
    right: 0;
    z-index: 2;
}
.comment-cell {
    min-width: 80px;
    max-width: 80px;
}
td[data-cell=right-border], th[data-cell=right-border] {
    border-right: 1px solid var(--calendarBorder);
}
.top-border {
    border-top: solid thin var(--calendarBorder);
}
.first-col {
    left: 0;
    min-width: var(--first-col-width);
    max-width: var(--first-col-width);
    border-left: solid thin var(--calendarBorder);
    border-right: solid thin var(--calendarBorder);
    z-index: 4;
}

.second-col {
    left: calc(var(--first-col-width) + 22px);
    min-width: var(--second-col-width);
    border-right: solid thin var(--calendarBorder);
    z-index: 3;
}
@media screen and (max-width: 959px) {
  table {
      min-width: 100%;
      thead {
          display: none;
      }

      tbody {
          tr {
              display: block;
              margin-bottom: -1px;

              td {
                  display: block;
                  border-left: solid thin var(--calendarBorder);
                  border-right: solid thin var(--calendarBorder);
                  border-bottom: none;

              }

              td:last-of-type {
                  border-bottom: solid thin var(--calendarBorder);
              }

              td:first-of-type {
                  border-top: solid thin var(--calendarBorder);
              }

          }
      }
  }
  tr[data-cell=last-row] {
      margin-bottom: 20px;
  }
  table tbody tr td.sticky-left:first-of-type {
      background-color: var(--bg3);
  }
  .sticky-left,
  .sticky-right {
      position: static;
      left: auto;
      right: auto;
      box-shadow: none;
      background: inherit;
  }
  .first-col,
  .second-col,
  .comment-cell {
      min-width: auto;
      max-width: none;
  }
  .inner-col {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 5px;
      width: 100%;
  }
}
</style>
