<template>
    <div>
        <div style="display:flex;justify-content: center;">
            <div v-if="special == 'file'" id="loaderMicro" style="z-index:99;margin:auto 15px auto 0;width: fit-content;">
                <div class="spinner-micro" style="border: 4px #ffffff solid;border-top: 4px #808080 solid;"></div>
            </div>  
            <div v-if="this.channel == 'copyText'" style="text-align: left;font-size: 16px;line-height: 2;white-space: break-spaces;">
                <p>{{question}}</p>
            </div>
            <div v-else style="text-align: center;font-size: 16px;line-height: 2;white-space: break-spaces;">
                <p>{{question}}</p>
                
            </div>
        </div>
        <div v-if="answer1 || answer2" style="text-align: center;padding-top: 15px;display:flex;">
            <button v-show="answer1" class="confirmButton" style="margin:auto;" @click="replyBack(1),$emit('close-toast')">{{answer1}}</button>
            <button v-show="answer2" class="confirmButton" style="margin:auto;" @click="replyBack(0),$emit('close-toast')">{{answer2}}</button>
        </div>            
    </div>
   
</template>
<script>


export default {
     
  data() {
    return {
      question: null,
      answer1: null,
      answer2: null,
      channel: null,
      special: null,
    }
  },
  mounted() {
      
        
  },
  created() {
    emitter.on('confirmQuestion', this.confirmQuestion);
    
  },

  methods: {
    replyBack(value) {        
      
      emitter.emit(this.channel,value);
      this.question = null;
      this.answer1 = null;
      this.answer2 = null;
      this.channel = null;
      
      
    },
    confirmQuestion: function(ask){
        
        this.question = ask.question;          
        this.answer1 = ask.answer1;
        this.answer2 = ask.answer2;
        this.channel = ask.channel;
        this.special = ask.special;
    },
    
  }
}
</script>