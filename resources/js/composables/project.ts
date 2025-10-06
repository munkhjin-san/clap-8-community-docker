import { User } from "@/interface/globalInterface";
import { Project } from "@/interface/projectInterface";
import { useAuthUserStore } from "@/store/auth";
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

    const getProjects = async (start?: DateTime, end?: DateTime) => {
        try {
            const today = DateTime.now()
            const which_half = today.month >= 3 && today.month <= 9 ? 'first' : 'second'
            const year = which_half ==='second' && today.month <= 3 ? (today.year - 1).toString() : today.year.toString()
            const params = {
                year: year,
                which_half: which_half,
                start: start,
                end: end,
            }
            const response = await axios.get('/get_projects', { params: params });
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
    watch(
        [route.params.projectId],
        (newList) => {
            console.log('projectList changed:', newList.length);
            // Force reevaluation of selectedProject
            console.log('Current selectedProject:', selectedProject.value);
        },
        { deep: true }
    );
    return {
        projectList,
        getProjects,
        setProjectList,
        selectedProject,
        usersProjects,
        memberData,
        isManagerOrMember,
        isManager
        
    };
}