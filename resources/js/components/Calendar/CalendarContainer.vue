<template>

    <div id="calendarOuterContainer" class="post-root">
        <div class="calendar-root-header">
            <HamBurger v-if="$store.state.mobile"/>
            <div class="calendar-search-wrap" id="calendarSearchResultWindow" >
                <PostSearchBar 
                    @searchStart="searchStart"  
                    @focus="$store.commit('setMenu', {name: 'calendarSearchResultWindow', id: 26})"
                    :searching="searching"
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`スケジュールを検索`"
                />
                <CalendarSearchResults 
                    v-if="searchKey.length && $store.state.menu.id == 26 && $store.state.menu.name == 'calendarSearchResultWindow'" 
                    :searchResult="searchResult" 
                    :searchFetch="searchFetch"
                    @jumpToRecord="jumpToRecord"
                    
                />
                
            </div>
            <div class="calendar-bar-container" style="">
                <CalendarBar
                    @jumpToday="jumpToToday"
                    @updated="userUpdated"
                    @setFacility="setFacility"
                    @setActiveMembers="val => activeMembers = val"
                    :facilitiesList="facilitiesList"
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
                <CalendarShiftButton @click="shiftToMonth(jumpTo)" v-if="jumpTo" :jumpTo="jumpTo" @close="jumpTo = null"/>
            </Transition>
        <!-- <Transition name="modalFade"> -->
            <DayView
                v-if="viewType == 0"
                ref="DayView"
                :daysOfMonth="daysOfMonth"
                :records="recordList"
                :initialLoader="initialLoader"
                :facilitiesList="facilitiesList"
                :holidays="holidays"
                @scroll="scrollListen"
                @releaseScroll="appendLock = false"
                @dropFinish="dropFinish"
                @edit="editRecord"
                @delete="deleteRecordConfirm"
                @create="createAtTime"
                @setListView="setListView"
            />
        <!-- </Transition> -->
        <!-- <Transition name="modalFade"> -->
            <MonthView 
                v-if="viewType == 1"
                ref="monthView"
                :records="recordList"
                :selected-year="selectedYear"
                :selected-month="selectedMonth"
                :facilitiesList="facilitiesList"
                :initialLoader="initialLoader"
                :active-month="activeMonth"
                :active-year="activeYear"
                :holidays="holidays"
                @slided="slided"
                @fromMonth="fromMonth"
                @addRecord="addRecord"
                @dropFinish="dropFinish"
                @jumpToDate="jumpToDate"
                @edit="editRecord"
                @delete="deleteRecordConfirm"
                @scroll="scrollListen"
            />
            <WeekView 
                v-if="viewType == 2"
                ref="weekView"
                :records="recordList"
                :selected-year="selectedYear"
                :selected-month="selectedMonth"
                :facilitiesList="facilitiesList"
                :initialLoader="initialLoader"
                :active-month="activeMonth"
                :active-year="activeYear"
                :holidays="holidays"
                :activeMembers="activeMembers"
                @slided="slided"
                @fromMonth="fromMonth"
                @addRecord="addRecord"
                @dropFinish="dropFinish"
                @jumpToDate="jumpToDate"
                @edit="editRecord"
                @delete="deleteRecordConfirm"
                @setListView="setListView"
            />
            <ListView 
                v-if="viewType == 3"
                :records="dayRecords"
                :activeMembers="activeMembers"
                :selectedDate="selectedDate"
                :facilitiesList="facilitiesList"
                :initialLoader="initialLoader"
                @edit="editRecord"
                @delete="deleteRecordConfirm"
                ref="ListView"
            />
        <!-- </Transition> -->
        <Transition name="modalFade">
            <div id="calendarViewMenu" class="boxMenu boardMenuIcon" v-if="$store.state.menu.name == 'calendarViewMenu' && $store.state.menu.id == 79" style="top: auto;right: 55px;z-index:6;bottom: 70px;">
                <ul>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(1)">月（すべて）</li>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(0)">日（すべて）</li>
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(2)">月（メンバー別）</li>                    
                    <li class="boxMenuItems cursor-pointer" @click.stop="switchView(3)">日（メンバー別）</li>      
                </ul>              
            </div>
        </Transition>
        <div title="" class="createBoardButton fileNewButton monthSiwtchButton" @click.stop="$store.commit('setMenu', {name : 'calendarViewMenu', id: 79})" :style="{zIndex: initialLoader ? 41 : 7}">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 32" style="width: 18px; margin: auto; fill: rgb(0, 0, 0);">
                <path d="M35.556 27.791v-1.812l-0.011-2.902-0.057-11.584-0.011-2.89-0.011-1.445v-0.195c0-0.080 0-0.172-0.011-0.252-0.011-0.172-0.034-0.333-0.069-0.493-0.069-0.333-0.184-0.642-0.333-0.941-0.298-0.596-0.757-1.101-1.296-1.48-0.551-0.367-1.193-0.596-1.858-0.654-0.161-0.011-0.344-0.011-0.447-0.011h-1.090l-1.239 0.011c-0.080 0-0.138-0.069-0.138-0.138v-0.631c0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.080 0-0.161-0.011-0.241-0.011h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.539c0 0.115-0.103 0.218-0.218 0.206-0.814-0.057-9.715-0.057-10.586-0.023-0.046 0-0.080-0.034-0.080-0.069 0-0.172 0.011-0.585 0-0.642 0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.057-0.011-0.138-0.023-0.218-0.023h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.642c0 0.103-0.080 0.183-0.183 0.183l-2.707-0.023c-0.654 0.023-1.308 0.218-1.87 0.562s-1.032 0.837-1.353 1.411c-0.161 0.287-0.287 0.596-0.367 0.918-0.046 0.161-0.069 0.321-0.092 0.493-0.011 0.080-0.011 0.161-0.023 0.252v1.675l-0.023 2.902c-0.023 3.865-0.057 7.719-0.069 11.584l-0.011 2.89v2.202c0.011 0.344 0.057 0.688 0.149 1.009 0.183 0.665 0.551 1.262 1.032 1.743s1.090 0.837 1.755 1.021c0.333 0.092 0.677 0.138 1.021 0.138h27.733c0.080 0 0.172-0.011 0.252-0.011 0.172-0.023 0.344-0.046 0.505-0.080 0.677-0.149 1.296-0.493 1.801-0.941 0.505-0.459 0.895-1.044 1.101-1.698 0.115-0.321 0.172-0.665 0.195-1.009 0-0.080 0-0.172 0.011-0.252v-0.195zM25.359 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.057-0.172 0.126-0.241s0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.183 0.493-0.459 0.493-0.195 0-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0.011-0.011 0.011-0.069zM8.786 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.046-0.172 0.126-0.241 0.069-0.069 0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.218 0.493-0.459 0.493s-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0-0.011 0.011-0.069zM33.308 7.157v1.445l-0.011 2.89-0.057 11.584-0.011 2.902v2.099c-0.011 0.138-0.034 0.275-0.080 0.413-0.092 0.264-0.252 0.505-0.459 0.7-0.218 0.183-0.47 0.321-0.734 0.378-0.057 0.011-0.115 0.023-0.183 0.034-0.034 0-0.092 0.011-0.138 0.011h-27.642c-0.138 0-0.275-0.011-0.413-0.057-0.264-0.069-0.516-0.218-0.723-0.413s-0.356-0.447-0.436-0.711c-0.046-0.138-0.057-0.275-0.069-0.413v-2.145l-0.011-2.89c-0.011-3.865-0.046-7.719-0.069-11.584l-0.034-2.913-0.011-1.445v-0.126c0-0.034 0-0.080 0.011-0.115 0.011-0.069 0.023-0.149 0.034-0.218 0.034-0.149 0.092-0.287 0.161-0.424 0.149-0.264 0.356-0.505 0.619-0.665 0.264-0.172 0.551-0.264 0.86-0.287l2.707-0.034c0.069 0 0.115 0.046 0.115 0.115l0.011 0.688c0 0.034 0 0.126 0.011 0.195 0 0.080 0.011 0.149 0.023 0.229 0.046 0.31 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.275-0.011 0.551-0.011c0.642 0 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.023-0.447c0-0.069 0.057-0.126 0.138-0.126 0.872 0.034 9.864 0.034 10.724-0.034 0.057 0 0.103 0.034 0.103 0.092 0 0.241 0.023 0.849 0.046 1.067 0.034 0.275 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.367 0 0.551-0.011c0.665-0.034 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.011-0.275 0.011-0.218c0-0.080 0.069-0.138 0.138-0.138l1.296 0.011h0.723c0.229 0 0.528 0 0.631 0.011 0.298 0.023 0.585 0.138 0.837 0.31s0.447 0.413 0.585 0.677c0.069 0.138 0.115 0.275 0.138 0.424 0.011 0.069 0.023 0.149 0.023 0.218v0.31z"></path> <path d="M30.509 10.598c-2.11-0.069-4.221-0.103-6.331-0.126-1.055-0.011-2.11-0.023-3.166-0.023l-3.166-0.011-3.166 0.011-3.166 0.023-4.748 0.069-1.583 0.034c-0.413 0.011-0.757 0.344-0.768 0.768-0.011 0.436 0.333 0.791 0.768 0.803l1.583 0.034 4.748 0.069 3.166 0.023 3.166 0.011 3.166-0.011c1.055 0 2.11-0.023 3.166-0.023 2.11-0.023 4.221-0.057 6.331-0.126 0.39-0.011 0.723-0.333 0.734-0.723 0.011-0.436-0.31-0.791-0.734-0.803zM15.771 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM22.366 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM29.075 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.057-0.952-0.505-1.135zM9.049 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.046-0.952-0.505-1.124zM15.771 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM22.366 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM29.075 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.057-0.952-0.505-1.124zM9.049 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.046-0.963-0.505-1.135zM15.771 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.963-0.505-1.135z"></path>
            </svg>
        </div>
        <!-- <div title="" class="createBoardButton fileNewButton monthSiwtchButton" @click="switchView" :style="{zIndex: initialLoader ? 41 : 7}">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 32" style="width: 18px; margin: auto; fill: rgb(0, 0, 0);">
                <path d="M35.556 27.791v-1.812l-0.011-2.902-0.057-11.584-0.011-2.89-0.011-1.445v-0.195c0-0.080 0-0.172-0.011-0.252-0.011-0.172-0.034-0.333-0.069-0.493-0.069-0.333-0.184-0.642-0.333-0.941-0.298-0.596-0.757-1.101-1.296-1.48-0.551-0.367-1.193-0.596-1.858-0.654-0.161-0.011-0.344-0.011-0.447-0.011h-1.090l-1.239 0.011c-0.080 0-0.138-0.069-0.138-0.138v-0.631c0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.080 0-0.161-0.011-0.241-0.011h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.539c0 0.115-0.103 0.218-0.218 0.206-0.814-0.057-9.715-0.057-10.586-0.023-0.046 0-0.080-0.034-0.080-0.069 0-0.172 0.011-0.585 0-0.642 0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.057-0.011-0.138-0.023-0.218-0.023h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.642c0 0.103-0.080 0.183-0.183 0.183l-2.707-0.023c-0.654 0.023-1.308 0.218-1.87 0.562s-1.032 0.837-1.353 1.411c-0.161 0.287-0.287 0.596-0.367 0.918-0.046 0.161-0.069 0.321-0.092 0.493-0.011 0.080-0.011 0.161-0.023 0.252v1.675l-0.023 2.902c-0.023 3.865-0.057 7.719-0.069 11.584l-0.011 2.89v2.202c0.011 0.344 0.057 0.688 0.149 1.009 0.183 0.665 0.551 1.262 1.032 1.743s1.090 0.837 1.755 1.021c0.333 0.092 0.677 0.138 1.021 0.138h27.733c0.080 0 0.172-0.011 0.252-0.011 0.172-0.023 0.344-0.046 0.505-0.080 0.677-0.149 1.296-0.493 1.801-0.941 0.505-0.459 0.895-1.044 1.101-1.698 0.115-0.321 0.172-0.665 0.195-1.009 0-0.080 0-0.172 0.011-0.252v-0.195zM25.359 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.057-0.172 0.126-0.241s0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.183 0.493-0.459 0.493-0.195 0-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0.011-0.011 0.011-0.069zM8.786 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.046-0.172 0.126-0.241 0.069-0.069 0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.218 0.493-0.459 0.493s-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0-0.011 0.011-0.069zM33.308 7.157v1.445l-0.011 2.89-0.057 11.584-0.011 2.902v2.099c-0.011 0.138-0.034 0.275-0.080 0.413-0.092 0.264-0.252 0.505-0.459 0.7-0.218 0.183-0.47 0.321-0.734 0.378-0.057 0.011-0.115 0.023-0.183 0.034-0.034 0-0.092 0.011-0.138 0.011h-27.642c-0.138 0-0.275-0.011-0.413-0.057-0.264-0.069-0.516-0.218-0.723-0.413s-0.356-0.447-0.436-0.711c-0.046-0.138-0.057-0.275-0.069-0.413v-2.145l-0.011-2.89c-0.011-3.865-0.046-7.719-0.069-11.584l-0.034-2.913-0.011-1.445v-0.126c0-0.034 0-0.080 0.011-0.115 0.011-0.069 0.023-0.149 0.034-0.218 0.034-0.149 0.092-0.287 0.161-0.424 0.149-0.264 0.356-0.505 0.619-0.665 0.264-0.172 0.551-0.264 0.86-0.287l2.707-0.034c0.069 0 0.115 0.046 0.115 0.115l0.011 0.688c0 0.034 0 0.126 0.011 0.195 0 0.080 0.011 0.149 0.023 0.229 0.046 0.31 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.275-0.011 0.551-0.011c0.642 0 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.023-0.447c0-0.069 0.057-0.126 0.138-0.126 0.872 0.034 9.864 0.034 10.724-0.034 0.057 0 0.103 0.034 0.103 0.092 0 0.241 0.023 0.849 0.046 1.067 0.034 0.275 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.367 0 0.551-0.011c0.665-0.034 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.011-0.275 0.011-0.218c0-0.080 0.069-0.138 0.138-0.138l1.296 0.011h0.723c0.229 0 0.528 0 0.631 0.011 0.298 0.023 0.585 0.138 0.837 0.31s0.447 0.413 0.585 0.677c0.069 0.138 0.115 0.275 0.138 0.424 0.011 0.069 0.023 0.149 0.023 0.218v0.31z"></path> <path d="M30.509 10.598c-2.11-0.069-4.221-0.103-6.331-0.126-1.055-0.011-2.11-0.023-3.166-0.023l-3.166-0.011-3.166 0.011-3.166 0.023-4.748 0.069-1.583 0.034c-0.413 0.011-0.757 0.344-0.768 0.768-0.011 0.436 0.333 0.791 0.768 0.803l1.583 0.034 4.748 0.069 3.166 0.023 3.166 0.011 3.166-0.011c1.055 0 2.11-0.023 3.166-0.023 2.11-0.023 4.221-0.057 6.331-0.126 0.39-0.011 0.723-0.333 0.734-0.723 0.011-0.436-0.31-0.791-0.734-0.803zM15.771 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM22.366 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM29.075 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.057-0.952-0.505-1.135zM9.049 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.046-0.952-0.505-1.124zM15.771 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM22.366 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM29.075 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.057-0.952-0.505-1.124zM9.049 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.046-0.963-0.505-1.135zM15.771 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.963-0.505-1.135z"></path>
            </svg>
        </div> -->
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="createWindow = true" :style="{zIndex: initialLoader ? 41 : 7}">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>

        <!-- <div :title="`新規作成 ${fastCreate.time}`" id="scheduleCreateFast" v-if="fastCreate.x && fastCreate.y && $store.state.menu.name == 'scheduleCreateFast' && $store.state.menu.id == 896" :style="{left: `${fastCreate.x - 15}px`, top: `${fastCreate.y -45}px`}" class="createBoardButton fileNewButton fastCreateButton" @click="createWindow = true">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div> -->
        <CalendarFastCreateButton 
            :data="fastCreate" 
            @close="resetFastCreate"
            v-if="fastCreate.time && $store.state.menu.name == 'scheduleCreateFast' && $store.state.menu.id == 896"
            @click="fastCreateOpen"
        />
        <Transition name="modalFade">                              
            <CalendarCreate 
                v-if="createWindow"   
                :editTarget="editTarget"   
                :facilitiesList="facilitiesList"  
                :preSelected="preSelected"
                :edit_all_record="edit_all_record"
                @close="closeCreate"      
            />
            
        </Transition> 
        <CalendarDragginItem v-if="$store.state.draggingCalendar"/>
    </div>
        
</template>
<script>
import HamBurger from '../Global/HamBurger.vue';
import PostSearchBar from '../Post/PostSearchBar.vue'
import DayView from './DayView.vue'
import moment from 'moment';
import MonthPicker from '../Global/MonthPicker.vue'
import { nextTick } from 'vue'
import CalendarCreate from './CalendarCreate.vue';
import CalendarBar from './CalendarBar.vue'
import MonthView from './MonthView.vue'
import WeekView from './WeekView.vue'
import ListView from './ListView.vue'
import DayPicker from './List/DayPicker.vue';
import CalendarSearchResults from './CalendarSearchResults.vue';
import CalendarDragginItem from './CalendarDragginItem.vue';
import CalendarShiftButton from './CalendarShiftButton.vue';
import holiday_jp from '@holiday-jp/holiday_jp'
import CalendarFastCreateButton from './CalendarFastCreateButton.vue'

export default{
    props: ['initial_date'],
    data() {
        return {
            currentDate: moment(),
            daysInMonth: [],
            topOffset: 0,
            bottomOffset: 0,
            appendLock: true,
            records: [],
            selectedMonth: moment().month(),
            selectedYear: moment().year(),  
            selectedDay: moment().date(),
            activeMonth: moment().month(),
            activeYear: moment().year(), 
            createWindow: false,
            editTarget: null,
            initialLoader: true,
            viewType: -1,
            slideCount: -1,
            tempRecord: null,
            searchKey: '',
            searchResult: [],
            searchFetch: 0,
            searchView: false,
            facilitiesList: [],
            searching: false,
            preSelected: null,
            prevScrollTop: 0,
            prevScrollLeft: 0,
            jumpTo: null,
            fastCreate: {
                x: 0,
                y: 0,
                time: null,
                stamp: null
            },
            edit_all_record: true,
            activeMembers: [],
            listDetails: null
        };
    },
    components:{
        HamBurger,
        PostSearchBar,
        DayView,
        MonthPicker,
        CalendarCreate,
        CalendarBar,
        MonthView,
        CalendarSearchResults,
        CalendarDragginItem,
        CalendarShiftButton,
        CalendarFastCreateButton,
        ListView,
        DayPicker,
        WeekView
    },
    unmounted(){
        window.removeEventListener("keydown", this.onKeyDown);
    },        
    mounted(){
        if(this.$route.query && this.$route.query.id && this.initial_date){
            
            const tempId = parseInt(this.$route.query.id)
            const m = moment(this.initial_date).month();
            const y = moment(this.initial_date).year();
            this.activeMonth = this.selectedMonth = m;
            this.activeYear = this.selectedYear = y;
            this.viewType = 0 
            const date = moment(this.initial_date).startOf('month').format('YYYY-MM-DD')
            this.getCalendar(date, 'mounted')
            // console.log('hasId', tempId)
            this.$store.commit('setTempRecord', tempId)
        }else{
            const date = moment().format('YYYY-MM-DD')
            this.getCalendar(date, 'mounted')            
            const type = parseInt(localStorage.getItem('viewType'))    
            this.viewType = type > -1 ? type : 1           
            
        }
        this.getFacilities()        
        window.addEventListener("keydown", this.onKeyDown);
        if(this.$store.state.sharingData){
            this.createWindow = true
        }
        this.updateGoogleCalendar()
    },
    computed:{
        selectedDate(){
            return moment([this.selectedYear, this.selectedMonth, this.selectedDay]).format('YYYY-MM-DD')
        },
        holidays(){
            const holidays = holiday_jp.between(new Date(this.activeYear - 1 + '-12-01'), new Date(this.activeYear + 1 + '-1-31'));
            return holidays
        },
        daysOfMonth() {
            
            const thisMonth = moment([this.activeYear, this.activeMonth]);
            const firstDayOfMonth = thisMonth.clone().subtract(this.topOffset, 'months').startOf('month');
            const lastDayOfMonth = thisMonth.clone().add(this.bottomOffset, 'months').endOf('month');
            const days = [];

            let currentDay = firstDayOfMonth.clone();
            while (currentDay.isSameOrBefore(lastDayOfMonth, 'day')) {
                const holiday = this.holidays.find(h => moment(h.date).isSame(currentDay, 'day'));
                days.push({
                    full: currentDay.format('YYYY-MM-DD'),
                    day: currentDay.format('D'),
                    day_holiday : holiday ? holiday.name : null,
                });
                currentDay.add(1, 'day');
            }

            return days;
        },
        recordList()
        {
            return this.records && this.records.length ? this.records : []
        },
        dayRecords(){
            const date = moment([this.selectedYear, this.selectedMonth, this.selectedDay]).format('YYYY-MM-DD')
            return this.recordList.filter(record => moment(record.date_start).isSame(date, 'day'))
       

        }
    },
    methods:{
        setListView(data){
            // const date = moment([this.selectedYear, this.selectedMonth, this.selectedDay]).format('YYYY-MM-DD')
            // console.log(date)
            // return
            // console.log(data)
            // this.listDetails = data
            const day = moment(data).date()
            this.selectedDay = day
            this.viewType = 3
        },
        updateGoogleCalendar(){
            // if(this.$store.state.user.)
        },
        fastCreateOpen(){
            if(this.fastCreate.time){
                const hour = moment(this.fastCreate.time).format('YYYY-MM-DD HH:mm:ss')
                this.preSelected = hour
                this.createWindow = true
                this.fastCreate = {
                    x: 0,
                    y: 0,
                    time: null,
                    stamp: null
                }
            }
            

        },
        shiftToMonth(direction){
            this.appendLock = true  
            this.initialLoader = true                
            const current = moment([this.activeYear, this.activeMonth])

            const index = direction == 'down' ? 1 : -1
              
            const new_month = current.clone().add(index, 'month').startOf('month').format('YYYY-MM-DD')                    
            this.getCalendar(new_month, 'shift')
            const m = current.clone().add(index, 'month').month()
            const y = current.clone().add(index, 'month').year()
            this.activeMonth = this.selectedMonth = m
            this.activeYear = this.selectedYear = y
            
                
                                     
            
        },
        addRecord(type, value){
            if(type == 'day'){
                console.log(value)
                const hour = moment().add(1, 'hour').startOf('hour').hour()
                const d = moment(value).hour(hour).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss')
                console.log(d)
                this.preSelected = d
                this.createWindow = true

            }
        },
        deleteRecordConfirm(record){
            
            var uniqueChannell = Math.random().toString(36).substring(5);   
            let question = record.repetition_type > 0 ? '繰り返しスケジュールすべて削除しますか。' : 'スケジュールを削除しますか。'
            let answers = record.repetition_type > 0 ? ['すべて', 'このスケジュールのみ'] : ['はい', 'いいえ']
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: question,
                closeButton: true, 
                autoClose: false,
                answers: answers,
                channel: uniqueChannell

            })            
            emitter.on(uniqueChannell, (data) => { 
                data.answer 
                if(record.repetition_type > 0){
                    const val = data.answer == answers[0] ? true : false
                    this.deleteRecord(record, val)
                }
                else{
                    if(data.answer == answers[0]){
                        this.deleteRecord(record, false)
                    }
                }
            });
            
            

        },
        deleteRecord(record, all_delete){
            axios.post('/calendar_delete_record',{id:record.id, all_delete: all_delete}).then(response => {  
                if(response.data){
                    
                    const date = moment([this.selectedYear, this.selectedMonth, 1]).format('YYYY-MM-DD')
                    this.getCalendar(date, 'updated')
                    const data = {
                        text: '削除しました。',
                        channel: Math.random().toString(36).substring(5),
                        icon: 0,
                        view: true
                    }
                    emitter.emit('setInfo', data)
                    
                }
                             
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        onKeyDown(e){
            if(e.keyCode == 27 && this.$store.state.draggingCalendar){
                this.$store.commit('setDraggingCalendar', null)
            }
        },
        dropFinish(record, date){
            axios.post('/calendar_drop',{id: record.id, date: date}).then(response => {  
                if(response.data){
                    const index = this.records.findIndex(ob => ob.id == response.data.id)
                    if(index > -1){
                        this.records[index] = response.data
                    }
                }
                             
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        editRecord(record){
            if(record.repetition_type > 0){
                var uniqueChannell = Math.random().toString(36).substring(5);   
                const answers = ['すべて', 'このスケジュールのみ']
                emitter.emit('setToast', {
                    active: true,  
                    type: 'info', 
                    content: '繰り返しスケジュールのすべてのレコードが編集しますか。',
                    closeButton: true, 
                    autoClose: false,
                    answers: answers,
                    channel: uniqueChannell

                })            
                emitter.on(uniqueChannell, (data) => { 
                    const val = data.answer == answers[0] ? true : false
                    this.edit_all_record = val
                    this.editTarget = record
                    this.createWindow = true
                });
            }else{
                this.editTarget = record
                this.createWindow = true
            }

        },
        setFacility(index, sub_index, value){
            this.facilitiesList[index][sub_index].selected = value
            const dateInstance = moment([ this.selectedYear, this.selectedMonth, 1 ]).format('YYYY-MM-DD');
            this.getCalendar(dateInstance, 'updated')
        },
        getFacilities(){
            axios.post('/get_all_facilities').then(response => this.facilitiesList = response.data)
        },
        searchStart(val){
            // this.searchView = true
            this.$store.commit('setMenu', {id : 26, name: 'calendarSearchResultWindow'})
            this.searchKey = val            
            if(val && val.length){
                this.searching = true
                axios.post('/get_calendar_search',{key: val}).then(response => {                      
                    this.searchResult = response.data        
                    this.searchFetch ++     
                    this.searching = false        
                })
            }else{
                this.searchResult = []
                this.searching = false
            }
            
        },
        switchView(val){
            this.$store.commit('setMenu', {name: '', id: null})
            this.initialLoader = true
            this.viewType = val

            const selected = moment([this.selectedYear, this.selectedMonth , 1])
            const thisMonth = moment().isSame(selected, 'month')
            if(thisMonth){
                nextTick(() => {
                    this.jumpExecute(moment().format('YYYY-MM-DD'))
                    
                })
                
            }
            nextTick(() => {
                this.initialLoader = false
            })
            localStorage.setItem('viewType', val)
        },
        userUpdated(){
            const dateInstance = moment([ this.selectedYear, this.selectedMonth, 1 ]).format('YYYY-MM-DD');
            this.getCalendar(dateInstance, 'updated')
        },
        jumpToDate(date){
            console.log('jj')
            this.appendLock = true
            const dataDate = moment(date)
            if(this.selectedMonth !== dataDate.month() || this.selectedYear !== dataDate.year()){
                this.selectedYear = dataDate.year()
                this.selectedMonth = dataDate.month()
                const diff = dataDate.startOf('month').format('YYYY-MM-DD')
                this.getCalendar(diff)
            }
            if(this.activeMonth !== dataDate.month() || this.activeYear !== dataDate.year()){
                this.activeYear = dataDate.year()
                this.activeMonth = dataDate.month()
            }
            this.topOffset = 0
            this.bottomOffset = 0
            this.viewType = 0
            nextTick(() => {
                document.getElementById(`day_val_${date}`)?.scrollIntoView()
            })
            setTimeout(() => {
                this.appendLock = false
            }, 500);
        },  
        fromMonth(data){
            
            this.$store.commit('setTempRecord',data.id)
            this.appendLock = true
            const dataDate = moment(data.date_start)
            if(this.selectedMonth !== dataDate.month() || this.selectedYear !== dataDate.year()){
                this.selectedYear = dataDate.year()
                this.selectedMonth = dataDate.month()
                const diff = dataDate.startOf('month').format('YYYY-MM-DD')
                this.initialLoader = true
                this.records = []
                this.getCalendar(diff, 'fromMonth')
            }
            if(this.activeMonth !== dataDate.month() || this.activeYear !== dataDate.year()){
                this.activeYear = dataDate.year()
                this.activeMonth = dataDate.month()
            }
            this.topOffset = 0
            this.bottomOffset = 0
            this.viewType = 0
            
            setTimeout(() => {
                this.appendLock = false
            }, 500);
        },
        slided(realIndex, previous){
            if(!this.appendLock){
                this.slideCount ++
                if(this.slideCount > 0){
                    if(previous == 11 && realIndex == 0){
                        this.selectedYear++
                    }else if(previous == 0 && realIndex == 11){
                        this.selectedYear--
                    }
                    this.selectedMonth = realIndex
                    const dateInstance = moment([ this.selectedYear, this.selectedMonth, 1 ]).format('YYYY-MM-DD');
                    // this.records = []
                    this.getCalendar(dateInstance, 'slided')
                }
            }   
            
            
        },
        jumpToToday(){
            this.appendLock = true
            const val = moment().format('YYYY-MM-DD')           
            if(moment().month() == this.selectedMonth){
                this.jumpExecute(val)
                setTimeout(() => {
                    this.appendLock = false
                }, 100);
                this.selectedDay = moment().date()
            }else{
                this.selectedMonth = this.activeMonth = moment().month()
                this.selectedYear = this.activeYear = moment().year()
                this.selectedDay = moment().date()
                console.log(this.selectedDay)
                this.records = []
                const date = moment([this.selectedYear, this.selectedMonth , 1]).format('YYYY-MM-DD')
                this.getCalendar(date, 'mounted')
            }
                      
        },
        jumpExecute(day){
            const id = this.viewType == 0 ? 'day_val_' : this.viewType == 1 ? 'day_val_m_' : this.viewType == 2 ? 'day_val_w_' : '_'
            const el = document.getElementById(id + day)   
            if(el){
                if(this.viewType == 2){
                    const el = this.$refs.weekView?.$refs[`w_day_${day}`]
                    if(el && el.length){
                        const rect = el[0].getBoundingClientRect()
                        const r_el = this.$refs.weekView?.$refs.cal_week_view
                        const space = this.$refs.weekView?.$refs.spacer
                        console.log(rect)
                        if(r_el && space){
                            const index = this.$store.state.mobile ? 0 : 60
                            const l = rect.x - space.getBoundingClientRect().width - index
                            console.log(l)
                            r_el.scrollBy(l, 0)
                        }
                    }
                }else{
                    el.scrollIntoView({block: 'start', behavior: 'instant'})
                }                
                if(this.viewType == 1){
                    this.$refs.monthView?.$refs.monthScrollContainer?.scrollBy(0, -40)
                    
                }   
                nextTick(() => {
                        this.initialLoader = false
                    })
            }     
                                 
            this.initialLoader = false
                    
        },
        closeCreate(val){
            this.createWindow = false
            this.editTarget = null
            this.preSelected = null
            if(val){
                const date = moment([this.selectedYear, this.selectedMonth, 1]).format('YYYY-MM-DD')
                this.getCalendar(date, 'updated')
            }
        },
        setDate(date){
            this.appendLock = true
            this.bottomOffset = 0
            this.topOffset = 0
            this.activeMonth = date.month - 1
            this.activeYear = date.year
            this.selectedMonth = date.month - 1
            this.selectedYear = date.year
            let d = 1
            if(date.day){
                this.selectedDay = date.day
                d = date.day
            }
            
            this.records = []
            const dateInstance = moment([ date.year, date.month - 1, d ]).format('YYYY-MM-DD');
            this.getCalendar(dateInstance, 'setDate')
            
            
        },
        createAtTime(data){    
            if(this.$store.state.menu.name && this.$store.state.menu.name !== 'scheduleCreateFast'){
                this.$store.commit('setMenu', {id: null, name: ''})
                return
            }           
            this.fastCreate = data
            this.$store.commit('setMenu', {id: 896, name: 'scheduleCreateFast'})            
        },
        resetFastCreate(){
            this.fastCreate = { x: 0, y: 0, time: null, stamp: null}
        },
        scrollListen(){
            this.resetFastCreate()
            const container = event.currentTarget;
            let direction = container.scrollTop > this.prevScrollTop ? 'down' : 'up'
            this.prevScrollTop = container.scrollTop;
            const tempMonth = this.selectedMonth            
            if(!this.appendLock){
                var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
                if(percent >= 97){   
                    this.jumpTo = 'down'                    
                }else if(percent <= 3){
                    this.jumpTo = 'up'                   
                }else{
                    this.jumpTo = null
                }
            }
            if(this.prevScrollLeft !== container.scrollLeft){
                // this.jumpTo = null
            }
            this.prevScrollLeft = container.scrollLeft;
            // const container = event.currentTarget;
            // let direction = container.scrollTop > this.prevScrollTop ? 'down' : 'up'
            // this.prevScrollTop = container.scrollTop;
            // if(!this.appendLock){
            //     var percent = 100 * event.currentTarget.scrollTop / (event.currentTarget.scrollHeight - event.currentTarget.clientHeight);  
            //     if(percent > 99 && direction == 'down'){   
                    
            //         this.bottomOffset ++ 
            //         this.appendLock = true
            //         const current = moment([this.activeYear, this.activeMonth])
            //         const next = current.clone().add(this.bottomOffset, 'month').startOf('month').format('YYYY-MM-DD')                    
            //         this.getCalendar(next, 'next')
                    
            //     }else if(percent < 1 && direction == 'up'){
            //         this.topOffset ++ 
            //         this.appendLock = true
            //         const currentScroll = event.currentTarget.scrollTop
            //         const currentScrollHeight = event.currentTarget.scrollHeight                    
            //         const current = moment([this.activeYear, this.activeMonth])
            //         const prev = current.clone().subtract(this.topOffset, 'month').startOf('month').format('YYYY-MM-DD')
                    
            //         this.getCalendar(prev, 'prev')
            //         nextTick(() => {                  
                        
            //             this.$refs.DayView.$refs.cal_day_view.scrollTop = this.$refs.DayView.$refs.cal_day_view.scrollHeight - ( currentScrollHeight - currentScroll)
                        
            //         });  
                    
            //     }

            //     const parent = this.$refs['DayView'].$refs['dayParent']

            //     for (const el of parent.children) {                    
            //         const ref = el
            //         const month = el.id.substring(8, 15) 
            //         const day = el.id.substring(8, 18) 
            //         const lastDay = moment(day).endOf('month').format('YYYY-MM-DD')
            //         if(moment(day).endOf('month').isSame(moment(day), 'day')){
            //             const rect = el.getBoundingClientRect()
            //             const cath = rect.y + el.clientHeight;
                        
            //             if(cath < 60){
            //                 if(el.nextSibling){
            //                     const nextId = el.nextSibling.id.substring(8, 15) 
            //                     const [yearStr, monthStr] = nextId.split('-');
      
            //                     const year = parseInt(yearStr, 10); 
            //                     const month = parseInt(monthStr, 10) - 1; 
            //                     if(this.selectedMonth !== month){
            //                         this.selectedMonth = month
            //                     }
            //                     if(this.selectedYear !== year){
            //                         this.selectedYear = year
            //                     }
                            
            //                 }
            //             }else if(cath > window.innerHeight && cath < window.innerHeight + 100){
                            
                            
            //                 if(el.previousSibling){
            //                     const nextId = el.id.substring(8, 15) 
            //                     const [yearStr, monthStr] = nextId.split('-');
      
            //                     const year = parseInt(yearStr, 10); 
            //                     const month = parseInt(monthStr, 10) - 1; 
            //                     if(this.selectedMonth !== month){
            //                         this.selectedMonth = month
            //                     }
            //                     if(this.selectedYear !== year){
            //                         this.selectedYear = year
            //                     }
                            
            //                 }
            //             }
            //         }                   
            //     }

            // }
            // const rect = {
            //     x: event.currentTarget.scrollLeft,
            //     y: event.currentTarget.scrollTop
            // }
            // this.$store.commit('setCalendarOffset', rect)
            
            
        },
        jumpToRecord(record){
            this.appendLock = true
            this.$store.commit('setTempRecord',record.id)
            const date = moment(record.date_start).startOf('month').format('YYYY-MM-DD')
            this.selectedMonth = this.activeMonth = moment(record.date_start).month()
            this.selectedYear = this.activeYear = moment(record.date_start).year()
            this.viewType = 0
            this.records = []
            this.getCalendar(date)
            console.log(date)
            this.searchView = false
            
        },
        getCalendar(day, method){
            let fac = {}
            for(const index in this.facilitiesList){
                
                const values = this.facilitiesList[index].filter(ob => ob.selected).map(ob => ob.value)
                console.log(index, values)
                if(values && values.length){
                    fac[index] = values
                }   

                
            }
            axios.post('/get_calendar_data',{day: day, facilities: fac, view_type: this.viewType}).then(response => {  
                
                if(method == 'updated'){
                    const valid_id = response.data.map(ob => ob.id)
                    this.records = this.records.filter(ob => valid_id.includes(ob.id))
                }
                response.data.forEach(item => {
                    const existingItem = this.records.find(record => record.id === item.id);
                    if (!existingItem) {
                        this.records.push(item);
                    }
                    
                });                                
                if(method == 'mounted'){
                    setTimeout(() => {     
                        const date = moment().format('YYYY-MM-DD')                   
                        this.jumpExecute(date)                       
                        this.initialLoader = false                        
                    })                    
                }                
                if(method == 'shift'){
                    nextTick(() => {     
                        let create = moment(day).startOf('month').format('YYYY-MM-DD')   
                        if(this.jumpTo == 'up'){
                            create = moment(day).endOf('month').format('YYYY-MM-DD')                            
                        }   
                        this.jumpExecute(create)                                               
                        this.initialLoader = false        
                        this.jumpTo = null                
                    })                    
                }
                if(method == 'fromMonth'){
                    nextTick(() => {                          
                        this.initialLoader = false                        
                    })                    
                }
                
                setTimeout(() => {
                    this.appendLock = false
                }, 200);               
        
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t(error.response.data.message))
                else if (error.request) this.errorToast(this.$t('commonError'))
                else this.errorToast(this.$t('commonError'))                          
            }.bind(this));
        },
        errorToast(message){
            emitter.emit('setToast', {
                active: true,  
                type: 'info', 
                content: message,
                closeButton: false, 
                autoClose: false,
                answers: ['OK']

            })   
        },
    }
}
</script>