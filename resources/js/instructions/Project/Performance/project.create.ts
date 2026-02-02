import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#projectCreate',
            popover: {
                title: '新規プロジェクト作成', 
                description: 'ここで新しいプロジェクトを作成できます。', 
                side: 'bottom', 
                align: 'start',
                showButtons: [],
            },                 
            onHighlighted: (el) => {
                el?.addEventListener('click', () => { driver.destroy()}, { once: true }); 
            }
        }
    ]
    return steps
}
