import { HelpItem } from "@/interface/helpInterface";
import { defineAsyncComponent } from "vue";

export const ProjectHelp:HelpItem = {
    title: "プロジェクト実績管理の使い方",
    content: "プロジェクト実績管理の使い方に関する説明です。",
    icon: defineAsyncComponent(() => import("@/components/Icons/Project.vue")),
    key: "about-project",
}