<template>
    <div class="weather-overlay" v-if="projects.length">
      <div class="weather-window">
        <div class="weather-title">
          <p>プロジェクトのコンディションを選択</p>
        </div>
        <div class="flex flex-col gap-5">
            <div v-for="project in projects" :key="project.id" class="project-card">
            <div class="project-name">{{ project.name }}</div>
    
            <div class="condition-options">
                <div
                :class="['condition-wrap', {selectedBackground: isSelected(num, project.id)}]"
                v-for="num in [0, 1, 2, 3, 4, 5]"
                :key="`${project.id}-${num}`"
                @click="toggleSelection(num, project.id)"
                >
                <WeatherIcon
                    :class="['condition-icon', { selected: isSelected(num, project.id) }]"
                    :which="num"
                    size="20"
                />
                </div>
            </div>
            </div>
        </div>
      </div>
    </div>
  </template>
  
  <script lang="ts" setup>
  import { Project } from "@/interface/projectInterface";
  import axios from "axios";
  import { inject, onMounted, ref } from "vue";
  import WeatherIcon from "./WeatherIcon.vue";
import { Dialog } from "@/interface/globalInterface";
  
  interface Chosen {
    value: number;
    project_record_id: number;
  }
  
  const projects = ref<Project[]>([]);
  const selected = ref<Chosen[]>([]);
  const { info, notify } = inject<Dialog>('dialog')!
  onMounted(() => {
    fetchManagingProjects();
  });
  
  const fetchManagingProjects = async () => {
    try {
      const response = await axios.get("/get_managing_projects");
      projects.value = response.data;
    } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
    }
  };
  
  const toggleSelection = async(val: number, projectId: number) => {
    const index = selected.value.findIndex(
      (item) => item.project_record_id === projectId
    );
  
    if (index !== -1) {
      selected.value[index].value = val;
    } else {
      selected.value.push({ value: val, project_record_id: projectId });
    }
  
    if (projects.value.length === selected.value.length) {
      try {
        await axios.post('/update_project_conditions', {selected: selected.value})
        info('保存しました。')
        fetchManagingProjects()
      } catch (e) {
        notify(e.response?.data.message || e?.message || 'エラーが発生しました。')
      }
    }
  };
  
  const isSelected = (val: number, projectId: number): boolean => {
    return selected.value.some(
      (item) => item.value === val && item.project_record_id === projectId
    );
  };
  </script>
  
  <style scoped>
  .weather-overlay {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: var(--overlay);
    height: 100%;
    width: 100%;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 43;
  }
  
  .weather-window {
    background: #fff;
    padding: 20px;
    width: 500px;
    max-height: 90%;
    overflow-y: auto;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  .weather-title {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 1.2rem;
  }

  
  .project-name {
    font-size: 1rem;
    margin-bottom: 10px;
  }
  
  .condition-options {
    display: flex;
    gap: 10px;
  }
  
  .condition-wrap {
    height: 75px;
    min-height: 75px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1 1 calc(16.66%);
    background-color: #fff;
    transition: background-color 0.2s ease;
    border-radius: 5px;
  }
  
  .condition-wrap:hover {
    background-color: #f3f3f3;
  }
  
  .condition-icon {
    transition: transform 0.2s ease;
  }
  
  .selected {
    transform: scale(1.3);
    color: #42b983;
  }
  .selectedBackground {
    background-color: #f3f3f3;
  }
  @media screen and (max-width: 768px) {
    .weather-window {
      max-height: calc(100% - 40px);
    }
    
  }
  </style>
  