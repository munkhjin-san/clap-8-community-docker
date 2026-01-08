import type { Component } from 'vue';
export interface HelpItem {
    title: string;
    content: string;
    description?: string;
    key:string
    icon: Component;
    children?: HelpItem[];
}
