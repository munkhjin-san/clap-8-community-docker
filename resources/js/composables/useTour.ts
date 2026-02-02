
import { useTutorialStore } from "@/store/tutorial";
import { Driver, driver } from "driver.js";
import type { DriveStep } from "driver.js";
import 'driver.js/dist/driver.css';
import { Ref } from "vue";

// type Step = { element: string; popover: { title: string; description: string; side?: any; align?: any } };
const TOUR_PREFIX = 'tour_completed:';
function waitForEl(selector: string, timeout = 8000) {
    return new Promise<HTMLElement>((resolve, reject) => {
        const el = document.querySelector<HTMLElement>(selector);
        if (el) return resolve(el);
        const obs = new MutationObserver(() => {
            const e = document.querySelector<HTMLElement>(selector);
            if (e) { obs.disconnect(); resolve(e); }
        });
        obs.observe(document.documentElement, { childList: true, subtree: true });
        setTimeout(() => { obs.disconnect(); reject(new Error(`Timeout waiting for ${selector}`)); }, timeout);
    });
}
const tutorialStore = useTutorialStore()
const instructionMap: Record<string, () => Promise<any>> = {
    "project.create": () => import("@/instructions/Project/Performance/project.create"),
    "project.create.achievements": () => import("@/instructions/Project/Performance/project.create.achievements"),
    "project.create.achievements.detail": () => import("@/instructions/Project/Performance/project.create.achievements.detail"),
    "project.details": () => import("@/instructions/Project/Performance/project.details"),
    "project.details.finance": () => import("@/instructions/Project/Performance/project.details.finance"),
    "project.details.finance.performance": () => import("@/instructions/Project/Performance/project.details.finance.performance"),
    "project.details.finance.performance.create": () => import("@/instructions/Project/Performance/project.details.finance.performance.create"),
    "project.details.finance.performance.details": () => import("@/instructions/Project/Performance/project.details.finance.performance.details"),
    "timesheet.dailyreport": () => import("@/instructions/Project/Performance/timesheet.dailyreport"),
    "timesheet.dailyreport.create": () => import("@/instructions/Project/Performance/timesheet.dailyreport.create"),
    "timesheet.dailyreport.create.details": () => import("@/instructions/Project/Performance/timesheet.dailyreport.create.details")
};

export function useTour() {
    let driverObj: Driver | null = null;

    async function startTour(key: string, opts: { force?: boolean; version?: string; el?: Ref<HTMLElement | null> } = {}) {

        console.log('Starting tour', key, opts);


        const driverObj = driver({
            allowClose: true,
            stagePadding: 6,
            animate: true,
            nextBtnText: '次へ',
            prevBtnText: '戻る',
            doneBtnText: '完了',
            popoverClass: 'd-tour-popover',
            onDestroyed: () => {
                console.log('Tour destroyed', key)
                // tutorialStore.setTutorial({ active: false, name: [] })
                // localStorage.setItem(versionedKey, new Date().toISOString());
                // optionally POST to your API: user_tours.markComplete(key, version)
            },
            onPopoverRender: (popover, { config, state }) => {
                if(key === 'board.message.correction') {
                    
                    if(opts.el?.value) {
                        opts.el.value.textContent = "これダミーメッセージ。しゅうせいおねがいします。";
                        const event = new Event('input', { bubbles: true });
                        opts.el.value.dispatchEvent(event);
                    }
                
                }
                if (key === 'timesheet.dailyreport.create.details') {
                    if (state?.activeIndex !== 1) return;
                    const el = document.querySelector('#performanceReport');
                    el?.scrollIntoView({ behavior: 'instant', block: 'center' });
                }
            },
        });
        const path = `assets/instructions/${key}.ts`;
        const importer = instructionMap[key];
        console.log('Importer for', key, importer);
        if (!importer) return null;

        const module = await importer();    
        console.log('Imported module for', key, module);
        if (!module.stepsBuilder) return;
        const steps = module.stepsBuilder(driverObj, opts.el) as DriveStep[] || [];

        console.log('Loaded steps for', key, steps);
        const versionedKey = `${TOUR_PREFIX}${key}:${opts.version ?? 'v1'}`;
        if (!opts.force && localStorage.getItem(versionedKey)) return;

        // Wait for all elements that have selectors
        // for (const s of steps) {
        //     if (s.element && s.element !== 'body') {
        //         try { await waitForEl(s.element as string); } catch { /* skip missing steps */ }
        //     }
        // }
        console.log('Starting tour', key, steps, opts);

        // Filter out steps whose elements still aren’t in the DOM
        const available = steps.filter(s => !s.element || !!document.querySelector(s.element as string));
        if (available.length === 0) return;

        driverObj.setSteps(available as any);
        driverObj.drive();
    }

    function stopTour() { driverObj?.destroy(); }
    return { startTour, stopTour };
}
