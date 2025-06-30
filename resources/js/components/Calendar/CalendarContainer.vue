<template>

    <div id="calendarOuterContainer" class="post-root">
        <div class="calendar-root-header" v-if="viewType !== 4">
            <HamBurger v-if="responsive.mobile"/>
            <div class="calendar-search-wrap" id="calendarSearchResultWindow" >
                <PostSearchBar 
                    @searchStart="searchStart"  
                    @focus="menu.setMenu( {name: 'calendarSearchResultWindow', id: 26})"
                    :searching="searching"
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`スケジュールを検索`"
                />
                <SearchResult 
                    v-if="searchKey.length && menu.id == 26 && menu.name == 'calendarSearchResultWindow'" 
                    :searchResult="searchResult" 
                    :searchFetch="searchFetch"
                    @jumpToRecord="jumpToRecord"                    
                />                
            </div>
            <div class="calendar-bar-container" style="">
                <TopBar
                    @jumpToday="jumpToToday"
                    @updated="userUpdated"
                    @setActiveMembers="val => activeMembers = val"
                    :selectedYear="selectedYear"
                    :selectedMonth="selectedMonth"
                    @refresh="getCalendar(DateTime.now().toISOTime(), 'updated')"

                />
                <div style="margin: 0 15px 0 auto;">
                    <DayPicker
                        v-if="viewType == 3"
                        v-model:month="selectedMonth"
                        v-model:year="selectedYear"
                        v-model:day="selectedDay"
                        right="0"
                        @setDate="setDate"
                        ref="daypicker" 
                    />
                    <MonthPickerNew 
                        v-else
                        v-model:month="selectedMonth"
                        v-model:year="selectedYear"
                        right="0"
                        @setDate="setDate"
                        ref="monthpicker"
                    />
                </div>
            </div>          
        </div>
        <Transition :name="`${jumpTo}ShiftPop`">
            <ShiftButton @click="shiftToMonth(jumpTo)" v-if="jumpTo" :jumpTo="jumpTo" @close="jumpTo = null" :viewType="viewType"/>
        </Transition>
        <NormalHourLayout
            v-if="viewType == 0"
            ref="normalHourLayoutRef"
            :daysOfMonth="daysOfMonth"
            :records="recordList"
            :initialLoader="initialLoader"
            :holidays="holidays"
            @scroll="scrollListen"
            @releaseScroll="appendLock = false"
            @create="createAtTime"
            @setListView="setListView"
        />
        <NormalMonthLayout 
            v-if="viewType == 1"
            ref="normalMonthLayoutRef"
            :records="recordList"
            :selected-year="selectedYear"
            :selected-month="selectedMonth"
            :initialLoader="initialLoader"
            :active-month="activeMonth"
            :active-year="activeYear"
            :holidays="holidays"
            @slided="slided"
            @fromMonth="fromMonth"
            @addRecord="addRecord"
            @jumpToDate="jumpToDate"
            @scroll="scrollListen"            
            @create="createAtTime"
        />
        <MemberMonthLayout 
            v-if="viewType == 2"
            ref="memberMonthLayoutRef"
            :records="recordList"
            :selected-year="selectedYear"
            :selected-month="selectedMonth"
            :initialLoader="initialLoader"
            :active-month="activeMonth"
            :active-year="activeYear"
            :holidays="holidays"
            :activeMembers="activeMembers"
            :appendLock="appendLock"
            @slided="slided"
            @fromMonth="fromMonth"
            @addRecord="addRecord"
            @jumpToDate="jumpToDate"
            @setListView="setListView"
            @create="createAtTime"
            @resetFastCreate="resetFastCreate"
            @scrollHorizontal="scrollListenHorizontal"
        />
        <MemberHourLayout 
            v-if="viewType == 3"
            :records="dayRecords"
            :activeMembers="activeMembers"
            :selectedDate="selectedDate"
            :initialLoader="initialLoader"
            @create="createAtTime"
            @resetFastCreate="resetFastCreate"
            @scrollHorizontal="scrollListenHorizontal"
            ref="memberHourLayoutRef"
        />
        <Transition name="modalFade">
            <div id="calendarViewMenu" class="boxMenu boardMenuIcon viewSwitchMenu" v-if="menu.name == 'calendarViewMenu' && menu.id == 79">   
                <div v-for="menuItem in viewMenu" class="boxMenuItems cursor-pointer" @click.stop="switchView(menuItem.value)">
                    {{ menuItem.title }}
                    <span v-if="viewType == menuItem.value">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                            <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </Transition>
        <Transition name="modalFade">
            <TempReserve 
                v-if="tempReserveWindow" 
                @close="closeCreate"
            />
        </Transition>
        <FloatButton 
            @click.stop="menu.setMenu( {name : 'calendarViewMenu', id: 79})" 
            :style="{zIndex: initialLoader ? 41 : 7}"
            :order="3"
        >
            <template #icon>
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 32" width="18" height="16" fill="black">
                    <path d="M35.556 27.791v-1.812l-0.011-2.902-0.057-11.584-0.011-2.89-0.011-1.445v-0.195c0-0.080 0-0.172-0.011-0.252-0.011-0.172-0.034-0.333-0.069-0.493-0.069-0.333-0.184-0.642-0.333-0.941-0.298-0.596-0.757-1.101-1.296-1.48-0.551-0.367-1.193-0.596-1.858-0.654-0.161-0.011-0.344-0.011-0.447-0.011h-1.090l-1.239 0.011c-0.080 0-0.138-0.069-0.138-0.138v-0.631c0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.080 0-0.161-0.011-0.241-0.011h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.539c0 0.115-0.103 0.218-0.218 0.206-0.814-0.057-9.715-0.057-10.586-0.023-0.046 0-0.080-0.034-0.080-0.069 0-0.172 0.011-0.585 0-0.642 0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.057-0.011-0.138-0.023-0.218-0.023h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.642c0 0.103-0.080 0.183-0.183 0.183l-2.707-0.023c-0.654 0.023-1.308 0.218-1.87 0.562s-1.032 0.837-1.353 1.411c-0.161 0.287-0.287 0.596-0.367 0.918-0.046 0.161-0.069 0.321-0.092 0.493-0.011 0.080-0.011 0.161-0.023 0.252v1.675l-0.023 2.902c-0.023 3.865-0.057 7.719-0.069 11.584l-0.011 2.89v2.202c0.011 0.344 0.057 0.688 0.149 1.009 0.183 0.665 0.551 1.262 1.032 1.743s1.090 0.837 1.755 1.021c0.333 0.092 0.677 0.138 1.021 0.138h27.733c0.080 0 0.172-0.011 0.252-0.011 0.172-0.023 0.344-0.046 0.505-0.080 0.677-0.149 1.296-0.493 1.801-0.941 0.505-0.459 0.895-1.044 1.101-1.698 0.115-0.321 0.172-0.665 0.195-1.009 0-0.080 0-0.172 0.011-0.252v-0.195zM25.359 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.057-0.172 0.126-0.241s0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.183 0.493-0.459 0.493-0.195 0-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0.011-0.011 0.011-0.069zM8.786 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.046-0.172 0.126-0.241 0.069-0.069 0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.218 0.493-0.459 0.493s-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0-0.011 0.011-0.069zM33.308 7.157v1.445l-0.011 2.89-0.057 11.584-0.011 2.902v2.099c-0.011 0.138-0.034 0.275-0.080 0.413-0.092 0.264-0.252 0.505-0.459 0.7-0.218 0.183-0.47 0.321-0.734 0.378-0.057 0.011-0.115 0.023-0.183 0.034-0.034 0-0.092 0.011-0.138 0.011h-27.642c-0.138 0-0.275-0.011-0.413-0.057-0.264-0.069-0.516-0.218-0.723-0.413s-0.356-0.447-0.436-0.711c-0.046-0.138-0.057-0.275-0.069-0.413v-2.145l-0.011-2.89c-0.011-3.865-0.046-7.719-0.069-11.584l-0.034-2.913-0.011-1.445v-0.126c0-0.034 0-0.080 0.011-0.115 0.011-0.069 0.023-0.149 0.034-0.218 0.034-0.149 0.092-0.287 0.161-0.424 0.149-0.264 0.356-0.505 0.619-0.665 0.264-0.172 0.551-0.264 0.86-0.287l2.707-0.034c0.069 0 0.115 0.046 0.115 0.115l0.011 0.688c0 0.034 0 0.126 0.011 0.195 0 0.080 0.011 0.149 0.023 0.229 0.046 0.31 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.275-0.011 0.551-0.011c0.642 0 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.023-0.447c0-0.069 0.057-0.126 0.138-0.126 0.872 0.034 9.864 0.034 10.724-0.034 0.057 0 0.103 0.034 0.103 0.092 0 0.241 0.023 0.849 0.046 1.067 0.034 0.275 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.367 0 0.551-0.011c0.665-0.034 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.011-0.275 0.011-0.218c0-0.080 0.069-0.138 0.138-0.138l1.296 0.011h0.723c0.229 0 0.528 0 0.631 0.011 0.298 0.023 0.585 0.138 0.837 0.31s0.447 0.413 0.585 0.677c0.069 0.138 0.115 0.275 0.138 0.424 0.011 0.069 0.023 0.149 0.023 0.218v0.31z"></path> <path d="M30.509 10.598c-2.11-0.069-4.221-0.103-6.331-0.126-1.055-0.011-2.11-0.023-3.166-0.023l-3.166-0.011-3.166 0.011-3.166 0.023-4.748 0.069-1.583 0.034c-0.413 0.011-0.757 0.344-0.768 0.768-0.011 0.436 0.333 0.791 0.768 0.803l1.583 0.034 4.748 0.069 3.166 0.023 3.166 0.011 3.166-0.011c1.055 0 2.11-0.023 3.166-0.023 2.11-0.023 4.221-0.057 6.331-0.126 0.39-0.011 0.723-0.333 0.734-0.723 0.011-0.436-0.31-0.791-0.734-0.803zM15.771 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM22.366 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM29.075 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.057-0.952-0.505-1.135zM9.049 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.046-0.952-0.505-1.124zM15.771 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM22.366 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM29.075 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.057-0.952-0.505-1.124zM9.049 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.046-0.963-0.505-1.135zM15.771 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.963-0.505-1.135z"></path>
                </svg>
            </template>
        </FloatButton>
        <FloatButton
            :order="2"
            @action="tempReserveWindow = true"
            :style="{zIndex: initialLoader ? 41 : 7}"
            title="空き時間確認"
        >
            <template #icon>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="black" viewBox="0 0 31.18 31.28"> 
                    <path d="M12.51,4.95l.78,7.48c.08.8-.5,1.52-1.3,1.61-.91.11-1.71-.7-1.61-1.61,0,0,.78-7.48.78-7.48.1-.81,1.26-.81,1.36,0h0Z"/>
                    <path d="M13.12,11.86l3.63,4.65c.48.64-.31,1.44-.95.97,0,0-4.72-3.54-4.72-3.54-.65-.49-.78-1.41-.29-2.05.56-.77,1.76-.78,2.33-.02h0Z"/>
                    <path d="M11.9,23.89c-3.18,0-6.17-1.24-8.42-3.5C-1.16,15.74-1.16,8.16,3.48,3.5,5.73,1.24,8.72,0,11.9,0c0,0,0,0,0,0,3.18,0,6.17,1.24,8.42,3.5h0c4.64,4.66,4.64,12.24,0,16.89-2.25,2.26-5.24,3.5-8.42,3.5ZM11.9,2.51c-2.51,0-4.87.98-6.65,2.76-3.67,3.68-3.67,9.67,0,13.35,1.78,1.78,4.14,2.76,6.65,2.76s4.87-.98,6.65-2.76c3.67-3.68,3.67-9.67,0-13.35-1.78-1.78-4.14-2.76-6.65-2.76Z"/>
                    <path d="M20.24,18.7c.8.54,1.52,1.19,2.12,1.94.2.25.39.51.55.81.52,1-.57,2.06-1.55,1.54-1.12-.65-2.01-1.62-2.73-2.68-.65-1.06.57-2.25,1.61-1.61h0Z"/>
                    <path d="M21.32,21.82c.09-.1.31-.34.4-.43,1.08-1.19,3.07-1.21,4.19-.04,1.41,1.35,2.81,2.71,4.2,4.08.95.83,1.36,2.22.89,3.42-.19.54-.57,1-.98,1.38-1.19,1.34-3.28,1.41-4.53.11-.17-.17-.64-.66-.82-.84-1.06-1.09-2.21-2.28-3.25-3.37-.2-.2-.53-.57-.68-.87-.61-1.11-.37-2.6.55-3.47,0,0,.03.03.03.03h0ZM22.81,23.31c-.21.24-.24.6-.08.86.06.1.07.1.31.33.75.72,1.5,1.44,2.25,2.17.37.36,1.32,1.29,1.67,1.64l.28.27.03.03h.01c.2.21.57.22.77.01.11-.11.34-.34.44-.45.18-.17.22-.46.1-.68-.05-.09-.04-.08-.28-.32-1.27-1.3-2.55-2.61-3.8-3.93-.08-.08-.31-.34-.4-.41-.25-.21-.68-.2-.92.04,0,0-.14.13-.14.13l-.29.26.03.03h0Z"/>
                </svg>
            </template>
        </FloatButton>

        <FloatButton title="新規作成" @click="createWindow = true" :style="{zIndex: initialLoader ? 41 : 7}" :order="1">
            <template #icon>
                <AddIcon fill="black"/>
            </template>
        </FloatButton>

        <FastCreateButton 
            :data="fastCreate" 
            @close="resetFastCreate"
            v-if="fastCreate.time && menu.name == 'scheduleCreateFast' && menu.id == 896"
            @click="fastCreateOpen"
        />
        <Transition name="modalFade">                              
            <CalendarCreate 
                v-if="createWindow"   
                :editTarget="editTarget"   
                :preSelectedDepartment="preSelectedDepartment"  
                :preSelected="preSelected"
                :edit_all_record="edit_all_record"
                :preSelectedMembers="preSelectedMembers"
                @close="closeCreate"      
            />            
        </Transition> 
        <Transition name="modalFade">
            <MeetingSummary :calendar-record="summeryViewing" v-if="summeryViewing" @close="setSummaryViewing(null)"/>
        </Transition>
        <DragItem v-if="draggingCalendar"/>
    </div>        
</template>
<script setup lang="ts">
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue'
import NormalHourLayout from './NormalHour/NormalHourLayout.vue';
import { nextTick, ref, computed, onMounted, provide, onUnmounted, inject } from 'vue'
import CalendarCreate from './CalendarCreate.vue';
import TopBar from './TopBar.vue'
import NormalMonthLayout from './NormalMonth/NormalMonthLayout.vue'
import MemberMonthLayout from './MemberMonth/MemberMonthLayout.vue'
import MemberHourLayout from './MemberHour/MemberHourLayout.vue'
import DayPicker from './DayPicker.vue';
import SearchResult from './SearchResult.vue';
import DragItem from './DragItem.vue';
import ShiftButton from './ShiftButton.vue';
import holiday_jp from '@holiday-jp/holiday_jp'
import FastCreateButton from './FastCreateButton.vue'
import { useRoute } from 'vue-router'
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import { useResponsive } from '@/store/responsive';
import { useSharingDataStore } from '@/store/sharingData'
import { useTempRecord } from '@/store/tempRecord';
import MeetingSummary from './MeetingSummary.vue';
import { User } from '@/interface/globalInterface';
import { DateTime, DayNumbers, MonthNumbers } from 'luxon';
import { CalendarRecord, FacilityItem, FastCreateData, NormalHourDay } from '@/interface/calendarInterface';
import MonthPickerNew from '../Global/MonthPickerNew.vue';
import { useCalendar } from '@/composables/calendar';
import TempReserve from './TempReserve.vue';
import FloatButton from '../Global/FloatButton.vue';
import AddIcon from '../Form/AddIcon.vue';
import { useDialog } from '@/composables/dialog';
import { useApi } from '@/composables/api';
    const { ask, ping } = useDialog()
    const api = useApi()
    const viewMenu = [
        {title: '月（スケジュール）', value: 1},
        {title: '月（時間）', value: 0},
        {title: '月（メンバー別）', value: 2},
        {title: '日（メンバー別）', value: 3},
    ]
    const sharingData = useSharingDataStore()
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const route = useRoute()
    const responsive = useResponsive()
    const tempRecord = useTempRecord()
    const props = defineProps(['initial_date'])
    
    const topOffset = ref(0)
    const bottomOffset = ref(0)
    const appendLock = ref(true)
    const records = ref<CalendarRecord[]>([])
    const selectedMonth = ref(DateTime.now().month)
    const selectedYear = ref(DateTime.now().year) 
    const selectedDay = ref(DateTime.now().day)
    const activeMonth = ref(DateTime.now().month)
    const activeYear = ref(DateTime.now().year) 
    const createWindow = ref(false)
    const editTarget = ref(null)
    const initialLoader = ref(true)
    const viewType = ref(-1)
    const slideCount = ref(-1)
    const searchKey = ref('')
    const searchResult = ref([])
    const searchFetch = ref(0)


    const searching = ref(0)
    const preSelected = ref('')
    const prevScrollTop = ref(0)
    const prevScrollLeft = ref(0)
    const jumpTo = ref<string | null>(null)
    const fastCreate = ref<FastCreateData>({
        x: 0,
        y: 0,
        time: '',
        stamp: null
    })
    const edit_all_record = ref(true)
    const activeMembers = ref([])
    const preSelectedMembers = ref<User[]>([])
    const normalHourLayoutRef = ref<InstanceType<typeof NormalHourLayout> | null>(null)
    const memberHourLayoutRef = ref<InstanceType<typeof MemberHourLayout> | null>(null)
    const memberMonthLayoutRef = ref<InstanceType<typeof MemberMonthLayout> | null>(null)
    const normalMonthLayoutRef = ref<InstanceType<typeof NormalMonthLayout> | null>(null)
    const summeryViewing = ref(null)
    const tempReserveWindow = ref(false)
    const { getFacilities, facilitiesList, departmentsList, getDepartments, selectedDepartment, setDraggingCalendar, draggingCalendar } = useCalendar()
    const layouts = computed(() => {
        return [normalHourLayoutRef.value, normalMonthLayoutRef.value, memberMonthLayoutRef.value, memberHourLayoutRef.value]
    })
    onUnmounted(() => {
        window.removeEventListener("keydown", onKeyDown);        
    })        
    onMounted(() => {
        const typeRaw = localStorage.getItem('viewType')
        const type = typeRaw ? Number(typeRaw) : 1
        viewType.value = type > -1 ? type : 1  
        if(route.query && route.query.id && props.initial_date){
            
            const tempId = Number(route.query.id)
            const m = DateTime.fromISO(props.initial_date).month;
            const y = DateTime.fromISO(props.initial_date).year;
            const d = DateTime.fromISO(props.initial_date).day;
            activeMonth.value = selectedMonth.value = m as MonthNumbers;
            activeYear.value = selectedYear.value = y;
            selectedDay.value = d as DayNumbers
            const date = DateTime.fromISO(props.initial_date)
            if(date.isValid){
                getCalendar(date.startOf('month').toISODate(), 'mounted')
            }
            
            tempRecord.setTempRecord(tempId)
        }else{
            const date = DateTime.now().toISODate()
            getCalendar(date, 'mounted')             
        }
        getFacilities()  
        getDepartments()      
        window.addEventListener("keydown", onKeyDown);
        if(sharingData.active){
            createWindow.value = true
        }
        if(auth.activeUser){
            preSelectedMembers.value.push(auth.activeUser as User)
        }
    })
    const preSelectedDepartment = computed(() => {
        return departmentsList.value.find(dep => 
            dep.members?.some(member => member.id === auth.id) || 
            dep.manager?.some(manager => manager.id === auth.id) || 
            dep.director?.id === auth.id
        );
    })
    const selectedDate = computed(() => {
        return selectedDateInstance.value.toISODate() as string
    })
    const holidays = computed(() => {
        const holidays = holiday_jp.between(new Date(activeYear.value - 1 + '-12-01'), new Date(activeYear.value + 1 + '-1-31'));
        return holidays
    })
    const daysOfMonth = computed(() => {
        
        const thisMonth = DateTime.fromObject({year: activeYear.value,month: activeMonth.value});
        if(!thisMonth.isValid) return []
        const firstDayOfMonth = thisMonth.startOf('month');
        const lastDayOfMonth = thisMonth.endOf('month');
        const days: NormalHourDay[] = [];
        let currentDay = firstDayOfMonth;
        while (currentDay <= lastDayOfMonth) {
            const holiday = holidays.value.find(h => DateTime.fromISO(h.date.toISOString()).hasSame(currentDay, 'day'));
            const day:NormalHourDay = {
                full: currentDay.toISODate(),
                day: currentDay.toFormat('ccc'),
                day_holiday : holiday ? holiday.name : null,
            }
            days.push(day);
            currentDay = currentDay.plus({days: 1});
        }
        return days;
    })
    const recordList = computed(() => {
        return records.value.length ? records.value : []
    })
    const dayRecords = computed(() => {
        const date = selectedDateInstance.value
        if(!date.isValid) return []
        const list = recordList.value.filter(record => {
            const recordDate = DateTime.fromSQL(record.date_start);
            if (!recordDate.isValid) return false;
            return recordDate.hasSame(date, 'day');
        });
        return list
    

    })
    const setListView = (data:string) => {
        const day = DateTime.fromISO(data)
        if(!day.isValid) return
        selectedDay.value = day.day
        viewType.value = 3
    }

    const fastCreateOpen = () => {
        if(fastCreate.value.time){
            const hour = DateTime.fromSQL(fastCreate.value.time).toSQL()
            preSelected.value = hour!
            createWindow.value = true
            fastCreate.value = {
                x: 0,
                y: 0,
                time: '',
                stamp: null
            }
        }            

    }

    const shiftToMonth = (direction:string) => {
        console.log('shiftToMonth', direction)

        const current = DateTime.fromObject({year: activeYear.value,month: activeMonth.value})
        if(!current.isValid) return
        appendLock.value = true  
        initialLoader.value = true               
        
        const index = (direction == 'down' || direction == 'right') ? 1 : -1        
        const new_month = current.plus({months:index}).startOf('month').toISODate()                  
        getCalendar(new_month, 'shift')
        const m = current.plus({months:index}).month
        const y = current.plus({months:index}).year
        activeMonth.value = selectedMonth.value = m
        activeYear.value = selectedYear.value = y            
                                    
        
    }

    const addRecord = (type, value, user) => {
        if(type == 'day'){
            if(user){
                const index = preSelectedMembers.value.find(ob => ob.id == user.id)
                if(!index){
                    preSelectedMembers.value.push(user)
                }                    
            }
            const hour = DateTime.now().plus({hours: 1}).startOf('hour').hour
            const d = DateTime.fromISO(value).set({hour: hour, minute: 0, second: 0}).toSQL()
            preSelected.value = d!
            createWindow.value = true            
        }
    }
    const deleteRecord = async(record) => {
        let question = record.repetition_type > 0 ? '繰り返しスケジュールすべて削除しますか。' : 'スケジュールを削除しますか。'
        let answers = [{label:'すべて', value:'all'}, {label:'このスケジュールのみ', value:'single'}, {label:'キャンセル', value:false}]
        const options = {
            answers: record.repetition_type > 0 ? answers : []
        }
        const answer = await ask(question, options)
        if(!answer.value) return
        const all_delete = answer.value == 'all'
  
        await api.post('/calendar_delete_record',{id:record.id, all_delete: all_delete}, { toast: '削除しました。' })
        const date = selectedDateInstance.value
        if(date.isValid){
            getCalendar(date.startOf('month').toISODate(), 'updated')
        }

    }
    const onKeyDown = (e) => {
        if(e.keyCode == 27 && draggingCalendar.value){
            setDraggingCalendar(null)
        }
    }
    const dropFinish = async (record:CalendarRecord, date:string) => {
        const data = await api.post('/calendar_drop',{id: record.id, date: date})
        const index = data ? records.value.findIndex(ob => ob.id == data.id) : -1

        if(index > -1){
            records.value[index] = data
        }                        
    }
    const editRecord = async(record) => {
        if(record.temp_flag == 1){
            ping('仮予約のスケジュールは編集できません。')
            return
        }
        if(record.repetition_type > 0){
            const options = {
                answers: [
                    {
                        label: 'すべて',
                        value: true
                    },
                    {
                        label: 'このスケジュールのみ',
                        value: false
                    }
                ]
            }
            const answer = await ask('繰り返しスケジュールのすべてのレコードが編集しますか。', options)
            edit_all_record.value = answer.value
            editTarget.value = record
            createWindow.value = true
        }else{
            editTarget.value = record
            createWindow.value = true
        }

    }
    const searchStart = async (word:string) => {
        searchKey.value = word
        menu.setMenu( {id : 26, name: 'calendarSearchResultWindow'})
        let val = searchKey.value            
        if(val && val.length){
            searching.value = 1
            const data = await api.post('/get_calendar_search',{key: val})                   
            searchResult.value = data        
            searchFetch.value ++     
            searching.value = 0        
    
        }else{
            searchResult.value = []
            searching.value = 0
        }
        
    }
    const switchView = (val) => {
        menu.close()
        initialLoader.value = true
        viewType.value = val

        const selected = selectedDateInstance.value.startOf('month')
        if(!selected.isValid) return
        const thisMonth = DateTime.now().hasSame(selected, 'month')
        if(thisMonth){
            nextTick(() => {
                jumpExecute(DateTime.now().toISODate())                
            })
            
        }
        nextTick(() => {
            initialLoader.value = false
        })
        
    }
    const userUpdated = () => {
        const dateInstance = selectedDateInstance.value.startOf('month')
        if(!dateInstance.isValid) return
        getCalendar(dateInstance.toISODate(), 'updated')
    }
    const jumpToDate = (date) => {
        appendLock.value = true
        const dataDate = DateTime.fromISO(date)
        if(!dataDate.isValid) return
        if(selectedMonth.value !== dataDate.month || selectedYear.value !== dataDate.year){
            selectedYear.value = dataDate.year
            selectedMonth.value = dataDate.month
            const diff = dataDate.startOf('month').toISODate()
            getCalendar(diff)
        }
        if(activeMonth.value !== dataDate.month || activeYear.value !== dataDate.year){
            activeYear.value = dataDate.year
            activeMonth.value = dataDate.month
        }
        topOffset.value = 0
        bottomOffset.value = 0
        viewType.value = 0
        nextTick(() => {
            document.getElementById(`day_val_${date}`)?.scrollIntoView()
        })
        setTimeout(() => {
            appendLock.value = false
        }, 500);
    }
    const fromMonth = (data:CalendarRecord) => {
        tempRecord.setTempRecord(data.id)
        appendLock.value = true
        const dataDate = DateTime.fromISO(data.date_start)
        if(!dataDate.isValid) return
        if(selectedMonth.value !== dataDate.month || selectedYear.value !== dataDate.year){
            selectedYear.value = dataDate.year
            selectedMonth.value = dataDate.month
            const diff = dataDate.startOf('month').toISODate()
            initialLoader.value = true
            records.value = []
            getCalendar(diff, 'fromMonth')
        }
        if(activeMonth.value !== dataDate.month || activeYear.value !== dataDate.year){
            activeYear.value = dataDate.year
            activeMonth.value = dataDate.month
        }
        viewType.value = 0
        
        setTimeout(() => {
            appendLock.value = false
        }, 500);
    }
    const slided = (realIndex, previous) => {
        if(!appendLock.value){
            slideCount.value ++
            if(slideCount.value > 0){
                if(previous == 11 && realIndex == 0){
                    selectedYear.value++
                }else if(previous == 0 && realIndex == 11){
                    selectedYear.value--
                }
                selectedMonth.value = realIndex 
                const dateInstance = DateTime.fromObject({year: selectedYear.value, month: selectedMonth.value, day: 1})
                if(!dateInstance.isValid) return
                const date = dateInstance.toISODate()
                getCalendar(date, 'slided')
            }
        }   
        
        
    }
    const jumpToToday = () => {
        appendLock.value = true
        const val = DateTime.now().toISODate()          
        if(selectedDateInstance.value.hasSame(DateTime.now(), 'month')){
            jumpExecute(val)
            setTimeout(() => {
                appendLock.value = false
            }, 100);
            selectedDay.value = DateTime.now().day
        }else{
            selectedMonth.value = activeMonth.value = DateTime.now().month
            selectedYear.value = activeYear.value = DateTime.now().year
            selectedDay.value = DateTime.now().day
            records.value = []
            getCalendar(selectedDateInstance.value.toISODate()!, 'mounted')
        }
                    
    }
    const selectedDateInstance = computed(() => {
        return DateTime.fromObject({ year: selectedYear.value, month: selectedMonth.value, day: selectedDay.value})
    })
    const jumpExecute = async(day) => {
        const layout = layouts.value[viewType.value]
        if(layout){
            await layout.containerScroll(day)
        } 
        initialLoader.value = false                
    }
    const closeCreate = (val) => {
        createWindow.value = false
        tempReserveWindow.value = false
        editTarget.value = null
        preSelected.value = ''
        edit_all_record.value = true
        if(val){
            const date = selectedDateInstance.value
            if(!date.isValid)return
            getCalendar(date.toISODate(), 'updated')
        }
        preSelectedMembers.value = [auth.activeUser as User]
    }
    const setDate = (date:{month: MonthNumbers, year: number, day?: DayNumbers}) => {
        appendLock.value = true
        bottomOffset.value = 0
        topOffset.value = 0
        activeMonth.value = date.month
        activeYear.value = date.year
        selectedMonth.value = date.month
        selectedYear.value = date.year
        let d = 1
        if(date.day){
            selectedDay.value = date.day
            d = date.day
        }
        
        records.value = []
        const dateInstance = selectedDateInstance.value
        if(!dateInstance.isValid) return
        getCalendar(dateInstance.toISODate(), 'setDate')
        
        
    }
    const createAtTime = (data, user) => {      
        menu.close()       
        if(user){
            const index = preSelectedMembers.value.find(ob => ob.id == user.id)
            if(!index){
                preSelectedMembers.value = [auth.activeUser as User] 
                preSelectedMembers.value.push(user)
            }                    
        }  
        if(menu.name && menu.name !== 'scheduleCreateFast'){
            menu.setMenu( {id: null, name: ''})
            return
        }           
        fastCreate.value = data
        menu.setMenu( {id: 896, name: 'scheduleCreateFast'})            
    }
    const resetFastCreate = () => {
        fastCreate.value = { x: 0, y: 0, time: '', stamp: null}
    }
    const scrollListen = (event: Event) => {
        resetFastCreate()
        const container = event.currentTarget as HTMLDivElement;
        prevScrollTop.value = container.scrollTop;          
        if(!appendLock.value){
            var percent = 100 * container.scrollTop / (container.scrollHeight - container.clientHeight);  
            if(percent >= 97){   
                jumpTo.value = 'down'                    
            }else if(percent <= 3){
                jumpTo.value = 'up'                   
            }else{
                jumpTo.value = null
            }
        }       
        
    }
    const scrollListenHorizontal = (event: Event) => {
        resetFastCreate()
        const container = event.currentTarget as HTMLDivElement;
        if(!appendLock.value){
            var percent = 100 * container.scrollLeft / (container.scrollWidth  - container.clientWidth);  
            if(percent >= 97){   
                jumpTo.value = 'right'                    
            }else if(percent <= 3){
                jumpTo.value = 'left'                   
            }else{
                jumpTo.value = null
            }
        }       
        
    }
    const jumpToRecord = (record) => {
        appendLock.value = true
        tempRecord.setTempRecord(record.id)
        const dInstance = DateTime.fromISO(record.date_start)
        if(!dInstance.isValid) return
        const date = dInstance.startOf('month').toISODate()
        selectedMonth.value = activeMonth.value = dInstance.month
        selectedYear.value = activeYear.value = dInstance.year
        if(viewType.value == 3){
            selectedDay.value = dInstance.day
        }                
        records.value = []
        getCalendar(date, 'search')
        
    }
    const getCalendar = async(day:string, method?:string, replaceId?: number) => {
        let fac = {}
        for(const index in facilitiesList.value){            
            const values = facilitiesList.value[index].filter((ob: FacilityItem) => ob.selected).map((ob: FacilityItem) => ob.value)
            if(values && values.length){
                fac[index] = values
            } 
        }   
        
        const depIds = selectedDepartment.value.map(ob => ob.id)
        const recordsList = await api.post('/get_calendar_data',{day: day, facilities: fac, view_type: viewType.value, departments: depIds})
            
        if(method == 'updated'){
            const valid_id = recordsList.map(ob => ob.id)
            records.value = records.value.filter(ob => valid_id.includes(ob.id))
        }
        recordsList.forEach(item => {
            const existingIndex = records.value.findIndex(record => record.id === item.id);
            if (existingIndex === -1) {
                records.value.push(item);
            }else{
                if(replaceId && item.id == replaceId){
                    records.value[existingIndex] = item
                }
            }
            
        });     
        
            
                                    
        if(method == 'mounted' || method == 'search'){
            setTimeout(() => {
                let date = DateTime.now().toISODate()       
                if(tempRecord.id){
                    const target = recordsList.find(ob => ob.id == tempRecord.id)
                    const instance = DateTime.fromISO(target.date_start)
                    if(target && instance.isValid){
                        date = instance.toISODate()
                    }
                }             
                jumpExecute(date)                       
                initialLoader.value = false                        
            });              
        }                
        if(method == 'shift'){
            nextTick(() => {     
                const aInstance = DateTime.fromISO(day) 
                let create = aInstance.startOf('month').toISODate()
                if(jumpTo.value == 'up'){
                    create = aInstance.endOf('month').toISODate()                          
                }    
                jumpExecute(create)                                               
                initialLoader.value = false        
                jumpTo.value = null                
            })                    
        }
        if(method == 'fromMonth'){
            nextTick(() => {                          
                initialLoader.value = false                        
            })                    
        }
        
        setTimeout(() => {
            appendLock.value = false
        }, 200);               
    

    }
    const setSummaryViewing = (record) => {
        summeryViewing.value = record
    }

    const confirmTemp = async(id:number, status: number) => {
        const question = status ? '仮予約を確定しますか。' : '仮予約をキャンセルしますか。';
        const pie = status ? '仮予約を確定しました。' : '仮予約をキャンセルしました。';
        await api.post('/calendar_temp_confirm', {id, status}, {
            ask: question,
            toast: pie          
        })
        const date = selectedDateInstance.value
        if(!date.isValid) return
        getCalendar(date.toISODate(), 'updated', id)

    }
    provide('deleteCalendar', deleteRecord)
    provide('editRecord', editRecord)
    provide('dropFinish', dropFinish)
    provide('setSummaryViewing', setSummaryViewing)
    provide('holidays', holidays)
    provide('confirmTemp', confirmTemp)

</script>