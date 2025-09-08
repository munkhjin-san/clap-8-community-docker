<template>
    <div class="post-root" v-if="auth.id == auth.activeUser.id">
        <div class="post-header">
            <HamBurger v-if="responsive.mobile"/>
            <div class="post-search-wrap">
                <PostSearchBar 
                    className="newChatMemberSearch" 
                    :customPlaceHolder="`${appNameJp}検索`" 
                    @focus="searchWindow = true"
                />                
            </div>            
        </div>
       
        <Transition name="modalFade">
            <PostSearchWindow 
                v-if="searchWindow"
                :appName="appName"
                :appTitle="appNameJp"
                @closePostSearch="searchWindow = false"
            />
        </Transition> 
        <Transition name="modalFade">                              
            <PostCreate 
                v-if="create"
                :key="componentKey" 
                :currentStatus="null" 
                :editTarget="editTarget"
                :sharedFrom="sharedFrom"
                @postFinish="postFinish"
                :filesToShare="filesToShare"  
                :getQuery="getQuery"
                :appName="String(appName)"
                :appNameJp="appNameJp"                
            />            
        </Transition>
          
        <div class="post-container scrollable" @scroll="scrollListen">
            
            <div v-if="hasQuery" style="height: auto;margin: 0 20px;display: flex;gap: 20px;">
                <div v-if="getQuery?.app_type" class="active-query">
                    <PostIcon v-if="Number(getQuery?.app_type) != 6" :which="getQuery?.app_type" size="20"/>
                    {{ getQuery?.app_type ? apps[String(getQuery.app_type)] : ''}}
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="getQuery?.member" class="active-query">
                    <div>{{ getQuery?.member }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
                <div v-if="getQuery?.search_tags" class="active-query"> 
                    <div>#{{ sanitized(getQuery.search_tags) }}</div>
                    <div @click="router.push({name: appName})" style="cursor:pointer">
                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" style="width: 10px;height:10px" class="modalWindowCloseButton" viewBox="0 0 32 32">
                            <path d="M31.165 28.569l-1.67-1.855-1.681-1.841-6.777-7.318c-0.362-0.387-0.964-1.006-1.363-1.412-0.227-0.23-0.227-0.594-0.001-0.826 0.397-0.408 0.993-1.023 1.355-1.409 1.133-1.215 2.25-2.446 3.378-3.667l3.375-3.674c1.12-1.227 2.233-2.463 3.335-3.709 0.569-0.64 0.583-1.621 0-2.278-0.629-0.712-1.715-0.779-2.426-0.15-1.247 1.103-2.482 2.218-3.711 3.338l-3.672 3.374c-1.222 1.128-2.453 2.246-3.669 3.378-0.49 0.456-0.967 0.925-1.447 1.394-0.211 0.206-0.551 0.206-0.765 0-0.48-0.469-0.957-0.938-1.448-1.394-1.213-1.13-2.443-2.248-3.665-3.375l-3.672-3.374c-1.23-1.121-2.465-2.234-3.711-3.338-0.641-0.566-1.621-0.582-2.279 0-0.712 0.63-0.779 1.717-0.149 2.428 1.103 1.247 2.218 2.482 3.336 3.709l3.375 3.674c1.127 1.222 2.244 2.453 3.378 3.667 0.36 0.385 0.957 1.002 1.354 1.409 0.227 0.232 0.225 0.597-0.001 0.826-0.401 0.406-1.002 1.024-1.363 1.412l-3.389 3.655-3.388 3.661-1.682 1.841-1.668 1.855c-0.6 0.669-0.615 1.707 0 2.392 0.661 0.732 1.789 0.792 2.522 0.131l1.855-1.667 1.841-1.682 7.318-6.776c0.487-0.455 0.959-0.922 1.432-1.389 0.214-0.209 0.557-0.209 0.769 0 0.476 0.466 0.949 0.934 1.433 1.389l7.318 6.776 1.841 1.682 1.855 1.667c0.671 0.602 1.707 0.618 2.392 0 0.736-0.659 0.796-1.789 0.135-2.522z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div v-else>
                <div v-if="appName == 'post'" style="display: flex; gap: 20px;font-size: 14px;flex-wrap: wrap;margin: 0 20px">
                    <router-link :to="`/${appName}?app_type=0`" :class="['pt-selector']">
                        <PostIcon which="0" size="20"/>
                        {{ apps[0] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=1`" :class="['pt-selector']">
                        <PostIcon which="1" size="20"/>
                        {{ apps[1] }}
                    </router-link>
                    <router-link :to="`/${appName}?app_type=2`" :class="['pt-selector']">
                        <PostIcon which="2" size="20"/>
                        {{ apps[2] }}
                    </router-link>
                    <!-- <router-link :to="`/${appName}?app_type=3`" :class="['pt-selector']">
                        <PostIcon which="3" size="20"/>
                        {{ apps[3] }}
                    </router-link> -->
                    <!-- <router-link :to="`/${appName}?app_type=4`" :class="['pt-selector']">
                        <PostIcon which="4" size="20"/>
                        {{ apps[4] }}
                    </router-link> -->
                    <router-link :to="`/${appName}?app_type=5`" :class="['pt-selector']">
                        <!-- <PostIcon which="5" size="20"/> -->
                         <svg fill="var(--primary-color)" id="a" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 152 152" class="mt-[3px]">
                            <path d="M176-22v200H-24V-22h200Z" style="fill: var(--background-color);"/>
                            <g>
                                <path d="M155.31707-1.5122H-3.5122v158.82927h158.82927V-1.5122Z"/>
                                <path d="M155.31707-1.5122v158.82927H-3.5122V-1.5122h158.82927ZM87.21627,127.75286c.82529-.99003,5.51216-3.7481,7.00741-4.89503,9.49055-7.27973,16.48105-14.71246,20.70656-26.12271,2.54881-6.88263,6.2437-17.07232,7.70601-24.09887.54232-2.60588,1.41322-6.56732-2.00497-7.46164-.29028-.28823-.06228-1.71156-.01766-2.17222,1.07757-11.1271,2.88723-17.93226.26706-29.39526C116.33314,13.71208,99.29161,2.87363,79.51024,1.80684c-16.28334-.87815-35.96135,8.15074-42.19684,23.94559-2.39962,6.07838-3.78322,13.14191-4.92438,19.56467-6.32746-1.90441-9.6839,3.1045-10.83471,8.57867-.93277,4.437-.15634,8.89657.10225,13.37452.30945,5.3587-.47809,13.29077,4.4258,16.84249,1.63716,1.18573,3.73196,1.54437,5.72354,1.39841.06316,1.5189-.09047,3.06643.00094,4.58442.08125,1.34916.14343,3.23395.52937,4.43521,2.49404,7.76284-2.64137,8.63808-3.3013,16.64637-.09545,1.15828-.22098,8.06764,1.51857,7.69408,2.04464-.43908-11.40655.01608-11.07975-1.73882.84249-4.52413,10.11898-20.58758,11.07729-24.97821.05789-.26525,3.98395,10.3204,4.27923,10.13942,1.03895,2.10817,2.08931,4.22466,3.33259,6.22643.89124,1.43495,3.64187,5.64145,4.8943,6.42272.98221.61271,2.4535.59552,2.39806-.82627-.02434-.62423-.9814-2.33426-1.32423-2.97922-3.53548-6.65119-6.31255-9.3471-7.77656-17.39417-.84033-4.61892-.26734-8.40728-.6403-12.82311-.07706-.91244-.5182-2.55254-1.76708-2.15971-1.77077.55698-.76816,3.45844-4.17374,3.02693-5.16429-.65436-4.3813-11.37807-4.60453-15.10235-.26878-4.48411-1.23102-8.92009.14606-13.32133,1.87002-5.97668,6.60687-5.57228,9.57407-.69148,1.33765,2.20032,1.74977,4.25069,2.67655,6.49419,1.41097,3.41562,3.39777.1207,4.04654-1.79823,1.54441-4.56804,1.21811-7.26837,4.35944-11.44544,3.68009-4.89348,5.98537-5.76965,8.12932-12.16336,1.84412-5.49955,2.74921-15.75537,8.88103-17.65555,9.91971-3.07401,30.19088,1.54158,39.5893,5.9594,18.73427,8.80624,16.50611,22.96402,14.69553,40.71567-1.59653,15.65297-3.41793,35.20844-14.24729,47.41125-4.08226,4.6-14.88801,13.60745-20.67872,15.22372-11.02101,3.07612-22.11578-2.68527-27.41792-12.38516-.69649-1.27417-2.87521-6.69986-3.80475-7.12222-.83041-.37732-1.64703-.00864-1.85086.86763-.57262,2.46164,1.71598,6.77955,3.02153,8.88867,4.68266,7.56492,12.38689,13.16108,21.39503,14.11717,2.56233.27196,4.59874-.19733,6.9954-.18426M66.49684,45.37489c-2.46471.1898-5.41169.89604-7.47718,2.27892-2.10338,1.40825-3.44853,3.84524.3717,3.4927,1.54024-.14214,3.29062-.82475,4.90448-.94823,3.39544-.25978,8.14738.16618,11.45174,1.02975,1.6817.4395,6.25383,3.22676,7.09116.53889.41798-1.34173-.50051-2.24898-1.47058-3.03752-3.59173-2.91959-10.38803-3.69976-14.87132-3.35451ZM31.41273,63.85367c-.09474,4.59504,3.84991,7.47321,3.52226.67479-.12548-2.60357-1.83205-10.08674-4.00843-11.60095-.71931-.50046-1.90767-.55737-2.33736.39202-.76671,1.69399,2.03447,4.81113,2.00077,6.70561-2.49533,2.2823-4.32451,6.41178-3.03762,9.73957.39184,1.01325,1.55778,2.40014,2.70893,1.42232.87699-.74494.07341-2.19353-.01523-3.13972-.12725-1.35829.05049-3.27038,1.16668-4.19364ZM67.90145,79.26923c.14622,2.49756,1.04872,4.156,3.41197,5.07589,1.41911.55238,3.36094.52387,4.53083,1.1277.8863.45746,1.84681,1.9069,2.69702,2.57127,4.15589,3.2475,10.87791,1.42329,13.20025-3.18061,3.51952-6.97724-2.20772-21.73277,4.58255-26.0516,3.27761-2.08466,10.49399.77422,13.66074,2.47159,1.08531.58172,4.85252,3.73367,4.75672.52891-.09756-3.26349-6.20055-6.72352-8.93493-7.66777-6.17792-2.13339-11.34639-1.10247-14.52575,4.89234-3.49892,6.59735-1.10957,13.75292-1.74499,20.69404-.38413,4.19618-2.93748,7.01012-7.40088,6.08523-2.28587-.47367-2.75936-2.7341-4.79508-3.59515-2.90609-1.22919-6.69366.02792-6.06963-4.56109.20107-1.47865,2.15028-3.88974-.73112-3.94712-1.46448-.02916-3.84604,2.05313-5.2375,2.8571-5.84198,3.37545-14.42621,5.06342-14.18903,13.53681.0362,1.29328.9973,6.45809,3.11701,5.05534,1.18453-.78389.09962-4.34553.21349-5.84245.17843-2.34556,1.68472-3.97419,3.56786-5.21109l9.89047-4.83932ZM66.10726,57.08287c-1.34231.11898-2.76532.44425-4.05764.8204-1.90665.55496-3.45574,1.77057-5.51594,1.41639-.92285-.15865-3.17792-1.71785-3.86905-.85008-.60014,1.01291.60391,2.36467,1.39637,2.93283,4.60616,3.30242,7.66049-1.2707,12.38288-.67549.99778.12576,2.09813,1.03099,3.17477,1.13449,1.11373.10706,1.62898-.3254,3.00979.09532,1.25953.38376,4.29093,3.21335,4.94109,1.12648.89156-2.86171-4.54904-5.32276-6.73335-5.77306-1.57561-.32481-3.12197-.36971-4.72893-.22728ZM113.31948,73.36896c1.00391-1.00877-.81515-2.81716-1.50946-3.56922-3.49577-3.78651-8.92189-6.51306-14.14018-4.95917-.92877.27657-3.741,1.68482-2.74421,2.90584.8175,1.0014,3.18714-.36076,4.47337-.17959.62515.08805,1.81089,1.17661,2.84122,1.46125,1.77379.49003,3.14658-.19598,4.58869.87461,1.4215,1.05529,3.11924,3.91466,5.07374,3.90669.26817-.00109,1.25695-.27974,1.41684-.4404ZM95.2658,80.09503c-.1908.19087-.26214.74894-.25708,1.02655.02928,1.60714,3.3277,6.34545,4.14842,8.35543.97143,2.37908,1.59999,4.62976,1.07761,7.19676-.25126,1.23472-1.68942,4.00748-1.67828,4.74552.00552.36554.28637.74624.5647.97147,1.82179.8868,3.58655-3.08514,4.00235-4.38921,1.76136-5.52415-.9025-12.46006-4.59266-16.67875-.90796-1.03801-1.78819-1.98926-3.26505-1.22778ZM58.69042,89.26574c-2.96169.34072-1.13057,5.22714-.1873,6.91406,4.64343,8.30417,15.40195,11.986,24.53052,11.39043,4.78323-.31207,10.3299-4.15017,13.15617-7.917,1.87221-2.49527,2.17745-3.28531-1.12889-4.12879-10.52075-2.68393-22.24123-3.80339-32.91717-6.10722-1.11855.19372-2.40879-.27165-3.45333-.15149ZM82.67225,111.7148c-.62842-.77432-4.21412-.53281-5.3045-.64352-2.31188-.23474-4.65871-.64485-6.88279-1.31233-1.25434-.37645-5.27555-2.64204-5.50748-.32532-.15376,1.53591,2.80761,3.03793,4.0259,3.56299,3.22916,1.3917,8.81716,2.5037,12.16372,1.29245,1.00464-.36362,2.48034-1.37263,1.50514-2.57426Z" style="fill: var(--background-color);"/>
                                <g>
                                <path d="M67.90145,79.26923l-9.89047,4.83932c-1.88314,1.23691-3.38944,2.86553-3.56786,5.21109-.11387,1.49692.97105,5.05856-.21349,5.84245-2.11971,1.40276-3.08081-3.76206-3.11701-5.05534-.23718-8.47339,8.34705-10.16136,14.18903-13.53681,1.39146-.80397,3.77302-2.88627,5.2375-2.8571,2.8814.05738.93219,2.46847.73112,3.94712-.62403,4.58901,3.16354,3.3319,6.06963,4.56109,2.03572.86105,2.50921,3.12147,4.79508,3.59515,4.4634.9249,7.01674-1.88904,7.40088-6.08523.63542-6.94112-1.75394-14.09668,1.74499-20.69404,3.17936-5.99481,8.34783-7.02573,14.52575-4.89234,2.73437.94425,8.83737,4.40428,8.93493,7.66777.0958,3.20476-3.67141.05282-4.75672-.52891-3.16675-1.69737-10.38312-4.55626-13.66074-2.47159-6.79027,4.31883-1.06303,19.07435-4.58255,26.0516-2.32234,4.6039-9.04436,6.42811-13.20025,3.18061-.85021-.66437-1.81073-2.11381-2.69702-2.57127-1.1699-.60384-3.11173-.57532-4.53083-1.1277-2.36325-.91988-3.26575-2.57833-3.41197-5.07589Z"/>
                                <path d="M58.69042,89.26574c1.04454-.12017,2.33478.34521,3.45333.15149,10.67594,2.30383,22.39643,3.42329,32.91717,6.10722,3.30635.84348,3.00111,1.63351,1.12889,4.12879-2.82627,3.76683-8.37294,7.60493-13.15617,7.917-9.12857.59558-19.88709-3.08626-24.53052-11.39043-.94328-1.68693-2.77439-6.57334.1873-6.91406ZM72.57078,100.74412c.61062,2.00541,2.92847,2.36333,4.60923,1.35128.74317.53473.87756,1.14163,1.98848,1.32084,1.72161.27772,2.07354-.69391,2.89641-.73793.45549-.02437.95285.51216,1.48286.65257,1.98466.52579,2.40443-1.35041,2.79045-1.38391,1.00759.62539,1.65054.77208,2.70213.11791,1.04431-.64964,3.38842-3.36792,1.38599-4.05207-2.04328-.69811-5.97416-.98524-8.31642-1.43981-4.66187-.90474-9.29562-1.99816-13.97622-2.80427-2.01154-.34644-7.97619-2.31528-6.42387,1.54581.51922,1.29146,1.36734,1.62466,2.69256,1.60014.43605,2.06692,1.46581,2.92372,3.5048,1.95052.64896,1.11927.78703,2.11556,2.28581,2.41119,1.08385.21378,1.39849-.48592,2.37779-.53227Z"/>
                                <path d="M66.49684,45.37489c4.48329-.34525,11.27959.43492,14.87132,3.35451.97007.78854,1.88856,1.69579,1.47058,3.03752-.83734,2.68787-5.40946-.09939-7.09116-.53889-3.30436-.86358-8.05629-1.28954-11.45174-1.02975-1.61386.12348-3.36424.80609-4.90448.94823-3.82023.35254-2.47508-2.08445-.3717-3.4927,2.06549-1.38288,5.01246-2.08912,7.47718-2.27892Z"/>
                                <path d="M66.10726,57.08287c1.60696-.14243,3.15332-.09753,4.72893.22728,2.18431.45029,7.62491,2.91135,6.73335,5.77306-.65016,2.08687-3.68157-.74272-4.94109-1.12648-1.38081-.42072-1.89606.01174-3.00979-.09532-1.07665-.10349-2.17699-1.00872-3.17477-1.13449-4.72238-.59521-7.77672,3.97792-12.38288.67549-.79247-.56816-1.99651-1.91993-1.39637-2.93283.69113-.86777,2.94619.69143,3.86905.85008,2.0602.35418,3.60929-.86143,5.51594-1.41639,1.29233-.37615,2.71534-.70143,4.05764-.8204Z"/>
                                <path d="M31.41273,63.85367c-1.11619.92326-1.29392,2.83535-1.16668,4.19364.08864.94619.89221,2.39478.01523,3.13972-1.15114.97782-2.31709-.40907-2.70893-1.42232-1.28689-3.32778.54229-7.45726,3.03762-9.73957.03369-1.89448-2.76748-5.01162-2.00077-6.70561.4297-.94939,1.61805-.89249,2.33736-.39202,2.17637,1.51421,3.88295,8.99738,4.00843,11.60095.32765,6.79842-3.617,3.92025-3.52226-.67479Z"/>
                                <path d="M95.2658,80.09503c1.47687-.76148,2.35709.18977,3.26505,1.22778,3.69016,4.21869,6.35402,11.1546,4.59266,16.67875-.4158,1.30407-2.18056,5.27601-4.00235,4.38921-.27833-.22523-.55918-.60593-.5647-.97147-.01114-.73804,1.42701-3.5108,1.67828-4.74552.52238-2.567-.10618-4.81768-1.07761-7.19676-.82072-2.00998-4.11914-6.74829-4.14842-8.35543-.00506-.27761.06628-.83568.25708-1.02655Z"/>
                                <path d="M113.31948,73.36896c-.15988.16066-1.14867.43931-1.41684.4404-1.9545.00797-3.65223-2.8514-5.07374-3.90669-1.4421-1.07059-2.8149-.38458-4.58869-.87461-1.03033-.28464-2.21607-1.37319-2.84122-1.46125-1.28623-.18117-3.65587,1.18099-4.47337.17959-.99679-1.22102,1.81544-2.62927,2.74421-2.90584,5.21829-1.55389,10.6444,1.17266,14.14018,4.95917.69431.75206,2.51337,2.56045,1.50946,3.56922Z"/>
                                <path d="M82.67225,111.7148c.9752,1.20162-.5005,2.21064-1.50514,2.57426-3.34655,1.21125-8.93456.09925-12.16372-1.29245-1.21829-.52506-4.17966-2.02708-4.0259-3.56299.23192-2.31672,4.25314-.05113,5.50748.32532,2.22407.66749,4.57091,1.07759,6.88279,1.31233,1.09038.11071,4.67608-.1308,5.3045.64352Z"/>
                                <path d="M72.57078,100.74412c-.9793.04635-1.29394.74605-2.37779.53227-1.49878-.29562-1.63685-1.29192-2.28581-2.41119-2.03898.9732-3.06875.11641-3.5048-1.95052-1.32522.02451-2.17334-.30868-2.69256-1.60014-1.55232-3.86109,4.41233-1.89224,6.42387-1.54581,4.6806.80611,9.31435,1.89953,13.97622,2.80427,2.34226.45457,6.27313.74169,8.31642,1.43981,2.00243.68415-.34168,3.40243-1.38599,4.05207-1.05159.65417-1.69454.50748-2.70213-.11791-.38601.0335-.80578,1.9097-2.79045,1.38391-.53001-.14042-1.02737-.67694-1.48286-.65257-.82288.04402-1.1748,1.01565-2.89641.73793-1.11092-.17921-1.24531-.78611-1.98848-1.32084-1.68077,1.01205-3.99862.65413-4.60923-1.35128Z" style="fill: var(--background-color);"/>
                                </g>
                            </g>
                        </svg>
                        {{ apps[5] }}
                    </router-link>
                    <!-- <router-link :to="`/${appName}?app_type=6`" :class="['pt-selector']">
                        {{ apps[6] }}
                    </router-link> -->
                </div>
                
            </div>
            <div v-if="topRecords.length" class="px-[20px] text-center text-[var(--primary-color)]">
                <p class="mb-[15px]">グラリンピックランキング</p>
                <div class="flex flex-wrap justify-center items-center">
                    <div class="px-[10px] py-[8px] flex items-center gap-[10px]" v-for="(record, index) in topRecords.slice(0, 2)" :key="record.user.id">
                        <div class="text-[25px]" v-if="record.award">{{ record.award }}</div>
                        <div class="flex items-center gap-[10px] flex-wrap">
                            <UserPanel :user="record.user" with-name disable-instant/>
                            <div class="text-[14px]">（{{ `🔥 ${amountOfMoneyParser(record.sum_calories)} kcal` }}）</div>
                        </div>                        
                    </div>
                    
                </div>
                <div class="mt-[15px] jump-link" @click="viewFullRanking = true">全ランキングを見る</div>
            </div>
            <div class="p-tag-container">
                <div v-if="tagLoading == 0" :class="['p-tag-wrap']">
                    <div class="tag-skeleton" :style="{width: randomWidth()}" :index="num" v-for="num in 30"></div>                    
                </div> 
                <div v-else :class="['p-tag-wrap', {'p-tag-expand' : topTags.expanded}]">
                    <router-link :to="`/${String(appName)}?search_tags=${tag.text}`" class="jump-link" v-for="tag in topTags.tags">#{{ sanitized(tag.text) }} ({{ tag[`${String(appName)}_occurence_count`] }})</router-link>
                </div>  
                
                <div style="padding: 0px 20px 10px 20px;display: flex;justify-content: center;gap: 10px;align-items: center;" @click="topTags.setExpanded()">                                      
                    <div title="すべて表示する" class="selector-accordion-el">
                        <Back :class="['selector-accordion-inactive' , {'selector-accordion-active' : topTags.expanded}]" v-show="tagLoading > 0" size="11" fill="var(--primary-color)"/>
                    </div>
                </div>          
            </div>
            
            <transition-group name="slidePop" tag="div" style="display: flex;flex-direction: column;gap: 20px;">
                <PostRecord 
                    v-for="(record, index) in records"
                    :key="`${record?.id}_${index}`"
                    :record="record"
                    :appName="String(appName)"
                    :appNameJp="appNameJp"
                    :apps="apps"  
                    @setChargeTarget=" val => chargeTarget = val"
                    @setCommentCount="setCommentCount"
                    @setClap="setClap"
                    @editRecord="editRecord"
                    @updateStatus="val => updateTarget = val"
                    @deleteRecord="deleteRecordConfirm"
                    @set-entry-data="val => entryData = val"
                    
                />                
            </transition-group>
        </div>  
            

        
      
        <div title="新規作成" id="boardCreate" class="createBoardButton fileNewButton" @click="newRecord">
            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 32 32" style="fill:#000;margin:auto;">
                <path d="M30.044 14.14c-2.402-0.231-4.804-0.341-7.206-0.422-1.535-0.058-3.071-0.079-4.606-0.090-0.326-0.002-0.587-0.265-0.588-0.591-0.004-1.537-0.018-3.074-0.078-4.613-0.092-2.4-0.218-4.802-0.542-7.205-0.084-0.612-0.565-1.119-1.205-1.206-0.769-0.103-1.477 0.437-1.582 1.206-0.324 2.401-0.449 4.804-0.542 7.205-0.059 1.536-0.074 3.071-0.078 4.606-0.001 0.325-0.263 0.59-0.59 0.59-1.534 0.005-3.068 0.020-4.602 0.078-2.404 0.094-4.805 0.219-7.207 0.543-0.612 0.081-1.119 0.564-1.205 1.205-0.103 0.769 0.436 1.477 1.205 1.58 2.402 0.324 4.804 0.449 7.207 0.543 1.536 0.059 3.074 0.073 4.612 0.078 0.325 0.001 0.587 0.262 0.59 0.587 0.011 1.536 0.033 3.070 0.090 4.606 0.080 2.402 0.192 4.805 0.423 7.207 0.066 0.699 0.622 1.278 1.349 1.348 0.823 0.079 1.556-0.524 1.633-1.348 0.231-2.402 0.342-4.805 0.423-7.207 0.057-1.538 0.079-3.077 0.090-4.615 0.002-0.324 0.263-0.583 0.587-0.586 1.538-0.011 3.077-0.034 4.615-0.090 2.402-0.080 4.804-0.193 7.206-0.423 0.7-0.066 1.279-0.622 1.349-1.349 0.076-0.823-0.528-1.557-1.351-1.634z"></path>
            </svg>
        </div>
        <Transition name="modalFade">
            <div v-if="infiniteLoader" style="position: absolute;left: 0;right: 0;bottom: 25px;margin: auto;width: fit-content;">
                <div class="spinner-mini" style="border-color: transparent rgb(134 134 134) rgb(134 134 134);"></div>
            </div>
        </Transition>
 
        <router-link v-if="hasQuery" :to="`/${String(appName)}`" class="post-list-reset">一覧表示に戻す</router-link>
        <Transition name="modalFade">
            <Charge 
                v-if="chargeTarget" 
                @close="closeCharge" 
                :chargeTarget="chargeTarget"
            />
        </Transition>
        <Transition name="modalFade">
            <Status 
                v-if="updateTarget" 
                :record="updateTarget"
                @close="closeStatus" 
            />
        </Transition>
        <Transition name="modalFade">
            <PostEntryCreate 
                v-if="entryData.record" 
                :record="entryData.record" 
                :edit-data="entryData.editData"
                @close="closeEntryCreate"
            />
        </Transition>
        <Transition name="modalFade">
            <PostEntryRanking :ranking="topRecords" v-if="viewFullRanking" @close="viewFullRanking = false"/>
        </Transition>
    </div>
    <div v-else style="height: 100%;width: 100%;">
        <div v-if="responsive.mobile" style="min-height: 60px;display: flex;align-items: center">
            <HamBurger/>
        </div>        
        <div style="color:var(--primary-color);height: 100%;width: 100%;text-align: center;justify-content: center;display: flex;align-items: center;flex-direction: column;">
            <p>アクセス権限ありません。</p>
            <router-link class="l-button" style="margin: 30px 0 70px 0;" to="/board">チャットへ戻る</router-link>
        </div>        
    </div>
</template>
<script setup lang="ts">
import HamBurger from '../Global/HamBurger.vue';
import PostRecord from './PostRecord.vue';
import PostCreate from './PostCreate.vue';
import PostSearchBar from './PostSearchBar.vue'
import Charge from './Charge.vue';
import Status from './Status.vue';
import PostSearchWindow from './PostSearchWindow.vue'
import PostIcon from './PostIcon.vue';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router'
import { provide } from 'vue';
import { useAuthUserStore } from '@/store/auth'
import { useResponsive } from '@/store/responsive';
import { useSharingDataStore } from '@/store/sharingData'
import { useBadgeStore } from '@/store/badge'
import { useTopTags } from '@/store/topTags'
import { instance } from '@/utils/broadcaster';
import { onUnmounted } from 'vue';
import Back from '../Icons/Back.vue';
import { useApi } from '@/composables/api';
import { Post, PostEntry, PostQuery, TopEntryUser } from '@/interface/postInterface';
import { PostMethodsKey } from '@/interface/keys';
import PostEntryCreate from './PostEntryCreate.vue';
import UserPanel from '../Global/UserPanel.vue';
import { amountOfMoneyParser } from '@/utils/tools';
import PostEntryRanking from './PostEntryRanking.vue';
    const badge = useBadgeStore()
    const sharingData = useSharingDataStore()
    const auth = useAuthUserStore()
    const responsive = useResponsive()
    const postList = ref<Post[]>([])
    const create = ref(false)
    const componentKey = ref(0)
    const sharedFrom = ref(null)
    const filesToShare = ref(null)
    const hasQuery = ref(false)
    const chargeTarget =  ref<number | null>(null)
    const editTarget = ref(null)
    const updateTarget = ref<Post | null>(null)
    const searchWindow = ref(false)
    const route = useRoute()    
    const router = useRouter()
    const infiniteLoader = ref(false)
    const tagsList = ref([])
    const tagLoading = ref(0)
    const topTags = useTopTags()
    const apps = ['ナイス', 'ナレッジ', 'チャレンジ', 'ノート', 'ヘルプ', 'グラリンピック', 'リフレッシュ']
    const api = useApi()
    const viewFullRanking = ref(false)
    const entryData = ref({
        record: <Post | null>null,
        editData: <PostEntry | null>null,
    })
    const topRecords = ref<TopEntryUser[]>([])
    const records = computed(() =>{
        return postList.value && postList.value.length ? postList.value : []
    })
    const appName = computed(() => {
        return route.name
    })
    const appNameJp = computed(() => {
        return appName.value == 'challenge' ? 'チャレンジ' : appName.value == 'post' ? 'ポスト' : ''
    })
    onMounted(() => {
        if(route.meta.data && Array.isArray(route.meta.data) && route.meta.data.length){
            postList.value = route.meta.data as Post[];
        }else{
            const query = getQuery.value
            fetchPosts(query)
        }
        instance.on('post:new', postSocketHandler)
        hasQuery.value = Object.getOwnPropertyNames(route.query).length ? true : false
        
    

        setTimeout(() => {
            if(route.name && (typeof route.name === 'string' && (route.name.includes('challenge') || route.name.includes('post'))) && !auth.isPartner && appName.value){
                badge.updatePostBadge(appName.value.toString())
            }            
        }, 2000);
        if(sharingData.active){
            newRecord()
        }
        getTopTags()
        getTopRecords()
    })
    onUnmounted(() => {
        instance.off('post:new', postSocketHandler)
    })
    const getTopRecords = async () => {
        const data = await api.post('/get_top_posts')
        topRecords.value = data
    }
    const postSocketHandler = (data) =>{
        console.log(data)
        const payload = data && data.length ? data[0] : null
      
        if(payload && payload?.app_name == appName.value && !hasQuery.value){
            console.log('payload', payload)
            const query = {
                id: payload.record_id,
                search_tags: null
            }
            fetchPosts(query, payload.record_id)
        }
    }
    
    const deleteRecordConfirm = async(record) => {
        const data = await api.post('/delete_post', {
            path: appName.value,
            id: record.id
        }, {
            ask: `${appNameJp.value}を削除しますか。`,
            toast: '削除しました。',
        })
        postList.value = postList.value.filter(ob => ob.id !== data)
    }
    const scrollListen = (event: Event) => {
        const target = event.currentTarget as HTMLElement;
        const percent = 100 * target.scrollTop / (target.scrollHeight - target.clientHeight);  
        if(percent > 99){          
            if (infiniteLoader.value){
                return;
            }                       
            infiniteLoader.value = true;
            let query = getQuery.value
            fetchPosts(query)                                   
        }
    }
    const closeStatus = (id) => {
        updateTarget.value = null
        if(id){
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
    }
    const editRecord = (record) => {
        editTarget.value = record
        create.value = true
    }
    const closeCharge = (id) => {
        chargeTarget.value = null
        if(id){                
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id
            }
            fetchPosts(query, id)
        }
        
    }
    const closeEntryCreate = (flag: boolean, id?:number) => {
        entryData.value = { record: null, editData: null }
        if(id){                
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || !query.id){
                query['id'] = id.toString()
            }
            fetchPosts(query, id)
        }
    }
    const getQuery = computed(():PostQuery => {
        const id = route.query.hasOwnProperty('id') && route.query.id ? route.query.id : null
        const search_tags = route.query.hasOwnProperty('search_tags') && route.query.search_tags ? route.query.search_tags : null
        const search_member = route.query.hasOwnProperty('member') && route.query.member ? route.query.member : null
        const search_type = route.query.hasOwnProperty('app_type') && route.query.app_type ? route.query.app_type : null
        const query = {
            id: id,
            search_tags: search_tags,
            member: search_member,
            app_type: search_type,
        }
        return query
    })
    
    const getTopTags = async() => {
        // if(topTags.appName == appName.value) {
        //     tagLoading.value ++
        //     return
        // }
        await topTags.getTags({appName: appName.value, reset: true, currentTag: getQuery.value?.search_tags})
        setTimeout(() => {
            tagLoading.value ++
        }, 300);
    }
    
    const postFinish = (flag, id) => {
        create.value = false
        editTarget.value = null
        if(flag && id){
            const query = {
                id: id,
                search_tags: null
            }
            fetchPosts(query, id)
            topTags.getTags({appName: appName.value, reset: false})
        }
    }
    const newRecord = () => {
        create.value = true
    }
    const fetchPosts = async (query, replace?:number) => {
        const data = await api.post('/get_posts', {
            path: appName.value,
            query: query,
            skip: postList.value.length,
        })

        if(replace ){
            const index = postList.value.findIndex(ob => ob.id == replace)
            if(index > -1){
                postList.value[index] = data[0]
            }else{
                postList.value.unshift(data[0])
            }
        }else{
            data.forEach((responseItem) => {
                const existingPost = postList.value.find((post) => post.id === responseItem.id);
                if (existingPost) {
                    Object.assign(existingPost, responseItem);
                } else {
                    postList.value.push(responseItem);
                }
            });
        }
        setTimeout(() => {
            infiniteLoader.value = false
        }, 500);

    }
    const setCommentCount = (num, id) => {
        const index = postList.value.findIndex(item => item.id === id);
        if(index > -1){
            postList.value[index].comments_count = num
        }
    }
    const setClap = (id: number) => {
        if(id){
            const idStr = String(id)
            let query = getQuery.value
            if(!query.hasOwnProperty('id') || query.id !== idStr){
                query['id'] = idStr
            }
            fetchPosts(query, id)
        }
    }    
    const sanitized = (text) => {
        return text ? text.replace(/#|♯|＃/g, '') : '';
    }
    const randomWidth = () => {        
        const range = (3 - 1) / 0.2;
        const index = (Math.floor(Math.random() * range) * 0.2) + 1;
        return `${(Math.floor(Math.random() * (90 - 70 + 1)) + 70) * index}px`;

    }
    provide(PostMethodsKey, {
        commentCount: (num, id) => setCommentCount(num, id),
    })

</script>
<style scoped lang="scss">
.active-query{
    font-size: 14px;
    background: var(--background-color);
    color: var(--primary-color);
    padding: 10px 10px;
    width: fit-content;
    display: flex;
    gap: 15px;
    align-items: center;
}

.tag-skeleton{
    overflow: hidden;
    height: 18px;
    animation: pulse-bg 2s infinite;
    border-radius: 3px;
    
}
@keyframes pulse-bg {
    0% {
        background-color: var(--skItem1);
    }
    50% {
        background-color: var(--skItem2);
    }
    100% {
        background-color: var(--skItem1);
    }
}
</style>