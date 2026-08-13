<template>
    <div v-if="auth.hasPrivilage" class="overlay">
        <div class="projectModalInner" style="width: 100%;height: 100%;">
            
            <div class="relative h-full">
              <div class="sticky top-0 bg-[var(--background-color)] items-center z-[7] p-5 gap-[10px] flex justify-between flex-wrap">
                <div class="flex gap-5">
                  <div class="cursor-pointer fill-[var(--primary-color)]" @click="router.back()">
                    <svg version="1.1" width="15" height="15" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                  </div>
                  <div>
                      <p>リソース</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 relative ml-auto flex-wrap md:flex-nowrap">
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
              <div class="overflow-auto h-[calc(100%-74px)]">
                <div class="cal-month-loader" style="height: 100%; top: 0;opacity: 0.6;" v-if="loader">
                      <div id="loaderMini">
                          <div class="spinner-mini"
                              style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
                      </div>
                  </div>
                <table>
                  <thead>
                    <tr>
                      <th :rowspan="2" class="sticky-left first-col top-border">
                        <div class="relative">
                          <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="toggleFilterMenu('memberFilter')">
                            メンバー名
                            <Filter :filtered="selectedMembers.length > 0" class="filter-icon" size="12"/>
                          </div>
                          <Transition name="slidePop">
                            <ResourceSort 
                              v-if="menu.parent == 'memberFilter'"
                              :options="memberOptions"
                              v-model:selected="selectedMembers"
                              id="memberFilter"
                              custom-place-holder="メンバー検索"
                              :searchable="true"
                            />
                          </Transition>
                        </div>
                        
                      </th>
                      <th :rowspan="2" class="sticky-left second-col top-border">
                        <div class="relative">
                          <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="toggleFilterMenu('projectFilter')">
                            プロジェクト名
                            <Filter :filtered="selectedProjects.length > 0" class="filter-icon" size="12"/>
                          </div>
                          <Transition name="slidePop">
                              <ResourceSort
                                v-if="menu.parent == 'projectFilter'" 
                                :options="projectOptions"
                                v-model:selected="selectedProjects"
                                id="projectFilter"
                                customPlaceHolder="プロジェクト検索"
                                :searchable="true" 
                              />
                          </Transition>
                        </div>  
                      </th>
                      <th 
                          v-for="(p, i) in periods"
                          :key="p.period"
                          colspan="6"
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
                            <th data-cell="right-border">
                              <div class="relative">
                                <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="toggleFilterMenu('empTypeFilter')">
                                  雇用形態
                                  <Filter :filtered="selectedEmpFilter.length > 0" v-if="allowRemainingFilter" class="filter-icon" size="12"/>
                                </div>
                                <Transition name="slidePop">
                                  <ResourceSort 
                                    v-if="menu.parent == 'empTypeFilter'"
                                    id="empTypeFilter"
                                    :options="empFilterOptions"
                                    v-model:selected="selectedEmpFilter"
                                  />
                                  
                                </Transition>
                              </div>  
                            </th>
                            <th data-cell="right-border">給料手当数量</th>
                            <th data-cell="right-border">所定労働日数</th>
                            <th data-cell="right-border">給料手当出金</th>
                            <th data-cell="right-border">数量合計</th>
                            <th>
                              <div class="relative">
                                <div class="cursor-pointer flex items-center gap-[5px]" @click.stop="toggleFilterMenu('minusPlusFilter')">
                                  数量残り
                                  <Filter :filtered="selectedFilter.length > 0" v-if="allowRemainingFilter" class="filter-icon" size="12"/>
                                </div>
                                <Transition name="slidePop">
                                  <ResourceSort 
                                    v-if="menu.parent == 'minusPlusFilter'"
                                    id="minusPlusFilter"
                                    :options="filterOptions"
                                    v-model:selected="selectedFilter"
                                  />
                                </Transition>
                              </div>
                              
                              
                            </th>
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
                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                            <span class="mobile">雇用形態</span>
                            {{ employmentType(member.member, p.period) }}
                          </div>
                        </td>
                        <td data-cell="right-border">
                          <div class="inner-col">
                            <span class="mobile">給料手当数量</span>
                            <div class="flex items-center">
                              <span class="flex-1 text-left tabular-nums">
                                {{ formatNumber(project.periods[p.period]?.['給料手当数量']) }}
                              </span>

                              <div v-if="auth.hasPrivilage && project.project && formatNumber(project.periods[p.period]?.['給料手当数量'])" class="ml-4 shrink-0">
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
                            {{ totalWorkingDays(member.member, p.period) }}
                          </div>
                        </td>
                        <td data-cell="right-border" v-if="projectIndex === 0" :rowspan="member.projects.length">
                          <div class="inner-col">
                             <span class="mobile">給料手当出金</span>
                             {{ totalWithDrawal(member.member, p.period) }}
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
                            {{ remainingQuantity(member.member, member.projects, p.period) }}
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
    <div v-else class="bg-[var(--background-color)] h-full text-center justify-center flex items-center flex-col">
        <p>アクセス権限ありません。</p>
        <router-link class="l-button" style="margin: 30px 0 70px 0;" :to="{ name: 'project' }">プロジェクトへ戻る</router-link>
    </div>
</template>
<script lang="ts" setup>
import { useRoute, useRouter } from 'vue-router';
import CloseIcon from '../Form/CloseIcon.vue';
import { useApi } from '@/composables/api';
import { DateTime, MonthNumbers } from 'luxon';
import { computed, onMounted, ref, watch } from 'vue';
import PeriodRangePicker from './ProjectTabs/Finance/PeriodRangePicker.vue';
import Back from '../Icons/Back.vue';
import { User } from '@/interface/globalInterface';
import CommentWindow from './Resource/CommentWindow.vue';
import { useAuthUserStore } from '@/store/auth';
import CommandButton from '../Global/CommandButton.vue';
import ResourceEdit from './Resource/ResourceEdit.vue';
import Filter from '../Icons/Filter.vue';
import { useMenuStore } from '@/store/menu';
import ResourceSort from './Resource/ResourceSort.vue';
type PeriodCell = { year:number; month:number; period:string; fiscalYear:number }

const router = useRouter()
const route = useRoute()
const api = useApi()
const MAX_RANGE_MONTHS = 12
const currentMonth = DateTime.now().startOf('month')
type ResourceValue = {
  '給料手当数量': number
  '所定労働日数': number
  '給料手当出金': number
  'レコード番号': number
  '部門コード': string 
  '雇用形態': string
}
type MemberMetaValue = {
  '雇用形態': string
  '所定労働日数': number
  '給料手当出金': number
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
const memberMeta = ref<Record<string, Record<string, MemberMetaValue>>>({})
const commentCount = ref<Record<string, number>>({})
const selectedCommentMember = ref<string | null>(null)
const auth = useAuthUserStore()
const menu = useMenuStore()
const loader = ref(true)
const editData = ref<EditResourceValue | null>(null)
const editModal = ref(false)
const filterOptions = ['－', '0', '＋']
const selectedFilter = ref<string[]>([])
const selectedEmpFilter = ref<string[]>([])
const empFilterOptions = ref<string[]>([])
const selectedProjects = ref<string[]>([])
const selectedMembers = ref<string[]>([])
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

// ダッシュボードのリマインドからの遷移用。ref の初期値として読むことで、
// watch が余計に発火して二重フェッチになるのを避ける。
const initialPeriod = (() => {
  const q = route.query.period
  if (typeof q !== 'string') return null
  const dt = DateTime.fromFormat(`${q}-01`, 'yyyy-MM-dd', { zone: 'Asia/Tokyo' })
  return dt.isValid ? dt : null
})()
const periodStart = ref<DateTime>(initialPeriod ?? currentMonth)
const periodEnd = ref<DateTime>(initialPeriod ?? currentMonth)

const periodStartIso = computed(() => periodStart.value.toFormat('yyyy-MM'))
const periodEndIso = computed(() => periodEnd.value.toFormat('yyyy-MM'))

const normalizedRange = computed(() => normalizeRange(periodStart.value, periodEnd.value))
const monthCount = computed(() =>
  Math.round(normalizedRange.value.end.diff(normalizedRange.value.start, 'months').months ?? 0) + 1
)
const allowRemainingFilter = computed(() => monthCount.value === 1)
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
const memberOptions = computed(() => {
  return resourceRows.value.map((row) => row.member)
})
const projectOptions = computed(() => {
  const projectsSet = new Set<string>()
  resourceRows.value.forEach((row) => {
    row.projects.forEach((project) => {
      projectsSet.add(project.project)
    })
  })
  return Array.from(projectsSet)
})
const searchResults = computed(() => {
  let results = resourceRows.value
  const notFilterable = selectedFilter.value.length === 0 &&
    selectedEmpFilter.value.length === 0 &&
    selectedProjects.value.length === 0 &&
    selectedMembers.value.length === 0
  const memberOrProject = selectedMembers.value.length > 0 || selectedProjects.value.length > 0
  if ((!allowRemainingFilter.value && !memberOrProject) || notFilterable) {
    return results
  }

  const period = periodStartIso.value
  return results
    .map((m) => ({
      ...m,
      projects:
        selectedProjects.value.length === 0
          ? m.projects
          : m.projects.filter((p) => selectedProjects.value.includes(p.project)),
    }))
    .filter((m) =>
      matchesRemainingFilter(m.member, m.projects, period) &&
      matchesEmpTypeFilter(m.member, period) &&
      matchesProjectFilter(m.projects) &&
      matchesMemberFilter(m.member)
    )
})
const matchesMemberFilter = (member: string) => {
  const selected = selectedMembers.value
  if (selected.length === 0) return true
  return selected.includes(member)
}
const matchesProjectFilter = (projects: Array<{project: string}>) => {
  const selected = selectedProjects.value
  if (selected.length === 0) return true
  return projects.some((p) => selected.includes(p.project))
}

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
    memberMeta.value = res.member_meta ?? {}
    empFilterOptions.value = res.options ?? []
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

const getWorkingDaysValue = (memberName: string, period: string) => {
  const raw = memberMeta.value?.[memberName]?.[period]?.['所定労働日数']
  if (raw === null || raw === undefined) {
    return null
  }
  const value = typeof raw === 'number' ? raw : Number(raw)
  return Number.isFinite(value) ? value : null
}
const getWithDrawalValue = (memberName: string, period: string) => {
  const raw = memberMeta.value?.[memberName]?.[period]?.['給料手当出金']
  if (raw === null || raw === undefined) {
    return null
  }
  const value = typeof raw === 'number' ? raw : Number(raw)
  return Number.isFinite(value) ? value : null
}
const getEmploymentValue = (memberName: string, period: string) => {
  const raw = memberMeta.value?.[memberName]?.[period]?.['雇用形態']
  if (raw === null || raw === undefined) {
    return null
  }
  return raw
}
const employmentType = (memberName: string, period: string) => {
  const value = getEmploymentValue(memberName, period)
  return value === null ? '' : value
}
const totalQuantity = (
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => formatNumber(totalQuantityValue(projects, period))

const totalWorkingDays = (memberName: string, period: string) => {
  const value = getWorkingDaysValue(memberName, period)
  return value === null ? '' : formatNumber(value)
}
const totalWithDrawal = (memberName: string, period: string) => {
  const value = getWithDrawalValue(memberName, period)
  return value === null ? '' : formatNumber(value)
}
const remainingQuantity = (
  memberName: string,
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  const remaining = remainingQuantityValue(memberName, projects, period)
  return remaining === null ? '' : formatNumber(remaining)
}
const remainingQuantityValue = (
  memberName: string,
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  const workingDays = getWorkingDaysValue(memberName, period)
  if (workingDays === null) {
    return null
  }
  return workingDays - totalQuantityValue(projects, period)
}
const matchesRemainingFilter = (
  memberName: string,
  projects: Array<{ periods: Record<string, ResourceValue> }>,
  period: string
) => {
  if (!allowRemainingFilter.value || selectedFilter.value.length === 0) {
    return true
  }
  const remaining = remainingQuantityValue(memberName, projects, period)
  if (remaining === null) {
    return false
  }
  return selectedFilter.value.some((option) => {
    if (option === '－') return remaining < 0
    if (option === '0') return remaining === 0
    if (option === '＋') return remaining > 0
    return false
  })
}
const matchesEmpTypeFilter = (memberName: string, period: string) => {
  if (!allowRemainingFilter.value || selectedEmpFilter.value.length === 0) {
    return true
  }
  const empType = getEmploymentValue(memberName, period)
  if (empType === null) {
    return false
  }
  return selectedEmpFilter.value.includes(empType)
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
const toggleFilterMenu = (val: string) => {
  const memberOrProject = val === 'projectFilter' || val === 'memberFilter'
  if (!allowRemainingFilter.value && !memberOrProject) return
  menu.setMenu({parent: val})
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
watch(allowRemainingFilter, (value) => {
    if (!value) {
        selectedFilter.value = []
        selectedEmpFilter.value = []
        if (menu.parent === 'minusPlusFilter' || menu.parent === 'empTypeFilter') {
            menu.close()
        }
    }
})
onMounted(async () => {
    await fetchData()
    await fetchCommentCounts()
    // comment_member が付いていればその要員のコメントを自動で開く（Finance の comment_period と同じ形）
    const member = route.query.comment_member
    if (typeof member === 'string' && member && showComment.value) {
        selectedCommentMember.value = member
    }
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
        th:nth-of-type(6n + 6) {
            border-right: 1px solid var(--calendarBorder);
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
.filter-icon {
  fill: var(--primary-color);
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
