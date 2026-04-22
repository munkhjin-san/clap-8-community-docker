<template>
    <div class="main-bar">
        <Modal v-if="iconEditModal" @close="closeIconEditModal">
            <template #title>
                <p>アイコンを編集する</p>
            </template>
            <template #content>                         
                <div>
                    <div style="display:flex;gap:15px;font-size: 14px;">
                        <div :class="['ch-selector', {chSelected : iconType == 0}]" @click="iconType = 0" style="font-size: 14px;">デフォルトアイコン</div>
                        <div :class="['ch-selector', {chSelected : iconType == 1}]" @click="iconType = 1" style="font-size: 14px;">画像アイコン</div>                
                    </div>               
                </div>
                <div  class="si-box" style="padding: 10px;position:relative;border: solid thin var(--primary-color);">
                    <div v-if="iconType == 0">
                        <span class="form-plc smallPlc">アイコンカラー</span>                  
                        <div class="flex justify-center">
                            <div class="si-box">
                                <ColorPicker v-model="iconBg"/>
                            </div>
                                                            
                        </div>
                    </div>
                    <div v-else>
                        <Cropper ref="cropperInstance"/>                       
                    </div>
                </div>
                <div class="si-box">
                    <div v-if="iconType == 0" style="width: fit-content;padding: 15px;margin: auto;">
                        <div id="boardIconPreview" class="iconPreview">
                            <img draggable="false" loading="lazy" class="iconPreviewInner" :src="defaultIcon">
                        </div>
                    </div>
                </div>    
                <div class="si-box">
                    <LoaderButton @triggered="sendIcon" :loading="sendLoader" content="保存する"/>
                </div>                    
            </template> 
        </Modal>   
        <div style="overflow: hidden;">
            <div class="profile-icon-content">
                <div id="imageWrap" style="position: relative;width: fit-content;margin: auto;min-height: 120px;">
                    <div @click.stop="iconClickMenu" class="cursor-pointer">
                        <UserPanel disable-instant :user="UserAllData" imgClass="profile-image" size="120"/>
                    </div>
                    <div id="iconMenuWrap" class="iconChange" v-if="menu.name == 'iconMenuWrap' && menu.id == 23">
                        <div @click="previewProfile(icon, 0)" class="cursor-pointer">フルサイズを表示</div>
                        <div v-if="auth_id == targetId" @click="iconEditModal = true" class="cursor-pointer">アイコン変更</div>
                        <div v-if="auth_id == targetId" @click="iconDeleteConfirm" class="cursor-pointer">削除</div>
                        
                    </div>
                </div>
            </div>

            <div class="bar01">
                <div class="text-[20px] mb-5 mt-8 flex justify-center relative gap-4 items-center" v-if="UserAllData?.name">
                    <p><span>{{UserAllData.name}}</span></p>
                    <div @click.stop="updateWeather" class="flex items-center gap-2 bg-[var(--bg3)] px-2 py-1 rounded">
                        <WeatherIcon v-if="weathers" :key="`weather_${weathers.value_int}`" :which="weathers.value_int" size="20"/>
                        <div class="flex items-center justify-center mt-1" :style="{transform: `rotate(270deg)`}">
                            <Back size="8"/>
                        </div>
                    </div>                    
                    <Transition name="upShiftPop">
                        <WeatherUpdater 
                            v-if="menu.name == 'weatherUpdater' && menu.id == auth.id" 
                            @reload="emit('updateUser')"/>
                    </Transition>
                </div>
                <div v-if="UserAllData.name_kana" class="bar02 mb-8">
                    <p>{{UserAllData.name_kana}}</p>
                </div>
                <div v-if="userDaysWeather.length" class="flex text-[12px] justify-center items-center gap-[8px] mt-4">
                    <div v-for="(weather, index) in userDaysWeather" :key="index" class="flex items-center">       
                        <p>{{DateTime.fromISO(weather.date).toFormat("d(EEE)")}}</p>
                        <WeatherIcon :key="`weather_${weather.value_int}`" :which="weather.value_int" size="16"/>  
                    </div>
                </div>                
                
                <div v-if="UserAllData.phone_number" class="bar03 flex items-center gap-2 justify-center">    
                    <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 29.36781 29.38005">
                        <path d="M27.08378,2.7689c-.70938-.65877-1.49932-1.21802-2.30905-1.73474-.50804-.31049-.91555-.52569-1.31187-.70961s-.63347-.2594-1.10009-.30999c-1.11015-.12036-2.03086.51344-2.35583,1.59441-.382,1.17816-.84938,2.63498-1.19497,3.819.00033.00966-.51458,1.7491-.48492,1.68587-.10524.41953-.05706.89102.09307,1.28187.38165,1.02393,1.30563,1.51866,2.30003,1.72743.35521.07458.45641.51417.40431.76353s-.23033.86886-.42264,1.35464c-.24197.62474-.54175,1.21126-.89564,1.77307-.72909,1.14652-1.67818,2.16645-2.65028,3.1373-.49546.49854-1.00564.97358-1.53152,1.41834-1.41945,1.2391-2.8345,2.04293-4.63645,2.59353-.34152.10435-.77792-.15179-.88916-.56326-.12904-.47731-.29021-.94579-.5927-1.34747-.61344-.80236-1.68158-1.2033-2.6622-.85322-.11924.03332-.44486.12877-.56723.1636-1.50015.43764-3.02691.90909-4.50917,1.40514-.6127.16831-1.18734.55993-1.47317,1.13984-.31104.62458-.44385,1.62069-.05346,2.35646.46332.87321.98008,1.69498,1.56089,2.48929.33659.4475.69242.89996,1.09701,1.29687,1.4902,1.48931,3.65602,2.36931,5.77396,2.07161,6.13628-.90125,11.58762-4.86368,15.48414-9.53709,2.21438-2.69856,3.93487-5.83827,4.79646-9.24132.1557-.6404.3444-1.41549.37709-2.06593.24314-2.14618-.7212-4.24962-2.24662-5.70917ZM27.19815,8.10323c-.05325.85618-.30269,1.79346-.53254,2.61524-2.21358,7.79303-8.99392,14.21237-16.85519,16.18021-.66205.17387-1.37044.28928-2.03472.3108-1.74538.00133-3.3258-1.1368-4.32483-2.51401-.49106-.63031-1.27295-1.78729-1.42634-2.08091s-.04114-.67939.30118-.76637,3.55857-.98118,5.27048-1.50465c.00381-.0042.038-.01742.10942.00578.24246.08612.33907.3184.42873.55475.09092.2252.14865.44732.1999.67863.18064.66963.56426,1.10106,1.2722,1.32283s2.01319-.1528,2.36997-.24542c.29852-.0775.5952-.1673.88834-.27017,2.19192-.76231,4.04653-2.24596,5.66819-3.8531,2.16492-2.13515,3.61027-4.19346,4.26273-7.0885.0532-.23608.18354-.60688.20251-1.14265s-.15249-.93402-.29062-1.13606c-.27488-.40338-.63765-.60973-.97989-.71623s-.76004-.23482-1.07291-.37462-.33185-.44338-.22487-.78326c.13704-.43536.38611-1.27244.38611-1.27244.35589-1.1825.7605-2.65423,1.08313-3.85221.04179-.25307.27404-.33108.51425-.30145.03891.02457.07955.04819.12402.06958.80523.44886,1.60077,1.03579,2.3238,1.6137.36004.29021.70928.59279,1.00713.93033.85541.97126,1.46493,2.37143,1.32981,3.62018Z"/>
                    </svg>                   
                    <a class="jump-link" :href="`tel:${UserAllData.phone_number}`">{{UserAllData.phone_number}}</a>
                </div>
                <div v-if="UserAllData.work_email" class="bar04 flex items-center gap-2 justify-center"> 
                    <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="16" width="22" viewBox="0 0 31.37902 25.54039">
                        <path d="M31.33216,9.90429l-.0293-6.33087-.00024-.04944c0-.01599.00006-.03186-.00128-.05701-.00653-.31342-.0531-.63818-.14386-.94025-.40625-1.40619-1.77679-2.44263-3.24121-2.43884-.48352-.00616-1.89557.01617-2.38483.00946C19.98444.14684,12.15455.00872,6.53895.01378c-.50385-.00763-1.89368-.00342-2.37408-.01227-.25122-.00061-.61182-.00616-.8598.00873C1.37854.13744-.05322,1.79833.03663,3.71428.01044,9.22972.01343,16.37273.0008,21.91545c-.02429,1.1178.50732,2.23047,1.40503,2.8996.59326.4588,1.34619.71442,2.09613.72382,1.43842.00262,13.32648.00244,15.04816-.00537,2.04669-.00311,7.33167-.01758,9.2984-.02155,1.81982.02319,3.41943-1.45038,3.52429-3.26996.02582-3.45569-.03699-8.83527-.04065-12.33771ZM29.35059,20.98326l-.00201.79132c.00214.17578-.00024.3847-.04291.55267-.14117.62958-.74158,1.11945-1.38763,1.12561-.02277.00073-.02997.00378-.06952.00293l-.19781-.00049-.39569-.00104c-.47992.00446-1.88739-.01282-2.37402-.00616-4.94122-.00439-15.55219-.03717-20.57532-.02136,0,0-.77893-.00079-.77893-.00073-.30463-.00549-.60266-.10364-.84412-.29126-.38129-.27972-.5929-.75067-.5755-1.2193.00403-5.53259-.02521-12.69525-.03577-18.20123l-.00061-.19781c-.00037-.01898.00037-.02966.00122-.03815l.00195-.02924c.00262-.78552.71588-1.45715,1.49744-1.43884.32983-.00305,1.0603-.00043,1.38483-.00555,6.26434-.03662,14.30255-.06573,20.57526-.12665v.03467c.49835-.00232,1.88873.01477,2.36328.01239.8255.00134,1.54352.73486,1.53369,1.55701-.0249,5.25183-.05334,12.27277-.07782,17.50122Z"/>
                        <path d="M27.43177,5.83068c-3.85706,2.20294-7.76099,4.66443-11.54102,7.0459-3.77063-2.4812-7.53534-4.99078-11.54852-7.07019-.31787-.16327-.7171-.08289-.94403.20978-.25635.33057-.19617.80627.13434,1.06256.93616.72595,1.90039,1.40796,2.86841,2.08386.93945.64893,1.98077,1.34564,2.94067,1.97028,1.97833,1.28552,4.01263,2.48297,6.01135,3.73608.31055.20007.72192.21277,1.05261,0,2.88098-1.85382,6.0697-3.89441,8.89026-5.80353.97583-.66364,1.95154-1.32745,2.91473-2.01099.29858-.21344.39648-.62439.21124-.95068-.19794-.34882-.64124-.47107-.99005-.27307Z"/>
                    </svg>                     
                    <a class="jump-link" :href="`mailto:${UserAllData.work_email}`">{{UserAllData.work_email}}</a>
                </div>
                <div class="bar05 gap-2" v-if="UserAllData?.refresh_current_balance !== null && canViewRefreshHistory">
                    <div @click="emit('openHistory')" title="リフレッシュ" class="flex gap-1 items-center cursor-pointer">
                        <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 152 152"><path d="M35.35664,122.00491c-2.92196,4.71027-6.09263,9.26686-9.03117,13.97086-1.53951,2.46444-3.33378,7.18873-6.0073,8.3482-3.69011,1.60036-7.54913-.52241-7.82955-4.58333-.20116-2.91308,2.19207-6.1748,3.69795-8.63033,3.5865-5.84821,7.75654-11.39238,11.81684-16.91359-3.52069-5.04324-6.16224-10.79308-7.39876-16.85097-5.15406-25.25064,8.69527-47.64356,29.41981-60.71786,25.29294-15.95632,54.14093-17.83465,81.49292-27.78443,3.39769-1.23597,7.61434-4.24429,11.06763-1.54447,1.41795,1.10857,1.8595,2.68369,2.03845,4.40686.14072,1.355.25042,3.28182.31016,4.67031,1.65578,38.48073-9.9261,85.51135-46.39714,105.09187-15.61261,8.38208-37.00117,11.79489-53.88275,5.44193-3.39181-1.27642-6.21515-3.0805-9.29709-4.90505ZM64.46498,88.42486c-8.13962,7.74857-15.57825,16.25774-22.55533,25.06733-.1484.56878,2.62196,2.09445,3.20674,2.40824,13.86161,7.43824,34.81025,3.94291,48.24892-3.11919,29.48572-15.49492,39.75076-53.33804,40.72847-84.36919.03954-1.25506.16262-8.09017-.15083-8.66259-.26877-.49082-.40163-.11804-.63624-.04258-3.74828,1.20558-7.35931,2.70103-11.17357,3.77578-14.06932,3.96434-28.46257,6.06422-42.40211,10.91821-26.54063,9.2419-53.72366,26.99623-49.0277,59.29154.4657,3.20276,2.07105,8.46276,3.70516,11.23819.14153.24037-.08594.48899.49917.37106,16.13675-19.90509,35.39727-37.40145,57.30399-50.80299,2.40554-1.4716,10.43623-6.57973,12.71964-6.63968,2.89089-.0759,5.13694,2.83283,4.40191,5.59945-.2283.85932-1.1071,1.75234-1.77223,2.32705-3.69668,3.19412-10.53862,6.70129-14.88047,9.72891-9.90494,6.90682-19.47192,14.58696-28.21551,22.91048Z"></path></svg>
                        <p>{{ amountOfMoneyParser(UserAllData?.refresh_current_balance) }}円</p> 
                        <span class="jump-link ml-2">詳細</span>
                    </div>
                                          
                </div>
            </div>
            <div class="albumBody mt-16">
                <div v-if="movExist && movExist.length">
                    <div class="flex items-center justify-center overflow-hidden flex-col relative">
                        <div class="max-w-[280px] w-full overflow-y-auto overflow-x-hidden px-2.5">
                            <div class="flex flex-col">
                                <div class="relative flex flex-col mb-[30px]" v-for="(mov, index) in movExist" :key="index">
                                    <div class="w-full">
                                        <div class="gn-img-container rounded cursor-pointer bg-[var(--soft-bg)] !max-h-40 min-w-[200px]" @click="previewImage(mov, index)">
                                            <img
                                                loading="lazy"
                                                @error="handleImgError(index)"
                                                class="gn-image !object-contain"
                                                v-if="mov.mime_type == 'image'"
                                                :src="imageError.includes(index) ? '/images/no-image.svg' : `/cdn/user_album/${targetId}/${mov.id}_${targetId}_${mov.path}.${mov.extension}`"
                                            />
                                            <video class="gn-image pointer-events-none max-h-[290px]" preload="metadata" v-else-if="isMov(mov.mime_type)" controls>
                                                <source :src="movSrc(mov)">
                                            </video>
                                        </div>
                                        <p class="gn-title">{{ mov.title }}</p>
                                        <div v-if="mov.tags.length" class="flex gap-x-[10px] gap-y-[5px] flex-wrap mt-[10px]">
                                            <p @click="viewalbumByTag(tag)" class="jump-link text-[14px]" v-for="tag in mov.tags" :tag="tag" :key="tag.id">#{{ sanitized(tag) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="targetId == auth_id" class="absolute right-0.5 top-1.5">
                                        <ItemMenu :items="[
                                            {title: '編集する', action:() => editAlbum(mov)},
                                            {title: '削除する', action:() => introMovDeleteConfirm(mov.id)}
                                        ]"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="max-w-[280px] w-full">
                            <FloatButton v-if="targetId == auth_id" @action="introUpload = true">
                                <template #icon>
                                    <AddIcon size="20"/>
                                </template>
                            </FloatButton>
                        </div>
                    </div>
                </div>
                
                <div class="w-full h-full bg-[var(--soft-bg)] flex justify-center min-h-[200px] rounded" v-else-if="targetId == auth_id">
                    <div @click="introUpload = true" class="text-[gray] flex flex-col items-center justify-center gap-3">
                        <svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" height="30" viewBox="0 0 24.99094 20.1551">
                            <path d="M24.98817,8.96682c-.005-.82039-.0217-1.64006-.04468-2.45892l-.0087-.30707-.00395-.09279c-.00172-.03335-.00302-.06638-.00629-.1005-.00498-.06735-.01247-.13579-.02192-.20492-.03909-.27606-.11312-.56881-.25758-.85805-.14178-.28808-.35971-.56707-.62785-.77585-.26727-.21085-.57135-.34796-.8618-.42708-.14581-.04008-.28961-.06697-.42927-.08334-.13828-.01666-.2812-.02397-.40081-.02479l-.60913-.01375-1.21729-.02751-1.70692-.03856-.27139-1.30351-.10485-.50313-.05242-.25159-.00328-.01571-.00531-.02257-.01716-.06434c-.01202-.04203-.02415-.08743-.0387-.12461-.02727-.07813-.05968-.15425-.0974-.22812-.14944-.29615-.3783-.5494-.65492-.72967-.27665-.17963-.60317-.28767-.93323-.30316l-4.14065-.00729-2.04672.00053-1.02337.00116-.51168.00059-.25584.00029-.12792.00015-.06396.00007-.04938.00099-.06184.00246c-.33024.01551-.65693.12364-.9337.3034-.27674.18037-.50568.43376-.65517.73006-.03774.07392-.07016.15007-.09743.22825-.01456.03718-.02669.0826-.03871.12465l-.01716.06438-.00531.02257-.00327.01573-.05239.25159-.10477.50315-.27119,1.30265-1.86699.04124-1.29727.02872-.16216.0036-.08108.00178-.04053.0009c-.01211.00007-.03826.00244-.0567.00367-.03942.00312-.08346.00613-.119.01022l-.10683.01259c-.0718.01039-.14359.02147-.21623.03639-.28949.05814-.5885.15752-.87447.32019-.28529.16132-.55462.39023-.76097.67012-.20789.27883-.34812.6026-.41905.92195-.03606.15774-.05643.32405-.0638.46989l-.01552.32807c-.01121.21887-.01999.43734-.02702.65555C.01427,7.93083.00125,8.80163.00005,9.6714c-.00172,1.73938.04573,3.47429.12875,5.20862.04175.86715.0954,1.73402.16022,2.60123l.01191.1626.01727.18976c.01484.13225.03573.26941.06671.41284.03245.14445.07612.29696.1431.45765.06687.16003.15981.33051.28943.49111.12796.16115.29581.30469.46793.40887.17249.10547.34651.17446.50389.22104.31615.09108.57879.11511.81761.13562.24041.01923.45277.0272.67202.03742.21802.00929.43823.02202.65447.02841.43371.01461.86825.03131,1.30023.04133.86551.02419,1.72893.04173,2.59232.05533.8634.01463,1.7262.02606,2.58913.02808.86282.00196,1.72557.00773,2.5883-.00048,0,0,3.65822.00595,4.99736-.02707,1.66458-.04104,4.16644.00159,4.99624-.13862s1.27253-.59028,1.42273-1.2124.21834-1.60315.29052-2.42183c.06799-.81894.1279-1.63874.17229-2.45922.07964-1.64127.11975-3.28412.1057-4.92484ZM18.16145,18.4186c-1.71737-.00754-3.43627-.00923-5.15507-.0299l-5.15509-.05139c-.85859-.01011-1.7171-.02281-2.57316-.04245-.42893-.00773-2.18837-.03412-2.53564-.08919s-.63133-.49184-.69737-.85309-.11023-1.70266-.14824-2.55584c-.07557-1.70622-.11616-3.4155-.10834-5.12049.00422-.85234.02007-1.70369.05319-2.55138.00758-.21208.01684-.42376.02844-.63486l.01609-.31696c.00404-.0691.01119-.11703.02293-.16923.0232-.10057.06074-.17925.11069-.24561.05015-.06629.11464-.12366.20392-.17409.0888-.05012.20267-.09092.33497-.11676.03282-.00679.06751-.0116.1022-.01683l.05355-.00598.04184-.00363c.00841-.00051.00901-.00169.02382-.00185l.04017-.00073.08034-.00147.16068-.00297,1.28542-.02364,2.57075-.0474c.41523-.00756.78586-.2999.87494-.7236l.00053-.00253v-.00002s.35146-1.67383.50783-2.4147c.04266-.2021.22081-.34626.42736-.34635,1.34656-.00055,6.14302.00241,7.49072.00327.20669.00013.38494.14499.42745.34726l.50659,2.41038.00063.00396c.08526.40619.44212.71431.87465.72165l2.44504.04157,1.22249.02079.61076.01037c.08375.00009.14415.00374.20787.0105.06237.00657.11902.01751.16937.03103.10152.02718.17406.06379.22706.10457.05301.04133.09375.0889.13312.16466.03918.07447.07081.18018.08951.30599.00449.03162.00845.06418.0111.09826.00202.01648.00249.03426.00353.05168l.00297.05955.01035.30272c.02742.80719.04862,1.61402.05828,2.42042.00889.80642.0114,1.61244.00627,2.41818-.00675.80569-.02563,1.6109-.05365,2.41567-.04073,1.33203-.11643,2.66225-.21325,3.99141-.02366.32482-.29192.5769-.61752.5838-.53298.01129-1.0697.01794-1.60691.01947-.85627.00613-1.7146.00881-2.57322.00576Z"/>
                            <path d="M16.2503,7.51592c-.48354-.49616-1.06587-.90029-1.70845-1.17428-.64254-.27385-1.34321-.41287-2.03917-.41399-.69596.00297-1.39592.14396-2.03673.41985-.64085.276-1.22052.68168-1.70095,1.17835-.95961.99582-1.49909,2.3743-1.47435,3.73768l.0283.50963c.02127.16888.05076.3371.07731.50528.03478.16668.08056.33144.12246.49671.05054.16271.11284.32201.17085.48243.25802.63204.64794,1.20955,1.13634,1.68271.48805.47364,1.06983.84781,1.70095,1.09235.63001.24737,1.30848.36086,1.97581.34598v-.03001c.66366.02035,1.3397-.09013,1.96815-.33445.63003-.24067,1.20956-.61418,1.69938-1.08312.48961-.46936.88547-1.04133,1.151-1.67122.2674-.62906.40737-1.31364.41351-1.9963.00948-.68262-.11504-1.37342-.37024-2.01426-.25305-.64169-.63305-1.23434-1.11419-1.73336ZM14.95582,13.71665c-.31196.33187-.69204.60036-1.10896.79277-.41805.19028-.87454.30259-1.34417.32493v-.03004c-.46594-.01685-.92001-.12617-1.3365-.31337-.41583-.18855-.79366-.45637-1.10739-.78352-.62333-.65476-1.01592-1.5331-1-2.44389.01173-.45323.11077-.89754.29138-1.30544.18207-.40722.44742-.77473.76953-1.07754.32135-.30412.7013-.54044,1.10739-.69752.4057-.15871.83827-.24049,1.27559-.23952.43733.00088.86919.08466,1.27316.24539.40437.15908.78166.39696,1.0999.70159.31966.30276.58167.66973.76169,1.07497.1794.40568.27766.84742.28914,1.29808.01313.90211-.34627,1.78926-.97075,2.45312Z"/>
                        </svg>
                        <p class="text-[12px] mb-3">
                            アルバムにファイルがありません。
                        </p>
                        <CommandButton :buttons="[{ title: 'アップロード', action: () => introUpload = true}]"/>
                    </div>                         
                </div>
                
            </div>
            <Transition name="modalFade">
                <UserIntroFile 
                    v-if="introUpload"
                    :UserAllData="UserAllData"
                    :editData="editData"
                    @closeModal="closeModal()"
                    @updateUser="emit('updateUser')"
                />
            </Transition>
            <Transition name="modalFade">
                <UserAlbumByTags 
                    v-if="viewAlbum"
                    :tagText="tagText"
                    :tagAlbums="tagAlbums"
                    :targetId=targetId
                    @closeModal="viewAlbum = false"
                />
            </Transition> 
        </div>
    </div>
</template>
<script setup>
import UserIntroFile from './UserIntroFile.vue';
import UserAlbumByTags from '../UserAlbumByTags.vue';
import WeatherUpdater from '../../Global/WeatherUpdater.vue';
import WeatherIcon from '@/components/Global/WeatherIcon.vue';
import { computed, inject, onMounted, ref, useTemplateRef } from 'vue';
import { useFilePreview } from '@/store/filePreview';
import { useAuthUserStore } from '@/store/auth'
import { useMenuStore } from "@/store/menu";
import ItemMenu from '@/components/Global/ItemMenu.vue'
import ColorPicker from '@/components/Global/ColorPicker.vue';
import UserPanel from '@/components/Global/UserPanel.vue';
import Modal from '@/components/Global/Modal.vue';
import LoaderButton from '@/components/Global/LoaderButton.vue';
import Cropper from '@/components/Global/Cropper.vue';
import { DateTime } from 'luxon';
import { useApi } from '@/composables/api';
import { amountOfMoneyParser } from '@/utils/tools';
import Edit from '@/components/Icons/Edit.vue';
import CommandButton from '@/components/Global/CommandButton.vue';
import FloatButton from '@/components/Global/FloatButton.vue';
import AddIcon from '@/components/Form/AddIcon.vue';
import Back from '@/components/Icons/Back.vue';
    const menu = useMenuStore()
    const auth = useAuthUserStore()
    const props = defineProps(['UserAllData', 'clapData', 'movExist', 'refreshSummary'])
    const emit = defineEmits(['updateUser', 'openHistory'])
    const iconEditModal = ref(false)
    const cropperIs = ref(false)
    const sendLoader = ref(false)
    const cropperInstance = useTemplateRef('cropperInstance')
    const auth_id = auth.id
    const targetId = props.UserAllData.id
    const viewAlbum = ref(false)
    const tagText = ref('')
    const tagAlbums = ref('')
    const editData = ref(null)
    const filePreview = useFilePreview()
    const introUpload = ref(false)
    const imageError = ref([])
    const iconType = ref(props.UserAllData?.icon_type ?? 0)
    const iconBg = ref(props.UserAllData?.icon_bg ?? '#000')
    const api = useApi()

    onMounted(async () => {
        await fetch(`https://news.glowd.co.jp/index.php?rest_route=/wp/v2/posts/&search=${props.UserAllData.name}&_embed&per_page=3`).then(response => response.json())
    })
    const canViewRefreshHistory = computed(() => {
        return !!(props.UserAllData && auth.id && ((props.UserAllData.id === auth.id && auth.isEmployee) || auth.isAdmin))
    })
    const handleImgError = (index) => {
        if (!imageError.value.includes(index)) {
            imageError.value.push(index)
        }
    }
    const defaultIcon = computed(() => {
        const color = encodeURIComponent(iconBg.value);
        const noSpace = props.UserAllData.name?.charAt(0).toUpperCase();   
        const basePath = '/user_default_thumbnail'
        return `${basePath}/${noSpace}/45/${color}`; 
    })
    const icon = computed(() => {
        return props.UserAllData.icons
    })
    const weathers = computed(() => {
        return props.UserAllData.weathers
    })
    const userDaysWeather = computed(() => {
        const weathers = props.UserAllData.days_weathers
        return weathers.sort((a, b) => new Date(a.date) - new Date(b.date));
    })
    const updateWeather = () => {
        if(props.UserAllData.id == auth.id){
            menu.setMenu( { name: 'weatherUpdater', id: auth.id})
        }
    }
    const closeModal = () => {
        introUpload.value = false
        editData.value = null
    }
    const viewalbumByTag = async(tag) => {
        tagText.value = tag.text
        const response = await api.post('/get_albums', { tag_id: tag.id })
        tagAlbums.value = response
        viewAlbum.value = true

    }  
    const sanitized = (tag) => {
        const sanitizedString = tag.text ? tag.text.replace(/#|♯|＃/g, '') : '';
        return sanitizedString;
    }
    const movSrc = (mov) => {
        return mov.path.includes('intro') ? '/cdn/user_album/' + targetId + '/' + mov.path : '/cdn/user_album/' + targetId + '/' + mov.id + '_' + targetId + '_' + mov.path + '.' + mov.extension
    }
    const previewProfile = () => {
        let target_data = {
            mime_type: 'image',
            extenstion: 'webp',
            id: props.UserAllData.icon_path
        }
        const color = props.UserAllData.icon_bg || '000000'
        const path = props.UserAllData.icon_path ? `/user_icon_thumbnail/${props.UserAllData.icon_path}/original`  : `/user_default_thumbnail/${props.UserAllData.name?.charAt(0)}/200/${color}`
        target_data['file_path'] = path       
        
        const data = {
            active: true,
            files: [target_data],
            source: 'user',
            index: 0,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const previewImage = (file, index) => {
        const files = props.movExist.map(fileData => ({
            ...fileData,
            file_path: fileData.path.includes('intro') ? `/cdn/user_album/${targetId}/${fileData.path}` : `/cdn/user_album/${fileData.user_id}/${fileData.id}_${fileData.user_id}_${fileData.path}.${fileData.extension}`,
            thumbnail_path: `/cdn/user_album/${fileData.user_id}/${fileData.id}_${fileData.user_id}_${fileData.path}_thumbnail.webp`
        }));   
        const data = {
            active: true,
            files,
            source: 'user',
            source_board_id: null,
            index: index,
            message: null,
        }
        filePreview.setFilePreview(data)
    }
    const editAlbum = (data) => {
        editData.value = data
        introUpload.value = true
    }
    const isMov = (type) => {
        return type.includes('video') 
    }
    const introMovDeleteConfirm = async(id) => {
        const response = await api.post('/mov_delete', {delete_id: targetId, mov_id: id}, {
            ask: '自己紹介Movを削除しますか。',
            toast: '削除しました。'
        })
        if(response == 'saved'){
            emit('updateUser');
        } 
    }
    const iconClickMenu = () => {
        menu.setMenu( {name: 'iconMenuWrap', id: 23})
    }
    const sendIcon = async() => {
        if(iconType.value == 0) {
            iconDeleteConfirm()
        }else if(iconType.value == 1 && cropperInstance.value){
            customIconCreate()
        }

    }
    const customIconCreate = async() => {

        sendLoader.value = true;
        const { blob, source } = await cropperInstance.value.complete();
        if(blob && source){
            const formData = new FormData();
            formData.append('croppedImage', blob/*, 'example.png' */);
            formData.append('orgImage', source)    
            await api.post('/user_icon_cropped_up_api', formData)
            iconEditModal.value = false;
            emit('updateUser')   
        }
    }     
    const closeIconEditModal = () => {
        iconEditModal.value = false
        cropCancel()
    }
    const cropCancel = () => {
        cropperIs.value = false;
        if(cropperInstance.value){
            cropperInstance.value.destroy();
            cropperInstance.value = null;
        }
                    
    }
    const iconDeleteConfirm = async() => {     
        sendLoader.value = true;
        await api.post('/user_icon_create_api', {icon_type: iconType.value, icon_bg: iconBg.value}, {
            ask: 'アイコンを変更しますか？',
            toast: 'アイコンを変更しました。'
        })
        emit('updateUser');
        iconEditModal.value = false;
        sendLoader.value = false;       
    }

</script>