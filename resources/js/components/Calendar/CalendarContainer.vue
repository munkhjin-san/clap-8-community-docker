<template>

    <div id="calendarOuterContainer" class="post-root">
        <div class="calendar-root-header">
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
                    @setFacility="setFacility"
                    @setActiveMembers="val => activeMembers = val"
                    :facilitiesList="facilitiesList"
                    :selectedYear="selectedYear"
                    :selectedMonth="selectedMonth"
                />
                <div style="margin: 0 15px 0 auto;">
                    <DayPicker
                        v-if="viewType == 3"
                        :selectedMonth="selectedMonth"
                        :selectedYear="selectedYear"
                        :selectedDay="selectedDay"
                        right="0"
                        @setDate="setDate"
                        ref="daypicker" 
                    />
                    <MonthPicker 
                        v-else
                        :selectedMonth="selectedMonth"
                        :selectedYear="selectedYear"
                        right="0"
                        @setDate="setDate"
                        ref="monthpicker"
                    />
                </div>
            </div>          
        </div>
        <Transition :name="`${jumpTo}ShiftPop`">
            <ShiftButton @click="shiftToMonth(jumpTo)" v-if="jumpTo" :jumpTo="jumpTo" @close="jumpTo = null"/>
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
        <MemberMonth 
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
        />
        <MemberHourLayout 
            v-if="viewType == 3"
            :records="dayRecords"
            :activeMembers="activeMembers"
            :selectedDate="selectedDate"
            :initialLoader="initialLoader"
            @create="createAtTime"
            @resetFastCreate="resetFastCreate"
            ref="memberHourLayoutRef"
        />
        <Transition name="modalFade">
            <div id="calendarViewMenu" class="boxMenu boardMenuIcon viewSwitchMenu" v-if="menu.name == 'calendarViewMenu' && menu.id == 79">
                <ul>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(1)">
                        月（カレンダー）
                        <span v-if="viewType == 1">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </span>
                    </li>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(0)">
                        月（時間）
                        <span v-if="viewType == 0">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </span>
                    </li>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(2)">
                        月（メンバー別）
                        <span v-if="viewType == 2">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </span>
                    </li>                    
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(3)">
                        日（メンバー別）
                        <span v-if="viewType == 3">
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" height="10" viewBox="0 0 38 32">
                                <path d="M36.486 0.324c-0.666-0.515-1.629-0.396-2.204 0.22l-3.039 3.271-3.060 3.328c-2.031 2.23-4.067 4.452-6.086 6.689-2.025 2.234-8.487 9.367-9.743 10.772-0.132 0.15-0.369 0.129-0.486-0.025-1.060-1.399-2.287-3.028-3.468-4.519-1.161-1.465-2.516-3.22-3.271-4.144-0.755-0.927-1.702-2.093-2.191-2.668-0.528-0.625-1.457-0.791-2.182-0.329-0.765 0.489-0.973 1.521-0.518 2.307 0.367 0.636 2.307 3.801 2.307 3.801 0.801 1.27 3.213 5.039 3.699 5.791 0.487 0.751 1.194 1.782 1.879 2.788 0.684 1.004 1.52 2.313 2.429 3.264s2.487 0.627 3.321-0.358c1.932-2.282 9.588-11.527 11.498-13.857 1.916-2.327 3.815-4.668 5.719-7.004l2.842-3.517 2.823-3.535c0.548-0.687 0.451-1.716-0.272-2.276z"></path>
                            </svg>
                        </span>
                    </li>      
                </ul>              
            </div>
        </Transition>
        <div title="" class="createBoardButton fileNewButton monthSiwtchButton" @click.stop="menu.setMenu( {name : 'calendarViewMenu', id: 79})" :style="{zIndex: initialLoader ? 41 : 7}">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 32" style="width: 18px; margin: auto; fill: rgb(0, 0, 0);">
                <path d="M35.556 27.791v-1.812l-0.011-2.902-0.057-11.584-0.011-2.89-0.011-1.445v-0.195c0-0.080 0-0.172-0.011-0.252-0.011-0.172-0.034-0.333-0.069-0.493-0.069-0.333-0.184-0.642-0.333-0.941-0.298-0.596-0.757-1.101-1.296-1.48-0.551-0.367-1.193-0.596-1.858-0.654-0.161-0.011-0.344-0.011-0.447-0.011h-1.090l-1.239 0.011c-0.080 0-0.138-0.069-0.138-0.138v-0.631c0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.080 0-0.161-0.011-0.241-0.011h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.539c0 0.115-0.103 0.218-0.218 0.206-0.814-0.057-9.715-0.057-10.586-0.023-0.046 0-0.080-0.034-0.080-0.069 0-0.172 0.011-0.585 0-0.642 0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.057-0.011-0.138-0.023-0.218-0.023h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.642c0 0.103-0.080 0.183-0.183 0.183l-2.707-0.023c-0.654 0.023-1.308 0.218-1.87 0.562s-1.032 0.837-1.353 1.411c-0.161 0.287-0.287 0.596-0.367 0.918-0.046 0.161-0.069 0.321-0.092 0.493-0.011 0.080-0.011 0.161-0.023 0.252v1.675l-0.023 2.902c-0.023 3.865-0.057 7.719-0.069 11.584l-0.011 2.89v2.202c0.011 0.344 0.057 0.688 0.149 1.009 0.183 0.665 0.551 1.262 1.032 1.743s1.090 0.837 1.755 1.021c0.333 0.092 0.677 0.138 1.021 0.138h27.733c0.080 0 0.172-0.011 0.252-0.011 0.172-0.023 0.344-0.046 0.505-0.080 0.677-0.149 1.296-0.493 1.801-0.941 0.505-0.459 0.895-1.044 1.101-1.698 0.115-0.321 0.172-0.665 0.195-1.009 0-0.080 0-0.172 0.011-0.252v-0.195zM25.359 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.057-0.172 0.126-0.241s0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.183 0.493-0.459 0.493-0.195 0-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0.011-0.011 0.011-0.069zM8.786 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.046-0.172 0.126-0.241 0.069-0.069 0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.218 0.493-0.459 0.493s-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0-0.011 0.011-0.069zM33.308 7.157v1.445l-0.011 2.89-0.057 11.584-0.011 2.902v2.099c-0.011 0.138-0.034 0.275-0.080 0.413-0.092 0.264-0.252 0.505-0.459 0.7-0.218 0.183-0.47 0.321-0.734 0.378-0.057 0.011-0.115 0.023-0.183 0.034-0.034 0-0.092 0.011-0.138 0.011h-27.642c-0.138 0-0.275-0.011-0.413-0.057-0.264-0.069-0.516-0.218-0.723-0.413s-0.356-0.447-0.436-0.711c-0.046-0.138-0.057-0.275-0.069-0.413v-2.145l-0.011-2.89c-0.011-3.865-0.046-7.719-0.069-11.584l-0.034-2.913-0.011-1.445v-0.126c0-0.034 0-0.080 0.011-0.115 0.011-0.069 0.023-0.149 0.034-0.218 0.034-0.149 0.092-0.287 0.161-0.424 0.149-0.264 0.356-0.505 0.619-0.665 0.264-0.172 0.551-0.264 0.86-0.287l2.707-0.034c0.069 0 0.115 0.046 0.115 0.115l0.011 0.688c0 0.034 0 0.126 0.011 0.195 0 0.080 0.011 0.149 0.023 0.229 0.046 0.31 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.275-0.011 0.551-0.011c0.642 0 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.023-0.447c0-0.069 0.057-0.126 0.138-0.126 0.872 0.034 9.864 0.034 10.724-0.034 0.057 0 0.103 0.034 0.103 0.092 0 0.241 0.023 0.849 0.046 1.067 0.034 0.275 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.367 0 0.551-0.011c0.665-0.034 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.011-0.275 0.011-0.218c0-0.080 0.069-0.138 0.138-0.138l1.296 0.011h0.723c0.229 0 0.528 0 0.631 0.011 0.298 0.023 0.585 0.138 0.837 0.31s0.447 0.413 0.585 0.677c0.069 0.138 0.115 0.275 0.138 0.424 0.011 0.069 0.023 0.149 0.023 0.218v0.31z"></path> <path d="M30.509 10.598c-2.11-0.069-4.221-0.103-6.331-0.126-1.055-0.011-2.11-0.023-3.166-0.023l-3.166-0.011-3.166 0.011-3.166 0.023-4.748 0.069-1.583 0.034c-0.413 0.011-0.757 0.344-0.768 0.768-0.011 0.436 0.333 0.791 0.768 0.803l1.583 0.034 4.748 0.069 3.166 0.023 3.166 0.011 3.166-0.011c1.055 0 2.11-0.023 3.166-0.023 2.11-0.023 4.221-0.057 6.331-0.126 0.39-0.011 0.723-0.333 0.734-0.723 0.011-0.436-0.31-0.791-0.734-0.803zM15.771 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM22.366 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM29.075 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.057-0.952-0.505-1.135zM9.049 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.046-0.952-0.505-1.124zM15.771 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM22.366 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM29.075 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.057-0.952-0.505-1.124zM9.049 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.046-0.963-0.505-1.135zM15.771 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.963-0.505-1.135z"></path>
            </svg>
        </div>
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true" :style="{zIndex: initialLoader ? 41 : 7}">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>

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
                :facilitiesList="facilitiesList"  
                :preSelected="preSelected"
                :edit_all_record="edit_all_record"
                :preSelectedMembers="preSelectedMembers"
                @close="closeCreate"      
            />            
        </Transition> 
        <DragItem v-if="draggingCalendar"/>
    </div>        
</template>
<script setup>
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue'
import NormalHourLayout from './NormalHour/NormalHourLayout.vue';
import moment from 'moment';
import MonthPicker from '../Global/MonthPicker.vue'
import { nextTick, ref, computed, onMounted, provide, onUnmounted, inject } from 'vue'
import CalendarCreate from './CalendarCreate.vue';
import TopBar from './TopBar.vue'
import NormalMonthLayout from './NormalMonth/NormalMonthLayout.vue'
import MemberMonth from './MemberMonth/MemberMonthLayout.vue'
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
    const records = ref([])
    const selectedMonth = ref(moment().month())
    const selectedYear = ref(moment().year()) 
    const selectedDay = ref(moment().date())
    const activeMonth = ref(moment().month())
    const activeYear = ref(moment().year()) 
    const createWindow = ref(false)
    const editTarget = ref(null)
    const initialLoader = ref(true)
    const viewType = ref(-1)
    const slideCount = ref(-1)
    const searchKey = ref('')
    const searchResult = ref([])
    const searchFetch = ref(0)
    const facilitiesList = ref([])
    const searching = ref(false)
    const preSelected = ref(null)
    const prevScrollTop = ref(0)
    const jumpTo = ref(null)
    const fastCreate = ref({
        x: 0,
        y: 0,
        time: null,
        stamp: null
    })
    const edit_all_record = ref(true)
    const activeMembers = ref([])
    const preSelectedMembers = ref([])
    const draggingCalendar = ref(null)
    const { notify, info, confirm } = inject('dialog')
    const normalHourLayoutRef = ref(null)
    const memberHourLayoutRef = ref(null)
    const memberMonthLayoutRef = ref(null)
    const normalMonthLayoutRef = ref(null)
    const layouts = computed(() => {
        return [normalHourLayoutRef.value, normalMonthLayoutRef.value, memberMonthLayoutRef.value, memberHourLayoutRef.value]
    })
    onUnmounted(() => {
        window.removeEventListener("keydown", onKeyDown);        
    })        
    onMounted(() => {
        const type = parseInt(localStorage.getItem('viewType'))   
        viewType.value = type > -1 ? type : 1  
        if(route.query && route.query.id && props.initial_date){
            
            const tempId = parseInt(route.query.id)
            const m = moment(props.initial_date).month();
            const y = moment(props.initial_date).year();
            const d = moment(props.initial_date).date();
            activeMonth.value = selectedMonth.value = m;
            activeYear.value = selectedYear.value = y;
            selectedDay.value = d
            const date = moment(props.initial_date).startOf('month').format('YYYY-MM-DD')
            getCalendar(date, 'mounted')
            tempRecord.setTempRecord(tempId)
        }else{
            const date = moment().format('YYYY-MM-DD')
            getCalendar(date, 'mounted')            
             
                     
            
        }
        getFacilities()        
        window.addEventListener("keydown", onKeyDown);
        if(sharingData.active){
            createWindow.value = true
        }
        if(auth.activeUser){
            preSelectedMembers.value.push(auth.activeUser)
        }
    })
   
    const selectedDate = computed(() => {
        return moment([selectedYear.value, selectedMonth.value, selectedDay.value]).format('YYYY-MM-DD')
    })
    const holidays = computed(() => {
        const holidays = holiday_jp.between(new Date(activeYear.value - 1 + '-12-01'), new Date(activeYear.value + 1 + '-1-31'));
        return holidays
    })
    const daysOfMonth = computed(() => {
        
        const thisMonth = moment([activeYear.value, activeMonth.value]);
        const firstDayOfMonth = thisMonth.clone().subtract(topOffset.value, 'months').startOf('month');
        const lastDayOfMonth = thisMonth.clone().add(bottomOffset.value, 'months').endOf('month');
        const days = [];

        let currentDay = firstDayOfMonth.clone();
        while (currentDay.isSameOrBefore(lastDayOfMonth, 'day')) {
            const holiday = holidays.value.find(h => moment(h.date).isSame(currentDay, 'day'));
            days.push({
                full: currentDay.format('YYYY-MM-DD'),
                day: currentDay.format('D'),
                day_holiday : holiday ? holiday.name : null,
            });
            currentDay.add(1, 'day');
        }
        return days;
    })
    const recordList = computed(() => {
        return records.value.length ? records.value : []
    })
    const dayRecords = computed(() => {
        const date = moment([selectedYear.value, selectedMonth.value, selectedDay.value]).format('YYYY-MM-DD')
        return recordList.value.filter(record => moment(record.date_start).isSame(date, 'day'))
    

    })
    const setListView = (data) => {
        const day = moment(data).date()
        selectedDay.value = day
        viewType.value = 3
    }

    const fastCreateOpen = () => {
        if(fastCreate.value.time){
            const hour = moment(fastCreate.value.time).format('YYYY-MM-DD HH:mm:ss')
            preSelected.value = hour
            createWindow.value = true
            fastCreate.value = {
                x: 0,
                y: 0,
                time: null,
                stamp: null
            }
        }            

    }

    const shiftToMonth = (direction) => {
        appendLock.value = true  
        initialLoader.value = true                
        const current = moment([activeYear.value, activeMonth.value])

        const index = direction == 'down' ? 1 : -1
            
        const new_month = current.clone().add(index, 'month').startOf('month').format('YYYY-MM-DD')                    
        getCalendar(new_month, 'shift')
        const m = current.clone().add(index, 'month').month()
        const y = current.clone().add(index, 'month').year()
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
            const hour = moment().add(1, 'hour').startOf('hour').hour()
            const d = moment(value).hour(hour).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss')
            preSelected.value = d
            createWindow.value = true            
        }
    }
    const deleteRecord = async(record) => {
        let question = record.repetition_type > 0 ? '繰り返しスケジュールすべて削除しますか。' : 'スケジュールを削除しますか。'
        let answers = [{label:'すべて', value:'all'}, {label:'このスケジュールのみ', value:'single'}, {label:'キャンセル', value:false}]
        const options = {
            answers: record.repetition_type > 0 ? answers : null
        }
        const answer = await confirm(question, options)
        if(answer == false) return
        const all_delete = answer == 'all'
        try{
            await axios.post('/calendar_delete_record',{id:record.id, all_delete: all_delete})
            const date = moment([selectedYear.value, selectedMonth.value, 1]).format('YYYY-MM-DD')
            getCalendar(date, 'updated')
            info('削除しました。') 
        } catch (e) { 
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        } 
    }
    const onKeyDown = (e) => {
        if(e.keyCode == 27 && draggingCalendar.value){
            draggingCalendar.value = null
        }
    }
    const dropFinish = (record, date) => {
        axios.post('/calendar_drop',{id: record.id, date: date}).then(response => {  
            if(response.data){
                const index = records.value.findIndex(ob => ob.id == response.data.id)
                if(index > -1){
                    records.value[index] = response.data
                }
            }                           
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。')                           
        });
    }
    const editRecord = async(record) => {
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
            const answer = await confirm('繰り返しスケジュールのすべてのレコードが編集しますか。', options)
            edit_all_record.value = answer
            editTarget.value = record
            createWindow.value = true
        }else{
            editTarget.value = record
            createWindow.value = true
        }

    }
    const setFacility = (index, sub_index, value) => {
        facilitiesList.value[index][sub_index].selected = value
        const dateInstance = moment([ selectedYear.value, selectedMonth.value, 1 ]).format('YYYY-MM-DD');
        getCalendar(dateInstance, 'updated')
    }
    const getFacilities = () => {
        axios.post('/get_all_facilities').then(response => facilitiesList.value = response.data)
    }
    const searchStart = (val) => {
        menu.setMenu( {id : 26, name: 'calendarSearchResultWindow'})
        searchKey.value = val            
        if(val && val.length){
            searching.value = true
            axios.post('/get_calendar_search',{key: val}).then(response => {                      
                searchResult.value = response.data        
                searchFetch.value ++     
                searching.value = false        
            })
        }else{
            searchResult.value = []
            searching.value = false
        }
        
    }
    const switchView = (val) => {
        menu.setMenu( {name: '', id: null})
        initialLoader.value = true
        viewType.value = val

        const selected = moment([selectedYear.value, selectedMonth.value , 1])
        const thisMonth = moment().isSame(selected, 'month')
        if(thisMonth){
            nextTick(() => {
                jumpExecute(moment().format('YYYY-MM-DD'))                
            })
            
        }
        nextTick(() => {
            initialLoader.value = false
        })
        
    }
    const userUpdated = () => {
        const dateInstance = moment([ selectedYear.value, selectedMonth.value, 1 ]).format('YYYY-MM-DD');
        getCalendar(dateInstance, 'updated')
    }
    const jumpToDate = (date) => {
        appendLock.value = true
        const dataDate = moment(date)
        if(selectedMonth.value !== dataDate.month() || selectedYear.value !== dataDate.year()){
            selectedYear.value = dataDate.year()
            selectedMonth.value = dataDate.month()
            const diff = dataDate.startOf('month').format('YYYY-MM-DD')
            getCalendar(diff)
        }
        if(activeMonth.value !== dataDate.month() || activeYear.value !== dataDate.year()){
            activeYear.value = dataDate.year()
            activeMonth.value = dataDate.month()
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
    const fromMonth = (data) => {
        tempRecord.setTempRecord(data.id)
        appendLock.value = true
        const dataDate = moment(data.date_start)
        if(selectedMonth.value !== dataDate.month() || selectedYear.value !== dataDate.year()){
            selectedYear.value.value = dataDate.year()
            selectedMonth.value = dataDate.month()
            const diff = dataDate.startOf('month').format('YYYY-MM-DD')
            initialLoader.value = true
            records.value = []
            getCalendar(diff, 'fromMonth')
        }
        if(activeMonth.value !== dataDate.month() || activeYear.value !== dataDate.year()){
            activeYear.value = dataDate.year()
            activeMonth.value = dataDate.month()
        }
        topOffset.value = 0
        bottomOffset.value = 0
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
                const dateInstance = moment([ selectedYear.value, selectedMonth.value, 1 ]).format('YYYY-MM-DD');   
                getCalendar(dateInstance, 'slided')
            }
        }   
        
        
    }
    const jumpToToday = () => {
        appendLock.value = true
        const val = moment().format('YYYY-MM-DD')           
        if(moment([selectedYear.value, selectedMonth.value]).isSame(moment(), 'month')){
            jumpExecute(val)
            setTimeout(() => {
                appendLock.value = false
            }, 100);
            selectedDay.value = moment().date()
        }else{
            console.log('ttttttttttt')
            selectedMonth.value = activeMonth.value = moment().month()
            selectedYear.value = activeYear.value = moment().year()
            selectedDay.value = moment().date()
            records.value = []
            const date = moment([selectedYear.value, selectedMonth.value , 1]).format('YYYY-MM-DD')
            getCalendar(date, 'mounted')
        }
                    
    }

    const jumpExecute = async(day) => {
        console.log('yyyy', day)
        const layout = layouts.value[viewType.value]
        if(layout){
            await layout.containerScroll(day)
        } 
        initialLoader.value = false                
    }
    const closeCreate = (val) => {
        createWindow.value = false
        editTarget.value = null
        preSelected.value = null
        if(val){
            const date = moment([selectedYear.value, selectedMonth.value, 1]).format('YYYY-MM-DD')
            getCalendar(date, 'updated')
        }
        preSelectedMembers.value = [auth.activeUser]
    }
    const setDate = (date) => {
        appendLock.value = true
        bottomOffset.value = 0
        topOffset.value = 0
        activeMonth.value = date.month - 1
        activeYear.value = date.year
        selectedMonth.value = date.month - 1
        selectedYear.value = date.year
        let d = 1
        if(date.day){
            selectedDay.value = date.day
            d = date.day
        }
        
        records.value = []
        const dateInstance = moment([ date.year, date.month - 1, d ]).format('YYYY-MM-DD');
        getCalendar(dateInstance, 'setDate')
        
        
    }
    const createAtTime = (data, user) => {             
        if(user){
            const index = preSelectedMembers.value.find(ob => ob.id == user.id)
            if(!index){
                preSelectedMembers.value = [auth.activeUser] 
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
        fastCreate.value = { x: 0, y: 0, time: null, stamp: null}
    }
    const scrollListen = () => {
        resetFastCreate()
        const container = event.currentTarget;
        let direction = container.scrollTop > prevScrollTop.value ? 'down' : 'up'
        prevScrollTop.value = container.scrollTop;
        const tempMonth = selectedMonth.value            
        if(!appendLock.value){
            var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
            if(percent >= 97){   
                jumpTo.value = 'down'                    
            }else if(percent <= 3){
                jumpTo.value = 'up'                   
            }else{
                jumpTo.value = null
            }
        }       
        
    }
    const jumpToRecord = (record) => {
        appendLock.value = true
        tempRecord.setTempRecord(record.id)
        const dInstance = moment(record.date_start)
        const date = dInstance.startOf('month').format('YYYY-MM-DD')
        selectedMonth.value = activeMonth.value = moment(record.date_start).month()
        selectedYear.value = activeYear.value = moment(record.date_start).year()
        if(viewType.value == 3){
            selectedDay.value = moment(record.date_start).date()
        }                
        records.value = []
        getCalendar(date, 'search')
        
    }
    const getCalendar = (day, method) => {
        let fac = {}
        for(const index in facilitiesList.value){
            
            const values = facilitiesList.value[index].filter(ob => ob.selected).map(ob => ob.value)
            if(values && values.length){
                fac[index] = values
            }   

            
        }
        axios.post('/get_calendar_data',{day: day, facilities: fac, view_type: viewType.value}).then(response => {  
            
            if(method == 'updated'){
                const valid_id = response.data.map(ob => ob.id)
                records.value = records.value.filter(ob => valid_id.includes(ob.id))
            }
            response.data.forEach(item => {
                const existingItem = records.value.find(record => record.id === item.id);
                if (!existingItem) {
                    records.value.push(item);
                }
                
            });     
            
                
                                      
            if(method == 'mounted' || method == 'search'){
                setTimeout(() => {
                    let date = moment().format('YYYY-MM-DD')      
                    if(tempRecord.id){
                        const target = response.data.find(ob => ob.id == tempRecord.id)
                        if(target){
                            date = moment(target.date_start).format('YYYY-MM-DD')
                        }
                    }             
                    jumpExecute(date)                       
                    initialLoader.value = false                        
                });              
            }                
            if(method == 'shift'){
                nextTick(() => {     
                    let create = moment(day).startOf('month').format('YYYY-MM-DD')   
                    if(jumpTo.value == 'up'){
                        create = moment(day).endOf('month').format('YYYY-MM-DD')                            
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
    
        }).catch(function (error) {
            if (error.response) notify(error.response.data.message)
            else if (error.request) notify('エラーが発生しました。')
            else notify('エラーが発生しました。')                        
        });
    }
    provide('deleteCalendar', deleteRecord)
    provide('editRecord', editRecord)
    provide('facilities', facilitiesList)
    provide('dropFinish', dropFinish)
    provide('draggingCalendar', draggingCalendar)

</script>