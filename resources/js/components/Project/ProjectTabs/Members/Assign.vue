<template>
    <div class="w-full h-[calc(100%-75px)] bg-[var(--background-color)] relative" v-if="selectedProject && hasPrivilage">
        <div class="absolute inset-0 m-auto w-fit h-fit z-[10]" v-if="fetching">
            <div class="spinner-mini"></div>
        </div>
        <VueFlow
            v-model:nodes="flowNodes"
            v-model:edges="flowEdges"
            :default-viewport="{ x: 0, y: 0, zoom: 1 }"
            :min-zoom="1"
            :max-zoom="1"
            :nodes-draggable="true"
            :nodes-connectable="false"
            :elements-selectable="false"
            :zoom-on-scroll="false"
            :pan-on-scroll="true"
            :fit-view-on-init="true"
            :style="{
                height: '100%',
                width: '100%',
            }"
            class="vueflow"
        >
            <template #node-custom="nodeProps">
                <!-- Member node handle: bottom if above core, top if below core -->
                <Handle v-if="nodeProps.data.memberData && nodeProps.data.isAbove" type="target" :position="Position.Bottom" :connectable="true" />
                <Handle v-if="nodeProps.data.memberData && !nodeProps.data.isAbove" type="target" :position="Position.Top" :connectable="true" />
                
                <!-- Core node handles: top and bottom only -->
                <template v-if="nodeProps.data.projectData">
                    <Handle v-for="num in topHandleCount" :key="'top-'+num" :id="`source-handle-top-${num}`" type="source" :position="Position.Top" :connectable="true" />
                    <Handle v-for="num in bottomHandleCount" :key="'bottom-'+num" :id="`source-handle-bottom-${num}`" type="source" :position="Position.Bottom" :connectable="true" />
                </template>
                <div class="bg-[var(--bg3)] h-full w-full flex items-center justify-center rounded-xl text-[14px] leading-normal" v-if="nodeProps.data.projectData">
                    <div class="px-3 w-[130%] text-center">{{ nodeProps.data.projectData.name }}</div>
                </div>
                <div v-if="nodeProps.data.memberData" @click="userSelect(nodeProps.data.memberData)">
                    <UserPanel disable-instant :user="nodeProps.data.memberData" />
                </div>
            </template>
        </VueFlow>
        <component :is="'style'">
        {{ handlePositionStyles }}
        </component>
        <div class="bg-[var(--background-color)] absolute bottom-4 border border-solid border-[var(--formBorder)] left-4 z-[10] bg-[var(--background-color)]/95 px-4 py-3 text-xs">
    
            <div class="space-y-2 text-[gray]">
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-dashed"></span>
                    <span>役割未割当</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-solid"></span>
                    <span>役割割当済</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-green"></span>
                    <span>対応不要</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-orange"></span>
                    <span>要対応</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-red"></span>
                    <span>要強対応</span>
                </div>
            </div>
        </div>
        <router-view v-slot="{ Component }">
        <component 
            :is="Component"
            v-if="activeMember" 
            :member="activeMember" 
            :assign-data="activeMemberAssignData" 
            @close="router.back()"
            @update="fetchMembersAssignData"
        />
        </router-view>
    </div>
    <div v-else class="text-center text-[gray] mt-10">権限がありません。</div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { type Node, type Edge, VueFlow, Position, Handle } from '@vue-flow/core';
import { useProject } from "@/composables/project";
import UserPanel from "@/components/Global/UserPanel.vue";
import { ProjectAssignRecord, ProjectMember } from "@/interface/projectInterface";
import { useAuthUserStore } from "@/store/auth";
import { useApi } from "@/composables/api";
import { useRoute, useRouter } from "vue-router";
const { selectedProject, isManager } = useProject()
const fetching = ref(false);
const api = useApi()
const assignDataList = ref<ProjectAssignRecord[]>([])
const flowNodes = ref<Node[]>([])
const flowEdges = ref<Edge[]>([])
const handlePositions = ref<{ handleId: string; leftPx: number }[]>([])
const topHandleCount = ref(0)
const bottomHandleCount = ref(0)
const router = useRouter()
const route = useRoute()
onMounted(() => {
    fetchMembersAssignData()
})

const fetchMembersAssignData = async () => {
    if (!selectedProject.value) return;
    fetching.value = true;
    try {
        const response = await api.get('/get_members_assign_data', {           
            project_id: selectedProject.value.id,            
        })
        assignDataList.value = response || [];
    } catch (error) {
        console.error("Failed to fetch members for assignment:", error);
    } finally {
        fetching.value = false;
    }
}

const auth = useAuthUserStore()
const activeMember = computed(() => {
    const memberId = route.params.memberId;
    if (!memberId) return null;
    return allMembers.value.find(m => m.id === Number(memberId)) || null;
})

const activeMemberAssignData = computed(() => {
    if (!activeMember.value) return [];
    const list = assignDataList.value.filter(m => m.user_id === activeMember.value?.id);
    return list && list.length > 0 ? list[0] : null;
})

const allMembers = computed(() => {
    return [...selectedProject.value?.manager || [], ...selectedProject.value?.members || []];
})

const FLOW = {
    size: 30,
    coreWidth: 180,
    coreHeight: 60,
    coreCenterX: 450,
    coreCenterY: 270,
}

const strokeStyle = {
    invalid: {
        strokeDasharray: "5,5",
        stroke: "#9CA3AF",
    },
}

const labelStyle = {
    labelBgStyle: { fill: "var(--bg3)", fillOpacity: 1, borderRadius: 4, color: "var(--primary-color)" },
    labelBgPadding: [8, 4] as [number, number],
    labelBgBorderRadius: 4,
    labelStyle: {
        fill: 'var(--primary-color)',
    }
}

const buildMemberPosition = (index: number, total: number) => {
    const ellipseRadiusX = 180 + total * 6
    const ellipseRadiusY = 120 + total * 5
    const angle = (index / Math.max(total, 1)) * 2 * Math.PI - Math.PI / 2

    return {
        x: FLOW.coreCenterX + ellipseRadiusX * Math.cos(angle) - FLOW.size / 2,
        y: FLOW.coreCenterY + ellipseRadiusY * Math.sin(angle) - FLOW.size / 2,
    }
}

const syncNodes = () => {
    const members = allMembers.value
    const existingNodeEntries: Array<[string, Node]> = flowNodes.value.map((node) => [node.id, node])
    const existingNodeMap = new Map<string, Node>(existingNodeEntries)
    const nodes: Node[] = []

    members.forEach((member, index) => {
        const nodeId = `member-${member.id}`
        const existingNode = existingNodeMap.get(nodeId)
        const position = existingNode?.position ?? buildMemberPosition(index, members.length)
        const isAbove = position.y + FLOW.size / 2 < FLOW.coreCenterY

        nodes.push({
            id: nodeId,
            type: "custom",
            position,
            draggable: true,
            selectable: false,
            style: {
                width: `${FLOW.size}px`,
                height: `${FLOW.size}px`,
                borderRadius: "9999px",
                background: "#F3F4F6",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                border: "1px solid #D1D5DB",
            },
            data: {
                memberData: member,
                isAbove,
            }
        })
    })

    nodes.push({
        id: "core",
        type: "custom",
        position: {
            x: FLOW.coreCenterX - FLOW.coreWidth / 2,
            y: FLOW.coreCenterY - FLOW.coreHeight / 2,
        },
        draggable: false,
        selectable: false,
        style: {
            width: `${FLOW.coreWidth}px`,
            height: `${FLOW.coreHeight}px`,
        },
        data: {
            projectData: selectedProject.value,
        }
    })

    flowNodes.value = nodes
    syncEdges()
}

const syncEdges = () => {
    const memberNodes = flowNodes.value.filter((node): node is Node => node.id !== 'core')
    const assignDataMap = new Map(assignDataList.value.map(assign => [assign.user_id, assign]))
    const aboveMembers: { member: ProjectMember; x: number }[] = []
    const belowMembers: { member: ProjectMember; x: number }[] = []

    memberNodes.forEach((node) => {
        const member = node.data.memberData as ProjectMember | undefined
        if (!member) return

        const isAbove = node.position.y + FLOW.size / 2 < FLOW.coreCenterY
        node.data = {
            ...node.data,
            isAbove,
            memberData: member,
        }

        if (isAbove) {
            aboveMembers.push({ member, x: node.position.x })
        } else {
            belowMembers.push({ member, x: node.position.x })
        }
    })

    aboveMembers.sort((a, b) => a.x - b.x)
    belowMembers.sort((a, b) => a.x - b.x)

    const nextHandlePositions: { handleId: string; leftPx: number }[] = []
    const edges: Edge[] = []

    const pushEdge = (member: ProjectMember, handleId: string, leftPx: number) => {
        const memberAssignData = assignDataMap.get(member.id) || null
        const role = member.pivot?.role_record

        nextHandlePositions.push({ handleId, leftPx })
        edges.push({
            id: `edge-core-member-${member.id}`,
            source: "core",
            target: `member-${member.id}`,
            sourceHandle: handleId,
            type: "smoothstep",
            // style: memberAssignData ? { stroke: memberAssignData.support_level || strokeStyle.invalid.stroke } : strokeStyle.invalid,
            style: {
                strokeDasharray: role ? undefined : strokeStyle.invalid.strokeDasharray,
                stroke: role ? (memberAssignData?.support_level || "#D1D5DB") : strokeStyle.invalid.stroke, 
            },
            label: role?.title || "",
            ...labelStyle
        })
    }

    aboveMembers.forEach((item, index) => {
        const handleId = `source-handle-top-${index + 1}`
        const leftPx = aboveMembers.length > 1
            ? (FLOW.coreWidth / (aboveMembers.length + 1)) * (index + 1)
            : FLOW.coreWidth / 2

        pushEdge(item.member, handleId, leftPx)
    })

    belowMembers.forEach((item, index) => {
        const handleId = `source-handle-bottom-${index + 1}`
        const leftPx = belowMembers.length > 1
            ? (FLOW.coreWidth / (belowMembers.length + 1)) * (index + 1)
            : FLOW.coreWidth / 2

        pushEdge(item.member, handleId, leftPx)
    })

    handlePositions.value = nextHandlePositions
    topHandleCount.value = aboveMembers.length
    bottomHandleCount.value = belowMembers.length
    flowEdges.value = edges
}

watch([allMembers, selectedProject], () => {
    syncNodes()
}, { immediate: true })

watch(assignDataList, () => {
    syncEdges()
}, { deep: true })

const hasPrivilage = computed(() => { 

    return auth.isBoss || auth.isAdmin || isManager.value || auth.isPM
})


const handlePositionStyles = computed(() => {
    const styles: string[] = []

    handlePositions.value.forEach(({ handleId, leftPx }) => {
        styles.push(`
            .vue-flow__handle[data-handleid=${handleId}] {
                left: ${leftPx}px;
                background: var(--bg3);
                border: none
            }
        `)
    })
    
    return styles.join('\n')
})


const userSelect = (member: ProjectMember) => {
    // console.log("Selected member:", member)
    // activeMemberId.value = member.id;
    router.push({ name: 'assign-member', params: { memberId: member.id } })
}
</script>

<style>
/* Kill the default node label padding/background quirks */
.vueflow .vue-flow__node {
  padding: 0;
}

/* Optional: hide selection outlines / handles if any appear */
.vueflow .vue-flow__node.selected {
  box-shadow: none !important;
}

.legend-line {
  display: inline-block;
  width: 28px;
  border-top: 2px solid #9ca3af;
  flex-shrink: 0;
}

.legend-line-dashed {
  border-top-style: dashed;
}

.legend-line-solid {
  border-top-style: solid;
}

.legend-line-green {
  border-top-style: solid;
  border-top-color: green;
}

.legend-line-orange {
  border-top-style: solid;
  border-top-color: orange;
}

.legend-line-red {
  border-top-style: solid;
  border-top-color: red;
}
</style>
