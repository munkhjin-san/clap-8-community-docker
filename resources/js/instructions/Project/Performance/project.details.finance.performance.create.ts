import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#goalCreation',
            popover: {
                title: '目標値作成',
                description: 'ここで新しい目標値を作成できます。',
                side: 'top', 
                align: 'start',
                showButtons: [],
            },
            onHighlighted: (el) => {
                el?.addEventListener('click', () => { driver.destroy()}, { once: true }); 
            }
        },
        
    ]
    return steps
}
