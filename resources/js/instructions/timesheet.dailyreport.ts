import type { Driver, DriveStep } from "driver.js";
export const stepsBuilder = (driver: Driver) => {
    const steps: DriveStep[] = [
        {
            element: '#reportHeader',
            popover: {
                title: '報告を選択', 
                description: '報告を選択してください。', 
                side: 'left', 
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
