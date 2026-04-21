<template>
    <div class="h-full w-full overflow-auto bg-[var(--bg3)] relative text-[var(--primary-color)]">
        <div class="h-[60px] items-center hidden under960:flex">
            <HamBurger/>
        </div>
        <div class="p-8 under960:p-6 under960:pt-0">       
            <div class="flex gap-8 under960:flex-col">
                <div class="flex-[0.4] flex  flex-col items-center bg-[var(--background-color)] p-8 under960:p-4 rounded">
                    <div class="relative min-h-120px w-full flex justify-center items-center">
                        <div v-if="isSelf" class="absolute right-0 top-0">
                            <ItemMenu :items="[
                                { title: 'アイコン変更', action: () => editIconModal = true }
                            ]"/>
                        </div>
                        
                        <div @click.stop="menu.setMenu({parent: 'iconMenuWrap'})" class="cursor-pointer">
                            <UserPanel @click="previewProfile" v-if="userData" disable-instant :user="userData" imgClass="profile-image" size="120"/>
                            <div v-else class="bg-[var(--bg2)] animate-pulse h-[120px] w-[120px] rounded-full"></div>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="text-[20px] mb-5 mt-8 flex justify-center relative gap-4 items-center">
                            <p><span>{{userData?.name}}</span></p>
                            <div v-if="userData && weathers" @click.stop="updateWeather" class="h-6 overflow-hidden cursor-pointer flex items-center gap-2 bg-[var(--bg3)] px-3 py-1 rounded-full">
                                <WeatherIcon :key="`weather_${weathers.value_int}`" :which="weathers.value_int" size="20"/>
                                <div v-if="isSelf" class="flex items-center justify-center mt-1" :style="{transform: `rotate(270deg)`}">
                                    <Back size="8"/>
                                </div>
                            </div>                    
                            <Transition name="upShiftPop">
                                <WeatherUpdater
                                    v-if="menu.name == 'weatherUpdater' && menu.id == auth.id" 
                                    @reload="fetchUserData()"
                                />
                            </Transition>
                        </div>
                        <div class="flex flex-col gap-4 items-center mb-8">                        
                            <div>
                                <p>{{userData?.name_kana}}</p>
                            </div>
                            <div v-if="userDaysWeather.length" class="flex text-[12px] justify-center items-center gap-5 mt-4">
                                <div v-for="(weather, index) in userDaysWeather" :key="index" class="cursor-pointer flex flex-col items-center relative group">       
                                    
                                    <WeatherIcon :key="`weather_${weather.value_int}`" :which="weather.value_int" size="20"/>  
                                    <div class="invisible group-hover:visible absolute top-full left-1/2 -translate-x-1/2 mt-[7px] z-10 pointer-events-none flex flex-col items-center">
                                        <span class="w-0 h-0 border-l-[5px] border-l-transparent border-r-[5px] border-r-transparent border-b-[7px] border-b-black"></span>
                                        <p class="bg-black text-white text-[11px] px-2 py-1 rounded whitespace-nowrap">{{DateTime.fromISO(weather.date).toFormat("d(EEE)")}}</p>
                                    </div>
                                </div>
                            </div>   
                        </div>
                        <div class="flex flex-col gap-4 items-center">
                            <div v-if="userData?.phone_number"  class="flex items-center gap-2 justify-center">    
                                <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="20" width="20" viewBox="0 0 29.36781 29.38005">
                                    <path d="M27.08378,2.7689c-.70938-.65877-1.49932-1.21802-2.30905-1.73474-.50804-.31049-.91555-.52569-1.31187-.70961s-.63347-.2594-1.10009-.30999c-1.11015-.12036-2.03086.51344-2.35583,1.59441-.382,1.17816-.84938,2.63498-1.19497,3.819.00033.00966-.51458,1.7491-.48492,1.68587-.10524.41953-.05706.89102.09307,1.28187.38165,1.02393,1.30563,1.51866,2.30003,1.72743.35521.07458.45641.51417.40431.76353s-.23033.86886-.42264,1.35464c-.24197.62474-.54175,1.21126-.89564,1.77307-.72909,1.14652-1.67818,2.16645-2.65028,3.1373-.49546.49854-1.00564.97358-1.53152,1.41834-1.41945,1.2391-2.8345,2.04293-4.63645,2.59353-.34152.10435-.77792-.15179-.88916-.56326-.12904-.47731-.29021-.94579-.5927-1.34747-.61344-.80236-1.68158-1.2033-2.6622-.85322-.11924.03332-.44486.12877-.56723.1636-1.50015.43764-3.02691.90909-4.50917,1.40514-.6127.16831-1.18734.55993-1.47317,1.13984-.31104.62458-.44385,1.62069-.05346,2.35646.46332.87321.98008,1.69498,1.56089,2.48929.33659.4475.69242.89996,1.09701,1.29687,1.4902,1.48931,3.65602,2.36931,5.77396,2.07161,6.13628-.90125,11.58762-4.86368,15.48414-9.53709,2.21438-2.69856,3.93487-5.83827,4.79646-9.24132.1557-.6404.3444-1.41549.37709-2.06593.24314-2.14618-.7212-4.24962-2.24662-5.70917ZM27.19815,8.10323c-.05325.85618-.30269,1.79346-.53254,2.61524-2.21358,7.79303-8.99392,14.21237-16.85519,16.18021-.66205.17387-1.37044.28928-2.03472.3108-1.74538.00133-3.3258-1.1368-4.32483-2.51401-.49106-.63031-1.27295-1.78729-1.42634-2.08091s-.04114-.67939.30118-.76637,3.55857-.98118,5.27048-1.50465c.00381-.0042.038-.01742.10942.00578.24246.08612.33907.3184.42873.55475.09092.2252.14865.44732.1999.67863.18064.66963.56426,1.10106,1.2722,1.32283s2.01319-.1528,2.36997-.24542c.29852-.0775.5952-.1673.88834-.27017,2.19192-.76231,4.04653-2.24596,5.66819-3.8531,2.16492-2.13515,3.61027-4.19346,4.26273-7.0885.0532-.23608.18354-.60688.20251-1.14265s-.15249-.93402-.29062-1.13606c-.27488-.40338-.63765-.60973-.97989-.71623s-.76004-.23482-1.07291-.37462-.33185-.44338-.22487-.78326c.13704-.43536.38611-1.27244.38611-1.27244.35589-1.1825.7605-2.65423,1.08313-3.85221.04179-.25307.27404-.33108.51425-.30145.03891.02457.07955.04819.12402.06958.80523.44886,1.60077,1.03579,2.3238,1.6137.36004.29021.70928.59279,1.00713.93033.85541.97126,1.46493,2.37143,1.32981,3.62018Z"/>
                                </svg>                   
                                <a class="jump-link" :href="`tel:${userData.phone_number}`">{{userData.phone_number}}</a>
                            </div>
                            <div v-if="userData?.work_email"  class="flex items-center gap-2 justify-center"> 
                                <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="16" width="22" viewBox="0 0 31.37902 25.54039">
                                    <path d="M31.33216,9.90429l-.0293-6.33087-.00024-.04944c0-.01599.00006-.03186-.00128-.05701-.00653-.31342-.0531-.63818-.14386-.94025-.40625-1.40619-1.77679-2.44263-3.24121-2.43884-.48352-.00616-1.89557.01617-2.38483.00946C19.98444.14684,12.15455.00872,6.53895.01378c-.50385-.00763-1.89368-.00342-2.37408-.01227-.25122-.00061-.61182-.00616-.8598.00873C1.37854.13744-.05322,1.79833.03663,3.71428.01044,9.22972.01343,16.37273.0008,21.91545c-.02429,1.1178.50732,2.23047,1.40503,2.8996.59326.4588,1.34619.71442,2.09613.72382,1.43842.00262,13.32648.00244,15.04816-.00537,2.04669-.00311,7.33167-.01758,9.2984-.02155,1.81982.02319,3.41943-1.45038,3.52429-3.26996.02582-3.45569-.03699-8.83527-.04065-12.33771ZM29.35059,20.98326l-.00201.79132c.00214.17578-.00024.3847-.04291.55267-.14117.62958-.74158,1.11945-1.38763,1.12561-.02277.00073-.02997.00378-.06952.00293l-.19781-.00049-.39569-.00104c-.47992.00446-1.88739-.01282-2.37402-.00616-4.94122-.00439-15.55219-.03717-20.57532-.02136,0,0-.77893-.00079-.77893-.00073-.30463-.00549-.60266-.10364-.84412-.29126-.38129-.27972-.5929-.75067-.5755-1.2193.00403-5.53259-.02521-12.69525-.03577-18.20123l-.00061-.19781c-.00037-.01898.00037-.02966.00122-.03815l.00195-.02924c.00262-.78552.71588-1.45715,1.49744-1.43884.32983-.00305,1.0603-.00043,1.38483-.00555,6.26434-.03662,14.30255-.06573,20.57526-.12665v.03467c.49835-.00232,1.88873.01477,2.36328.01239.8255.00134,1.54352.73486,1.53369,1.55701-.0249,5.25183-.05334,12.27277-.07782,17.50122Z"/>
                                    <path d="M27.43177,5.83068c-3.85706,2.20294-7.76099,4.66443-11.54102,7.0459-3.77063-2.4812-7.53534-4.99078-11.54852-7.07019-.31787-.16327-.7171-.08289-.94403.20978-.25635.33057-.19617.80627.13434,1.06256.93616.72595,1.90039,1.40796,2.86841,2.08386.93945.64893,1.98077,1.34564,2.94067,1.97028,1.97833,1.28552,4.01263,2.48297,6.01135,3.73608.31055.20007.72192.21277,1.05261,0,2.88098-1.85382,6.0697-3.89441,8.89026-5.80353.97583-.66364,1.95154-1.32745,2.91473-2.01099.29858-.21344.39648-.62439.21124-.95068-.19794-.34882-.64124-.47107-.99005-.27307Z"/>
                                </svg>                     
                                <a class="jump-link" :href="`mailto:${userData.work_email}`">{{userData.work_email}}</a>
                            </div>
                            <div class="gap-2" v-if="isSelf && auth.isEmployee || auth.isAdmin">
                                <div @click="showRefreshHistory = true" title="リフレッシュ" class="flex gap-1 items-center cursor-pointer">
                                    <svg fill="var(--primary-color)" xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 0 152 152"><path d="M35.35664,122.00491c-2.92196,4.71027-6.09263,9.26686-9.03117,13.97086-1.53951,2.46444-3.33378,7.18873-6.0073,8.3482-3.69011,1.60036-7.54913-.52241-7.82955-4.58333-.20116-2.91308,2.19207-6.1748,3.69795-8.63033,3.5865-5.84821,7.75654-11.39238,11.81684-16.91359-3.52069-5.04324-6.16224-10.79308-7.39876-16.85097-5.15406-25.25064,8.69527-47.64356,29.41981-60.71786,25.29294-15.95632,54.14093-17.83465,81.49292-27.78443,3.39769-1.23597,7.61434-4.24429,11.06763-1.54447,1.41795,1.10857,1.8595,2.68369,2.03845,4.40686.14072,1.355.25042,3.28182.31016,4.67031,1.65578,38.48073-9.9261,85.51135-46.39714,105.09187-15.61261,8.38208-37.00117,11.79489-53.88275,5.44193-3.39181-1.27642-6.21515-3.0805-9.29709-4.90505ZM64.46498,88.42486c-8.13962,7.74857-15.57825,16.25774-22.55533,25.06733-.1484.56878,2.62196,2.09445,3.20674,2.40824,13.86161,7.43824,34.81025,3.94291,48.24892-3.11919,29.48572-15.49492,39.75076-53.33804,40.72847-84.36919.03954-1.25506.16262-8.09017-.15083-8.66259-.26877-.49082-.40163-.11804-.63624-.04258-3.74828,1.20558-7.35931,2.70103-11.17357,3.77578-14.06932,3.96434-28.46257,6.06422-42.40211,10.91821-26.54063,9.2419-53.72366,26.99623-49.0277,59.29154.4657,3.20276,2.07105,8.46276,3.70516,11.23819.14153.24037-.08594.48899.49917.37106,16.13675-19.90509,35.39727-37.40145,57.30399-50.80299,2.40554-1.4716,10.43623-6.57973,12.71964-6.63968,2.89089-.0759,5.13694,2.83283,4.40191,5.59945-.2283.85932-1.1071,1.75234-1.77223,2.32705-3.69668,3.19412-10.53862,6.70129-14.88047,9.72891-9.90494,6.90682-19.47192,14.58696-28.21551,22.91048Z"></path></svg>
                                    <p v-if="userData?.refresh_current_balance !== undefined">{{ amountOfMoneyParser(userData?.refresh_current_balance) }}円</p> 
                                    <span class="jump-link ml-2">詳細</span>
                                </div>                                                
                            </div>
                        </div>
                    </div>                   
                </div>
                <div class="flex-[0.6] flex flex-col items-center bg-[var(--background-color)] p-8 under960:p-4 rounded relative">
                    <div v-if="isSelf" class="absolute right-4 top-4">
                        <ItemMenu :items="[
                            { title: 'プロフィール編集', action: () => editInfoModal = true },
                        ]"/>
                    </div>
                    <div class="profile-fields">
                        <div class="flex flex-wrap gap-3">
                            <div v-if="userData?.positions?.name" class="profile-badge">
                                <span class="badge-label">役職</span>
                                <span class="badge-value">{{ userData?.positions?.name }}</span>
                            </div>
                            <div v-if="userData?.offices?.name" class="profile-badge">
                                <span class="badge-label">営業所</span>
                                <span class="badge-value">{{ userData?.offices?.name }}</span>
                            </div>
                        </div>

                        <div v-if="userData?.motto" class="profile-row">
                            <span class="row-label">好きな言葉</span>
                            <p class="row-value">{{ userData?.motto }}</p>
                        </div>
                        <div v-if="userData?.enjoy" class="profile-row">
                            <span class="row-label">私の「楽」</span>
                            <p class="row-value">{{ userData?.enjoy }}</p>
                        </div>
                        <div v-if="userData?.intro" class="profile-row">
                            <span class="row-label">自己紹介</span>
                            <p class="row-value">{{ userData?.intro }}</p>
                        </div>
                        <div v-if="userData?.recommend" class="profile-row">
                            <span class="row-label">推し</span>
                            <p class="row-value" v-html="urlCheck(userData?.recommend)"></p>
                        </div>

                    </div>
                </div>
            </div>
            <div v-if="isSelf || (userData?.user_album && userData.user_album.length > 0)"  class="bg-[var(--background-color)] p-8 under960:p-4 rounded mt-8">
                <div class="relative">
                    <div ref="albumSwiperEl" class="swiper album-swiper overflow-hidden">
                        <div class="swiper-wrapper">
                            <!-- First slide: add to album (only for self) -->
                            <div v-if="isSelf" class="swiper-slide album-slide album-slide-add">
                                <div @click="introUpload = true" class="text-[gray] flex flex-col items-center justify-center gap-3 h-full cursor-pointer">
                                    <svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" height="30" viewBox="0 0 24.99094 20.1551">
                                        <path d="M24.98817,8.96682c-.005-.82039-.0217-1.64006-.04468-2.45892l-.0087-.30707-.00395-.09279c-.00172-.03335-.00302-.06638-.00629-.1005-.00498-.06735-.01247-.13579-.02192-.20492-.03909-.27606-.11312-.56881-.25758-.85805-.14178-.28808-.35971-.56707-.62785-.77585-.26727-.21085-.57135-.34796-.8618-.42708-.14581-.04008-.28961-.06697-.42927-.08334-.13828-.01666-.2812-.02397-.40081-.02479l-.60913-.01375-1.21729-.02751-1.70692-.03856-.27139-1.30351-.10485-.50313-.05242-.25159-.00328-.01571-.00531-.02257-.01716-.06434c-.01202-.04203-.02415-.08743-.0387-.12461-.02727-.07813-.05968-.15425-.0974-.22812-.14944-.29615-.3783-.5494-.65492-.72967-.27665-.17963-.60317-.28767-.93323-.30316l-4.14065-.00729-2.04672.00053-1.02337.00116-.51168.00059-.25584.00029-.12792.00015-.06396.00007-.04938.00099-.06184.00246c-.33024.01551-.65693.12364-.9337.3034-.27674.18037-.50568.43376-.65517.73006-.03774.07392-.07016.15007-.09743.22825-.01456.03718-.02669.0826-.03871.12465l-.01716.06438-.00531.02257-.00327.01573-.05239.25159-.10477.50315-.27119,1.30265-1.86699.04124-1.29727.02872-.16216.0036-.08108.00178-.04053.0009c-.01211.00007-.03826.00244-.0567.00367-.03942.00312-.08346.00613-.119.01022l-.10683.01259c-.0718.01039-.14359.02147-.21623.03639-.28949.05814-.5885.15752-.87447.32019-.28529.16132-.55462.39023-.76097.67012-.20789.27883-.34812.6026-.41905.92195-.03606.15774-.05643.32405-.0638.46989l-.01552.32807c-.01121.21887-.01999.43734-.02702.65555C.01427,7.93083.00125,8.80163.00005,9.6714c-.00172,1.73938.04573,3.47429.12875,5.20862.04175.86715.0954,1.73402.16022,2.60123l.01191.1626.01727.18976c.01484.13225.03573.26941.06671.41284.03245.14445.07612.29696.1431.45765.06687.16003.15981.33051.28943.49111.12796.16115.29581.30469.46793.40887.17249.10547.34651.17446.50389.22104.31615.09108.57879.11511.81761.13562.24041.01923.45277.0272.67202.03742.21802.00929.43823.02202.65447.02841.43371.01461.86825.03131,1.30023.04133.86551.02419,1.72893.04173,2.59232.05533.8634.01463,1.7262.02606,2.58913.02808.86282.00196,1.72557.00773,2.5883-.00048,0,0,3.65822.00595,4.99736-.02707,1.66458-.04104,4.16644.00159,4.99624-.13862s1.27253-.59028,1.42273-1.2124.21834-1.60315.29052-2.42183c.06799-.81894.1279-1.63874.17229-2.45922.07964-1.64127.11975-3.28412.1057-4.92484ZM18.16145,18.4186c-1.71737-.00754-3.43627-.00923-5.15507-.0299l-5.15509-.05139c-.85859-.01011-1.7171-.02281-2.57316-.04245-.42893-.00773-2.18837-.03412-2.53564-.08919s-.63133-.49184-.69737-.85309-.11023-1.70266-.14824-2.55584c-.07557-1.70622-.11616-3.4155-.10834-5.12049.00422-.85234.02007-1.70369.05319-2.55138.00758-.21208.01684-.42376.02844-.63486l.01609-.31696c.00404-.0691.01119-.11703.02293-.16923.0232-.10057.06074-.17925.11069-.24561.05015-.06629.11464-.12366.20392-.17409.0888-.05012.20267-.09092.33497-.11676.03282-.00679.06751-.0116.1022-.01683l.05355-.00598.04184-.00363c.00841-.00051.00901-.00169.02382-.00185l.04017-.00073.08034-.00147.16068-.00297,1.28542-.02364,2.57075-.0474c.41523-.00756.78586-.2999.87494-.7236l.00053-.00253v-.00002s.35146-1.67383.50783-2.4147c.04266-.2021.22081-.34626.42736-.34635,1.34656-.00055,6.14302.00241,7.49072.00327.20669.00013.38494.14499.42745.34726l.50659,2.41038.00063.00396c.08526.40619.44212.71431.87465.72165l2.44504.04157,1.22249.02079.61076.01037c.08375.00009.14415.00374.20787.0105.06237.00657.11902.01751.16937.03103.10152.02718.17406.06379.22706.10457.05301.04133.09375.0889.13312.16466.03918.07447.07081.18018.08951.30599.00449.03162.00845.06418.0111.09826.00202.01648.00249.03426.00353.05168l.00297.05955.01035.30272c.02742.80719.04862,1.61402.05828,2.42042.00889.80642.0114,1.61244.00627,2.41818-.00675.80569-.02563,1.6109-.05365,2.41567-.04073,1.33203-.11643,2.66225-.21325,3.99141-.02366.32482-.29192.5769-.61752.5838-.53298.01129-1.0697.01794-1.60691.01947-.85627.00613-1.7146.00881-2.57322.00576Z"/>
                                        <path d="M16.2503,7.51592c-.48354-.49616-1.06587-.90029-1.70845-1.17428-.64254-.27385-1.34321-.41287-2.03917-.41399-.69596.00297-1.39592.14396-2.03673.41985-.64085.276-1.22052.68168-1.70095,1.17835-.95961.99582-1.49909,2.3743-1.47435,3.73768l.0283.50963c.02127.16888.05076.3371.07731.50528.03478.16668.08056.33144.12246.49671.05054.16271.11284.32201.17085.48243.25802.63204.64794,1.20955,1.13634,1.68271.48805.47364,1.06983.84781,1.70095,1.09235.63001.24737,1.30848.36086,1.97581.34598v-.03001c.66366.02035,1.3397-.09013,1.96815-.33445.63003-.24067,1.20956-.61418,1.69938-1.08312.48961-.46936.88547-1.04133,1.151-1.67122.2674-.62906.40737-1.31364.41351-1.9963.00948-.68262-.11504-1.37342-.37024-2.01426-.25305-.64169-.63305-1.23434-1.11419-1.73336ZM14.95582,13.71665c-.31196.33187-.69204.60036-1.10896.79277-.41805.19028-.87454.30259-1.34417.32493v-.03004c-.46594-.01685-.92001-.12617-1.3365-.31337-.41583-.18855-.79366-.45637-1.10739-.78352-.62333-.65476-1.01592-1.5331-1-2.44389.01173-.45323.11077-.89754.29138-1.30544.18207-.40722.44742-.77473.76953-1.07754.32135-.30412.7013-.54044,1.10739-.69752.4057-.15871.83827-.24049,1.27559-.23952.43733.00088.86919.08466,1.27316.24539.40437.15908.78166.39696,1.0999.70159.31966.30276.58167.66973.76169,1.07497.1794.40568.27766.84742.28914,1.29808.01313.90211-.34627,1.78926-.97075,2.45312Z"/>
                                    </svg>
                                    <p v-if="!userData?.user_album?.length" class="text-[12px] mb-3">
                                        アルバムにファイルがありません。
                                    </p>
                                    <CommandButton :buttons="[{ title: 'アップロード', action: () => introUpload = true}]"/>
                                </div>
                            </div>
                            <!-- Album item slides -->
                            <div
                                v-for="(item, index) in userData?.user_album"
                                :key="item.id"
                                class="swiper-slide album-slide cursor-pointer group/slide"
                                
                            >
                                <img
                                    v-if="item.mime_type === 'image'"
                                    :src="albumFilePath(item)"
                                    class="album-media"
                                    loading="lazy"
                                />
                                <video
                                    v-else-if="item.mime_type.includes('video')"
                                    class="album-media"
                                    preload="metadata"
                                >
                                    <source :src="albumFilePath(item)">
                                </video>
                                <!-- Title: top overlay -->
                                <div v-if="item.title" class="album-slide-title-top">{{ item.title }}</div>
                                <!-- Tags: bottom overlay -->
                                <div v-if="item.tags?.length" class="album-slide-tags" @click.stop>
                                    <div v-for="tag in item.tags" :key="tag.id" class="album-tag cursor-pointer" @click.stop="openTagPanel(tag)">#{{ tag.text }}</div>
                                </div>
                                <!-- Item menu (owner only) -->
                                <div v-if="isSelf" class="absolute right-1 top-1" @click.stop>
                                    <ItemMenu :items="[
                                        { title: '編集する', action: () => editAlbum(item) },
                                        { title: '削除する', action: () => deleteAlbumItem(item.id) },
                                    ]"/>
                                </div>
                            </div>
                        </div>
                        <div class="swiper-button-prev album-prev"></div>
                        <div class="swiper-button-next album-next"></div>
                        <div class="swiper-pagination album-pagination"></div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--background-color)] p-8 under960:p-4 rounded mt-8">
                <div class="relative">
                    <p class="text-[13px] font-semibold text-[var(--third-color)] mb-4 tracking-wide">グラウドニュース関連記事</p>
                    <!-- Skeleton loading -->
                    <div v-if="newsLoading" class="flex gap-5 overflow-hidden">
                        <div v-for="i in 5" :key="i" class="news-skeleton bg-[var(--bg2)] animate-pulse"></div>
                    </div>
                    <div v-else-if="processedNewsItems.length" ref="newsSwiperEl" class="swiper news-swiper overflow-hidden">
                        <div class="swiper-wrapper">
                            <a
                                v-for="(item, index) in processedNewsItems"
                                :key="index"
                                :href="item.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="swiper-slide news-slide"
                            >
                                <img v-if="item.src" :src="item.src" class="news-img" loading="lazy" />
                                <div v-else class="news-img-placeholder"></div>
                                <div class="news-overlay">
                                    <p class="news-title" v-html="item.title"></p>
                                    <p class="news-date">{{ item.date }}</p>
                                </div>
                            </a>
                        </div>
                        <div class="swiper-button-prev news-prev"></div>
                        <div class="swiper-button-next news-next"></div>
                        <div class="swiper-pagination news-pagination"></div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center gap-3 py-10">
                        <svg id="a" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" height="88" viewBox="0 0 345.04999 1380">
                            <path d="M.04999,138c.03003-22.98-.02002-46.02,0-69h138V0h138v69h69v759h-69v69h69v138h-138c-22.97998,0-46.02002.01001-69,0s-46.02002.03003-69,0-46.02002.02002-69.01001,0c-.01001-45.97998.03003-92.02002,0-138.01001h69v-69H.03998C-.02002,690.01001.08997,551.96997.03998,413.97998c22.98999-.01001,46.02002.03003,69.01001,0v-69c22.97998.03,46.02002-.03,69,0v69c-22.97998,0-46.02002-.03003-69,0v345h207c-.02002-114.97998.03003-230.02002,0-345-.02002-91.98001.01001-184.02002,0-276.01001H.03998l.01001.03003Z" style="fill: #4c6174;"/>
                            <rect x="276.04999" y="828" width="69" height="69" style="fill: #efefef;"/>
                            <path d="M.04999,138h276.01001c0,91.98001-.03003,184.02002,0,276h-69v69h-69v-69h69v-69.01001c-22.98999-.01001-46.02002.03-69.01001,0s-46.02002.03-69,0c-22.98999-.03-46.02002.01999-69.01001,0,.03003-21.84,0-43.72-.03998-65.58002,0-1.85999,1.03998-3.75,1.03998-4.42999l68.01001,1.01001v-67.48999L.03998,206.98999c.01001-22.98001-.03003-46.01999,0-69l.01001.01001ZM207.04999,207h-69v69h69v-69Z" style="fill: #efefef;"/>
                            <path d="M.04999,828h69v69H.04999c-.01001-22.97998,0-46.02002,0-69Z" style="fill: #efefef;"/>
                            <path d="M.04999,207l69.02002,1.51001v67.48999l-68.02002-1.01001c-.03003-22.23999-1.01001-45.39001-1-68v.01001Z" style="fill: #4c6174;"/>
                            <path d="M138.04999,345c22.98999.03,46.02002-.01999,69.01001,0v69.01001h-69v69h69v-69h69c.03003,114.97998-.02002,230.02002,0,345H69.06v-345c22.97998-.03003,46.02002,0,69,0v-69l-.01001-.01001Z" style="fill: #b9c7d3;"/>
                            <rect x="138.04999" y="207" width="69" height="69" style="fill: #4c6174;"/>
                            <rect x="276.04999" y="1035" width="69" height="345" style="fill: #fefefe;"/>
                            <path d="M138.04999,1242c.03003,22.97998-.02002,46.02002,0,69h69c.02002-22.97998-.03003-46.02002,0-69h69v138H.04999v-69h69c.02002-22.97998-.03003-46.02002,0-69h69Z" style="fill: #4c6174;"/>
                            <path d="M138.04999,1035c.06,68.97998-.08002,138.02002,0,207h-69c.08002-68.97998-.06-138.02002,0-207,22.97998.03003,46.02002-.01001,69,0Z" style="fill: #b9c7d3;"/>
                            <path d="M207.04999,1035c-.06006,68.97998.07996,138.02002,0,207-.03003,22.97998.02002,46.02002,0,69h-69c-.02002-22.97998.03003-46.02002,0-69-.08002-68.97998.06-138.02002,0-207,22.97998.01001,46.02002,0,69,0Z" style="fill: #fefefe;"/>
                            <path d="M276.04999,1035v207h-69c.07996-68.97998-.06006-138.02002,0-207h69Z" style="fill: #b9c7d3;"/>
                        </svg>
                        <p class="text-[13px] font-semibold text-[var(--third-color)] tracking-wide">関連記事はありません</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Oshi slide panel -->
        <OshiPanel v-model="oshiPanel" :tag="oshiTag" />
        <Teleport to="body">
            <Transition name="modalFade">
                <ProfileIconUpdater
                    v-if="editIconModal && userData"
                    :userData="userData"
                    @close="flag => {
                        editIconModal = false;
                        if(flag) fetchUserData();                    
                    }"
                />
            </Transition>
        </Teleport>
        <Teleport to="body">
            <Transition name="modalFade">
                <ProfileInfoEditor
                    v-if="editInfoModal && userData"
                    :userData="userData"
                    @close="flag => {
                        editInfoModal = false;
                        if(flag) fetchUserData();                    
                    }"
                />
            </Transition>
        </Teleport>
        <Teleport to="body">
            <Transition name="modalFade">
                <UserIntroFile
                    v-if="introUpload && userData"
                    :UserAllData="userData"
                    :editData="editData"
                    @closeModal="introUpload = false; editData = null"
                    @updateUser="fetchUserData()"
                />
            </Transition>
        </Teleport>
        <Teleport to="body">
            <Transition name="modalFade">
                <UserRefreshHistoryModal
                    v-if="showRefreshHistory && userData"
                    :user-id="userData.id"
                    @close="showRefreshHistory = false"
                />
            </Transition>
        </Teleport>
    </div>
</template>
<script setup lang="ts">
import { useApi } from '@/composables/api';
import { User, UserAlbum } from '@/interface/globalInterface';
import { useAuthUserStore } from '@/store/auth';
import { computed, defineAsyncComponent, nextTick, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import HamBurger from '../Global/HamBurger.vue';
import { useMenuStore } from '@/store/menu';
import UserPanel from '../Global/UserPanel.vue';
import WeatherIcon from '../Global/WeatherIcon.vue';
import Back from '../Icons/Back.vue';
import { amountOfMoneyParser, urlCheck } from '@/utils/tools';
import { DateTime } from 'luxon';
import ItemMenu from '../Global/ItemMenu.vue';
import { useFilePreview } from '@/store/filePreview';
import Error from '@/components/Global/Error.vue'
import Swiper from 'swiper';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Navigation, Pagination } from 'swiper/modules';
import CommandButton from '@/components/Global/CommandButton.vue';
import OshiPanel from '@/components/Profile/OshiPanel.vue';
const ProfileIconUpdater = defineAsyncComponent({ loader: () => import('./ProfileIconUpdater.vue'), errorComponent: Error })
const ProfileInfoEditor = defineAsyncComponent({ loader: () => import('./ProfileInfoEditor.vue'), errorComponent: Error })
const WeatherUpdater = defineAsyncComponent({ loader: () => import('../Global/WeatherUpdater.vue'), errorComponent: Error })
const UserIntroFile = defineAsyncComponent({ loader: () => import('./UserEditComps/UserIntroFile.vue'), errorComponent: Error })
const UserRefreshHistoryModal = defineAsyncComponent({ loader: () => import('./UserRefreshHistoryModal.vue'), errorComponent: Error })
const userData = ref<User | null>(null);
const loading = ref(true);
const route = useRoute();
const api = useApi()
const auth = useAuthUserStore()
const menu = useMenuStore()
const editIconModal = ref(false)
const editInfoModal = ref(false)
const introUpload = ref(false)
const editData = ref<UserAlbum | null>(null)
const albumSwiperEl = ref<HTMLElement | null>(null)
const newsSwiperEl = ref<HTMLElement | null>(null)
const newsItems = ref<any[]>([])
const newsLoading = ref(true)
const oshiPanel = ref(false)
const showRefreshHistory = ref(false)
const oshiTag = ref<{ id: number; text: string } | null>(null)
let albumSwiperInstance: Swiper | null = null
let newsSwiperInstance: Swiper | null = null

const filePreview = useFilePreview()
onMounted(async () => {
    await fetchUserData();
    await nextTick()
    await getNews()
    
})
const getNews = async () => {
    if (!userData.value) return
    newsLoading.value = true
    const data = await fetch(`https://news.glowd.co.jp/index.php?rest_route=/wp/v2/posts/&search=${userData.value.name}&_embed&`).then(response => response.json())
    newsItems.value = data
    newsLoading.value = false
}
const fetchUserData = async () => {
    console.log('ff')
    const id = route.params.userId
    if(!id) return

    const data = await api.post('/profile_get_update_user', { id })
    if (data && 'id' in data) {
        userData.value = data
        if (data.id === auth.id) auth.setUser(data)
    }  
    loading.value = false
}
watch(userData, async () => {
    await nextTick()
    if (albumSwiperInstance) {
        albumSwiperInstance.destroy(true, true)
        albumSwiperInstance = null
    }
    if (albumSwiperEl.value) {
        albumSwiperInstance = new Swiper(albumSwiperEl.value, {
            slidesPerView: 'auto',
            spaceBetween: 40,
            modules: [Navigation, Pagination],
            navigation: {
                nextEl: '.album-next',
                prevEl: '.album-prev',
            },
            pagination: {
                el: '.album-pagination',
                type: 'bullets',
                clickable: true,
            },
        })
    }
})
const isSelf = computed(() => {
    return userData.value && userData.value.id == auth.id
})
const processedNewsItems = computed(() => {
    return newsItems.value.map((news: any) => {
        const src = news?._embedded?.['wp:featuredmedia']?.[0]?.['media_details']?.['sizes']?.['medium']?.['source_url'] ?? ''
        return {
            title: news?.title?.rendered ?? '',
            src,
            url: news?.link ?? '',
            date: news?.date ? DateTime.fromISO(news.date).toFormat('yyyy/MM/dd') : '',
        }
    })
})
watch(newsItems, async () => {
    await nextTick()
    if (newsSwiperInstance) {
        newsSwiperInstance.destroy(true, true)
        newsSwiperInstance = null
    }
    if (newsSwiperEl.value && processedNewsItems.value.length) {
        newsSwiperInstance = new Swiper(newsSwiperEl.value, {
            slidesPerView: 'auto',
            spaceBetween: 40,
            modules: [Navigation, Pagination],
            navigation: {
                nextEl: '.news-next',
                prevEl: '.news-prev',
            },
            pagination: {
                el: '.news-pagination',
                type: 'bullets',
                clickable: true,
            },
        })
    }
})
const weathers = computed(() => {
    return userData.value?.weathers
})
const userDaysWeather = computed(() => {
    const weathers = userData.value?.days_weathers || []
    return weathers.sort((a, b) => new Date(a.date).getDate() - new Date(b.date).getDate());
})
const updateWeather = () => {
    if(!isSelf.value) return
    menu.setMenu( { name: 'weatherUpdater', id: auth.id})    
}
const openTagPanel = (tag: { id: number; text: string }) => {
    oshiTag.value = tag
    oshiPanel.value = true
}
const editAlbum = (item: UserAlbum) => {
    editData.value = item
    introUpload.value = true
}
const deleteAlbumItem = async (id: number) => {
    if (!userData.value) return
    const res = await api.post('/mov_delete', { delete_id: userData.value.id, mov_id: id }, {
        ask: 'アルバムから削除しますか？',
        toast: '削除しました。',
    })
    if (res === 'saved') fetchUserData()
}
const albumFilePath = (item: UserAlbum): string => {
    const userId = userData.value?.id
    return item.path.includes('intro')
        ? `/cdn/user_album/${userId}/${item.path}`
        : `/cdn/user_album/${userId}/${item.id}_${userId}_${item.path}.${item.extension}`
}
const previewAlbumItem = (index: number) => {
    if (!userData.value?.user_album) return
    const files = userData.value.user_album.map(item => ({
        ...item,
        file_path: albumFilePath(item),
        thumbnail_path: `/cdn/user_album/${item.user_id}/${item.id}_${item.user_id}_${item.path}_thumbnail.webp`,
    }))
    filePreview.setFilePreview({
        active: true,
        files,
        source: 'user',
        source_board_id: null,
        index,
        message: null,
    })
}
const previewProfile = () => {
    if(!userData.value) return
    let target_data: { [key: string]: any } = {
        mime_type: 'image',
        extenstion: 'webp',
        id: userData.value.icon_path
    }
    const color = userData.value.icon_bg || '000000'
    const path = userData.value.icon_path ? `/user_icon_thumbnail/${userData.value.icon_path}/original`  : `/user_default_thumbnail/${userData.value.name?.charAt(0)}/200/${color}`
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
</script>
<style scoped>
.profile-fields {
    display: flex;
    flex-direction: column;
    gap: 26px;
    width: 100%;
}

/* Chips for position / office */
.profile-badge {
    display: inline-flex;
    flex-direction: column;
    gap: 2px;
    padding: 8px 16px;
    border-radius: 4px;
    background: var(--background-color);
    min-width: 80px;
}

.badge-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--third-color);
    letter-spacing: 0.03em;
}

.badge-value {
    font-size: 15px;
    font-weight: 500;
    color: var(--primary-color);
    line-height: 1.3;
}

/* Text info rows */
.profile-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 16px;
    border-radius: 4px;
    background: var(--background-color);
}

.row-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--third-color);
    letter-spacing: 0.03em;
}

.row-value {
    font-size: 14px;
    color: var(--primary-color);
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
    margin: 0;
}
</style>
<style>
.album-swiper {
    --swiper-navigation-color: var(--primary-color);
    --swiper-pagination-color: var(--primary-color);
    --swiper-navigation-size: 18px;
    padding-bottom: 36px !important;
}
.album-swiper .swiper-slide {
    width: 200px;
    height: 200px;
    border-radius: 6px;
    overflow: hidden;
    background: var(--bg3);
    flex-shrink: 0;
}
.album-swiper .album-slide-add {
    border: 1px dashed var(--formBorder);
    background: var(--bg3);
}
.album-swiper .album-media {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.album-swiper .album-slide-title-top {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    padding: 6px 8px 18px;
    background: linear-gradient(rgba(0,0,0,0.5), transparent);
    color: #fff;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    pointer-events: none;
}
.album-swiper .album-slide-tags {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 18px 8px 6px;
    background: linear-gradient(transparent, rgba(0,0,0,0.6));
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
}
.album-swiper .album-tag {
    font-size: 10px;
    color: var(--link-color);
    background: rgba(255, 255, 255, 0.796);
    border-radius: 3px;
    padding: 3px 5px;
    white-space: nowrap;
}
.album-swiper .swiper-button-prev,
.album-swiper .swiper-button-next {
    top: calc(50% - 18px);
    width: 20px !important;
    height: 20px !important;
    background: #0000004e;
    border-radius: 50px;
    padding: 3px;
}
.album-swiper .swiper-button-prev, .album-swiper .swiper-button-next {
    ::slotted(svg), svg {
        width: 80%;
        height: 80%;
        fill: white;
    }
}
.album-swiper .swiper-pagination {
    bottom: 6px;
}
@media (max-width: 960px) {
    .album-swiper .swiper-slide {
        width: 150px;
        height: 150px;
    }
}
.news-swiper {
    --swiper-navigation-color: var(--primary-color);
    --swiper-pagination-color: var(--primary-color);
    --swiper-navigation-size: 18px;
    padding-bottom: 36px !important;
}
.news-swiper .swiper-slide {
    width: 220px;
    height: 160px;
    border-radius: 6px;
    overflow: hidden;
    background: var(--bg3);
    flex-shrink: 0;
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
}
.news-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.news-img-placeholder {
    width: 100%;
    height: 100%;
    background: var(--bg2);
}
.news-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 24px 8px 8px;
    background: linear-gradient(transparent, rgba(0,0,0,0.72));
    pointer-events: none;
}
.news-title {
    font-size: 11px;
    color: #fff;
    line-height: 1.5;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}
.news-date {
    font-size: 10px;
    color: rgba(255,255,255,0.65);
}
.news-swiper .swiper-button-prev,
.news-swiper .swiper-button-next {
    top: calc(50% - 30px);
    width: 20px !important;
    height: 20px !important;
    background: #0000004e;
    border-radius: 50px;
    padding: 3px;
}
.news-swiper .swiper-pagination {
    bottom: 6px;
}
.news-skeleton {
    width: 220px;
    height: 160px;
    border-radius: 6px;
    flex-shrink: 0;
}
@media (max-width: 960px) {
    .news-skeleton {
        width: 160px;
        height: 120px;
    }
}
@media (max-width: 960px) {
    .news-swiper .swiper-slide {
        width: 160px;
        height: 120px;
    }
}
</style>