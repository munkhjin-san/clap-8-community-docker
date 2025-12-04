<template>
    <div @click.stop="closeComment">
        <!-- <p class="leading-normal mb-2">みんなのひとこと</p> -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
            <div
            v-for="(member, index) in members"
            :key="member.id"
            class="flex gap-2 rounded-xl items-center p-2 [box-shadow:0_1px_1px_#0000004d,0_0_40px_#8080801a_inset] bg-[var(--background-color)]"
            >
            <UserPanel
                :user="member"
                :disable-instant="true"
                :with-name="false"
                size="25"
            />
            <WeatherIcon
                v-if="member?.custom_field_data_records?.length"
                :which="member?.custom_field_data_records[0]?.value_int"
                size="15"
                style="min-width: 15px;"
            />
            <div class="relative cursor-pointer min-w-0">
                <p
                    class="truncate whitespace-nowrap text-sm min-w-0"
                    @click.stop="toggleComment(member)"
                >
                    {{ member?.custom_field_data_records?.[0]?.value_text }}
                </p>

                <div
                    v-if="menu.name === 'commentBox' && menu.id === member?.id"
                    ref="commentBox"
                    class="comment-box text-sm"
                    :style="getCommentBoxStyle(index)"
                    style="z-index: 9;"
                >
                    <div style="word-break: break-word;">
                        {{ member?.custom_field_data_records?.[0]?.value_text }}
                    </div>
                </div>


            </div>
            
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import WeatherIcon from './WeatherIcon.vue';
import UserPanel from './UserPanel.vue';
import { useMenuStore } from '@/store/menu';
import { onMounted, ref } from 'vue';
defineProps<{
    members: any[];
}>()
const menu = useMenuStore()
const boxPosition = (name, member) => {
    menu.setMenu({name: name, id: member?.id})
    
}
const isTouchDevice = ref(false)
onMounted(() => {
    isTouchDevice.value = 
        'ontouchstart' in window ||
        navigator.maxTouchPoints > 0
})
const commentBoxPos = ref<{ top: number; left: number }>({ top: 0, left: 0 });

const openComment = (member) => {
  menu.setMenu({ name: 'commentBox', id: member.id });
  // boxPosition probably positions it relative to ref
  boxPosition('commentBox', member);
};

const closeComment = () => {
  menu.close();
};
const getCommentBoxStyle = (index: number) => {
  if (isTouchDevice.value) {
    // mobile logic: stick to left or right
    if (index % 2 === 0) {
      // 0,2,4...
      return {
        top: '100%',     // or '0px' or whatever you want
        left: '0',
        right: 'auto',
      };
    } else {
      // 1,3,5...
      return {
        top: '100%',
        right: '0',
        left: 'auto',
      };
    }
  }

  // desktop: use calculated position
  return {
    top: `${commentBoxPos.value.top}px`,
    left: `${commentBoxPos.value.left}px`,
  };
};
const toggleComment = (member) => {
  if (menu.name === 'commentBox' && menu.id === member.id) {
    closeComment();
  } else {
    openComment(member);
  }
};
</script>