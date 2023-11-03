<template>
    <div v-if="viewWeatherComponent" id="weatherID" class="weather-wrapper">
        <div class="list-wrapper">
            <p>おはようございます。今日のコンディションを選択してください。</p>
            <ul>
                <li><input type="radio" v-on:change="saveWeather" v-model="weatherSelect" :value="0" name="test" id="cb1" />
                  <label for="cb1">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-4.31 0 80 80" enable-background="new -4.31 0 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <path fill="#EA5323" d="M12.407,72.25L8.577,79.95c0,0-7.411,0.166-7.835-0.111c-0.422-0.277-5.28-30.6,16.767-54.901  C40.824-0.78,70.121-0.595,70.822,0.252c0.701,0.84,0.531,11.121,0.531,11.121L12.407,72.25L12.407,72.25z"/>
                      <path fill="#F18A1B" d="M40.46,16.402c-14.477,8.185-25.406,20.571-30.469,39.95C7.584,65.565,8.49,79.938,8.49,79.938h8.548  l25.426-37.33l28.889-27.073V7.622C71.353,7.622,54.068,8.701,40.46,16.402z"/>
                      <path fill="#EFD12D" d="M22.755,47.307c-6.979,13.443-5.729,32.631-5.729,32.631l8.343-0.006l28.497-37.859l17.494-17.989  l-0.014-8.541c0,0-9.971-0.093-25.492,7.423C33.386,28.994,26.553,39.997,22.755,47.307L22.755,47.307z"/>
                      <path fill="#92C33D" d="M36.947,40.567c-14.47,18.08-11.731,39.365-11.731,39.365h9.283l15.853-28.76L71.359,33.75l-0.006-9.753  C71.353,23.997,49.875,24.402,36.947,40.567L36.947,40.567z"/>
                      <path fill="#21B5D9" d="M44.527,44.633C30.091,58.495,33.302,79.932,33.302,79.932h7.447l11.104-27.83l19.506-10.207l-0.006-9.627  C71.353,32.268,58.587,31.143,44.527,44.633L44.527,44.633z"/>
                      <path fill="#6E70B3" d="M71.058,47.176c0.443-0.697,0.301-7.941,0.301-7.941s-12.074-0.219-22.252,11.248  c-10.155,11.447-8.693,29.449-8.693,29.449s6.961,0.209,7.244-0.352c0.568-1.125-1.924-14.377,6.379-23.727  C62.638,46.174,70.816,47.545,71.058,47.176L71.058,47.176z"/>
                    </svg>
                  </label>
                </li>
                <li><input type="radio" v-on:change="saveWeather" name="test" id="cb2" v-model="weatherSelect" :value="1"/>
                  <label for="cb2">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-0.5 -0.5 80 80" enable-background="new -0.5 -0.5 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <circle fill="#EA5532" cx="39.676" cy="39.51" r="21.538"/>
                      <path fill="#EA5532" d="M45.875,11.806c0.442,0.884-0.005,1.607-0.993,1.607H34.119c-0.989,0-1.436-0.724-0.994-1.607l5.571-11.143  c0.442-0.885,1.166-0.885,1.608,0L45.875,11.806z"/>
                      <path fill="#EA5532" d="M33.125,67.194c-0.442-0.884,0.005-1.607,0.994-1.607h10.764c0.988,0,1.436,0.724,0.993,1.607l-5.571,11.143  c-0.442,0.885-1.166,0.885-1.608,0L33.125,67.194z"/>
                      <path fill="#EA5532" d="M11.806,33.125c0.884-0.442,1.607,0.005,1.607,0.994v10.763c0,0.989-0.723,1.437-1.607,0.994L0.664,40.304  c-0.885-0.442-0.885-1.166,0-1.608L11.806,33.125z"/>
                      <path fill="#EA5532" d="M67.195,45.875c-0.885,0.442-1.608-0.005-1.608-0.994V34.119c0-0.989,0.724-1.436,1.608-0.994l11.143,5.571  c0.885,0.442,0.885,1.166,0,1.608L67.195,45.875z"/>
                      <path fill="#EA5532" d="M24.425,15.409c0.938,0.313,1.133,1.14,0.435,1.839l-7.611,7.611c-0.699,0.699-1.527,0.503-1.84-0.434  L11.47,12.607c-0.313-0.938,0.199-1.45,1.137-1.137L24.425,15.409z"/>
                      <path fill="#EA5532" d="M54.576,63.59c-0.938-0.313-1.134-1.141-0.435-1.839l7.61-7.611c0.699-0.699,1.527-0.504,1.84,0.435  l3.939,11.818c0.313,0.938-0.199,1.45-1.137,1.137L54.576,63.59z"/>
                      <path fill="#EA5532" d="M15.409,54.575c0.313-0.938,1.141-1.134,1.84-0.435l7.611,7.611c0.699,0.698,0.504,1.526-0.435,1.839  L12.607,67.53c-0.938,0.313-1.45-0.198-1.137-1.137L15.409,54.575z"/>
                      <path fill="#EA5532" d="M63.591,24.426c-0.313,0.938-1.141,1.133-1.84,0.434l-7.61-7.611c-0.699-0.699-0.504-1.526,0.435-1.839  l11.818-3.939c0.938-0.313,1.449,0.199,1.137,1.137L63.591,24.426z"/>
                    </svg>
                  </label>
                </li>
                <li><input type="radio" v-on:change="saveWeather" name="test" id="cb3" v-model="weatherSelect" :value="2"/>
                  <label for="cb3">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-0.5 -15.755 80 80" enable-background="new -0.5 -15.755 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <path fill="#ddd" d="M67.278,23.974c0.217-1.004,0.337-2.044,0.337-3.112c0-8.134-6.594-14.728-14.728-14.728  c-2.683,0-5.191,0.729-7.357,1.981C42.775,3.272,37.577,0,31.606,0c-8.847,0-16.02,7.172-16.02,16.02  c0,0.153,0.018,0.302,0.022,0.453C6.954,16.693,0,23.763,0,32.473c0,8.701,6.941,15.766,15.587,15.998v0.021h51.09V48.49  c0.017,0,0.033,0.002,0.05,0.002C73.505,48.492,79,42.997,79,36.219C79,29.627,73.798,24.264,67.278,23.974z"/>
                    </svg>
                  </label>
                </li>
                <li><input type="radio" v-on:change="saveWeather" name="test" id="cb4" v-model="weatherSelect" :value="3"/>
                  <label for="cb4">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-0.872 -0.5 80 80" enable-background="new -0.872 -0.5 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <path fill="#187FC4" d="M78.236,39.296C75.278,20.79,60.07,7.177,41.608,6.045V2.479C41.608,1.11,40.498,0,39.129,0  c-1.37,0-2.48,1.11-2.48,2.479v3.566C18.188,7.177,2.979,20.79,0.021,39.296c-0.093,0.583,0.132,1.172,0.592,1.544  c0.458,0.373,1.082,0.472,1.634,0.258c1.281-0.493,2.61-0.743,3.95-0.743c3.923,0,7.58,2.122,9.544,5.536  c0.589,1.024,2.276,1.024,2.864,0c1.965-3.414,5.623-5.536,9.546-5.536c3.313,0,6.423,1.525,8.498,4.051v26.259  c0,0.172,0.018,0.339,0.05,0.501C36.964,75.525,40.56,79,44.985,79c4.596,0,8.335-3.739,8.335-8.334  c0-1.368-1.109-2.479-2.479-2.479c-1.37,0-2.479,1.111-2.479,2.479c0,1.862-1.515,3.377-3.377,3.377  c-1.863,0-3.377-1.515-3.377-3.377V44.409c2.074-2.528,5.186-4.053,8.498-4.053c3.923,0,7.579,2.122,9.544,5.536  c0.295,0.513,0.842,0.829,1.433,0.829c0.592,0,1.138-0.315,1.433-0.828c1.965-3.415,5.623-5.537,9.545-5.537  c1.341,0,2.67,0.25,3.95,0.743c0.55,0.214,1.175,0.114,1.634-0.257C78.104,40.468,78.329,39.879,78.236,39.296z"/>
                    </svg>
                  </label>
                </li>
                <li><input type="radio" v-on:change="saveWeather" name="test" id="cb6" v-model="weatherSelect" :value="5"/>
                  <label for="cb6">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-12.685 -0.5 80 80" enable-background="new -12.685 -0.5 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <path fill="#9FD9F6" d="M44.568,30.516c1.826-3.032,2.896-6.571,2.896-10.368C47.464,9.021,38.443,0,27.315,0  C16.188,0,7.167,9.021,7.167,20.148c0,3.797,1.07,7.335,2.896,10.368C3.924,35.525,0,43.144,0,51.685C0,66.77,12.229,79,27.315,79  C42.401,79,54.63,66.77,54.63,51.685C54.63,43.144,50.707,35.525,44.568,30.516z"/>
                      <circle fill="#FFFFFF" cx="20.258" cy="14.877" r="3.058"/>
                      <circle fill="#FFFFFF" cx="34.373" cy="14.877" r="3.058"/>
                      <path fill="#FFFFFF" d="M27.315,28.582c-3.203,0-6.214-1.247-8.479-3.513c-0.702-0.701-0.702-1.841,0-2.542  c0.701-0.702,1.841-0.702,2.542,0c1.585,1.585,3.694,2.459,5.936,2.459c2.242,0,4.351-0.874,5.937-2.459  c0.703-0.702,1.841-0.702,2.543,0c0.702,0.703,0.701,1.841-0.001,2.542C33.529,27.334,30.517,28.582,27.315,28.582z"/>
                    </svg>
                  </label>
                </li>
                <li><input type="radio" v-on:change="saveWeather" name="test" id="cb5" v-model="weatherSelect" :value="4"/>
                  <label for="cb5">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:a="http://ns.adobe.com/AdobeSVGViewerExtensions/3.0/" version="1.1" x="0px" y="0px" width="50px" height="50px" viewBox="-13.079 -0.501 80 80" enable-background="new -13.079 -0.501 80 80" xml:space="preserve">
                      <defs>
                      </defs>
                      <path fill="#FABE00" d="M53.212,35.691c1.118-1.173,0.708-2.132-0.913-2.132H30.438c-1.62,0-2.361-1.19-1.647-2.646L42.676,2.646  C43.391,1.19,42.648,0,41.028,0H16.77c-1.621,0-3.341,1.266-3.822,2.814L0.126,44.047c-0.481,1.548,0.451,2.813,2.072,2.813H18.01  c1.621,0,2.553,1.267,2.071,2.814l-8.684,27.93c-0.482,1.548,0.041,1.855,1.159,0.683L53.212,35.691z"/>
                    </svg>
                  </label>
                </li>
              </ul>
              <!-- <button @click="saveWeather">保存する</button> -->
        </div>
    </div>
</template>
<script>
    
    import moment from 'moment';
    export default {
        data(){
          return{
            weatherSelect: null,
            viewWeatherComponent: false,
          }
        },
        mounted(){
            this.getTodayWeather()
        },
        methods: {
          getTodayWeather(){
              let today = moment().local().format('YYYY-MM-DD')
              const user_id = this.$store.state.user.id
              const yesterday = localStorage.getItem('weather_' + user_id)
              if(today != yesterday){
                axios.post('/today_weather', {today} ).then(
                  response => {
                      if(_.isEmpty(response.data)){
                          this.viewWeatherComponent = true
                      }else if(response.data == 'weekend'){
                          this.viewWeatherComponent = false
                      }else{
                          this.viewWeatherComponent = false
                      }
                    }
                  );
              }
              
            },
          saveWeather(){
            let today = moment().local().format('YYYY-MM-DD') 
              axios.post('/save_weather', {today, value: this.weatherSelect} ).then(
              response => {
                  this.getTodayWeather()
                  const user_id = this.$store.state.user.id
                  localStorage.setItem('weather_' + user_id, today)
                }
              ).catch(function (error) {
                if (error.response) this.errorToast('エラーが発生しました。 ' + error.response.data.message)
                else if (error.request) this.errorToast('エラーが発生しました。')
                else this.errorToast('エラーが発生しました。 ' + error.message)     
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
<style scoped lang="scss">
    .weather-wrapper{
        position: fixed;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(0, 0, 0, 0.6);
        z-index: 44;
    }
    .list-wrapper button{
        margin-top: 20px;
        margin-bottom: 10px;
        color: white;
        background: black;
        padding: 5px 10px;
    }
    .list-wrapper p{
        font-size: 20px;
        margin-top: 20px;
        word-break: keep-all;
        line-height: 1.5;
    }
    .list-wrapper{
        background: #fff;
        text-align: center;
        padding: 10px;
    }
    ul {
        list-style-type: none;
      }
      
      li {
        display: inline-block;
      }
      
      input[type="radio"][id^="cb"] {
        display: none;
      }
      
      li label {
        border: 1px solid #fff;
        padding: 20px;
        display: block;
        position: relative;
        margin: 10px;
        cursor: pointer;
      }
      
      li label:before {
        background-color: white;
        color: white;
        content: " ";
        display: block;
        border-radius: 50%;
        border: 1px solid grey;
        position: absolute;
        top: -5px;
        left: -5px;
        width: 25px;
        height: 25px;
        text-align: center;
        line-height: 28px;
        transition-duration: 0.4s;
        transform: scale(0);
        z-index: 1;
      }
      
      label img {
        height: 100px;
        width: 100px;
        transition-duration: 0.2s;
        transform-origin: 50% 50%;
      }
      
      :checked + label {
        border-color: #ddd;
      }
      
      
      
      :checked + label img {
        transform: scale(0.9);
      }

      @media screen and (max-width: 959px) {
        .list-wrapper p{
            font-size: 16px;
            word-break: break-word;
        }
        .list-wrapper{
            
            max-width: 75%;
        }
      }
</style>