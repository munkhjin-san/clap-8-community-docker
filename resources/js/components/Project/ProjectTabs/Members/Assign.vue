<template>
  <div class="w-full h-[600px] bg-[var(--background-color)]" v-if="selectedProject">
    <VueFlow
      :nodes="rNodes.nodes"
      :edges="rNodes.edges"
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
        height: '500px',
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
                <Handle v-for="num in rNodes.topHandleCount" :key="'top-'+num" :id="`source-handle-top-${num}`" type="source" :position="Position.Top" :connectable="true" />
                <Handle v-for="num in rNodes.bottomHandleCount" :key="'bottom-'+num" :id="`source-handle-bottom-${num}`" type="source" :position="Position.Bottom" :connectable="true" />
            </template>
            <div class="bg-[var(--bg3)] h-full w-full flex items-center justify-center rounded-xl" v-if="nodeProps.data.projectData">
                <div>{{ nodeProps.data.projectData.name }}</div>
            </div>
            <div v-if="nodeProps.data.memberData" @click="userSelect(nodeProps.data.memberData)">
                <UserPanel disable-instant :user="nodeProps.data.memberData" size="40"/>
            </div>
        </template>
    </VueFlow>
    <component :is="'style'">
    {{ handlePositionStyles }}
    </component>
    <AsignMember v-if="activeMember" :member="activeMember" @close="activeMemberId = null"/>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { type Node, type Edge, VueFlow, Position, Handle } from '@vue-flow/core';
import { useProject } from "@/composables/project";
import UserPanel from "@/components/Global/UserPanel.vue";
import { ProjectMember } from "@/interface/projectInterface";
import AsignMember from "./Asign/AsignMember.vue";
import { AssignmentFitEvaluationResponse } from "@/interface/assign";
const { selectedProject } = useProject()
const activeMemberId = ref<number | null>(null);


const activeMember = computed(() => {
    if (!activeMemberId.value) return null;
    return allMembers.value.find(m => m.id === activeMemberId.value) || null;
})

const allMembers = computed(() => {
    return [...selectedProject.value?.manager || [], ...selectedProject.value?.members || []];
})

const rNodes = computed(() => {
    const nodes: Node[] = [];
    const edges: Edge[] = [];
    const members = allMembers.value;
    const size = 40
    
    // Core position and size
    const coreWidth = 150
    const coreHeight = 60
    const coreCenterX = 450
    const coreCenterY = 270
    const coreX = coreCenterX - coreWidth / 2
    const coreY = coreCenterY - coreHeight / 2
    
    // Ellipse parameters
    const ellipseRadiusX = 180 + members.length * 8 // Grow with member count
    const ellipseRadiusY = 120 + members.length * 5
    
    const strokeStyle = {
        valid: {
            stroke: "#10B981",
        },
        invalid: {            
            strokeDasharray: "5,5",
            stroke: "#9CA3AF",            
        }
    }
    
    // Track members by position (above or below core)
    const aboveMembers: { member: typeof members[0]; x: number }[] = []
    const belowMembers: { member: typeof members[0]; x: number }[] = []

    const labelStyle = {
        labelBgStyle: { fill: "var(--bg3)", fillOpacity: 1, borderRadius: 4, color: "var(--primary-color)" },                
        labelBgPadding: [8, 4] as [number, number],
        labelBgBorderRadius: 4,
        labelStyle: {
            fill: 'var(--primary-color)',
        }
    }
    
    // Distribute members around ellipse
    members.forEach((member, index) => {
        // Distribute evenly around the ellipse, starting from top
        const angle = (index / members.length) * 2 * Math.PI - Math.PI / 2
        
        // Add small random offset to angle for organic feel
        const angleOffset = (Math.random() - 0.5) * 0.3
        const finalAngle = angle + angleOffset
        
        // Calculate position on ellipse
        const x = coreCenterX + ellipseRadiusX * Math.cos(finalAngle) - size / 2
        const y = coreCenterY + ellipseRadiusY * Math.sin(finalAngle) - size / 2
        
        // Add small random offset for bubble tea effect
        const xOffset = (Math.random() - 0.5) * 20
        const yOffset = (Math.random() - 0.5) * 20
        
        const finalX = x + xOffset
        const finalY = y + yOffset
        
        // Determine if member is above or below core center
        const isAbove = finalY + size / 2 < coreCenterY
        
        if (isAbove) {
            aboveMembers.push({ member, x: finalX })
        } else {
            belowMembers.push({ member, x: finalX })
        }
        
        nodes.push({
            id: `member-${member.id}`,
            type: "custom",
            position: { x: finalX, y: finalY },
            draggable: true,
            selectable: false,
            style: {
                width: `${size}px`,
                height: `${size}px`,
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
    
    // Sort by x position for handle ordering
    aboveMembers.sort((a, b) => a.x - b.x)
    belowMembers.sort((a, b) => a.x - b.x)
    
    const core: Node = {
        id: "core",
        type: "custom",
        position: { x: coreX, y: coreY },
        draggable: false,
        selectable: false,
        style: {
            width: `${coreWidth}px`,
            height: `${coreHeight}px`,
        },
        data: {
            projectData: selectedProject.value,
        }
    }
    
    // Calculate handle positions (evenly distributed)
    const handlePositions: { handleId: string; leftPx: number }[] = []
    
    // Create edges for above members (top handles)
    aboveMembers.forEach((item, handleIndex) => {
        const role = item.member.pivot?.role_record
        const handleId = `source-handle-top-${handleIndex + 1}`
        
        // Evenly distribute handles across core width
        const leftPx = aboveMembers.length > 1 
            ? (handleIndex / (aboveMembers.length - 1)) * coreWidth 
            : coreWidth / 2
        
        handlePositions.push({ handleId, leftPx })
        
        edges.push({
            id: `edge-core-member-${item.member.id}`,
            source: "core",
            target: `member-${item.member.id}`,
            sourceHandle: handleId,
            type: "smoothstep",
            style: role ? strokeStyle.valid : strokeStyle.invalid,
            label: role && role.title ? role.title : "",
            ...labelStyle
        })
    })
    
    // Create edges for below members (bottom handles)
    belowMembers.forEach((item, handleIndex) => {
        const role = item.member.pivot?.role_record
        const handleId = `source-handle-bottom-${handleIndex + 1}`
        
        // Evenly distribute handles across core width
        const leftPx = belowMembers.length > 1 
            ? (handleIndex / (belowMembers.length - 1)) * coreWidth 
            : coreWidth / 2
        
        handlePositions.push({ handleId, leftPx })
        
        edges.push({
            id: `edge-core-member-${item.member.id}`,
            source: "core",
            target: `member-${item.member.id}`,
            sourceHandle: handleId,
            type: "smoothstep",
            style: role ? strokeStyle.valid : strokeStyle.invalid,
            label: role && role.title ? role.title : "",
            ...labelStyle
        })
    })
    
    nodes.push(core)
    return {
        nodes, 
        edges, 
        handlePositions,
        topHandleCount: aboveMembers.length,
        bottomHandleCount: belowMembers.length,
    }
})



const handlePositionStyles = computed(() => {
    const styles: string[] = []
    const { handlePositions } = rNodes.value
    
    handlePositions.forEach(({ handleId, leftPx }) => {
        styles.push(`
            .vue-flow__handle[data-handleid=${handleId}] {
                left: ${leftPx}px;
            }
        `)
    })
    
    return styles.join('\n')
})


const userSelect = (member: ProjectMember) => {
    console.log("Selected member:", member)
    activeMemberId.value = member.id;
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
</style>
