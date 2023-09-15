<template>
<div class="taskOuterContainerBg" style="position: relative;" >
    <div
        id="taskAddButton" 
        :title="$t('createNew')" 
        :style="{bottom: '15px' ,right:'15px'}" 
        :class="{hiddenButton : createHidden}" 
        class="createBoardButton fileNewButton" 
        @click="newTask">
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
            <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
        </svg>        
    </div>
    
    <div class="taskSortWrap" v-if="calendarHide">
        <div :title="$t('list')" v-if="$store.state.calendarView" @click="listView" class="messageMenuContainer">
            
            <svg style="margin: auto;" class="dot-menu" xmlns="http://www.w3.org/2000/svg" width="30" height="13" viewBox="0 0 30 23">
                <path d="M10.333 4.029l9.099.256 9.1-.315a1.49 1.49 0 0 0 1.352-1.352 1.49 1.49 0 0 0-1.352-1.612C22.475.553 16.394.623 10.334.947c-.762.05-1.387.657-1.44 1.439-.057.852.588 1.587 1.439 1.643m18.309 5.742c-6.094-.453-12.21-.383-18.309-.059-1.91.181-1.915 2.899 0 3.082 1.526.1 3.051.146 4.578.192a150.95 150.95 0 0 0 9.152-.006c1.527-.046 3.053-.11 4.578-.244a1.49 1.49 0 0 0 1.353-1.352 1.49 1.49 0 0 0-1.352-1.613m-.104 9.508l-4.551-.243a155.47 155.47 0 0 0-13.655.185c-1.908.181-1.913 2.898 0 3.083l4.552.19a148.57 148.57 0 0 0 9.104-.006l4.551-.244a1.49 1.49 0 0 0 1.352-1.353 1.49 1.49 0 0 0-1.353-1.612M2.481 17.948c-.702.017-1.333.338-1.84.8-1.708 2.089.261 4.969 2.82 4.054a3.47 3.47 0 0 0 .888-.511c.408-.517.696-1.164.688-1.845.022-1.367-1.195-2.56-2.556-2.498"/>
                <path d="M2.481 8.975c-.702.019-1.333.338-1.84.801-1.708 2.087.26 4.967 2.82 4.054a3.5 3.5 0 0 0 .888-.511c.408-.518.696-1.166.688-1.847.022-1.367-1.195-2.559-2.556-2.497m0-8.973c-.702.017-1.333.337-1.84.8C-1.067 2.89.9 5.77 3.46 4.856a3.44 3.44 0 0 0 .888-.511c.408-.517.696-1.163.688-1.845C5.059 1.132 3.842-.06 2.481.002"/>
            </svg>
        </div>
        <div :title="$t('calendar')" v-if="$store.state.listView" @click="calendarView" class="messageMenuContainer">                    
            <svg version="1.1" class="dot-menu" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 32" width="30" height="17" style="margin: auto;">
                <path d="M35.556 27.791v-1.812l-0.011-2.902-0.057-11.584-0.011-2.89-0.011-1.445v-0.195c0-0.080 0-0.172-0.011-0.252-0.011-0.172-0.034-0.333-0.069-0.493-0.069-0.333-0.184-0.642-0.333-0.941-0.298-0.596-0.757-1.101-1.296-1.48-0.551-0.367-1.193-0.596-1.858-0.654-0.161-0.011-0.344-0.011-0.447-0.011h-1.090l-1.239 0.011c-0.080 0-0.138-0.069-0.138-0.138v-0.631c0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.080 0-0.161-0.011-0.241-0.011h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.539c0 0.115-0.103 0.218-0.218 0.206-0.814-0.057-9.715-0.057-10.586-0.023-0.046 0-0.080-0.034-0.080-0.069 0-0.172 0.011-0.585 0-0.642 0-0.080-0.011-0.161-0.023-0.241-0.046-0.31-0.161-0.619-0.321-0.895-0.321-0.539-0.849-0.963-1.457-1.135-0.149-0.046-0.31-0.080-0.47-0.092-0.057-0.011-0.138-0.023-0.218-0.023h-0.688c-0.642 0-1.273 0.252-1.732 0.688-0.459 0.424-0.746 1.055-0.78 1.686v0.642c0 0.103-0.080 0.183-0.183 0.183l-2.707-0.023c-0.654 0.023-1.308 0.218-1.87 0.562s-1.032 0.837-1.353 1.411c-0.161 0.287-0.287 0.596-0.367 0.918-0.046 0.161-0.069 0.321-0.092 0.493-0.011 0.080-0.011 0.161-0.023 0.252v1.675l-0.023 2.902c-0.023 3.865-0.057 7.719-0.069 11.584l-0.011 2.89v2.202c0.011 0.344 0.057 0.688 0.149 1.009 0.183 0.665 0.551 1.262 1.032 1.743s1.090 0.837 1.755 1.021c0.333 0.092 0.677 0.138 1.021 0.138h27.733c0.080 0 0.172-0.011 0.252-0.011 0.172-0.023 0.344-0.046 0.505-0.080 0.677-0.149 1.296-0.493 1.801-0.941 0.505-0.459 0.895-1.044 1.101-1.698 0.115-0.321 0.172-0.665 0.195-1.009 0-0.080 0-0.172 0.011-0.252v-0.195zM25.359 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.057-0.172 0.126-0.241s0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.183 0.493-0.459 0.493-0.195 0-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0.011-0.011 0.011-0.069zM8.786 5.987v-0.275l0.023-1.090 0.034-2.122c0.011-0.092 0.046-0.172 0.126-0.241 0.069-0.069 0.161-0.103 0.252-0.103h0.723c0.023 0 0.046 0 0.069 0.011 0.092 0.023 0.172 0.092 0.229 0.172 0.023 0.046 0.046 0.080 0.046 0.126v0.378l0.023 1.090 0.046 2.122c0.023 0.229-0.218 0.493-0.459 0.493s-0.551-0.011-0.551-0.011h-0.138c-0.011 0-0.034 0-0.046-0.011-0.034-0.011-0.057-0.011-0.092-0.023-0.115-0.046-0.206-0.138-0.252-0.241-0.023-0.057-0.034-0.103-0.046-0.161v-0.046c0.011-0.011 0-0.011 0.011-0.069zM33.308 7.157v1.445l-0.011 2.89-0.057 11.584-0.011 2.902v2.099c-0.011 0.138-0.034 0.275-0.080 0.413-0.092 0.264-0.252 0.505-0.459 0.7-0.218 0.183-0.47 0.321-0.734 0.378-0.057 0.011-0.115 0.023-0.183 0.034-0.034 0-0.092 0.011-0.138 0.011h-27.642c-0.138 0-0.275-0.011-0.413-0.057-0.264-0.069-0.516-0.218-0.723-0.413s-0.356-0.447-0.436-0.711c-0.046-0.138-0.057-0.275-0.069-0.413v-2.145l-0.011-2.89c-0.011-3.865-0.046-7.719-0.069-11.584l-0.034-2.913-0.011-1.445v-0.126c0-0.034 0-0.080 0.011-0.115 0.011-0.069 0.023-0.149 0.034-0.218 0.034-0.149 0.092-0.287 0.161-0.424 0.149-0.264 0.356-0.505 0.619-0.665 0.264-0.172 0.551-0.264 0.86-0.287l2.707-0.034c0.069 0 0.115 0.046 0.115 0.115l0.011 0.688c0 0.034 0 0.126 0.011 0.195 0 0.080 0.011 0.149 0.023 0.229 0.046 0.31 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.275-0.011 0.551-0.011c0.642 0 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.023-0.447c0-0.069 0.057-0.126 0.138-0.126 0.872 0.034 9.864 0.034 10.724-0.034 0.057 0 0.103 0.034 0.103 0.092 0 0.241 0.023 0.849 0.046 1.067 0.034 0.275 0.161 0.596 0.321 0.86 0.321 0.516 0.837 0.918 1.422 1.067 0.149 0.034 0.298 0.069 0.447 0.080h0.367s0.367 0 0.551-0.011c0.665-0.034 1.193-0.229 1.64-0.631s0.746-0.975 0.791-1.594c0.011-0.184 0.011-0.229 0.011-0.333l0.011-0.275 0.011-0.218c0-0.080 0.069-0.138 0.138-0.138l1.296 0.011h0.723c0.229 0 0.528 0 0.631 0.011 0.298 0.023 0.585 0.138 0.837 0.31s0.447 0.413 0.585 0.677c0.069 0.138 0.115 0.275 0.138 0.424 0.011 0.069 0.023 0.149 0.023 0.218v0.31z"></path> 
                <path d="M30.509 10.598c-2.11-0.069-4.221-0.103-6.331-0.126-1.055-0.011-2.11-0.023-3.166-0.023l-3.166-0.011-3.166 0.011-3.166 0.023-4.748 0.069-1.583 0.034c-0.413 0.011-0.757 0.344-0.768 0.768-0.011 0.436 0.333 0.791 0.768 0.803l1.583 0.034 4.748 0.069 3.166 0.023 3.166 0.011 3.166-0.011c1.055 0 2.11-0.023 3.166-0.023 2.11-0.023 4.221-0.057 6.331-0.126 0.39-0.011 0.723-0.333 0.734-0.723 0.011-0.436-0.31-0.791-0.734-0.803zM15.771 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM22.366 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.952-0.505-1.135zM29.075 15.61c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.057-0.952-0.505-1.135zM9.049 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.046-0.952-0.505-1.124zM15.771 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM22.366 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.436-0.046-0.952-0.505-1.124zM29.075 20.198c-0.195-0.080-0.39-0.103-0.585-0.138s-0.39-0.034-0.585-0.034c-0.195 0-0.39 0.011-0.585 0.034s-0.39 0.046-0.585 0.103c-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.046 0.195-0.023 0.39-0.057 0.585-0.138 0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.436-0.057-0.952-0.505-1.124zM9.049 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.172-0.447-0.046-0.963-0.505-1.135zM15.771 24.774c-0.195-0.080-0.39-0.115-0.585-0.138-0.195-0.034-0.39-0.034-0.585-0.034s-0.39 0.011-0.585 0.034c-0.195 0.023-0.39 0.046-0.585 0.103-0.287 0.080-0.528 0.31-0.619 0.619-0.138 0.47 0.138 0.963 0.619 1.090 0.195 0.057 0.39 0.080 0.585 0.103s0.39 0.034 0.585 0.034c0.195 0 0.39-0.011 0.585-0.034s0.39-0.057 0.585-0.138c0.218-0.092 0.413-0.264 0.505-0.505 0.183-0.447-0.046-0.963-0.505-1.135z"></path>
            </svg>
        </div>   
        <div @click.stop="$store.commit('setMenu', {name : 'taskSortMenu', id: 789})" class="messageMenuContainer" style="margin-left: -5px;">
            
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" class="dot-menu" height="13" viewBox="0 0 7 32" style="margin: auto;min-width: 5px;">
                <path d="M6.905 28.051c-0.011-0.447-0.114-0.881-0.275-1.273-0.039-0.1-0.085-0.196-0.135-0.287-0.047-0.093-0.096-0.185-0.153-0.27l-0.083-0.129-0.042-0.065-0.090-0.122c-0.036-0.051-0.102-0.135-0.143-0.182l-0.033-0.040c-0.095-0.111-0.2-0.214-0.319-0.302l-0.001-0.001-0.081-0.058-0.065-0.040-0.132-0.082c-0.086-0.057-0.178-0.104-0.273-0.152-0.092-0.049-0.188-0.096-0.289-0.132-0.392-0.164-0.829-0.262-1.277-0.273-0.896-0.026-1.818 0.321-2.465 0.963-0.653 0.634-1.041 1.546-1.042 2.464-0.003 0.456 0.083 0.907 0.238 1.316 0.154 0.41 0.465 0.877 0.744 1.194 0.281 0.32 0.76 0.57 1.169 0.728s0.86 0.245 1.316 0.245c0.917 0.007 1.831-0.388 2.465-1.038 0.641-0.648 0.993-1.567 0.968-2.461z"></path><path d="M3.405 12.33c-0.447 0.013-0.881 0.115-1.272 0.278-0.1 0.038-0.195 0.085-0.287 0.135-0.093 0.047-0.185 0.097-0.27 0.154l-0.129 0.083-0.064 0.042-0.124 0.088c-0.050 0.039-0.132 0.104-0.181 0.145l-0.040 0.035c-0.111 0.096-0.214 0.202-0.302 0.319-0.001 0-0.001 0.001-0.001 0.001l-0.058 0.081-0.040 0.064-0.082 0.134c-0.056 0.086-0.104 0.179-0.15 0.271-0.049 0.095-0.095 0.189-0.132 0.289-0.164 0.394-0.262 0.832-0.27 1.277-0.025 0.899 0.324 1.82 0.967 2.467 0.636 0.651 1.549 1.038 2.465 1.037 0.456 0.003 0.906-0.086 1.315-0.239 0.41-0.156 0.781-0.374 1.112-0.619l0.188-0.188c0.246-0.331 0.463-0.701 0.619-1.112 0.157-0.408 0.245-0.858 0.245-1.315 0.003-0.918-0.392-1.832-1.043-2.465-0.648-0.639-1.567-0.991-2.464-0.961z"></path><path d="M6.162 5.606c0.282-0.359 0.493-0.767 0.622-1.187 0.129-0.417 0.186-0.842 0.196-1.255l-0.035-0.263c-0.107-0.399-0.264-0.799-0.493-1.174-0.224-0.376-0.526-0.721-0.888-1-0.721-0.569-1.682-0.821-2.582-0.694-0.903 0.117-1.746 0.622-2.276 1.347-0.267 0.36-0.451 0.767-0.563 1.174-0.033 0.103-0.054 0.206-0.071 0.307-0.021 0.103-0.038 0.207-0.043 0.309l-0.015 0.152-0.007 0.078-0.003 0.096c-0.003 0.132-0.001 0.262 0.004 0.39l0.008 0.16c0.018 0.077 0.033 0.152 0.056 0.227l0.028 0.092 0.028 0.075 0.053 0.145c0.032 0.096 0.077 0.191 0.122 0.287 0.043 0.096 0.089 0.189 0.145 0.282 0.21 0.371 0.494 0.717 0.84 1.002 0.691 0.57 1.633 0.863 2.538 0.754 0.904-0.099 1.771-0.58 2.336-1.302z"></path>
            </svg>
        </div>
        <div style="width:0px;height:0px">
            <TaskSortMenu 
                v-if="($store.state.menu.name == 'taskSortMenu' && $store.state.menu.id == 789)"
                @selectMember="selectMember" 
                @jumpToToday="jumpToToday"
            />                
        </div>
    </div>
    <Transition name="modalFade">
    <TaskCreate 
        v-if="taskModalView" 
        @closeTaskModal="closeTaskModal" 
        @taskDeleted="taskDeleted"
        :editTaskData="editTaskData"/>
    </Transition>
    <div v-if="$store.state.listView" style="height:100%;overflow: hidden scroll;position: relative;background:inherit" @scroll="scrollEvent">
            <div class="no-comment-text" v-if="!incompletedTasks.length && !completedTasks.length && !expiredTasks.length" style="font-size:14px;">
                <p>{{$t('noItemsCurrentlyAvailable')}}</p>
            </div>
            <div v-if="expiredTasks.length" style="display:flex;position: sticky;top: 0px;z-index: 2; background: inherit;height:40px;">                
                <div @click="viewExpiredTasks = !viewExpiredTasks" class="taskSelectTab" >
                    <svg :class="{ arrowUp : viewExpiredTasks}" class="categoryArrow" style="width:6px;fill:#888 !important;margin-right:5px;" version="1.1"  width="5" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" fill="#888">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg> 
                    <span>{{$t('expiredTask')}}({{expiredTasks.length}})</span>                    
                </div>               
                
            </div>   
            <div :class="{collapseTasks : !viewExpiredTasks}" v-for="(tasks, date) in expireGroupedTasks" :key="date" class="task-list-wrap">                
                    <div id="expired" class="task-date">
                        <p style="line-height:16px;">{{ formatDate(date) }} {{ formatMonth(date) }}</p>
                    </div> 
                    <TaskBox 
                        boxClass="task-box-container"
                        v-for="item in tasks"
                        :key="item.id"
                        :item="item"
                        :completeFlag="false"  
                        :taskUserViewId="taskUserViewId" 
                        :taskUserViewFlag="taskUserViewFlag"
                        :myColor="myColor" 
                        @editTask="editTask" 
                        @taskUserViewToggle="taskUserViewToggle"
                        @completeTaskBefore="completeTaskBefore"
                        @taskDeleted="taskDeleted"
                        />     
            </div>    
            <div v-if="incompletedTasks.length" style="display:flex;position: sticky;top: 0;z-index: 2;background: inherit;height:40px;">                
                <div  @click="viewActiveTask = !viewActiveTask" class="taskSelectTab">
                    <svg :class="{ arrowUp : viewActiveTask}" class="categoryArrow" style="width:6px;fill:#888 !important;margin-right:5px;" version="1.1"  width="5" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" fill="#888">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg> 
                    <span>{{$t('inCompleteTask')}}({{incompletedTasks.length}})</span>                    
                </div>               
                
            </div>  
            <div :class="{collapseTasks : !viewActiveTask}" v-for="(tasks, date) in incompGroupedTasks" :key="date" class="task-list-wrap">
                 
                <div :id="date" class="task-date">
                    <p style="line-height:16px;">{{ formatDate(date) }} {{ formatMonth(date) }}</p>
                </div>  
                <TaskBox 
                    boxClass="task-box-container"
                    v-for="item in tasks"
                    :key="item.id" 
                    :item="item"
                    :completeFlag="false" 
                    :taskUserViewId="taskUserViewId" 
                    :taskUserViewFlag="taskUserViewFlag"
                    :tooManyTask="tooManyTask"
                    :myColor="myColor"
                    @editTask="editTask" 
                    @taskUserViewToggle="taskUserViewToggle"
                    @completeTaskBefore="completeTaskBefore"
                    @taskDeleted="taskDeleted"
                /> 
            </div>    
            <div v-if="completedTasks.length" style="display:flex;position: sticky;top: 0px;z-index: 2; background: inherit; height:40px;">                
                <div @click="viewCompletedTasks = !viewCompletedTasks" class="taskSelectTab">
                    <svg :class="{ arrowUp : viewCompletedTasks}" class="categoryArrow" style="width:6px;fill:#888 !important;margin-right:5px;" version="1.1"  width="5" viewBox="0 0 20 32" xmlns="http://www.w3.org/2000/svg" fill="#888">
                        <path d="M0.775 17.789c1.305 1.166 2.612 2.332 3.927 3.486 1.311 1.161 2.634 2.308 3.953 3.46 1.316 1.156 2.646 2.296 3.973 3.439 1.33 1.139 2.667 2.273 4.015 3.394 0.662 0.551 1.647 0.52 2.272-0.107 0.65-0.654 0.619-1.725-0.020-2.393-1.198-1.253-2.407-2.495-3.621-3.729-1.232-1.245-2.462-2.492-3.704-3.725-0.902-0.9-1.803-1.802-2.707-2.699-0.033-0.032-0.055-0.069-0.072-0.106-0.045-0.036-0.082-0.080-0.111-0.129-0.069-0.047-0.129-0.117-0.176-0.216-0.021-0.047-0.044-0.092-0.066-0.136-0.12-0.062-0.214-0.168-0.246-0.325-0.001-0.005-0.002-0.009-0.003-0.014-0.104-0.157-0.187-0.327-0.254-0.505-0.109-0.185-0.182-0.388-0.226-0.601-0.002-0.012-0.005-0.024-0.007-0.036-0.016-0.085-0.028-0.172-0.036-0.259-0.195-0.593-0.26-1.183 0.030-1.653 0.006-0.157 0.067-0.277 0.157-0.361 0.019-0.050 0.039-0.099 0.063-0.149 0.040-0.084 0.1-0.145 0.17-0.188 0.008-0.015 0.019-0.028 0.028-0.042 0.032-0.13 0.106-0.228 0.202-0.293 0.072-0.145 0.157-0.287 0.26-0.43 0.046-0.063 0.101-0.113 0.163-0.151 0.018-0.020 0.037-0.038 0.059-0.054 0.014-0.059 0.044-0.116 0.094-0.165 0.9-0.888 1.797-1.782 2.699-2.672 1.244-1.231 2.476-2.475 3.714-3.717l1.843-1.871 1.832-1.885c0.655-0.681 0.669-1.793-0.044-2.48-0.652-0.631-1.693-0.624-2.385-0.038l-1.964 1.66-1.995 1.71c-1.32 1.149-2.648 2.293-3.962 3.45s-2.636 2.308-3.943 3.474c-1.311 1.159-3.284 2.806-4.106 3.689s-0.792 2.492 0.191 3.369z"></path>
                    </svg> 
                    <span>{{$t('completeTask')}}({{completedTasks.length}})</span>                    
                </div>               
                
            </div>   
            <div :class="{collapseTasks : !viewCompletedTasks}" v-for="(tasks, date) in compGroupedTasks" :key="date" class="task-list-wrap">                
                <div id="expired" class="task-date">
                    <p style="line-height:16px;">{{ formatDate(date) }} {{ formatMonth(date) }}</p>
                </div> 
                    <TaskBox 
                        boxClass="task-box-container"
                        v-for="item in tasks"
                        :key="item.id"
                        :item="item"
                        :completeFlag="true"  
                        :taskUserViewId="taskUserViewId" 
                        :taskUserViewFlag="taskUserViewFlag"
                        :myColor="myColor" 
                        @editTask="editTask" 
                        @taskUserViewToggle="taskUserViewToggle"
                        @completeTaskBefore="completeTaskBefore"/>
                           
            </div>

             
    </div>
    <div id="calendarWrapper" style="height:100%;" v-if="$store.state.calendarView">
        <Transition name="modalFade">
            <Calendar 
                :calendarHide="calendarHide"
                :records="calendarTasks"
                :myColor="myColor" 
                @newTask="newTask"
                @editTask="editTask"
                @taskDeleted="taskDeleted"
                @completeTaskBefore="completeTaskBefore"
                ref="calendar"
            />
        </Transition>        
    </div>
    <div v-if="taskMemberOpen">
        <TaskMemberSelect
            :taskList="taskList"
            :checkedMemberIds="checkedMemberIds"
            @closeMe="taskMemberOpen = false" 
            @selectedMembers="selectedMembers"
        />
    </div>
</div>
</template>
<script>
import TaskSortMenu from './TaskSortMenu.vue'
import TaskCreate from './TaskCreate.vue'
import TaskBox from './TaskBox.vue'
import Calendar from './Calendar/Calendar.vue'
import moment from 'moment'
import TaskMemberSelect from './TaskMemberSelect.vue'
import colors from '../../../../../assets/colors.json'
 
export default {
    props: ["fullScreen", "ftSelector"],
    data() {
        return {
            taskList: [],
            taskPathSelector: 0,
            taskModalView: false,
            editTaskData: null,
            taskUserViewFlag: false,
            taskUserViewId: null,
            viewActiveTask: true,
            viewCompletedTasks: false,
            viewExpiredTasks: true,
            taskKey: 0,
            scrollPosition: 0,
            createHidden: false,
            sortIs: {
                by: 'deadline',
                order: this.$store.state.remember.task_sort_desc ? 'desc' : 'asc',
                myRecord: 1
            },
            viewAsList: true,
            calendarHide: true,
            tooManyTask: null,
            taskMemberOpen: false,
            taskSelectedMembers: [],
            checkedMemberIds: null,
            avialableColors: colors
        };
    },
    mounted() {
        if (this.$store.state.urlTaskId) {
            this.viewCompletedTasks = true;
        }
        if (this.$store.state.messageShareToTask) {
            this.newTask();
        }
        emitter.on("messageShareToTask", (data) => {
            if (this.$store.state.messageShareToTask) {
                setTimeout(() => {
                    this.newTask();
                }, 0);
            }
        });
        emitter.on('taskDeleted', (data) => {
            this.taskDeleted()
        })
        emitter.on('editTask', (data) => {
            this.editTask(data)
        })
        emitter.on('completeTaskBefore', (data) => {
            this.completeTaskBefore(data)
        })
        emitter.on('listView', (data) => {
            this.tooManyTask = data
            const now = moment().format('YYYY-MM-DD')
            if(now > data){
                this.viewExpiredTasks = true
            }else{
                this.viewActiveTask = true
            }
            this.listView()
        })
        this.getTask(0);
        
        if(localStorage.getItem('taskCalendar') == 'list'){
            this.$store.commit('setListView', true)
        }else if(localStorage.getItem('taskCalendar') == 'calendar'){
            this.$store.commit('setCalendarView', true)
            this.$store.commit('setListView', false)
        }
    },
    created() {
        this.$store.commit("setFileInstance", null);
    },
    watch: {
        taskModalView(after, before) {
            const index = after ? 30 : 8;
            this.$parent.$emit("setTrayZindex", index);
        },
        '$store.state.taskFeedBack.active' (after, before) {            
            this.getTask(0);
        },
    },
    computed: {
        myColor(){
            if(this.$store.state.user && this.avialableColors){
                const color = this.avialableColors.filter(ob => ob.id == this.$store.state.user.color)
                if(color.length){
                    return this.$store.state.dark ? color[0].dark : color[0].light
                }
                return ''
            }
            return ''
        },
        createButtonStyle() {
            var width = window.innerWidth
                || document.documentElement.clientWidth
                || document.body.clientWidth;
            return this.fullScreen || width > 959;
        },
        openedBoard() {
            return this.$store.state.activeBoard ? this.$store.state.activeBoard : null;
        },
        allTasks() {
            return this.taskList;
        },
        myTasks() {
            let list = [];
            this.taskList.map(ob => {
                const me = ob.to_users.filter(user => id == this.$store.state.user.id);
                if (me.length) {
                    list.push(ob);
                }
            });
            return list;
        },
        calendarTasks(){
            let list = [];
            let sortAbleTasks =  this.sortIs.myRecord ? this.allTasks : this.taskSelectedMembers
            if (this.ftSelector == 0) {
                const completed = this.completedTasks.map( ob => ob.id)
                sortAbleTasks.map(ob => {
                    const has_completed = completed.indexOf(ob.id)
                    if(has_completed == -1 && ob.comp_flag == 0){
                        list.push(ob)
                    }
                });
                return list
            }
            else if (this.ftSelector == 1) {
                this.myTasks.map(ob => {
                    const me = ob.to_users.filter(user => id == this.$store.state.user.id);
                    me.length && me[0].pivot.comp_flag === 0 ? list.push(ob) : false;
                });
                return list;
            }
            return [];

        },
        compGroupedTasks(){
            const tasksByDate = {}
            for(let i = 0; i < this.completedTasks.length; i++){
                if(this.completedTasks[i+1]){
                    if(moment(this.completedTasks[i].end_at).format('D') == moment(this.completedTasks[i+1].end_at).format('D')){
                        this.completedTasks[i+1].day = true
                    }else{
                        this.completedTasks[i+1].day = false
                    }
                }
            }
            this.completedTasks.forEach((task) => {
                const date = moment(task.end_at).format('YYYY-MM')
                if(date in tasksByDate) {
                    tasksByDate[date].push(task)
                } else {
                    tasksByDate[date] = [task]
                }
            })
            
            return tasksByDate
        },
        incompGroupedTasks() {
            const tasksByDate = {}
            for(let i = 0; i < this.incompletedTasks.length; i++){
                if(this.incompletedTasks[i+1]){
                    if(moment(this.incompletedTasks[i].end_at).format('D') == moment(this.incompletedTasks[i+1].end_at).format('D')){
                        this.incompletedTasks[i+1].day = true
                    }else{
                        this.incompletedTasks[i+1].day = false
                    }
                }
            }
            this.incompletedTasks.forEach((task) => {
                const date = moment(task.end_at).format('YYYY-MM')
                if(date in tasksByDate) {
                    tasksByDate[date].push(task)
                } else {
                    tasksByDate[date] = [task]
                }
            })
            return tasksByDate
        },
        expireGroupedTasks(){
            const tasksByDate = {}
            for(let i = 0; i < this.expiredTasks.length; i++){
                if(this.expiredTasks[i+1]){
                    if(moment(this.expiredTasks[i].end_at).format('D') == moment(this.expiredTasks[i+1].end_at).format('D')){
                        this.expiredTasks[i+1].day = true
                    }
                }
            }
            this.expiredTasks.forEach((task) => {
                const date = moment(task.end_at).format('YYYY-MM')
                if(date in tasksByDate) {
                    tasksByDate[date].push(task)
                } else {
                    tasksByDate[date] = [task]
                }
            })
            return tasksByDate
        },
        completedTasks() {
            let sortAbleTasks = this.sortIs.myRecord ? this.allTasks : this.taskSelectedMembers
            let list = [];

            if (this.ftSelector == 0) {
                sortAbleTasks.map(ob => {
                    const me = ob.to_users.filter(user => user.id == this.$store.state.user.id);    
                    if (me.length && me[0].pivot.comp_flag === 1) {
                        list.push(ob);
                    }
                    else if (ob.comp_flag == 1) {
                        list.push(ob);
                    }else {
                        const all_members = ob.to_users
                        const has_all_completed = all_members.filter(ob => ob.pivot.comp_flag == 1)
                        if(all_members.length == has_all_completed.length){
                            list.push(ob);
                        }
                    }
                });
                return list;
            }
            
            return [];
        },
        incompletedTasks() {
            let list = [];
            let sortAbleTasks = this.sortIs.myRecord ? this.allTasks : this.taskSelectedMembers
            if (this.ftSelector == 0) {
                const completed = this.completedTasks.map( ob => ob.id)
                sortAbleTasks.map(ob => {
                    const has_completed = completed.indexOf(ob.id)
                    const today = moment().format('YYYY-MM-DD')
                    const end = moment(ob.end_at).format('YYYY-MM-DD')
                    const overdue = today <= end
                    if(has_completed == -1 && overdue && ob.comp_flag == 0){
                        list.push(ob)
                    }
                    
                });
                return list

            }
            
            return [];
        },
        expiredTasks(){
            let list = [];
            let sortAbleTasks = this.sortIs.myRecord ? this.allTasks : this.taskSelectedMembers
            if (this.ftSelector == 0) {
                sortAbleTasks.map(ob => {
                    const today = moment().format('YYYY-MM-DD')
                    const end = moment(ob.end_at).format('YYYY-MM-DD')
                    const overdue = today > end
                    if(overdue && ob.pivot.comp_flag == 0){
                        list.push(ob)
                    }
                });
                return list

            }
            else if (this.ftSelector == 1) {
                this.myTasks.map(ob => {
                    const me = ob.to_users.filter(user => id == this.$store.state.user.id);
                    me.length && me[0].pivot.comp_flag === 0 ? list.push(ob) : false;
                });
                return list;
            }
            return [];
        },
    },
    methods: {
        formatDate(date){
            return moment(date).format('YYYY')
        },
        formatMonth(date){
            return moment(date).locale(this.$store.state.local).format('MMM')
        },
        jumpToToday(){
            if(this.$store.state.calendarView){
                this.$refs.calendar.jumpToToday()
            }else{
                const taskmonth = document.getElementById(moment().format('YYYY-MM'))
                if(taskmonth){
                    taskmonth.nextElementSibling.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            this.$store.state.menu.name = ''
        },
        selectedMembers(members){
            if(members.length){
                this.checkedMemberIds = members
                const membersSet = new Set(members);
                this.taskSelectedMembers = this.taskList.filter(task => {
                    const taskUserIDs = task.to_users.map(user => id.toString());
                    return taskUserIDs.some(userID => membersSet.has(userID));
                });
                this.sortIs.myRecord = 0
            }else{
                this.checkedMemberIds = []
                this.sortIs.myRecord = 1
            }
            
        },
        selectMember(val){
            this.taskMemberOpen = val
            this.$store.commit('setMenu', {name : '', id: null})
        },
        scrollListen(){
            var percent = 100 * event.target.scrollTop / (event.target.scrollHeight - event.target.clientHeight);       
            if(event.target.scrollTop < 0){
                this.createHidden = false
                
            }else if(percent > 98){
                
                this.createHidden = true
            }else{                 
                this.createHidden = event.target.scrollTop > this.scrollPosition
                this.scrollPosition = event.target.scrollTop;
            }
            const mobile = this.$store.state.mobile
            if(mobile) this.$emit('setSearchView', !this.createHidden)
            
        },
        calendarView(){
            this.$store.commit('setCalendarView', true)
            this.$store.commit('setListView', false)
            localStorage.setItem('taskCalendar', 'calendar')
            this.createHidden = false
        },
        listView(){
            this.$store.commit('setListView', true)
            this.$store.commit('setCalendarView', false)
            localStorage.setItem('taskCalendar', 'list')
        },
       
        newTask(day) {
            this.editTaskData = null;
            this.taskModalView = true;
        },
        taskDeleted() {
            this.getTask(this.taskPathSelector);
            this.taskModalView = false;
            this.$parent.$emit("updateTaskNotify");
        },
        editTask(task) {
            const usersId = task.to_users.map(ob => ob.id);
            if (usersId.indexOf(this.$store.state.user.id) > -1) {
                this.editTaskData = task;
                this.taskModalView = true;
            }
        },
        taskUserViewToggle(id) {
            if (this.taskUserViewFlag && this.taskUserViewId == id) {
                this.taskUserViewId = null;
                this.taskUserViewFlag = false;
            }
            else if (this.taskUserViewFlag) {
                this.taskUserViewId = id;
            }
            else if (!this.taskUserViewFlag) {
                this.taskUserViewFlag = true;
                this.taskUserViewId = id;
            }
        },
        completeTaskBefore(task) {
            if(task.comp_flag == 0){
                const today = moment().format('YYYY-MM-DD')
                const end = moment(task.end_at).format('YYYY-MM-DD')
                const overdue = today > end
                if(overdue){
                    const data = { 
                        active: true,
                        data: task
                    }
                    this.$store.commit('setTaskFeedback', data);
                     return
                }
            }
           
            var userData = task.to_users.filter(obj => obj.id == this.$store.state.user.id);
            if (userData.length) {
                if (userData[0].pivot.comp_flag == 1 || userData[0].pivot.comp_flag == "1") {
                    this.completeTask(task.id, 0);
                }
                else {
                    this.completeTask(task.id, 1);
                }
            }
        },
        completeTask(task_id, compFlag) {
            
            axios.post("/complete_task_api", { task_id: task_id, comp_flag: compFlag }).then(response => {
                this.getTask(this.taskPathSelector);
                this.$parent.$emit("updateTaskNotify");
            }).catch(function (error) {
                if (error.response) this.errorToast(this.$t('unknownError') + error.response.data.message)
                else if (error.request) this.errorToast(this.$t('unknownError'))
                else this.errorToast(this.$t('unknownError') + error.message)                           
            }.bind(this));
        },
        taskSelector(flag) {
            this.taskPathSelector = flag;
            this.getTask(flag);
        },
        getTask(flag) {
            axios.post("/get_task_api", { record_id: this.openedBoard.id, flag: flag }).then(response => {
                this.taskList = response.data;
                this.taskKey++;
            });
        },
        closeTaskModal(update) {
            this.taskModalView = false
            if (update) {
                this.getTask(this.taskPathSelector);
                this.$parent.$emit("updateTaskNotify");
            }
        },
        scrollEvent() {
            this.createHidden = event.target.scrollTop > this.scrollPosition;
            this.scrollPosition = event.target.scrollTop;
        },       
    },
    components: {
    TaskSortMenu,
    TaskCreate,
    TaskBox,
    Calendar,
    TaskMemberSelect
}
}
</script>
