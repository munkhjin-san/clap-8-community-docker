import { User } from "@/interface/globalInterface";
import { Project } from "@/interface/projectInterface";
import { useAuthUserStore } from "@/store/auth";
import { detailedDateOptions } from "@/utils/tools";
import axios from "axios";
import { DateTime } from "luxon";
import { computed, ref, watch } from "vue";
import { useRoute } from "vue-router";
const list = ref<Project[]>([]);
export function useProject() {
    
    

    
    const auth = useAuthUserStore()

    const projectList = computed(() => list.value);
    const route = useRoute();
    const setProjectList = (projects: Project[]) => {
        list.value = projects;
    };

    const getProjects = async (start?: DateTime, end?: DateTime, id?: number) => {
        try {
            const today = DateTime.now()
            const which_half = today.month >= 3 && today.month <= 9 ? 'first' : 'second'
            const year = which_half ==='second' && today.month <= 3 ? (today.year - 1).toString() : today.year.toString()
            const params = {
                year: year,
                which_half: which_half,
                start: start,
                end: end,
                id: id
            }
            const response = await axios.get('/get_projects', { params: params });
            if(id && response.data.length > 0) {
                const existingIndex = list.value.findIndex(proj => proj.id === id);
                if (existingIndex !== -1) {
                    list.value[existingIndex] = response.data[0];
                } else {
                    list.value.push(response.data[0]);
                }
                return;
            }
            list.value = [...response.data];
        } catch (e) {}
    };

    const selectedProject = computed(() => {
        // Explicitly access projectList.value to ensure reactivity
        const projects = projectList.value;
        const projectId = route.params.projectId;
        
        
        
        // Convert projectId to number for comparison
        const project = projects.find((proj) => proj.id === Number(projectId));
        return project ?? null;
    });
    const updateProject = async (fields: any[]) => {
        const projectId = Number(route.params.projectId)
        const { data } = await axios.post('/project_refresh', { id: projectId, fields })

        const i = list.value.findIndex(p => p.id === data.id)
        if (i !== -1) list.value[i] = { ...list.value[i], ...data.patch }
    }


    const usersProjects = computed(():Project[] => {
        return projectList.value.filter(project => {
            const membersArray = Array.isArray(project.members) ? project.members : Object.values(project.members) as User[];
            const managerArray = Array.isArray(project.manager) ? project.manager : Object.values(project.manager) as User[]
            const director = project?.director
            return membersArray.some((member: { id: number | null; }) => member && member.id === auth.id) 
                || managerArray.some((member: { id: number | null; }) => member && member.id === auth.id)
                || director?.id === auth.id;
        });
    })
    const memberData = computed(() => {
        
        const project = selectedProject.value;
        const userId = route.params.memberId
        if(!userId) {
            return null
        }
        if (project) {
            const membersArray = Array.isArray(project.members) ? project.members : [];
            const managerArray = Array.isArray(project.manager) ? project.manager : []
            const mergedArray = [...membersArray, ...managerArray]
            return mergedArray.find((member: { id: number | null; }) => member && member.id === Number(userId))
        }
        return null
    })
    const isManager = computed(() => {
        const project = selectedProject.value;
        if (!project) return false;
        const managerArray = Array.isArray(project.manager) ? project.manager : []
        return managerArray.some((member: { id: number | null; }) => member && member.id === auth.id);
    })
    const isManagerOrMember = computed(() => {
        if (memberData.value && memberData.value.pivot.authority === 1) {
            return selectedProject.value?.director_id === auth.id
        } 
        return selectedProject.value?.manager.some((ob: { id: number | null; }) => ob.id === auth.id)
    })

    const refreshProject = () => {
        const projectId = route.params.projectId;
        if (!projectId) return;
        getProjects(undefined, undefined, Number(projectId));        
    }
    const selectedDate = computed(() => {
        const options = detailedDateOptions()
        const span = route.params.span as string
        const [year, which_half] = span.split('-')
        const goalDate = options.find(option => option.year === year && option.which_half === which_half)
        return goalDate
    })
    return {
        projectList,
        getProjects,
        setProjectList,
        refreshProject,
        selectedProject,
        usersProjects,
        memberData,
        isManagerOrMember,
        isManager,
        selectedDate,
        updateProject
        
    };
}