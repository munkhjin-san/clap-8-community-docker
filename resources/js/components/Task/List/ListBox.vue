<template>


    <div :class="boxClass">
        <div class="flex justify-between">            
            <div :id="'task_box_' + item.id" class="task-box-inner" :style="{backgroundColor: taskColor.mycolor, color: taskColor.color, position: 'relative', cursor: 'pointer', width: '-webkit-fill-available'}">
                <div :class="[{'justify-between' : isBoard, 'gap-[10px]' : !isBoard},'flex', 'items-center', 'w-full', 'relative']" :style="{ marginTop: responsive.mobile ? '0' : '5px'}">
                    <div class="flex items-center gap-[10px] text-sm overflow-hidden" @click.stop="router.push(`/project/${item.project_record_id}`)" v-if="item.project">
                        <svg :fill="taskColor.mycolor === myColor ? '#000' : 'var(--primary-color)'" height="23" width="20" @click.stop="router.push(`/project/gantt-chart/${item.project_record_id}`)" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 32" style="height: 20px;min-width:17px;overflow: visible;">
                            <path d="M6.058 20.463c0.444 0.332 0.999 0.537 1.602 0.555l0.004 0h4.641l0.213-0.012c0.455-0.035 0.87-0.18 1.227-0.408l-0.011 0.006c0.496-0.295 0.886-0.756 1.11-1.287l0.413-1.039c0.035-0.094 0.153-0.154 0.248-0.118l2.078 0.744c0.094 0.035 0.165 0.094 0.224 0.165l1.004 1.287 0.638 0.815 0.319 0.413 0.118 0.142 0.13 0.142 0.295 0.26c0.459 0.348 1.039 0.558 1.669 0.558 0.483 0 0.938-0.123 1.333-0.341l-0.014 0.007c0.461-0.248 0.85-0.626 1.122-1.086 0.221-0.394 0.352-0.866 0.352-1.368 0-0.339-0.060-0.665-0.169-0.967l0.006 0.020c-0.094-0.248-0.224-0.484-0.378-0.697l-0.649-0.827-1.263-1.606-0.638-0.815-0.094-0.13-0.13-0.142c-0.271-0.269-0.592-0.488-0.948-0.642l-0.020-0.008-0.543-0.201c-0.083-0.024-0.106-0.13-0.047-0.189l0.449-0.531c0.047-0.047 0.106-0.071 0.165-0.035l2.362 0.992c0.142 0.047 0.224 0.106 0.461 0.177 0.229 0.066 0.492 0.104 0.765 0.104 0.6 0 1.158-0.184 1.619-0.5l-0.010 0.006 0.248-0.189 0.118-0.106 0.083-0.083 0.579-0.602 2.314-2.397 0.378-0.378 0.106-0.118c0.071-0.071 0.13-0.165 0.189-0.248 0.236-0.342 0.378-0.744 0.425-1.145 0.106-0.815-0.083-1.464-0.472-1.937-0.519-0.652-1.313-1.066-2.203-1.066-0.102 0-0.202 0.005-0.301 0.016l0.012-0.001c-0.618 0.079-1.162 0.348-1.584 0.745l0.001-0.001-0.059 0.047-0.248 0.248-1.818 1.842-0.614-0.26c1.19-0.829 1.96-2.191 1.96-3.732 0-2.071-1.389-3.818-3.285-4.36l-0.032-0.008-0.378-0.094-0.39-0.059c-0.13-0.024-0.26-0.024-0.39-0.024-0.040-0.001-0.087-0.002-0.134-0.002-1.603 0-3.020 0.792-3.882 2.006l-0.010 0.015c-0.495 0.715-0.791 1.6-0.791 2.555 0 0.161 0.008 0.32 0.025 0.476l-0.002-0.020 0.024 0.177-0.85-0.354-0.543-0.224-0.13-0.059-0.035-0.012-0.059-0.024-0.118-0.035c-0.254-0.082-0.546-0.129-0.849-0.129-0.41 0-0.801 0.087-1.153 0.242l0.018-0.007-0.142 0.059-1.063 0.484-2.125 0.956c-0.189 0.083-0.319 0.13-0.614 0.295-0.835 0.495-1.387 1.391-1.387 2.416 0 0.568 0.169 1.097 0.46 1.538l-0.006-0.011c0.515 0.766 1.379 1.262 2.358 1.262 0.287 0 0.565-0.043 0.826-0.122l-0.020 0.005 0.213-0.071 0.106-0.047 0.047-0.024 0.035-0.012 0.272-0.118 0.531-0.236 1.547-0.697c0.106-0.047 0.224-0.047 0.319 0l0.142 0.071c0.047 0.024 0.059 0.083 0.024 0.118l-1.63 1.889-0.118 0.142c-0.138 0.184-0.257 0.394-0.348 0.619l-0.007 0.019-0.083 0.224-0.756 1.889c-0.035 0.071-0.106 0.13-0.189 0.13h-2.562c-0.61 0.006-1.168 0.219-1.611 0.571l0.005-0.004c-0.682 0.521-1.117 1.334-1.117 2.249 0 0.31 0.050 0.609 0.143 0.888l-0.006-0.020c0.143 0.581 0.485 1.066 0.949 1.388l0.008 0.005zM6.967 17.984c0.053-0.16 0.152-0.294 0.281-0.388l0.002-0.002c0.071-0.047 0.142-0.094 0.213-0.118 0.083-0.024 0.165-0.047 0.248-0.047h3.318c0.401 0 0.779-0.236 0.945-0.638l1.063-2.657 0.083-0.224 0.047-0.094 0.059-0.083 0.035-0.035 0.071-0.083 2.492-2.893 0.153-0.236c0.236-0.508 0.012-1.11-0.496-1.334l-2.031-0.933h-0.012c-0.121-0.058-0.263-0.093-0.413-0.093s-0.292 0.034-0.419 0.095l0.006-0.003-2.125 0.956-0.531 0.236-0.26 0.118-0.035 0.012-0.024 0.012-0.035 0.012-0.059 0.024c-0.165 0.059-0.354 0.047-0.52-0.012-0.171-0.056-0.311-0.168-0.4-0.316l-0.002-0.003c-0.082-0.123-0.131-0.274-0.131-0.437 0-0.017 0.001-0.033 0.002-0.050l-0 0.002c0.021-0.273 0.175-0.506 0.398-0.636l0.004-0.002c0.104-0.055 0.248-0.123 0.394-0.185l0.043-0.016 2.125-0.956 1.063-0.484 0.118-0.059 0.059-0.024 0.13-0.035h0.272l0.13 0.024 0.024 0.012h0.012l0.035 0.012 0.13 0.059 0.531 0.224 1.075 0.449 2.137 0.897c0.354 0.153 0.791 0.071 1.075-0.224 0.175-0.175 0.284-0.417 0.284-0.685 0-0.273-0.113-0.52-0.295-0.696l-0-0c-0.483-0.468-0.783-1.124-0.783-1.849 0-0.545 0.169-1.050 0.458-1.466l-0.006 0.009c0.342-0.496 0.862-0.886 1.441-1.075 0.295-0.094 0.602-0.13 0.909-0.118l0.236 0.012 0.224 0.035 0.224 0.059 0.224 0.071c0.579 0.213 1.086 0.626 1.393 1.157 0.307 0.52 0.437 1.157 0.331 1.759-0.094 0.602-0.413 1.169-0.886 1.582-0.459 0.398-1.053 0.652-1.705 0.685l-0.007 0h-0.012c-0.354 0.025-0.651 0.243-0.789 0.549l-0.002 0.006c-0.046 0.105-0.072 0.227-0.072 0.355 0 0.375 0.227 0.696 0.55 0.836l0.006 0.002 3.342 1.405h0.012c0.331 0.142 0.72 0.071 0.992-0.201l2.232-2.303 0.272-0.272 0.165-0.13c0.151-0.098 0.336-0.157 0.535-0.157 0.247 0 0.474 0.091 0.647 0.24l-0.001-0.001c0.172 0.18 0.278 0.424 0.278 0.693 0 0.039-0.002 0.077-0.006 0.115l0-0.005c-0.023 0.152-0.077 0.287-0.156 0.405l0.002-0.004-0.071 0.083-0.035 0.035-0.024 0.035-0.331 0.331-2.35 2.362-0.697 0.697-0.083 0.071-0.189 0.094c-0.13 0.047-0.272 0.071-0.413 0.059l-0.213-0.035-0.307-0.13-3.047-1.311c-0.354-0.153-0.791-0.071-1.051 0.248v0.012l-1.948 2.267c-0.137 0.161-0.22 0.371-0.22 0.601 0 0.399 0.25 0.739 0.603 0.873l0.006 0.002h0.012l1.913 0.697c0.071 0.035 0.154 0.071 0.213 0.13l0.094 0.083 0.047 0.047 0.059 0.071 0.626 0.815 1.252 1.641 0.614 0.803 0.118 0.213 0.047 0.224c0 0.083 0 0.154-0.024 0.236l-0.083 0.224c-0.088 0.136-0.203 0.247-0.338 0.328l-0.005 0.003c-0.115 0.060-0.252 0.095-0.397 0.095-0.193 0-0.371-0.062-0.515-0.168l0.002 0.002-0.094-0.083-0.047-0.047-0.047-0.059-0.319-0.413-0.626-0.815-1.263-1.63c-0.11-0.145-0.258-0.256-0.43-0.317l-0.006-0.002-3.554-1.299c-0.098-0.035-0.211-0.055-0.328-0.055-0.412 0-0.766 0.248-0.921 0.604l-0.002 0.006-0.815 2.043c-0.069 0.157-0.183 0.283-0.327 0.364l-0.004 0.002c-0.067 0.046-0.147 0.079-0.233 0.094l-0.003 0-0.13 0.024h-0.189l-4.487 0.012c-0.165 0-0.331-0.059-0.472-0.165-0.195-0.141-0.321-0.368-0.321-0.624 0-0.085 0.014-0.167 0.039-0.243l-0.002 0.005zM39.014 24.030c-0.094-1.252-0.567-1.925-1.016-2.161s-1.004-0.295-1.488-0.142l0.083-0.024-8.892 1.901-8.892 1.925-14.678 3.436c-2.964 0.649-4.133 1.24-4.133 1.972s0.555 0.909 1.996 1.027c1.157 0.094 10.556 0.047 10.556 0.047l22.388-0.224s1.948-0.047 2.397-0.118c0.449-0.071 0.779-0.236 1.075-0.649 0.295-0.425 0.449-0.968 0.567-1.913s0.13-3.826 0.035-5.078zM36.9 29.024c0 0 0 0 0 0 0 0.322-0.258 0.584-0.578 0.59h-0.001c-0.862 0.035-2.066 0.071-3.259 0.083-1.488 0.012-5.396-0.012-9.34 0.071l-15.162 0.272c-0.165 0-0.189-0.236-0.035-0.272l1.807-0.366s7.002-1.452 8.903-1.83c1.901-0.378 8.903-1.854 8.903-1.854l8.266-1.736c0.025-0.005 0.053-0.009 0.083-0.009 0.221 0 0.4 0.178 0.401 0.398v0c0.012 1.039 0.024 3.46 0.012 4.652z"></path>
                        </svg>
                        <p class="text-sm overflow-hidden text-ellipsis whitespace-nowrap">{{item.project.name}}</p>
                    </div>
                    <div class="flex items-center gap-[10px] text-sm overflow-hidden" @click.stop="jumpToTask" v-else-if="item.board">
                        <BoardIcon :item="item.board" :imgClass="'userNormalIcon'"/>
                        <BoardTitle class="whitespace-nowrap" :item="item.board"/>
                    </div>
                    <svg v-if="!isBoard" :fill="taskColor.mycolor === myColor ? '#000' : 'var(--primary-color)'" style="transform: rotate(180deg);" version="1.1" width="11" height="11" class="cursor-pointer" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg>
                    <div @click="viewTaskUsers" class="flex w-fit">
                        <div v-for="user in taskUsers.slice(0, 3)" style="position:relative;">
                            <div v-if="user" :title="user.name" class="column-01">
                                <UserPanel size="15" :disableInstant="true" :user="user" imgClass="u_icon_15"/>   
                                <div title="タスクが完了しました" v-if="user.pivot.progress_flag > 0" class="completed-badge-large completed-badge-medium" :style="{background: taskStatusBackgrounds[user.pivot.progress_flag]}"></div>                         
                            </div>
                        </div>                                                                                       
                        <p style="margin-top:2px;cursor:pointer;font-size: 12px;margin-left: 3px;" v-if="taskUsers && taskUsers.length > 3">({{taskUsers.length}})</p>                                            
                    </div> 
                    <div v-if="isBoard" class="flex gap-[5px] items-center">
                        <div v-if="selfMember?.pivot?.pin_flag == 1">
                            <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 32 32">
                                <path d="M19.713 28.513c0.045-0.043 0.121-0.125 0.187-0.193 0.067-0.070 0.128-0.148 0.192-0.22 0.122-0.151 0.236-0.306 0.34-0.466 0.414-0.641 0.679-1.346 0.817-2.061 0.137-0.716 0.151-1.449 0.033-2.176-0.062-0.386-0.164-0.773-0.311-1.149-0.037-0.095-0.022-0.198 0.040-0.277l3.236-4.041 3.276-4.116c0.070-0.089 0.184-0.134 0.297-0.121 0.133 0.013 0.267 0.022 0.401 0.022 0.466 0.005 0.925-0.055 1.364-0.169 0.44-0.115 0.861-0.282 1.258-0.502 0.397-0.221 0.773-0.489 1.117-0.834l0.008-0.008 0.005-0.006c0.427-0.434 0.42-1.131-0.013-1.559l-10.277-10.307c-0.44-0.44-1.152-0.441-1.593-0.001l-0.005 0.006c-0.347 0.347-0.618 0.728-0.837 1.129-0.217 0.404-0.38 0.829-0.489 1.269-0.143 0.567-0.191 1.16-0.141 1.75 0.010 0.109-0.034 0.218-0.12 0.286l-4.122 3.291-4.038 3.237c-0.078 0.062-0.184 0.076-0.277 0.040-0.376-0.147-0.762-0.247-1.148-0.31-0.727-0.117-1.46-0.103-2.176 0.033-0.716 0.138-1.42 0.405-2.062 0.818-0.16 0.104-0.316 0.218-0.467 0.339-0.072 0.065-0.149 0.125-0.22 0.193-0.068 0.065-0.15 0.142-0.193 0.187l-0.622 0.621c-0.486 0.485-0.487 1.271-0.001 1.756l0.001 0.002 5.901 5.914c0.058 0.058 0.059 0.15 0.004 0.21-0.199 0.217-0.399 0.433-0.6 0.648-0.394 0.424-0.787 0.852-1.185 1.27-0.796 0.843-1.596 1.679-2.387 2.528l-1.179 1.279-1.167 1.288c-0.775 0.862-1.555 1.722-2.321 2.593-0.333 0.378-0.325 0.964 0.053 1.333 0.365 0.355 0.955 0.347 1.338 0.008 0.863-0.758 1.714-1.529 2.567-2.297l1.288-1.169 1.279-1.179c0.847-0.79 1.685-1.592 2.527-2.386 0.419-0.401 0.846-0.792 1.271-1.186 0.216-0.199 0.431-0.399 0.647-0.6 0.061-0.055 0.153-0.053 0.211 0.005l5.916 5.901c0.484 0.485 1.269 0.484 1.753-0.001l0.625-0.623zM6.029 13.958c0.341-0.224 0.749-0.388 1.182-0.474 0.43-0.088 0.887-0.099 1.316-0.032 0.431 0.065 0.834 0.212 1.162 0.42l0.018 0.011c0.428 0.27 0.907 0.285 1.415-0.086l4.759-3.878 4.764-3.898c0.344-0.281 0.505-0.751 0.375-1.206-0.141-0.493-0.155-1.027-0.032-1.541 0.027-0.117 0.211-0.237 0.335-0.111l1.351 1.329 5.164 5.123 1.339 1.368c0.135 0.134-0.014 0.343-0.149 0.374-0.516 0.127-1.037 0.111-1.501-0.043-0.429-0.14-0.923-0.014-1.226 0.356l-0.013 0.018-3.894 4.744-3.88 4.759c-0.393 0.519-0.37 0.961-0.085 1.411l0.010 0.018c0.209 0.329 0.357 0.732 0.42 1.163 0.066 0.43 0.055 0.885-0.034 1.317-0.086 0.434-0.25 0.839-0.474 1.182 0 0.001-0.001 0.002-0.001 0.003-0.071 0.109-0.228 0.122-0.32 0.029l-6.010-6.024-6.022-6.010c-0.093-0.092-0.081-0.248 0.028-0.32 0.001 0 0.001 0 0.002-0.001z"></path>
                            </svg>
                        </div>
                        <ItemMenu 
                            v-if="completeButtonFilter"
                            :items="itemsCollection"
                        />
                        <!-- <div v-else-if="item.project_record_id">
                            <svg class="side-app-icon" @click.stop="router.push(`/project/${item.project_record_id}`)" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 32" style="height: 15px;min-width:17px;overflow: visible;">
                                <path d="M6.058 20.463c0.444 0.332 0.999 0.537 1.602 0.555l0.004 0h4.641l0.213-0.012c0.455-0.035 0.87-0.18 1.227-0.408l-0.011 0.006c0.496-0.295 0.886-0.756 1.11-1.287l0.413-1.039c0.035-0.094 0.153-0.154 0.248-0.118l2.078 0.744c0.094 0.035 0.165 0.094 0.224 0.165l1.004 1.287 0.638 0.815 0.319 0.413 0.118 0.142 0.13 0.142 0.295 0.26c0.459 0.348 1.039 0.558 1.669 0.558 0.483 0 0.938-0.123 1.333-0.341l-0.014 0.007c0.461-0.248 0.85-0.626 1.122-1.086 0.221-0.394 0.352-0.866 0.352-1.368 0-0.339-0.060-0.665-0.169-0.967l0.006 0.020c-0.094-0.248-0.224-0.484-0.378-0.697l-0.649-0.827-1.263-1.606-0.638-0.815-0.094-0.13-0.13-0.142c-0.271-0.269-0.592-0.488-0.948-0.642l-0.020-0.008-0.543-0.201c-0.083-0.024-0.106-0.13-0.047-0.189l0.449-0.531c0.047-0.047 0.106-0.071 0.165-0.035l2.362 0.992c0.142 0.047 0.224 0.106 0.461 0.177 0.229 0.066 0.492 0.104 0.765 0.104 0.6 0 1.158-0.184 1.619-0.5l-0.010 0.006 0.248-0.189 0.118-0.106 0.083-0.083 0.579-0.602 2.314-2.397 0.378-0.378 0.106-0.118c0.071-0.071 0.13-0.165 0.189-0.248 0.236-0.342 0.378-0.744 0.425-1.145 0.106-0.815-0.083-1.464-0.472-1.937-0.519-0.652-1.313-1.066-2.203-1.066-0.102 0-0.202 0.005-0.301 0.016l0.012-0.001c-0.618 0.079-1.162 0.348-1.584 0.745l0.001-0.001-0.059 0.047-0.248 0.248-1.818 1.842-0.614-0.26c1.19-0.829 1.96-2.191 1.96-3.732 0-2.071-1.389-3.818-3.285-4.36l-0.032-0.008-0.378-0.094-0.39-0.059c-0.13-0.024-0.26-0.024-0.39-0.024-0.040-0.001-0.087-0.002-0.134-0.002-1.603 0-3.020 0.792-3.882 2.006l-0.010 0.015c-0.495 0.715-0.791 1.6-0.791 2.555 0 0.161 0.008 0.32 0.025 0.476l-0.002-0.020 0.024 0.177-0.85-0.354-0.543-0.224-0.13-0.059-0.035-0.012-0.059-0.024-0.118-0.035c-0.254-0.082-0.546-0.129-0.849-0.129-0.41 0-0.801 0.087-1.153 0.242l0.018-0.007-0.142 0.059-1.063 0.484-2.125 0.956c-0.189 0.083-0.319 0.13-0.614 0.295-0.835 0.495-1.387 1.391-1.387 2.416 0 0.568 0.169 1.097 0.46 1.538l-0.006-0.011c0.515 0.766 1.379 1.262 2.358 1.262 0.287 0 0.565-0.043 0.826-0.122l-0.020 0.005 0.213-0.071 0.106-0.047 0.047-0.024 0.035-0.012 0.272-0.118 0.531-0.236 1.547-0.697c0.106-0.047 0.224-0.047 0.319 0l0.142 0.071c0.047 0.024 0.059 0.083 0.024 0.118l-1.63 1.889-0.118 0.142c-0.138 0.184-0.257 0.394-0.348 0.619l-0.007 0.019-0.083 0.224-0.756 1.889c-0.035 0.071-0.106 0.13-0.189 0.13h-2.562c-0.61 0.006-1.168 0.219-1.611 0.571l0.005-0.004c-0.682 0.521-1.117 1.334-1.117 2.249 0 0.31 0.050 0.609 0.143 0.888l-0.006-0.020c0.143 0.581 0.485 1.066 0.949 1.388l0.008 0.005zM6.967 17.984c0.053-0.16 0.152-0.294 0.281-0.388l0.002-0.002c0.071-0.047 0.142-0.094 0.213-0.118 0.083-0.024 0.165-0.047 0.248-0.047h3.318c0.401 0 0.779-0.236 0.945-0.638l1.063-2.657 0.083-0.224 0.047-0.094 0.059-0.083 0.035-0.035 0.071-0.083 2.492-2.893 0.153-0.236c0.236-0.508 0.012-1.11-0.496-1.334l-2.031-0.933h-0.012c-0.121-0.058-0.263-0.093-0.413-0.093s-0.292 0.034-0.419 0.095l0.006-0.003-2.125 0.956-0.531 0.236-0.26 0.118-0.035 0.012-0.024 0.012-0.035 0.012-0.059 0.024c-0.165 0.059-0.354 0.047-0.52-0.012-0.171-0.056-0.311-0.168-0.4-0.316l-0.002-0.003c-0.082-0.123-0.131-0.274-0.131-0.437 0-0.017 0.001-0.033 0.002-0.050l-0 0.002c0.021-0.273 0.175-0.506 0.398-0.636l0.004-0.002c0.104-0.055 0.248-0.123 0.394-0.185l0.043-0.016 2.125-0.956 1.063-0.484 0.118-0.059 0.059-0.024 0.13-0.035h0.272l0.13 0.024 0.024 0.012h0.012l0.035 0.012 0.13 0.059 0.531 0.224 1.075 0.449 2.137 0.897c0.354 0.153 0.791 0.071 1.075-0.224 0.175-0.175 0.284-0.417 0.284-0.685 0-0.273-0.113-0.52-0.295-0.696l-0-0c-0.483-0.468-0.783-1.124-0.783-1.849 0-0.545 0.169-1.050 0.458-1.466l-0.006 0.009c0.342-0.496 0.862-0.886 1.441-1.075 0.295-0.094 0.602-0.13 0.909-0.118l0.236 0.012 0.224 0.035 0.224 0.059 0.224 0.071c0.579 0.213 1.086 0.626 1.393 1.157 0.307 0.52 0.437 1.157 0.331 1.759-0.094 0.602-0.413 1.169-0.886 1.582-0.459 0.398-1.053 0.652-1.705 0.685l-0.007 0h-0.012c-0.354 0.025-0.651 0.243-0.789 0.549l-0.002 0.006c-0.046 0.105-0.072 0.227-0.072 0.355 0 0.375 0.227 0.696 0.55 0.836l0.006 0.002 3.342 1.405h0.012c0.331 0.142 0.72 0.071 0.992-0.201l2.232-2.303 0.272-0.272 0.165-0.13c0.151-0.098 0.336-0.157 0.535-0.157 0.247 0 0.474 0.091 0.647 0.24l-0.001-0.001c0.172 0.18 0.278 0.424 0.278 0.693 0 0.039-0.002 0.077-0.006 0.115l0-0.005c-0.023 0.152-0.077 0.287-0.156 0.405l0.002-0.004-0.071 0.083-0.035 0.035-0.024 0.035-0.331 0.331-2.35 2.362-0.697 0.697-0.083 0.071-0.189 0.094c-0.13 0.047-0.272 0.071-0.413 0.059l-0.213-0.035-0.307-0.13-3.047-1.311c-0.354-0.153-0.791-0.071-1.051 0.248v0.012l-1.948 2.267c-0.137 0.161-0.22 0.371-0.22 0.601 0 0.399 0.25 0.739 0.603 0.873l0.006 0.002h0.012l1.913 0.697c0.071 0.035 0.154 0.071 0.213 0.13l0.094 0.083 0.047 0.047 0.059 0.071 0.626 0.815 1.252 1.641 0.614 0.803 0.118 0.213 0.047 0.224c0 0.083 0 0.154-0.024 0.236l-0.083 0.224c-0.088 0.136-0.203 0.247-0.338 0.328l-0.005 0.003c-0.115 0.060-0.252 0.095-0.397 0.095-0.193 0-0.371-0.062-0.515-0.168l0.002 0.002-0.094-0.083-0.047-0.047-0.047-0.059-0.319-0.413-0.626-0.815-1.263-1.63c-0.11-0.145-0.258-0.256-0.43-0.317l-0.006-0.002-3.554-1.299c-0.098-0.035-0.211-0.055-0.328-0.055-0.412 0-0.766 0.248-0.921 0.604l-0.002 0.006-0.815 2.043c-0.069 0.157-0.183 0.283-0.327 0.364l-0.004 0.002c-0.067 0.046-0.147 0.079-0.233 0.094l-0.003 0-0.13 0.024h-0.189l-4.487 0.012c-0.165 0-0.331-0.059-0.472-0.165-0.195-0.141-0.321-0.368-0.321-0.624 0-0.085 0.014-0.167 0.039-0.243l-0.002 0.005zM39.014 24.030c-0.094-1.252-0.567-1.925-1.016-2.161s-1.004-0.295-1.488-0.142l0.083-0.024-8.892 1.901-8.892 1.925-14.678 3.436c-2.964 0.649-4.133 1.24-4.133 1.972s0.555 0.909 1.996 1.027c1.157 0.094 10.556 0.047 10.556 0.047l22.388-0.224s1.948-0.047 2.397-0.118c0.449-0.071 0.779-0.236 1.075-0.649 0.295-0.425 0.449-0.968 0.567-1.913s0.13-3.826 0.035-5.078zM36.9 29.024c0 0 0 0 0 0 0 0.322-0.258 0.584-0.578 0.59h-0.001c-0.862 0.035-2.066 0.071-3.259 0.083-1.488 0.012-5.396-0.012-9.34 0.071l-15.162 0.272c-0.165 0-0.189-0.236-0.035-0.272l1.807-0.366s7.002-1.452 8.903-1.83c1.901-0.378 8.903-1.854 8.903-1.854l8.266-1.736c0.025-0.005 0.053-0.009 0.083-0.009 0.221 0 0.4 0.178 0.401 0.398v0c0.012 1.039 0.024 3.46 0.012 4.652z"></path>
                            </svg>
                        </div> -->
                        <!-- <div v-else>
                            <svg @click.stop="jumpToTask" class="side-app-icon" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 39 32" style="height: 15px;">
                                <path d="M39.365 27.314v-2.971l-0.013-3.975-0.076-15.873v-0.14l-0.013-0.165c-0.013-0.114-0.013-0.229-0.025-0.343s-0.038-0.229-0.051-0.343l-0.089-0.33c-0.14-0.432-0.33-0.851-0.597-1.219-0.254-0.368-0.584-0.698-0.94-0.978s-0.762-0.495-1.194-0.635c-0.432-0.14-0.889-0.229-1.333-0.229l-10.933-0.013-19.619-0.089c-0.038-0.013-0.114-0.013-0.165-0.013l-0.178 0.013c-0.457 0.038-0.914 0.127-1.346 0.305s-0.825 0.419-1.181 0.724c-0.356 0.292-0.66 0.66-0.902 1.054-0.254 0.394-0.444 0.825-0.546 1.283-0.038 0.114-0.051 0.229-0.064 0.343s-0.038 0.229-0.038 0.343l-0.013 0.178v3.378l-0.025 3.987-0.025 7.949v8.076l0.013 0.178c0.013 0.114 0.013 0.241 0.025 0.356s0.038 0.229 0.051 0.356l0.089 0.343c0.14 0.457 0.343 0.876 0.61 1.27s0.597 0.724 0.965 1.016c0.368 0.279 0.787 0.521 1.244 0.66 0.444 0.152 0.914 0.241 1.384 0.241l30.87-0.038c0.47-0.038 0.927-0.14 1.359-0.317s0.838-0.432 1.194-0.724c0.356-0.305 0.66-0.66 0.902-1.054s0.432-0.838 0.533-1.295c0.038-0.114 0.051-0.229 0.063-0.343s0.038-0.229 0.038-0.343l0.013-0.178v-0.165l0.013-0.279zM36.914 4.419v0.064l-0.076 15.873-0.013 3.975v3.39c0 0.051-0.013 0.102-0.013 0.14 0 0.051-0.013 0.102-0.025 0.14-0.038 0.19-0.127 0.368-0.229 0.533s-0.229 0.317-0.381 0.444-0.317 0.229-0.495 0.305c-0.178 0.076-0.368 0.114-0.559 0.127l-30.667-0.025c-0.19 0-0.381-0.038-0.559-0.102-0.178-0.051-0.356-0.152-0.508-0.267s-0.292-0.254-0.406-0.406c-0.102-0.152-0.19-0.33-0.241-0.508-0.013-0.051-0.025-0.089-0.038-0.14l-0.025-0.14c-0.013-0.051-0.013-0.089-0.013-0.14v-8.127l-0.013-7.936-0.013-3.975v-3.378c0-0.051 0.013-0.102 0.013-0.14s0.013-0.102 0.025-0.14c0.038-0.19 0.114-0.368 0.216-0.546 0.102-0.165 0.229-0.33 0.381-0.457s0.317-0.229 0.495-0.305c0.178-0.076 0.368-0.114 0.571-0.14h1.905l1.981-0.013 15.873-0.076 10.908-0.013c0.203 0 0.419 0.051 0.61 0.114 0.203 0.063 0.381 0.165 0.546 0.292s0.317 0.279 0.432 0.457c0.114 0.178 0.203 0.356 0.267 0.559l0.038 0.152 0.025 0.152c0.013 0.051 0.013 0.102 0.013 0.152v0.076c-0.025-0.013-0.025 0-0.025 0.025z"></path><path d="M32.14 8.203c-2.070-0.076-4.152-0.127-6.222-0.152-1.041-0.013-2.070-0.025-3.111-0.038l-3.111-0.013-3.111 0.013-3.111 0.038c-1.041 0.013-2.070 0.038-3.111 0.051l-1.537 0.025-1.562 0.038c-0.495 0.013-0.902 0.419-0.927 0.927-0.013 0.521 0.406 0.952 0.927 0.965l1.562 0.038 1.549 0.025c1.041 0.013 2.070 0.038 3.111 0.051l3.111 0.038 3.111 0.013 3.111-0.013c1.041 0 2.070-0.025 3.111-0.038 2.070-0.025 4.152-0.063 6.222-0.152 0.47-0.025 0.851-0.394 0.876-0.876 0.013-0.495-0.381-0.927-0.889-0.94zM25.6 15.073c-1.524-0.076-3.060-0.114-4.584-0.152-0.762-0.013-1.524-0.038-2.286-0.038l-2.298-0.013-2.286 0.013-2.286 0.038c-1.524 0.025-3.060 0.051-4.584 0.114-0.483 0.025-0.889 0.406-0.902 0.902-0.025 0.521 0.381 0.965 0.902 0.99 1.524 0.064 3.060 0.089 4.584 0.114l2.286 0.025 2.286 0.013 2.298-0.013c0.762 0 1.524-0.025 2.286-0.038 1.524-0.025 3.060-0.064 4.584-0.152 0.457-0.025 0.838-0.394 0.863-0.863 0.025-0.483-0.356-0.914-0.863-0.94zM19.060 21.956c-0.978-0.089-1.968-0.114-2.946-0.152s-1.968-0.038-2.946-0.038c-0.495 0-0.978 0-1.473 0.013l-1.473 0.038c-0.978 0.025-1.968 0.051-2.946 0.114-0.47 0.025-0.851 0.406-0.889 0.889-0.038 0.521 0.368 0.965 0.889 1.003 0.978 0.064 1.968 0.089 2.946 0.114l1.473 0.038c0.495 0.013 0.978 0.013 1.473 0.013 0.978-0.013 1.968-0.013 2.946-0.038s1.968-0.064 2.946-0.152c0.432-0.038 0.8-0.381 0.838-0.838 0.025-0.521-0.343-0.965-0.838-1.003z"></path>
                            </svg>
                        </div> -->
                    </div>
                    
                </div>               
                
                <div :style="{height: `${dynamicHeight}`, overflow: 'hidden', transition: 'height 0.1s ease', marginTop: '10px'}">
                    <p ref="taskBody" style="line-height: 1.5;white-space: pre-line;word-break: break-all;" v-html="truncatedRemarks"></p>
                </div>  
                <div @click="setTruncate" class="jump-link" style="margin-top:10px" v-if="dynamicHeight !== 'auto'">{{ dynamicHeight == '162px' ? '続きを表示する' : '閉じる' }}</div>

                <div v-if="item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{ dateDetail(item.end_at!) }}</div>                         
                </div>
                <div v-if="item.response_time && item.end_at" style="margin-top: 10px;">
                    <div :style="{fontSize: '12px', color: dateColor}">{{timeFormat(item.response_time)}}</div>
                </div>
                <div @click="viewSupervisors" v-if="!canModify && supervisors.length" style="display:flex;width: fit-content; margin-top: 15px;align-items: center">
                    <div v-for="user in supervisors.slice(0, 3)" style="position:relative;">
                        <div v-if="user" :title="user.name" class="column-01">
                            <UserPanel size="15" :disableInstant="true" :user="user" imgClass="u_icon_15"/>                            
                        </div>
                        <div class="column-01" v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="u_icon_15">
                                <circle cx="15" cy="15" r="15" fill="#ddd"/>
                            </svg>
                        </div>
                    </div>
                    <span style="margin-left: 5px;">(承認者)</span>
                </div>
                <div v-if="canModify" style="display: flex;margin-top: auto;gap: 15px;min-height: 25px;align-items: end;">
                    <div @click.stop="viewApprovalUsers('未承認', waitingApprovalUsers)" style="display: flex;font-size: 12px;cursor: pointer">未承認 ({{ waitingApprovalUsers.length}})</div>
                    <div @click.stop="viewApprovalUsers('未完了', unCompletedUsers)" style="display: flex;font-size: 12px;cursor: pointer">未完了 ({{ unCompletedUsers.length}})</div> 
                    <div @click.stop="viewApprovalUsers('完了', completedUsers)" style="display: flex;font-size: 12px;cursor: pointer">完了 ({{ completedUsers.length }})</div>
                </div>
                <div v-if="statusApprove" style="margin-top: 15px;color:tomato;">
                    未承認
                </div>
                <div v-else-if="isTask && completeButtonFilter" style="display:flex;align-items: center;margin-top: 15px;position:relative;white-space: nowrap;flex-wrap: wrap;gap: 10px;">
    
                    <GanttButton v-if="isExecutor" :status="isExecutor.pivot.progress_flag" :loading="updating.status" viewType="button" @action="(flag) => completeTaskBefore(flag)"/>
                    <!-- <CommandButton
                        v-if="isExpired"
                        :buttons="[
                            {title: '延長', action:() => extendTask()}
                        ]"
                    /> -->
                </div>
            </div>
        </div>
        <Transition name="modalFade">
            <RollDice 
                v-if="playNineWindow"
                :taskId="item.id"
                @close="closeNineWindow"
            />
        </Transition>
    </div> 

</template>

<script setup lang="ts">
import { computed, inject, nextTick, onMounted, reactive, useTemplateRef, ref } from 'vue'
import colors from 'assets/colors.json'
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useMessageUsers } from '@/store/messageUsers'
import { useUrlTask } from '@/store/urlTask';
import { useUrlTaskEdit } from '@/store/urlTaskEdit'
import UserPanel from '@/components/Global/UserPanel.vue';
import axios from 'axios';
import { Task, CommandButtonInterface, Dialog } from '@/interface/globalInterface';
import { timeFormat, urlCheck } from '@/utils/tools';
import { DateTime } from 'luxon';
import ItemMenu from '@/components/Global/ItemMenu.vue';
import GanttButton from '../Gantt/GanttButton.vue';
import RollDice from '@/components/Global/RollDice.vue';
import { taskStatusBackgrounds } from '@/utils/tools';
import { dateDetail } from '@/utils/workApi';
import { useTaskRequest } from '@/store/taskRequest'
import { useTaskUsers } from '@/store/taskUsers';
import { useBadgeStore } from '@/store/badge';
import { useRoute, useRouter } from 'vue-router';
import CommandButton from '@/components/Global/CommandButton.vue';
import BoardIcon from '@/components/Board/Mixed/BoardIcon.vue';
import BoardTitle from '@/components/Board/Mixed/BoardTitle.vue';
    const messageUsers = useMessageUsers()
    const responsive = useResponsive()
    const auth = useAuthUserStore()
    const urlTask = useUrlTask()
    const urlTaskEdit = useUrlTaskEdit()
    const taskUsersStore = useTaskUsers()
    const props = defineProps<{
        item: Task
        boxClass: string;
        isBoard: boolean
    }>()

    const updating = reactive({
        status: false
    })
    const emit = defineEmits(['reload', 'editTask', 'setActiveTask', 'getBoardTasks'])
    const { notify, info, confirm } = inject<Dialog>('dialog')!;
    const dynamicHeight = ref('auto')
    const playNineWindow = ref(false)
    const taskBody = useTemplateRef('taskBody')
    const taskRequest = useTaskRequest()
    const badge = useBadgeStore()
    const router = useRouter()
    const route = useRoute()
    onMounted(() => {
        if(urlTask.id == props.item.id){
            nextTick(() => {                  
                var elem = document.getElementById('task_box_' + props.item.id) as HTMLDivElement;          
                if(elem){
                    elem.scrollIntoView({block: 'center' });   
                    setTimeout(() => {
                        elem.classList.add("reached");
                        setTimeout(() => {
                            elem.classList.remove("reached");                    
                        }, 5000);  
                        urlTask.setUrlTaskId(null)   
                    }, 150);  
                }          
            });
            if(urlTaskEdit.active){             
                emit('editTask', props.item)      
            }
        } 
        checkTruncate()
    })

    const myColor = computed(() => {
        if(auth && auth.user && colors){
            const color = colors.filter(ob => ob.id == auth.user?.color)
            if(color.length){
                return color[0].light
            }
            return ''
        }
        return ''
    })
    const isExecutor = computed(() => {
        return props.item.executors.find(ob => ob.id == auth.activeUser.id)
    })
    const isTask = computed(() => {
        const included = taskUsers.value.some(ob => ob.id === auth.activeUser.id) || supervisors.value.some(ob => ob.id == auth.activeUser.id);
        return props.item.end_at && included ? true : false
    })
    const taskColor = computed(() => {
        const userIds = taskUsers.value.map(ob => ob.id)
        const me = userIds.filter(ob => ob == auth.activeUser.id)
        const colors = {
            mycolor: me && me.length ? myColor.value : (route.name === 'remind' ? "var(--message-background)" : responsive.mobile ? "var(--message-background)" : "var(--task-background)"),
            color: me && me.length ? "#000" : "var(--primary-color)"
        }
        return colors
    })
    const completeButtonFilter = computed(() => {            
        const included = taskUsers.value.some(ob => ob.id === auth.activeUser.id);
        return included || canModify.value        
    })
    const completedUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.progress_flag == 2)
    })
    const unCompletedUsers = computed(() => {
        return props.item.executors.filter(ob => (ob.pivot.progress_flag == 0 || ob.pivot.progress_flag == 1) && ob.pivot.status_flag == 0)
    })
    const waitingApprovalUsers = computed(() => {
        return props.item.executors.filter(ob => ob.pivot.status_flag == 1)
    })
    const taskUsers = computed(() => {
        return props.item?.executors ?? []
    })
    const supervisors = computed(() => {
        return props.item?.supervisors ?? []
    })
    const selfMember = computed(() => {
        return taskUsers.value.find(ob => ob.id == auth.activeUser.id) || supervisors.value.find(ob => ob.id == auth.activeUser.id);
    })
    const isOverdue = computed(() => {
        if (!props.item?.end_at) return false;
    
        const endDate = DateTime.fromISO(props.item.end_at).toISODate();
        const now = DateTime.now().toISODate();
        
        return endDate ? now > endDate : false;
    })
    const dateColor = computed(() => {
        return isOverdue.value ? 'tomato' : '#89898F'
    })
    const canModify = computed(() => {
        return supervisors.value.some(ob => ob.id === auth.activeUser.id)
    })
    const statusApprove = computed(() => {
        const me = taskUsers.value.find(ob => ob.id == auth.activeUser.id)
        if(me && me.pivot.status_flag == 1){
            return true
        }else{
            return false
        }
    })
    const itemsCollection = computed(() => {
        const items:CommandButtonInterface[] = []
        if((!supervisors.value.length && props.isBoard) || canModify.value){
            items.push(
                { title: '編集', action:() => emit('editTask', props.item)}, 
                { title: '削除', action:() => deleteTask()}
            )
        }
        items.push(
            { title: selfMember.value?.pivot?.pin_flag == 1 ? 'ピン留めを外す' : 'ピン留め', action:() => pinTask()}
        )
        return items
    })
    const isExpired = computed(() => {
        if (props.item?.end_at) {
            const me = taskUsers.value.filter(ob => ob.id === auth.activeUser.id);
            const taskIncompleteForMe = me.length ? me[0]?.pivot?.comp_flag : false;

            const dateTime = props.item.end_at ? DateTime.fromISO(props.item.end_at) : null;
            const expired = dateTime && dateTime.isValid 
                ? dateTime.toISODate() < DateTime.now().toISODate() 
                : false;

            return !taskIncompleteForMe && expired;
        }
    })
    const deleteTask = async() => {
        const confirmed = await confirm('タスクを削除しますか。')
        if(!confirmed) return

        try{
            await axios.delete('/task_item', {data: {task_id: props.item.id}})
            info('削除しました。')
            badge.getTaskBadge()
            emit('getBoardTasks')
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
        }
    }


    const viewTaskUsers = () => {
        const data = {
            active: true,
            userList: taskUsers.value,
            title: 'タスクメンバー',
            isTask: props.item.end_at !== null
        }
        messageUsers.setMessageUsers(data)
    }
    const viewSupervisors = () => {
        const data = {
            active: true,
            userList: supervisors.value,
            title: 'タスク承認者',
        }
        messageUsers.setMessageUsers(data)
    }
    const viewApprovalUsers = (title, users) => {
        const data = {
            active: true,
            userList: users,
            title: `タスク${title ? title : ''}メンバー`,
            task: props.item,
        }
        taskUsersStore.setTaskUsers(data)
    }
    const closeNineWindow = async (flag) => {
        if (flag) {
            const answer = await confirm('グラウドナインを中止しますか？\n中止した場合再度挑戦することはできません。');
            if (!answer) return;
        }
        playNineWindow.value = false;
        updateStatus(2);
    };
    const completeTaskBefore = (flag) => {        
        const userData = taskUsers.value.find(obj => obj.id == auth.activeUser.id);
        const data = {
            active: true,
            data: props.item,
        }
        if(supervisors.value.length && flag == 2) {
            taskRequest.setTaskRequest(data)
            return
        }
        if (flag == 2) {
            const canPlayNine = taskUsers.value.some(
                member => member.id === auth.activeUser.id && member.pivot.glowd_nine === 1 && member.pivot.try_flag === 0
            );
            if (canPlayNine) {
                if (isOverdue.value) {
                    notify('期限内にタスクを完了しなかったため、<br>グラウドナインは適用されませんでした。');
                    updateStatus(flag);
                } else {
                    playNineWindow.value = true;
                }
            } else {
                updateStatus(flag);
            }
        } else {
            updateStatus(flag)
        }
    }
    
    const updateStatus = async(flag: number) => {

        const params = {
            id: props.item.id,
            params: {progress_flag: flag}
        }
        try{
            updating.status = true
            await axios.patch(`/complete_task`, params)
            emit('getBoardTasks')
            badge.getTaskBadge()
            badge.getRemindBadge()
        }catch (e) {
            notify(e.response?.data.message || e?.message || 'エラーが発生しました。')        
        }finally {
            updating.status = false
        }
    }
    const pinTask = async() => {
        try {
            await axios.put('/task_update_pin', {id: selfMember.value?.pivot.id})
            emit('getBoardTasks')
            if(taskBody.value && taskBody.value?.clientHeight > 162){
                setTruncate()
            }
        } catch (e) {

        }
    }
    const checkTruncate = () => {
        if(taskBody.value && !selfMember.value?.pivot?.pin_flag){
            if(taskBody.value?.clientHeight > 162){
                setTruncate()
            }
        } 
    }
    const truncatedRemarks = computed(() => {
        const remarks = urlCheck(props.item.remarks)
        return remarks
    })
    const setTruncate = () => {
        dynamicHeight.value = dynamicHeight.value == '162px' ? `${taskBody.value?.clientHeight}px` : '162px'
    }
    const jumpToTask = () => {
        const link = document.createElement('a');
        link.href = `${window.location.origin}/board/${props.item.board_id}?t=${props.item.id}&action=true`;                
        document.body.appendChild(link);            
        link.click();   
        link.remove();
    }
    const extendTask = () => {
        console.log(props.item)
    }
</script>
