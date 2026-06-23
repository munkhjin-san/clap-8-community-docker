<template>
    <div class="w-full h-[calc(100%-75px)] bg-[var(--background-color)] relative" v-if="selectedProject && hasPrivilage">
        <div class="absolute inset-0 m-auto w-fit h-fit z-[10]" v-if="fetching">
            <div class="spinner-mini"></div>
        </div>
        <div class="px-5 relative z-[5]">
            <div>
                <p class="text-[12px] text-[gray] leading-normal">適合評価を開始するには、メンバーアイコンをクリックしてメンバーを選択してください。<br>
                プロジェクメンバーでないユーザーを追加したい場合は、「アサインメンバー選択」から可能です。
                </p>
                <button class="mt-4 text-[12px] px-2 py-1 bg-[var(--bg3)]" @click.stop="selectNonMember">アサインメンバー選択</button>
            </div>
            <Transition name="slidePop">
                <div @click.stop @touchstart.stop id="p-user-pick" v-if="menu.parent == 'p-user-pick'" class="max-w-[80vw] left-[20px] absolute top-full w-max max-h-[400px] bg-[var(--background-color)] border border-solid border-[var(--secondary-background)] shadow-lg rounded-md overflow-auto z-[4]">
                    <div class="sticky top-0 bg-[var(--background-color)] z-[2] p-3">                
                        <div class="flex w-full ">
                            <input 
                                name="asset-member-search-input" 
                                v-model="searchName" 
                                class="border border-solid border-[var(--formBorder)] px-3 py-2 w-full focus:border-[var(--primary-color)] text-[var(--primary-color)]" 
                                placeholder="メンバー検索" 
                                type="text"
                                @click.stop
                            />
                        </div>
                    </div>
                    <div class="px-3 pb-3">
                        <div>
                            <div v-if="searchResult.length">
                                <div @click="selectMember(resultUser)" v-for="resultUser in searchResult" :key="resultUser.id" class="cursor-pointer hover:bg-[var(--secondary-background)] p-2 flex items-center gap-2 rounded-md" >
                                    <UserPanel size="25" disable-instant :user="resultUser" with-name/>
                                </div>
                            </div>
                            <div v-else>
                                <div class="text-sm text-[gray] py-3 text-center">該当するメンバーが見つかりません</div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
        <div class="w-full h-full mt-[-50px]">
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
                    <div class="u-round-wrap" v-if="nodeProps.data.memberData" @click="userSelect(nodeProps.data.memberData)">
                        <UserPanel disable-instant :user="nodeProps.data.memberData" />
                        <div class="u-chip absolute whitespace-nowrap text-[11px] mx-auto bottom-[-20px] left-1/2 transform -translate-x-1/2 bg-[var(--primary-color)] text-[var(--background-color)] px-2 rounded py-1">{{ nodeProps.data.memberData.name }}</div>
                    </div>
                </template>
            </VueFlow>
        </div>
        <component :is="'style'">
        {{ handlePositionStyles }}
        </component>
        <div class="bg-[var(--background-color)] absolute bottom-4 border border-solid border-[var(--formBorder)] left-4 z-[10] bg-[var(--background-color)]/95 px-4 py-3 text-xs">
    
            <div class="space-y-2 text-[gray]">
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-solid"></span>
                    <span>プロジェクトメンバー</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-dashed"></span>
                    <span>メンバーでないユーザー</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-green"></span>
                    <span>対応完了</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-orange"></span>
                    <span>通常対応</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="legend-line legend-line-red"></span>
                    <span>重点対応</span>
                </div>
            </div>
        </div>
        <router-view v-slot="{ Component }">
        <component 
            v-if="isReady"
            :is="Component"
            :assignDataList="assignDataList"
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
import CommandButton from "@/components/Global/CommandButton.vue";
import { useMenuStore } from "@/store/menu";
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
const nonMemberUsers = ref<ProjectMember[]>([])
const menu = useMenuStore()
const searchName = ref<string>('');
const isReady = ref(false);
onMounted(() => {
    fetchMembersAssignData()
})
const searchResult = computed(() => {
    if(!searchName.value.length) return nonMemberUsers.value;
    const totalList:ProjectMember[] = nonMemberUsers.value;
    if(!searchName.value.length) return [];
    const lowerSearch = searchName.value.toLowerCase();
    return totalList.filter(user => user.name?.toLowerCase().includes(lowerSearch))
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
        isReady.value = true;
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
    const nonMembers = assignDataList.value.filter(assign => !members.some(member => member.id === assign.user_id)).map(assign => {
        return {
            id: assign.user_id,
            name: assign.user?.name || "Unknown User",
            icon_path: assign.user?.icon_path || "",
            icon_bg: assign.user?.icon_bg || "#000000",
            pivot: {
                role_record: assign.project_member_role || null,
            }
        } as ProjectMember
    })

    const mergedMembers = [...members, ...nonMembers]
    const existingMemberNodeCount = (flowNodes.value as { id: string }[]).filter(n => n.id !== 'core').length
    const countChanged = existingMemberNodeCount !== mergedMembers.length
    const existingNodeEntries: Array<[string, Node]> = flowNodes.value.map((node) => [node.id, node])
    const existingNodeMap = new Map<string, Node>(existingNodeEntries)
    const nodes: Node[] = []

    mergedMembers.forEach((member, index) => {
        const nodeId = `member-${member.id}`
        const existingNode = countChanged ? undefined : existingNodeMap.get(nodeId)
        const position = existingNode?.position ?? buildMemberPosition(index, mergedMembers.length)
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
    const projectMemberIds = new Set(allMembers.value.map(m => m.id))
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
        const isProjectMember = projectMemberIds.has(member.id)
        const role = member.pivot?.role_record

        nextHandlePositions.push({ handleId, leftPx })
        edges.push({
            id: `edge-core-member-${member.id}`,
            source: "core",
            target: `member-${member.id}`,
            sourceHandle: handleId,
            type: "smoothstep",
            style: {
                strokeDasharray: isProjectMember ? undefined : strokeStyle.invalid.strokeDasharray,
                stroke: memberAssignData?.support_level || strokeStyle.invalid.stroke,
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

watch([allMembers, selectedProject, assignDataList], () => {
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
const selectMember = (member: ProjectMember) => {
    menu.close()
    router.push({ name: 'assign-member', params: { memberId: member.id } })
}

const selectNonMember = () => {
    menu.setMenu({parent: 'p-user-pick'})
    if(nonMemberUsers.value.length === 0){
        fetchNonMembers()
    }
}
const fetchNonMembers = async () => {
    if (!selectedProject.value) return;
    fetching.value = true;
    try {
        const response = await api.post('/get_non_member_users', {           
            project_id: selectedProject.value.id,            
        })
        nonMemberUsers.value = response || [];
    } catch (error) {
        console.error("Failed to fetch non-member users:", error);
    } finally {
        fetching.value = false;
    }
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
.u-round-wrap{
    position: relative;
    display: inline-block;
}
.u-chip {
    visibility: hidden;
    opacity: 0;
}
.u-round-wrap:hover .u-chip {
    visibility: visible;
    opacity: 1;
    transition: opacity 0.2s ease-in-out;
}
</style>
